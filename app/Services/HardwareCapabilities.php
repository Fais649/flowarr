<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

/**
 * Detects the ffmpeg encoders and GPU devices available to the transcoder.
 *
 * The selection mirrors detect_gpu() in scripts/transcode_media.sh so the UI
 * shows the encoder transcodes will actually use.
 */
class HardwareCapabilities
{
    private const CACHE_KEY = 'hardware_capabilities';

    private const ENCODERS = ['hevc_nvenc', 'hevc_vaapi', 'libx265'];

    /** @var Closure(list<string>): ?string */
    private Closure $run;

    /** @var Closure(string): bool */
    private Closure $pathExists;

    /**
     * @param  (Closure(list<string>): ?string)|null  $run  runs a command, returns stdout or null on failure
     * @param  (Closure(string): bool)|null  $pathExists
     */
    public function __construct(?Closure $run = null, ?Closure $pathExists = null)
    {
        $this->run = $run ?? function (array $command): ?string {
            try {
                $process = new Process($command);
                $process->setTimeout(10);
                $process->run();

                return $process->isSuccessful() ? $process->getOutput() : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $this->pathExists = $pathExists ?? fn (string $path): bool => file_exists($path);
    }

    /**
     * Transcode mode passed to the transcode script: auto, nvidia, amd or software.
     */
    public static function configuredMode(): string
    {
        if (! filter_var(config('services.ffmpeg.enable_gpu_transcoding', true), FILTER_VALIDATE_BOOL)) {
            return 'software';
        }

        $mode = strtolower((string) config('services.ffmpeg.hw_mode', 'auto'));

        return in_array($mode, ['auto', 'nvidia', 'amd', 'software'], true) ? $mode : 'auto';
    }

    /**
     * @return array{ffmpeg_version: string|null, ffprobe: bool, encoders: array<string, bool>, devices: array{nvidia: bool, vaapi: bool}, vaapi_device: string, mode: string, selected_encoder: string|null, detected_at: string}
     */
    public function detect(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => $this->probe());
    }

    /**
     * @return array{ffmpeg_version: string|null, ffprobe: bool, encoders: array<string, bool>, devices: array{nvidia: bool, vaapi: bool}, vaapi_device: string, mode: string, selected_encoder: string|null, detected_at: string}
     */
    public function probe(): array
    {
        $ffmpeg = (string) config('services.ffmpeg.bin', 'ffmpeg');
        $vaapiDevice = (string) config('services.ffmpeg.vaapi_device', '/dev/dri/renderD128');

        $versionOutput = ($this->run)([$ffmpeg, '-hide_banner', '-version']);
        $version = null;
        if ($versionOutput !== null && preg_match('/ffmpeg version (\S+)/', $versionOutput, $matches)) {
            $version = $matches[1];
        }

        $encoderOutput = $versionOutput !== null ? (($this->run)([$ffmpeg, '-hide_banner', '-encoders']) ?? '') : '';
        $encoders = [];
        foreach (self::ENCODERS as $encoder) {
            $encoders[$encoder] = (bool) preg_match('/\s'.preg_quote($encoder, '/').'\s/', $encoderOutput);
        }

        $devices = [
            'nvidia' => ($this->run)(['nvidia-smi', '-L']) !== null
                || ($this->pathExists)('/dev/nvidiactl')
                || ($this->pathExists)('/dev/nvidia0'),
            'vaapi' => ($this->pathExists)($vaapiDevice),
        ];

        $mode = self::configuredMode();

        return [
            'ffmpeg_version' => $version,
            'ffprobe' => ($this->run)(['ffprobe', '-version']) !== null,
            'encoders' => $encoders,
            'devices' => $devices,
            'vaapi_device' => $vaapiDevice,
            'mode' => $mode,
            'selected_encoder' => $version === null ? null : $this->selectEncoder($mode, $encoders, $devices),
            'detected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, bool>  $encoders
     * @param  array{nvidia: bool, vaapi: bool}  $devices
     */
    private function selectEncoder(string $mode, array $encoders, array $devices): string
    {
        $gpu = match ($mode) {
            'nvidia' => 'nvidia',
            'amd' => 'amd',
            'software' => 'none',
            default => $devices['nvidia'] ? 'nvidia' : ($devices['vaapi'] ? 'amd' : 'none'),
        };

        if ($gpu === 'nvidia' && $encoders['hevc_nvenc']) {
            return 'hevc_nvenc';
        }

        if ($gpu === 'amd' && $encoders['hevc_vaapi'] && $devices['vaapi']) {
            return 'hevc_vaapi';
        }

        return 'libx265';
    }
}

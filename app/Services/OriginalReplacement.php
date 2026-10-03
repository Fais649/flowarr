<?php

namespace App\Services;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Models\Library;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class OriginalReplacement
{
    public function policy(?Library $library, bool $legacyReplace): string
    {
        return $library->original_handling ?? ($legacyReplace ? 'immediate' : 'keep');
    }

    /** @phpstan-impure Playback and time can change between checks. */
    public function canReplace(array $replacement): bool
    {
        if ($replacement['policy'] === 'immediate') {
            return true;
        }
        if (! app(ProcessingGate::class)->isOpen()) {
            return false;
        }
        if ($replacement['policy'] === 'idle') {
            return app(StreamTracker::class)->count() === 0;
        }
        $start = $replacement['start'] ?? null;
        $end = $replacement['end'] ?? null;
        if (! $start || ! $end || $start === $end) {
            return false;
        }
        $time = now()->format('H:i');

        return $start < $end ? $time >= $start && $time < $end : $time >= $start || $time < $end;
    }

    public function replace(Execution $execution): bool
    {
        return Cache::lock('original-replacement:'.hash('sha256', $execution->file_path), 300)->get(function () use ($execution): bool {
            $execution->refresh();
            $replacement = $execution->replacement;
            if ($execution->status !== ExecutionStatus::COMPLETED || $execution->replacement_status !== 'pending' || ! $replacement || ! $this->canReplace($replacement)) {
                return false;
            }
            try {
                $source = $execution->file_path;
                $output = $replacement['output'];
                $target = TranscodeOutput::pathFor($source, true);
                clearstatcache();
                if (is_link($source) || is_link($output) || ! is_file($source) || ! is_file($output)) {
                    throw new RuntimeException('Original or transcoded copy is missing or is a symlink.');
                }
                if (filesize($source) !== $replacement['size'] || filemtime($source) !== $replacement['mtime'] || fileinode($source) !== $replacement['inode']) {
                    throw new RuntimeException('Original changed; keeping both files.');
                }
                if (hash_file('sha256', $output) !== $replacement['hash']) {
                    throw new RuntimeException('Transcoded copy changed; keeping both files.');
                }
                if ($source !== $target && file_exists($target)) {
                    throw new RuntimeException('Replacement target already exists; keeping both files.');
                }
                if (! $this->canReplace($replacement)) {
                    return false;
                }
                clearstatcache();
                if (is_link($source) || is_link($output) || filesize($source) !== $replacement['size'] || filemtime($source) !== $replacement['mtime'] || fileinode($source) !== $replacement['inode']) {
                    throw new RuntimeException('Original changed before replacement; keeping both files.');
                }
                if (! rename($output, $target)) {
                    throw new RuntimeException('Could not move the validated transcoded copy.');
                }
                if ($source !== $target && ! unlink($source)) {
                    throw new RuntimeException('Replacement saved but original could not be removed.');
                }
                $execution->update(['replacement_status' => 'replaced', 'message' => null]);

                return true;
            } catch (Throwable $e) {
                $execution->update(['replacement_status' => 'failed', 'message' => $e->getMessage()]);

                return false;
            }
        }) ?? false;
    }
}

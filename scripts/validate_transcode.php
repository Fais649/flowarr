<?php

$source = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$output = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
$cap = (int) $argv[3];
$duration = (float) ($source['format']['duration'] ?? 0);
$outputDuration = (float) ($output['format']['duration'] ?? 0);
if ($duration <= 0 || $outputDuration <= 0 || abs($duration - $outputDuration) > max(1, $duration * 0.001)) {
    throw new RuntimeException('Transcoded duration does not match source');
}
foreach (['audio', 'subtitle', 'attachment'] as $type) {
    $count = fn (array $data): int => count(array_filter($data['streams'], fn (array $s): bool => $s['codec_type'] === $type));
    if ($count($source) !== $count($output)) {
        throw new RuntimeException('Transcode lost '.$type.' tracks');
    }
}
if ($cap > 0 && (float) $output['format']['size'] * 8 / $outputDuration > $cap) {
    throw new RuntimeException('Transcoded file exceeds total bitrate limit');
}

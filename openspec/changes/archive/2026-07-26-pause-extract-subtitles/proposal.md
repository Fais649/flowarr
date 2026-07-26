## Why

`ExtractSubtitlesJob` is the only job that doesn't check `shouldPause()` — it runs ffprobe and mkvmerge synchronously with no polling loop, so during a Jellyfin stream it continues processing, defeating the entire pause mechanism. This also means it can't participate in future concurrency limits since those check the same `shouldPause` condition.

## What Changes

- Rewrite `ExtractSubtitlesJob::handle()` to use the same polling loop pattern as `TranscodeMediaJob` and `ConvertSubtitleJob`
- Replace synchronous `->run()` calls with `->start()` + polling loop that checks `shouldPause()` and sends SIGSTOP/SIGCONT

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `subtitle-extraction`: Extraction job must honor pause signals and support the polling loop pattern

## Impact

- `app/Jobs/ExtractSubtitlesJob.php` — refactor `handle()` to use polling loop, add `shouldPause()` method

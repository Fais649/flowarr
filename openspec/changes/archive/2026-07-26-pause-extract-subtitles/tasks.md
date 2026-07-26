## 1. Add shouldPause to ExtractSubtitlesJob

- [x] 1.1 Add `shouldPause(): bool` method checking `Cache::get('media_processing_paused')` and `Cache::get('active_streams', 0) > 0`
- [x] 1.2 Refactor ffmpeg extraction calls from `->run()` to `->start()` with polling loop + SIGSTOP/SIGCONT
- [x] 1.3 Refactor mkvmerge strip call from `->run()` to `->start()` with polling loop + SIGSTOP/SIGCONT
- [x] 1.4 Keep ffprobe probe as synchronous `->run()` (fast operation, no pause needed)

## 2. Verification

- [x] 2.1 Run existing ExtractSubtitlesJob tests to confirm extraction logic still works
- [x] 2.2 Run `vendor/bin/sail bin pint` to format

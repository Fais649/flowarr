## Context

`ExtractSubtitlesJob` currently runs ffprobe, ffmpeg, and mkvmerge as synchronous subprocesses using `->run()` — a blocking call that waits for the process to finish. It has no polling loop, no `shouldPause()` check, and no SIGSTOP/SIGCONT handling. During a Jellyfin stream, extraction continues uninterrupted, defeating the pause mechanism.

The other two jobs (`TranscodeMediaJob`, `ConvertSubtitleJob`) already use the correct pattern: `->start()` followed by a `while(isRunning)` polling loop that checks `shouldPause()` every 200ms.

## Goals / Non-Goals

**Goals:**
- Add `shouldPause()` method to `ExtractSubtitlesJob`
- Refactor ffprobe, ffmpeg, and mkvmerge calls to use `->start()` + polling loop + SIGSTOP/SIGCONT
- Match the pattern used by the other two jobs exactly

**Non-Goals:**
- Changing the extraction logic itself
- Adding concurrency limits (separate change)
- Refactoring the pause logic into a shared trait or base class (future consideration)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Polling interval | 200ms (`usleep(200000)`) | Matches existing jobs. Balances responsiveness vs. CPU. |
| shouldPause logic | Check `media_processing_paused` OR `active_streams > 0` | Matches existing jobs exactly. |
| Process factory | Keep `Closure $processFactory` parameter for testability | Already present in the constructor. The polling loop wraps it the same way. |
| ffprobe handling | ffprobe is fast (<1s) — keep synchronous `->run()` | No need for a polling loop on a sub-second operation. Only ffmpeg and mkvmerge need the loop. |

## Risks / Trade-offs

- **Extraction is multi-step** — ffprobe (sync), then per-stream ffmpeg extraction (async + polling), then mkvmerge strip (async + polling). Each ffmpeg extraction needs its own polling loop. Low risk — straightforward application of the existing pattern.

## 1. Configuration

- [x] 1.1 Add `jellyfin` section to `config/services.php` with `webhook_token` key

## 2. Webhook Controller

- [x] 2.1 Create `app/Http/Controllers/JellyfinWebhookController.php` with `__invoke` method
- [x] 2.2 Parse event type from Jellyfin webhook payload
- [x] 2.3 Increment `active_streams` cache counter on `playback.start`
- [x] 2.4 Decrement `active_streams` cache counter on `playback.stop` (floor 0)
- [x] 2.5 Validate `X-Flowarr-Token` header when token is configured; skip validation when empty/null

## 3. Routes

- [x] 3.1 Register `POST /webhooks/jellyfin` route in `routes/web.php` without auth middleware

## 4. Update TranscodeMediaJob Pause Logic

- [x] 4.1 Extract pause condition into a helper: pause if `Cache::get('media_processing_paused')` OR `Cache::get('active_streams', 0) > 0`
- [x] 4.2 Update inner and outer pause loops to use the combined condition

## 5. Update ConvertSubtitleJob Pause Logic

- [x] 5.1 Replace `$convert->run()` with `$convert->start()` + polling loop
- [x] 5.2 Add same combined pause check (SIGSTOP/SIGCONT) to the polling loop
- [x] 5.3 Add `use Illuminate\Support\Facades\Cache;` import

## 6. Tests

- [x] 6.1 Create `tests/Feature/JellyfinWebhookTest.php` covering playback start, stop, auth, unknown event, counter edge cases
- [x] 6.2 Update `TranscodeMediaJobTest` to cover the combined pause condition
- [x] 6.3 Update `ConvertSubtitleJobTest` to cover pause/resume during conversion

## 7. Documentation

- [x] 7.1 Add Jellyfin webhook setup section to `README.md`

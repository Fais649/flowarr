## 1. Hardware Detection Service

- [ ] 1.1 Create `app/Services/HardwareAccelerationService.php` with `detect()` method that probes ffmpeg encoders and returns best available
- [ ] 1.2 Add `services.ffmpeg.encoder` and `services.ffmpeg.vaapi_device` config entries to `config/services.php` with priority-ordered fallback list
- [ ] 1.3 Add unit test `tests/Unit/HardwareAccelerationServiceTest.php` with mocked ffmpeg probe results for NVENC, VAAPI, and no-GPU scenarios

## 2. TranscodeJob Encoder Resolution

- [ ] 2.1 Update `TranscodeMediaJob::handle()` to use `HardwareAccelerationService` for encoder resolution instead of static `use_nvenc` config
- [ ] 2.2 Add VAAPI encoder path with `-vaapi_device` flag and `format=nv12,hwupload` video filter
- [ ] 2.3 Add encoder-specific preset mapping (NVENC → p4, VAAPI → default/b balanced, libx265 → medium)
- [ ] 2.4 Add explicit encoder override via `FFMPEG_ENCODER` env var (bypasses detection entirely)
- [ ] 2.5 Update existing `TranscodeMediaJob` test to cover all three encoder paths

## 3. Docker Image & Compose

- [ ] 3.1 Add `libva2`, `libva-drm2`, `mesa-va-drivers` to Dockerfile apt-get install
- [ ] 3.2 Rebuild image, push to Docker Hub (`fais649/flowarr:latest`)
- [ ] 3.3 Update `docker-compose.yml` with AMD GPU device mount (`/dev/dri:/dev/dri`) uncommented and NVIDIA GPU block commented out

## 4. Documentation

- [ ] 4.1 Update `README.md` — add GPU acceleration section documenting AMD/NVIDIA/Intel hardware transcoding support, required host drivers, and device mount configuration

## 5. Verification

- [ ] 5.1 Verify GPU detection works: `docker run --rm flowarr php artisan tinker --execute 'app(App\Services\HardwareAccelerationService::class)->detect()'`
- [ ] 5.2 Verify VAAPI libraries present in image: `docker run --rm flowarr ffmpeg -encoders 2>/dev/null | grep hevc_vaapi`
- [ ] 5.3 Verify software fallback works without GPU devices mounted

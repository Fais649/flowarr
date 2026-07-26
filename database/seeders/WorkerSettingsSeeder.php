<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class WorkerSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('concurrency.transcode_media', '1');
        Setting::set('concurrency.extract_subs', '4');
        Setting::set('concurrency.convert_sub', '4');
        Setting::set('media_processing_paused', '0');
    }
}

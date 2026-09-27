<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One worker configuration per job type is required for anything to be
     * processed. Production containers only run migrations (never seeders),
     * so create the defaults here. Inserts go through the query builder to
     * avoid firing the WorkerObserver before the queue tables are in use.
     */
    public function up(): void
    {
        $defaults = [
            'transcode_media' => 'Transcode Worker',
            'extract_subs' => 'Subtitle Extraction Worker',
            'convert_sub' => 'Subtitle Conversion Worker',
        ];

        foreach ($defaults as $jobType => $name) {
            if (DB::table('workers')->where('job_type', $jobType)->exists()) {
                continue;
            }

            DB::table('workers')->insert([
                'name' => $name,
                'job_type' => $jobType,
                'concurrency' => 1,
                'replace_original' => false,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};

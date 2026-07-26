<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE FROM executions WHERE library_job_id NOT IN (SELECT id FROM library_jobs)');

        Schema::table('executions', function (Blueprint $table) {
            $table->foreign('library_job_id')
                ->references('id')
                ->on('library_jobs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropForeign(['library_job_id']);
        });
    }
};

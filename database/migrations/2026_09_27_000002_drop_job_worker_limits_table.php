<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Concurrency now lives on the workers table and is enforced by the
     * supervisor process pool, so the old per-job-type limits are unused.
     */
    public function up(): void
    {
        Schema::dropIfExists('job_worker_limits');
    }

    public function down(): void
    {
        Schema::create('job_worker_limits', function (Blueprint $table) {
            $table->id();
            $table->string('job_type')->unique();
            $table->integer('max_concurrent')->default(1);
            $table->timestamps();
        });
    }
};

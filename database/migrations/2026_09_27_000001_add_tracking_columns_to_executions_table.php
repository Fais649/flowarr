<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // worker_id was a free-form string that was never populated; replace it
        // with a real foreign key so executions know which worker config ran them.
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn('worker_id');
        });

        Schema::table('executions', function (Blueprint $table) {
            $table->foreignId('worker_id')->nullable()->after('library_job_id')->constrained()->nullOnDelete();
            $table->decimal('progress', 5, 2)->nullable()->after('status');
            $table->text('message')->nullable()->after('progress');
            $table->text('output')->nullable()->after('message');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_mtime')->nullable()->after('file_size');
            $table->timestamp('heartbeat_at')->nullable()->after('started_at');

            $table->index(['library_job_id', 'file_path']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropIndex(['library_job_id', 'file_path']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn(['progress', 'message', 'output', 'file_size', 'file_mtime', 'heartbeat_at']);
        });

        Schema::table('executions', function (Blueprint $table) {
            $table->string('worker_id')->nullable();
        });
    }
};

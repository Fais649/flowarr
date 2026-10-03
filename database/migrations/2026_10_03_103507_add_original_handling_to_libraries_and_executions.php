<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libraries', function (Blueprint $table) {
            $table->string('original_handling')->nullable();
            $table->string('replacement_start', 5)->nullable();
            $table->string('replacement_end', 5)->nullable();
        });
        Schema::table('executions', function (Blueprint $table) {
            $table->string('replacement_status')->nullable()->index();
            $table->json('replacement')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('executions', fn (Blueprint $table) => $table->dropColumn(['replacement_status', 'replacement']));
        Schema::table('libraries', fn (Blueprint $table) => $table->dropColumn(['original_handling', 'replacement_start', 'replacement_end']));
    }
};

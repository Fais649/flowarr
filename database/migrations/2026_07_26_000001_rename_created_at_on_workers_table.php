<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('workers', 'createdAt')) {
            Schema::table('workers', function (Blueprint $table) {
                $table->renameColumn('createdAt', 'created_at');
            });
        }

        if (! Schema::hasColumn('workers', 'updated_at')) {
            Schema::table('workers', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->renameColumn('created_at', 'createdAt');
            $table->dropColumn('updated_at');
        });
    }
};

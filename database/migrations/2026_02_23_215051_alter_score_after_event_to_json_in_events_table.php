<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->json('score_after_raid')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('score_after_raid')->nullable()->change();
        });
    }
};

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
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->string('type')->nullable()->after('id');
            $table->string('team_id')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('notes')->nullable();
            $table->dropColumn('type');
            $table->dropColumn('team_id');
        });
    }
};

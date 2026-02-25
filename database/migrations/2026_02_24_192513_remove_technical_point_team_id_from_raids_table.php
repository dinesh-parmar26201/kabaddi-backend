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
        Schema::table('raids', function (Blueprint $table) {
            $table->dropColumn('technical_point_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raids', function (Blueprint $table) {
            $table->string('technical_point_team_id')->nullable();
        });
    }
};

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
        Schema::table('match_players', function (Blueprint $table) {
            $table->boolean('green_card')->default(false);
            $table->boolean('yellow_card')->default(false);
            $table->boolean('red_card')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_players', function (Blueprint $table) {
            $table->dropColumn(['green_card', 'yellow_card', 'red_card']);
        });
    }
};

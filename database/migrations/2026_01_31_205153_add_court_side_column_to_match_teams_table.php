<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('match_teams', function (Blueprint $table) {
            $table->string('court_side')->nullable()->after('tshirt_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_teams', function (Blueprint $table) {
            $table->dropColumn('court_side');
        });
    }
};

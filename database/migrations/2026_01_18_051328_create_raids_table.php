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
        Schema::create('raids', function (Blueprint $table) {
            $table->id();

            $table->string('match_id')->nullable();

            $table->string('raid_number')->nullable();
            $table->string('half')->nullable();

            $table->string('raid_team_id')->nullable();
            $table->string('raider_id')->nullable();

            $table->string('outcome')->nullable();

            $table->boolean('bonus_point')->default(false);
            $table->boolean('super_raid')->default(false);
            $table->boolean('raider_lineout')->default(false);
            $table->boolean('all_out')->default(false);

            $table->string('technical_point_team_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raids');
    }
};

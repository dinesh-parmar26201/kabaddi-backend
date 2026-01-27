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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            $table->string('tournament_id')->nullable();

            $table->string('team_a_id')->nullable();
            $table->string('team_b_id')->nullable();

            $table->string('start_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();

            $table->string('venue')->nullable();
            $table->string('ground_name')->nullable();

            $table->string('organizer_phone')->nullable();
            $table->string('organizer_email')->nullable();

            $table->string('toss_winner_team_id')->nullable();
            $table->string('toss_decision')->nullable();

            $table->string('status')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};

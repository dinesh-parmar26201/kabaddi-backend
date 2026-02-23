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
         Schema::create('raid_defender_lineouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('raid_id')
                ->constrained('raids')
                ->cascadeOnDelete();

                
            $table->foreignId('match_id')
                ->constrained('matches')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('match_players')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raid_defender_lineouts');
    }
};

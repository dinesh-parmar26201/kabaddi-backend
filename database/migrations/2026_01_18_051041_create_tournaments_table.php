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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('banner')->nullable();

            $table->string('organizer_name')->nullable();
            $table->string('organizer_phone')->nullable();
            $table->string('organizer_email')->nullable();

            $table->string('ground')->nullable();
            $table->string('city')->nullable();

            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();

            $table->string('category')->nullable();
            $table->string('status')->nullable();

            $table->string('created_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};

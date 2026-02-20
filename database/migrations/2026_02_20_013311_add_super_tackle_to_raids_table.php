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
            $table->boolean('super_tackle')->default(false)->after('super_raid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raids', function (Blueprint $table) {
            $table->dropColumn('super_tackle');
        });
    }
};

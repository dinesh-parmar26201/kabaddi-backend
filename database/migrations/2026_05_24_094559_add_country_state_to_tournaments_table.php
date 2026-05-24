<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {

            $table->string('country')->nullable()->after('ground');
            $table->string('state')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {

            $table->dropColumn(['country','state']);
        });
    }
};
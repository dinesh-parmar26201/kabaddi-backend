<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('name');
            $table->string('type')->nullable()->after('gender');
            $table->string('age_group')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['gender', 'type', 'age_group']);
        });
    }
};
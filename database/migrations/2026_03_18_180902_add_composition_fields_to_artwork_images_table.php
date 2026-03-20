<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artwork_images', function (Blueprint $table) {
            $table->unsignedTinyInteger('composition_position')->nullable()->after('position');
            $table->unsignedSmallInteger('rotation')->default(0)->after('composition_position');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_images', function (Blueprint $table) {
            $table->dropColumn(['composition_position', 'rotation']);
        });
    }
};
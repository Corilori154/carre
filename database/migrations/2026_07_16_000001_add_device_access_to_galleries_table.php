<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('access_password')->nullable()->after('email');
            $table->string('device_token_hash', 64)->nullable()->after('access_password');
            $table->timestamp('claimed_at')->nullable()->after('device_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn(['access_password', 'device_token_hash', 'claimed_at']);
        });
    }
};

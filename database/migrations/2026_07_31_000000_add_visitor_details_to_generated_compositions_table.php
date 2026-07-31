<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_compositions', function (Blueprint $table) {
            $table->foreignId('gallery_id')->nullable()->after('artwork_id')->constrained()->nullOnDelete();
            // Nullable only for compositions created before this feature was deployed.
            $table->string('first_name')->nullable()->after('gallery_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('generated_compositions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gallery_id');
            $table->dropColumn(['first_name', 'last_name', 'email']);
        });
    }
};

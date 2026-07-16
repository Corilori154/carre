<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->json('composition');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_compositions');
    }
};

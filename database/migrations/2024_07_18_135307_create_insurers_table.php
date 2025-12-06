<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('email');
            $table->integer('daily_capacity')->default(1000);
            $table->integer('min_batch_size')->default(10);
            $table->integer('max_batch_size')->default(100);
            $table->enum('date_preference', ['encounter', 'submission'])->default('encounter');
            $table->json('specialty_efficiencies')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
}; 
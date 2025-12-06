<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_id')->constrained()->onDelete('cascade');
            $table->string('provider_name');
            $table->date('encounter_date');
            $table->date('submission_date');
            $table->integer('priority_level')->default(5);
            $table->string('specialty');
            $table->decimal('total_amount', 15, 2);
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
}; 
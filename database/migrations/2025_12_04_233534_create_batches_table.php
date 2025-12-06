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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_id')->constrained()->onDelete('cascade');
            $table->string('provider_name');
            $table->date('batch_date');
            $table->string('identifier')->unique();
            $table->integer('claim_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->boolean('processed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};

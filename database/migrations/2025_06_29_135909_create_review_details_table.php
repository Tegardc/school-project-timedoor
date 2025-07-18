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
        Schema::create('review_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewId')->constrained('reviews')->onDelete('cascade');
            $table->foreignId('questionId')->constrained('questions')->onDelete('cascade');
            $table->decimal('score');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_details');
    }
};

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
        Schema::create('explanation_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('explanation');
            $table->string('difficulty_level')->default('medium');
            $table->boolean('is_personalized')->default(false);
            $table->timestamps();
            
            // Um índice composto para lookups rápidos com nome mais curto
            $table->unique(['question_id', 'difficulty_level', 'is_personalized'], 'explanation_cache_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explanation_cache');
    }
};

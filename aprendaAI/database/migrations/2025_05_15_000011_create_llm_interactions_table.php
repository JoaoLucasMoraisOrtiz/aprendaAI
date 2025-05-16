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
        Schema::create('llm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('interaction_type', ['explanation', 'question', 'study_plan', 'recommendation', 'performance_analysis', 'performance_analysis_skipped']); // Added performance_analysis types
            $table->text('prompt');
            $table->text('response')->nullable();
            $table->string('model_used')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('feedback_rating')->nullable();
            $table->enum('status', ['success', 'failed', 'processing'])->default('processing');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_interactions');
    }
};

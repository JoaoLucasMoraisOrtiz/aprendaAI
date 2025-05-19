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
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->float('proficiency_level')->default(0);
            $table->enum('mastery_status', ['easy', 'medium', 'hard'])->default('easy');
            $table->integer('questions_answered')->default(0);
            $table->integer('questions_correct')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamp('last_interaction')->nullable();
            $table->json('adaptive_recommendations')->nullable();
            $table->json('focus_areas')->nullable();
            $table->integer('learning_streak')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};

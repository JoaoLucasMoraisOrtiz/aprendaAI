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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('easy');
            $table->integer('order')->default(0);
            $table->integer('estimated_time_minutes')->default(60);
            $table->json('prerequisites')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};

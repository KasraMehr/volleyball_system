<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 140);
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index(['club_id', 'status', 'starts_on']);
        });

        Schema::create('workout_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->string('day_label', 40)->nullable();
            $table->string('title', 140);
            $table->string('category', 30);
            $table->unsignedSmallInteger('sets')->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workout_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unsignedTinyInteger('effort')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['workout_exercise_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_completions');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workout_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date')->index();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('standing_reach_cm', 5, 1)->nullable();
            $table->decimal('vertical_jump_cm', 5, 1)->nullable();
            $table->decimal('spike_reach_cm', 5, 1)->nullable();
            $table->decimal('block_reach_cm', 5, 1)->nullable();
            $table->decimal('agility_seconds', 5, 2)->nullable();
            $table->text('coach_notes')->nullable();
            $table->timestamps();
            $table->index(['club_id', 'athlete_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_assessments');
    }
};

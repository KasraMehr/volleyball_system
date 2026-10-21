<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_head_coach')->default(false);
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'coach_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_assignments');
    }
};

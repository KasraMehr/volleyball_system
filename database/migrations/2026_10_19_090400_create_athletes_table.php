<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('membership_number', 30)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 15)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10);
            $table->string('position', 40)->nullable();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['club_id', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athletes', function (Blueprint $table): void {
            $table->string('national_code', 10)->nullable()->after('membership_number');
            $table->string('emergency_name')->nullable()->after('phone');
            $table->string('emergency_phone', 15)->nullable()->after('emergency_name');
            $table->text('medical_notes')->nullable()->after('jersey_number');
            $table->unique(['club_id', 'national_code']);
        });

        Schema::create('athlete_guardians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 40)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['athlete_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_guardians');
        Schema::table('athletes', function (Blueprint $table): void {
            $table->dropUnique(['club_id', 'national_code']);
            $table->dropColumn(['national_code', 'emergency_name', 'emergency_phone', 'medical_notes']);
        });
    }
};

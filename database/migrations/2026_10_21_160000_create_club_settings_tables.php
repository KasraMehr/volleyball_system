<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table): void {
            $table->string('city')->nullable()->after('province');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('registration_number', 80)->nullable()->after('website');
            $table->text('address')->nullable()->after('registration_number');
        });

        Schema::create('venues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('floor_type', 60)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('venues');
        Schema::table('clubs', function (Blueprint $table): void {
            $table->dropColumn(['city', 'email', 'website', 'registration_number', 'address']);
        });
    }
};

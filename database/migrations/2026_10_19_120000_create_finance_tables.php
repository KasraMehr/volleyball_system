<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->string('frequency', 20)->default('monthly');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('discount')->default(0);
            $table->date('due_date')->index();
            $table->string('status', 20)->default('unpaid')->index();
            $table->string('payment_token', 64)->unique();
            $table->timestamps();
            $table->index(['club_id', 'athlete_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 30);
            $table->string('status', 20)->default('successful')->index();
            $table->string('reference_number', 80)->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['club_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_plans');
    }
};

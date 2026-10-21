<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->uuid('authority')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('initiated')->index();
            $table->string('reference_number', 100)->nullable()->unique();
            $table->text('callback_url');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('payment_attempts'); }
};

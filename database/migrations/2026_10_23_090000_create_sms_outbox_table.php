<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_outbox', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('club_notification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('recipient', 20)->index();
            $table->text('message');
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_message_id', 100)->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['club_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sms_outbox'); }
};

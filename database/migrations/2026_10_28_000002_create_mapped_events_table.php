<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapped_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inbound_webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->nullable();
            $table->string('order_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->json('payload');
            $table->boolean('valid')->default(false);
            $table->json('errors');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapped_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('provider', 30)->default('stripe');


            $table->string('provider_session_id', 100)
                ->nullable()
                ->unique();

            $table->string('provider_payment_id', 100)
                ->nullable()
                ->unique();


            $table->unsignedBigInteger('amount_cents');

            $table->char('currency', 3)->default('USD');

            $table->string('status', 30)->default('pending');

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference', 32)->unique();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('showtime_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('hold_id')->nullable();

            $table->string('status', 30)->default('pending_payment');

            $table->unsignedBigInteger('total_amount_cents');

            $table->char('currency', 3)->default('USD');

            $table->dateTime('confirmed_at')->nullable();

            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['showtime_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

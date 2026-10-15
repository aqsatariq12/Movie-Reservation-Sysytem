
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtime_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('showtime_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('seat_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 20)->default('available');

            $table->unsignedBigInteger('hold_id')->nullable();

            $table->unsignedBigInteger('booking_id')->nullable();

            $table->dateTime('held_until')->nullable();

            $table->unsignedInteger('price_cents');

            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id']);

            $table->index(['status', 'held_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_seats');
    }
};

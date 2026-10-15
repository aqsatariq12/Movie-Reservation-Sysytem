<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('ticket_scans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->nullable()
                ->constrained('tickets')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('showtime_id')
                ->nullable()
                ->constrained('showtimes')
                ->nullOnDelete();

            $table->char('token_hash', 64)->nullable();

            $table->string('result', 30);

            $table->dateTime('scanned_at');

            $table->timestamps();

            $table->index(['showtime_id', 'scanned_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_scans');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showtime_seats', function (Blueprint $table) {
            $table->foreign('hold_id')
                ->references('id')
                ->on('holds')
                ->restrictOnDelete();

            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->restrictOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('hold_id')
                ->references('id')
                ->on('holds')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['hold_id']);
        });

        Schema::table('showtime_seats', function (Blueprint $table) {
            $table->dropForeign(['hold_id']);
            $table->dropForeign(['booking_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genre_movie', function (Blueprint $table) {
            $table->foreign('movie_id')->references('id')->on('movies')->cascadeOnDelete();
            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete();
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->foreign('screen_id')->references('id')->on('screens')->cascadeOnDelete();
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreign('movie_id')->references('id')->on('movies')->restrictOnDelete();
            $table->foreign('screen_id')->references('id')->on('screens')->restrictOnDelete();
        });

        Schema::table('showtime_seats', function (Blueprint $table) {
            $table->foreign('showtime_id')->references('id')->on('showtimes')->cascadeOnDelete();
            $table->foreign('seat_id')->references('id')->on('seats')->restrictOnDelete();
            $table->foreign('hold_id')->references('id')->on('holds')->restrictOnDelete();
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
        });

        Schema::table('holds', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('showtime_id')->references('id')->on('showtimes')->restrictOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('showtime_id')->references('id')->on('showtimes')->restrictOnDelete();
            $table->foreign('hold_id')->references('id')->on('holds')->restrictOnDelete();
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
            $table->foreign('seat_id')->references('id')->on('seats')->restrictOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
            $table->foreign('booking_seat_id')->references('id')->on('booking_seats')->restrictOnDelete();
        });

        Schema::table('ticket_scans', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('showtime_id')->references('id')->on('showtimes')->nullOnDelete();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('ticket_scans', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['showtime_id']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['booking_seat_id']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['seat_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['showtime_id']);
            $table->dropForeign(['hold_id']);
        });

        Schema::table('holds', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['showtime_id']);
        });

        Schema::table('showtime_seats', function (Blueprint $table) {
            $table->dropForeign(['showtime_id']);
            $table->dropForeign(['seat_id']);
            $table->dropForeign(['hold_id']);
            $table->dropForeign(['booking_id']);
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropForeign(['movie_id']);
            $table->dropForeign(['screen_id']);
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->dropForeign(['screen_id']);
        });

        Schema::table('genre_movie', function (Blueprint $table) {
            $table->dropForeign(['movie_id']);
            $table->dropForeign(['genre_id']);
        });
    }
};
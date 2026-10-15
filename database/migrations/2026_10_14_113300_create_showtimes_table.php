
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('movie_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('screen_id')
                ->constrained()
                ->restrictOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedInteger('base_price_cents');

            $table->string('status', 20)->default('scheduled');

            $table->timestamps();

            $table->index(['screen_id', 'starts_at']);
            $table->index(['movie_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};

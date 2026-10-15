
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->string('name',191)->unique();
            $table->unsignedSmallInteger('total_rows');
            $table->unsignedSmallInteger('seats_per_row');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};

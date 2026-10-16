<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: showtimes need movies and screens first.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingSeeder::class,
            GenreSeeder::class,
            MovieSeeder::class,
            ScreenSeeder::class,
            ShowtimeSeeder::class,
        ]);
    }
}

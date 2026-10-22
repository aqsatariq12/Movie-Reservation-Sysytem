<?php

namespace Database\Seeders;

use App\Enums\MovieStatus;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    /**
     * The same eight films shown in the Figma home page.
     * poster_path is relative to /public (images live in public/images/cinebook).
     */
    public function run(): void
    {
        $movies = [
            [
                'title' => 'The Last Horizon',
                'synopsis' => 'Beyond the edge of the known universe, one signal leads an astronaut back to everything she thought she had lost.',
                'duration_minutes' => 124,
                'rating' => 'PG-13',
                'poster_path' => 'images/cinebook/movie-1.png',
                'release_date' => '2026-10-02',
                'status' => MovieStatus::NowShowing,
                'genres' => ['sci-fi', 'drama'],
            ],
            [
                'title' => 'Night Circuit',
                'synopsis' => 'A courier racing through a neon city discovers the package she carries can rewrite the night.',
                'duration_minutes' => 112,
                'rating' => 'PG-13',
                'poster_path' => 'images/cinebook/movie-2.png',
                'release_date' => '2026-10-09',
                'status' => MovieStatus::NowShowing,
                'genres' => ['action', 'sci-fi'],
            ],
            [
                'title' => 'A Quiet October',
                'synopsis' => 'Two estranged sisters return to a lakeside town for one last autumn together.',
                'duration_minutes' => 108,
                'rating' => 'PG',
                'poster_path' => 'images/cinebook/movie-3.png',
                'release_date' => '2026-10-02',
                'status' => MovieStatus::NowShowing,
                'genres' => ['drama'],
            ],
            [
                'title' => 'Side by Side',
                'synopsis' => 'Two rival road-trippers share one car, one map and absolutely no common sense.',
                'duration_minutes' => 96,
                'rating' => 'PG-13',
                'poster_path' => 'images/cinebook/movie-4.png',
                'release_date' => '2026-10-09',
                'status' => MovieStatus::NowShowing,
                'genres' => ['comedy'],
            ],
            [
                'title' => 'The Paper Kingdom',
                'synopsis' => 'A curious girl folds her way into a hidden world where every creature is made of paper.',
                'duration_minutes' => 102,
                'rating' => 'PG',
                'poster_path' => 'images/cinebook/movie-5.png',
                'release_date' => '2026-10-02',
                'status' => MovieStatus::NowShowing,
                'genres' => ['family'],
            ],
            [
                'title' => 'Beneath the Blue',
                'synopsis' => 'A diver uncovers a city the ocean forgot.',
                'duration_minutes' => 116,
                'rating' => 'PG-13',
                'poster_path' => 'images/cinebook/movie-6.png',
                'release_date' => '2026-10-23',
                'status' => MovieStatus::ComingSoon,
                'genres' => ['drama'],
            ],
            [
                'title' => 'Signal Zero',
                'synopsis' => 'A radio engineer hears a voice from tomorrow.',
                'duration_minutes' => 118,
                'rating' => 'PG-13',
                'poster_path' => 'images/cinebook/movie-7.png',
                'release_date' => '2026-10-30',
                'status' => MovieStatus::ComingSoon,
                'genres' => ['sci-fi', 'action'],
            ],
            [
                'title' => 'The Great Detour',
                'synopsis' => 'A family takes the long way home.',
                'duration_minutes' => 104,
                'rating' => 'PG',
                'poster_path' => 'images/cinebook/movie-8.png',
                'release_date' => '2026-11-06',
                'status' => MovieStatus::ComingSoon,
                'genres' => ['comedy', 'family'],
            ],
        ];

        foreach ($movies as $data) {
            $genreSlugs = $data['genres'];
            unset($data['genres']);

            $movie = Movie::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                $data,
            );

            $movie->genres()->sync(
                Genre::whereIn('slug', $genreSlugs)->pluck('id')
            );
        }
    }
}

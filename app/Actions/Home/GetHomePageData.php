<?php

namespace App\Actions\Home;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * Builds everything the public home page needs.
 *
 * STEP 1 (now): returns placeholder data so the UI matches Figma exactly.
 * STEP 2 (later): replace each placeholder block below with real queries
 * (Movie, Showtime, Genre, Setting models) and keep the same array keys,
 * so the Blade views do not have to change.
 */
class GetHomePageData
{
    public function execute(?string $selectedDate = null): array
    {
        return [
            'featured' => $this->featured(),
            'dates' => $this->dates($selectedDate),
            'showtimeSection' => $this->showtimeSection(),
            'nowShowing' => $this->nowShowing(),
            'comingSoon' => $this->comingSoon(),
            'moods' => $this->moods(),
        ];
    }

    /** The big hero film. TODO: first movie with status = now_showing and a showtime tonight. */
    private function featured(): array
    {
        return [
            'title' => 'The Last Horizon',
            'year' => 2026,
            'rating' => 'PG-13',
            'duration' => 124,
            'genres' => ['Sci-Fi', 'Drama'],
            'synopsis' => 'Beyond the edge of the known universe, one signal leads an astronaut back to everything she thought she had lost.',
            'image' => 'images/cinebook/hero-last-horizon.png',
            'tonight' => 'Tonight · Screen 1 · 19:30 · From $10',
            'url' => $this->movieUrl('the-last-horizon'),
            'trailer_url' => '#',
        ];
    }

    /** Seven day strip. The active day comes from ?date=YYYY-MM-DD (defaults to today). */
    private function dates(?string $selected): array
    {
        $days = collect(range(0, 6))->map(fn (int $i) => Carbon::today()->addDays($i));

        $active = $days->first(fn (Carbon $day) => $day->toDateString() === $selected) ?? $days->first();

        return $days->map(fn (Carbon $day, int $i) => [
            'label' => $i === 0 ? 'Today · '.$day->format('M j') : $day->format('D · M j'),
            'url' => route('home', ['date' => $day->toDateString()]).'#showtimes',
            'active' => $day->equalTo($active),
        ])->all();
    }

    /** "What's on at Downtown?" card. TODO: showtimes for the selected date, grouped by movie. */
    private function showtimeSection(): array
    {
        return [
            'subtitle' => 'Screen 1 & Screen 2 IMAX · USD',
            'all_showtimes_url' => $this->moviesUrl(),
            'rows' => [
                [
                    'title' => 'The Last Horizon',
                    'screen' => 'Screen 1',
                    'standard_price' => '$10',
                    'premium_price' => '$12',
                    'times' => [
                        ['time' => '14:30', 'closed' => true],
                        ['time' => '19:30', 'closed' => false],
                        ['time' => '22:00', 'closed' => false],
                    ],
                ],
            ],
        ];
    }

    /** TODO: Movie::where('status', 'now_showing')->with('genres')->take(5)->get() */
    private function nowShowing(): array
    {
        return [
            [
                'title' => 'The Last Horizon',
                'rating' => 'PG-13',
                'duration' => 124,
                'genres' => ['Sci-Fi', 'Drama'],
                'next_showtimes' => 'Today · 19:30 / 22:00',
                'poster' => 'images/cinebook/movie-1.png',
                'url' => $this->movieUrl('the-last-horizon'),
            ],
            [
                'title' => 'Night Circuit',
                'rating' => 'PG-13',
                'duration' => 112,
                'genres' => ['Action', 'Sci-Fi'],
                'next_showtimes' => 'Today · 18:00 · IMAX',
                'poster' => 'images/cinebook/movie-2.png',
                'url' => $this->movieUrl('night-circuit'),
            ],
            [
                'title' => 'A Quiet October',
                'rating' => 'PG',
                'duration' => 108,
                'genres' => ['Drama'],
                'next_showtimes' => 'Tomorrow · 14:30',
                'poster' => 'images/cinebook/movie-3.png',
                'url' => $this->movieUrl('a-quiet-october'),
            ],
            [
                'title' => 'Side by Side',
                'rating' => 'PG-13',
                'duration' => 96,
                'genres' => ['Comedy'],
                'next_showtimes' => 'Tomorrow · 17:00',
                'poster' => 'images/cinebook/movie-4.png',
                'url' => $this->movieUrl('side-by-side'),
            ],
            [
                'title' => 'The Paper Kingdom',
                'rating' => 'PG',
                'duration' => 102,
                'genres' => ['Family'],
                'next_showtimes' => 'Today · 15:00 / 20:30',
                'poster' => 'images/cinebook/movie-5.png',
                'url' => $this->movieUrl('the-paper-kingdom'),
            ],
        ];
    }

    /** TODO: Movie::where('status', 'coming_soon')->orderBy('release_date')->take(3)->get() */
    private function comingSoon(): array
    {
        return [
            [
                'title' => 'Beneath the Blue',
                'rating' => 'PG-13',
                'duration' => 116,
                'genres' => ['Drama'],
                'release_date' => Carbon::parse('2026-10-23'),
                'tagline' => 'Dive deeper.',
                'description' => 'A diver uncovers a city the ocean forgot.',
                'poster' => 'images/cinebook/movie-6.png',
                'url' => $this->movieUrl('beneath-the-blue'),
            ],
            [
                'title' => 'Signal Zero',
                'rating' => 'PG-13',
                'duration' => 118,
                'genres' => ['Sci-Fi', 'Action'],
                'release_date' => Carbon::parse('2026-10-30'),
                'tagline' => 'The silence is a signal.',
                'description' => 'A radio engineer hears a voice from tomorrow.',
                'poster' => 'images/cinebook/movie-7.png',
                'url' => $this->movieUrl('signal-zero'),
            ],
            [
                'title' => 'The Great Detour',
                'rating' => 'PG',
                'duration' => 104,
                'genres' => ['Comedy', 'Family'],
                'release_date' => Carbon::parse('2026-11-06'),
                'tagline' => 'The wrong turn. The right adventure.',
                'description' => 'A family takes the long way home.',
                'poster' => 'images/cinebook/movie-8.png',
                'url' => $this->movieUrl('the-great-detour'),
            ],
        ];
    }

    /** "Find your mood" chips. TODO: Genre::orderBy('name')->get() */
    private function moods(): array
    {
        return collect(['Action', 'Comedy', 'Drama', 'Sci-Fi', 'Family'])
            ->map(fn (string $name) => [
                'name' => $name,
                'url' => $this->moviesUrl(['genre' => str($name)->slug()->toString()]),
            ])->all();
    }

    /* ---------------------------------------------------------------
     | URL helpers: fall back to "#" until the real routes exist, so the
     | home page never crashes while other pages are still being built.
     * ------------------------------------------------------------- */

    private function movieUrl(string $slug): string
    {
        return Route::has('movies.show') ? route('movies.show', $slug) : '#';
    }

    private function moviesUrl(array $query = []): string
    {
        return Route::has('movies.index') ? route('movies.index', $query) : '#';
    }
}

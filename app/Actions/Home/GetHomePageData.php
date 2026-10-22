<?php

namespace App\Actions\Home;

use App\Enums\MovieStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class GetHomePageData
{
    public function execute(?string $selectedDate = null): array
    {
        $date = $this->selectedDate($selectedDate);

        return [
            'featured' => $this->featured(),
            'dates' => $this->dates($date),
            'showtimeSection' => $this->showtimeSection($date),
            'nowShowing' => $this->nowShowing(),
            'comingSoon' => $this->comingSoon(),
            'moods' => $this->moods(),
        ];
    }

    /**
     * Only allow dates in the seven-day date selector.
     */
    private function selectedDate(?string $selected): Carbon
    {
        $today = Carbon::today();

        if (!$selected) {
            return $today;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $selected);
        } catch (\Throwable) {
            return $today;
        }

        if (!$date || $date->format('Y-m-d') !== $selected) {
            return $today;
        }

        if ($date->lt($today) || $date->gt($today->copy()->addDays(6))) {
            return $today;
        }

        return $date;
    }

    /**
     * Featured film for the hero section.
     */
    private function featured(): array
    {
        $movie = Movie::query()
            ->nowShowing()
            ->with([
                'genres',
                'showtimes' => function ($query) {
                    $query->scheduled()
                        ->upcoming()
                        ->orderBy('starts_at');
                }
            ])
            ->whereHas('showtimes', function ($query) {
                $query->scheduled()->upcoming();
            })
            ->orderByRaw("CASE WHEN slug = 'the-last-horizon' THEN 0 ELSE 1 END")
            ->first();

        if (!$movie) {
            return [
                'title' => 'Welcome to CineBook',
                'year' => Carbon::today()->year,
                'rating' => 'Not rated',
                'duration' => 0,
                'genres' => [],
                'synopsis' => 'Discover movies and find your next movie night.',
                'image' => 'images/cinebook/hero-last-horizon.png',
                'tonight' => 'Explore the movies currently showing.',
                'url' => $this->moviesUrl(),
                'trailer_url' => '#',
            ];
        }

        $showtime = $movie->showtimes->first();

        $image = $movie->slug === 'the-last-horizon'
            ? 'images/cinebook/hero-last-horizon.png'
            : ($movie->poster_url ?: 'images/cinebook/hero-last-horizon.png');

        return [
            'title' => $movie->title,
            'year' => $movie->release_date?->year ?? Carbon::today()->year,
            'rating' => $movie->rating,
            'duration' => $movie->duration_minutes,
            'genres' => $movie->genres->pluck('name')->all(),
            'synopsis' => $movie->synopsis,
            'image' => $image,
            'tonight' => $showtime
                ? 'Next showtime · ' . $showtime->screen->name
                . ' · ' . $showtime->starts_at->format('H:i')
                . ' · From ' . $this->price($showtime->base_price_cents)
                : 'Check available showtimes.',
            'url' => $this->movieUrl($movie->slug),
            'trailer_url' => $movie->trailer_url ?: '#',
        ];
    }

    /**
     * Seven-day date selector.
     */
    private function dates(Carbon $selectedDate): array
    {
        return collect(range(0, 6))
            ->map(function (int $i) use ($selectedDate) {
                $day = Carbon::today()->addDays($i);

                return [
                    'label' => $i === 0
                        ? 'Today · ' . $day->format('M j')
                        : $day->format('D · M j'),

                    'url' => route('home', [
                        'date' => $day->toDateString(),
                    ]) . '#showtimes',

                    'active' => $day->isSameDay($selectedDate),
                ];
            })
            ->all();
    }

    /**
     * Real showtimes for the selected date.
     */
    private function showtimeSection(Carbon $date): array
    {
        $showtimes = Showtime::query()
            ->with(['movie', 'screen'])
            ->whereHas('movie', fn($movie) => $movie->visible())
            ->scheduled()
            ->onDate($date)
            ->orderBy('starts_at')
            ->get();

        $rows = $showtimes
            ->groupBy(
                fn($showtime) =>
                    $showtime->movie_id . '-' . $showtime->screen_id
            )
            ->map(function ($items) {
                $first = $items->first();
                $price = $this->price($first->base_price_cents);

                return [
                    'title' => $first->movie->title,
                    'movie_url' => $this->movieUrl($first->movie->slug),
                    'screen' => $first->screen->name,
                    'standard_price' => $price,

                    // Premium pricing is not stored separately yet.
                    // Replace this when pricing tiers are implemented.
                    'premium_price' => $price,

                    'times' => $items->map(fn($showtime) => [
                        'time' => $showtime->starts_at->format('H:i'),
                        'closed' => $showtime->starts_at->isPast(),
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'subtitle' => 'Available screens · USD',
            'all_showtimes_url' => $this->moviesUrl(),
            'rows' => $rows,
        ];
    }

    /**
     * Movies currently showing.
     */
    private function nowShowing(): array
    {
        return Movie::query()
            ->nowShowing()
            ->with([
                'genres',
                'showtimes' => function ($query) {
                    $query->scheduled()
                        ->upcoming()
                        ->orderBy('starts_at');
                },
            ])
            ->orderBy('title')
            ->take(5)
            ->get()
            ->map(function ($movie) {
                $nextShowtime = $movie->showtimes->first();

                return [
                    'title' => $movie->title,
                    'rating' => $movie->rating,
                    'duration' => $movie->duration_minutes,
                    'genres' => $movie->genres->pluck('name')->all(),

                    'next_showtimes' => $nextShowtime
                        ? $nextShowtime->starts_at->format('D · H:i')
                        : 'No upcoming showtimes',

                    'poster' => $movie->poster_url,
                    'url' => $this->movieUrl($movie->slug),
                ];
            })
            ->all();
    }

    /**
     * Movies coming soon.
     */
    private function comingSoon(): array
    {
        return Movie::query()
            ->comingSoon()
            ->with('genres')
            ->orderBy('release_date')
            ->take(3)
            ->get()
            ->map(function ($movie) {
                return [
                    'title' => $movie->title,
                    'rating' => $movie->rating,
                    'duration' => $movie->duration_minutes,
                    'genres' => $movie->genres->pluck('name')->all(),
                    'release_date' => $movie->release_date,
                    'tagline' => 'Coming soon to CineBook.',
                    'description' => $movie->synopsis,
                    'poster' => $movie->poster_url,
                    'url' => $this->movieUrl($movie->slug),
                ];
            })
            ->all();
    }

    /**
     * Genre links for the mood selector.
     */
    private function moods(): array
    {
        return Genre::query()
            ->orderBy('name')
            ->get()
            ->map(fn($genre) => [
                'name' => $genre->name,
                'url' => $this->moviesUrl([
                    'genre' => $genre->slug,
                ]),
            ])
            ->all();
    }

    private function price(?int $cents): string
    {
        return '$' . number_format(($cents ?? 0) / 100, 2);
    }

    private function movieUrl(string $slug): string
    {
        return Route::has('movies.show')
            ? route('movies.show', $slug)
            : $this->moviesUrl();
    }

    private function moviesUrl(array $query = []): string
    {
        return Route::has('movies.index')
            ? route('movies.index', $query)
            : route('home');
    }
}

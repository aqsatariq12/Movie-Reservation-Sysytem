<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Enums\MovieStatus;
class MovieController extends Controller
{

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $genreSlug = $request->string('genre')->toString();

        $validated = $request->validate([
            'show_date' => ['nullable', 'date'],
        ]);

        $showDate = $validated['show_date'] ?? null;

        $movies = Movie::with('genres')
            ->visible()
            ->search($search)
            ->inGenre($genreSlug)
            ->when($showDate, function ($query) use ($showDate) {
                $query->whereHas('showtimes', function ($showtimes) use ($showDate) {
                    $showtimes->scheduled()
                        ->upcoming()
                        ->whereDate('starts_at', $showDate);
                });
            })
            ->latest()
            ->get();

        $genres = Genre::orderBy('name')->get();

        return view('movies.index', compact(
            'movies',
            'genres',
            'search',
            'genreSlug',
            'showDate'
        ));
    }



    public function show(Movie $movie): View
    {
        abort_if($movie->status === MovieStatus::Archived, 404);

        $movie->load([
            'genres',
            'showtimes' => function ($query) {
                $query->with('screen')
                    ->scheduled()
                    ->upcoming()
                    ->orderBy('starts_at');
            },
        ]);

        $showtimes = $movie->showtimes->map(function ($showtime) {
            $bookingClosesAt = $showtime->starts_at->copy()->subMinutes(15);

            return [
                'showtime' => $showtime,
                'booking_open' => now()->lt($bookingClosesAt),
                'booking_closes_at' => $bookingClosesAt,
            ];
        });

        return view('movies.show', compact('movie', 'showtimes'));
    }

}

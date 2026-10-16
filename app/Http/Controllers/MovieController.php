<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(): View
    {
        $movies = Movie::with('genres')
            ->latest()
            ->get();

        return view('movies.index', compact('movies'));
    }

    public function show(Movie $movie): View
    {
        $movie->load([
            'genres',
            'showtimes' => function ($query) {
                $query->with('screen')
                    ->scheduled()
                    ->upcoming()
                    ->orderBy('starts_at');
            },
        ]);

        return view('movies.show', compact('movie'));
    }
}

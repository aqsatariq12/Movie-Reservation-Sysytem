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
}

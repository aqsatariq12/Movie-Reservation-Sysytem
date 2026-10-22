<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $status = MovieStatus::tryFrom((string) $request->query('status'));

        $movies = Movie::query()
            ->with('genres')
            ->search($request->query('search'))
            ->inGenre($request->query('genre'))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->orderBy('id')
            ->paginate(8)
            ->withQueryString();

        $statusCounts = Movie::toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.movies.index', [
            'movies'       => $movies,
            'genres'       => Genre::orderBy('name')->get(),
            'statuses'     => MovieStatus::cases(),
            'statusCounts' => $statusCounts,
            'totalCount'   => $statusCounts->sum(),
            'activeStatus' => $status,
            'featured'     => Movie::nowShowing()->withCount('showtimes')->first(),
        ]);
    }

    public function create()
    {
        return view('admin.movies.form', $this->formData(
            new Movie(['status' => MovieStatus::ComingSoon])
        ));
    }

    public function store(MovieRequest $request)
    {
        $data = $request->safe()->except(['poster', 'genre_ids']);

        if ($request->hasFile('poster')) {
            $data['poster_path'] = $request->file('poster')->store('posters', 'public');
        }

        $movie = Movie::create($data);
        $movie->genres()->sync($request->validated('genre_ids'));

        return redirect()->route('admin.movies.index')
            ->with('status', "“{$movie->title}” was added.");
    }

    public function edit(Movie $movie)
    {
        return view('admin.movies.form', $this->formData($movie));
    }

    public function update(MovieRequest $request, Movie $movie)
    {
        $data = $request->safe()->except(['poster', 'genre_ids']);

        if ($request->hasFile('poster')) {
            $this->deleteUploadedPoster($movie);
            $data['poster_path'] = $request->file('poster')->store('posters', 'public');
        }

        $movie->update($data);
        $movie->genres()->sync($request->validated('genre_ids'));

        return redirect()->route('admin.movies.index')
            ->with('status', "“{$movie->title}” was updated.");
    }

    /** Movies are archived, never deleted, so booking history stays intact. */
    public function archive(Movie $movie)
    {
        $movie->update(['status' => MovieStatus::Archived]);

        return redirect()->route('admin.movies.index')
            ->with('status', "“{$movie->title}” was archived.");
    }

    private function formData(Movie $movie): array
    {
        return [
            'movie'       => $movie,
            'genres'      => Genre::orderBy('name')->get(),
            'statuses'    => MovieStatus::cases(),
            'ratings'     => MovieRequest::RATINGS,
            'hasShowtimes' => $movie->exists && $movie->showtimes()->exists(),
        ];
    }

    /** Only remove files uploaded via admin, never the seeded images/... posters. */
    private function deleteUploadedPoster(Movie $movie): void
    {
        if ($movie->poster_path && ! str_starts_with($movie->poster_path, 'images/')) {
            Storage::disk('public')->delete($movie->poster_path);
        }
    }
}
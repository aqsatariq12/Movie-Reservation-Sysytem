<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(Request $request)
    {
        $genres = Genre::withCount('movies')->orderBy('id')->get();

        // ?edit=ID  -> show the Edit panel
        $editing = $request->filled('edit')
            ? $genres->firstWhere('id', (int) $request->query('edit'))
            : null;

        // ?delete=ID -> show the delete confirmation (in-use or unused)
        $deleting = $request->filled('delete')
            ? $genres->firstWhere('id', (int) $request->query('delete'))
            : null;

        $deletingTitles = ($deleting && $deleting->movies_count > 0)
            ? $deleting->movies()->orderBy('title')->pluck('title')
            : collect();

        return view('admin.genres.index', compact('genres', 'editing', 'deleting', 'deletingTitles'));
    }

    public function store(GenreRequest $request)
    {
        $genre = Genre::create($request->validated());

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre “{$genre->name}” was added.");
    }

    public function update(GenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre “{$genre->name}” was updated.");
    }

    public function destroy(Genre $genre)
    {
        // Server-side guard: never delete a genre that movies still use.
        if ($genre->movies()->exists()) {
            return redirect()->route('admin.genres.index', ['delete' => $genre->id])
                ->with('status', "“{$genre->name}” is still used by movies and can’t be deleted.");
        }

        $name = $genre->name;
        $genre->delete();

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre “{$name}” was deleted.");
    }
}
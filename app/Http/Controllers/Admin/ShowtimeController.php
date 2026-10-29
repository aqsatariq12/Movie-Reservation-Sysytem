<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Showtimes\CreateShowtimeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowtimeIndexRequest;
use App\Http\Requests\Admin\StoreShowtimeRequest;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Actions\Showtimes\UpdateShowtimeAction;
use App\Http\Requests\Admin\UpdateShowtimeRequest;

class ShowtimeController extends Controller
{
    public function index(ShowtimeIndexRequest $request)
    {
        $tab = $request->query('tab', 'upcoming');

        $base = Showtime::query()
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->when($request->filled('screen_id'), fn($q) => $q->where('showtimes.screen_id', $request->integer('screen_id')))
            ->when($request->filled('movie_id'), fn($q) => $q->where('showtimes.movie_id', $request->integer('movie_id')))
            ->when($request->filled('from'), fn($q) => $q->where('showtimes.starts_at', '>=', Carbon::parse($request->query('from'))->startOfDay()))
            ->when($request->filled('to'), fn($q) => $q->where('showtimes.starts_at', '<=', Carbon::parse($request->query('to'))->endOfDay()));

        $counts = [
            'upcoming' => (clone $base)->where('showtimes.status', 'scheduled')->where('showtimes.starts_at', '>=', now())->count(),
            'past' => (clone $base)->where('showtimes.status', 'scheduled')->where('showtimes.starts_at', '<', now())->count(),
            'cancelled' => (clone $base)->where('showtimes.status', 'cancelled')->count(),
        ];

        $list = (clone $base)->select('showtimes.*', 'movies.title as movie_title');

        match ($tab) {
            'past' => $list->where('showtimes.status', 'scheduled')->where('showtimes.starts_at', '<', now())->orderByDesc('showtimes.starts_at'),
            'cancelled' => $list->where('showtimes.status', 'cancelled')->orderByDesc('showtimes.starts_at'),
            default => $list->where('showtimes.status', 'scheduled')->where('showtimes.starts_at', '>=', now())->orderBy('showtimes.starts_at'),
        };

        $showtimes = $list
            ->with('screen')
            ->withCount([
                'showtimeSeats as sold_count' => fn($q) => $q->where('status', 'sold'),
                'showtimeSeats as held_count' => fn($q) => $q->where('status', 'held')->where('held_until', '>', now()),
            ])
            ->paginate(6)
            ->withQueryString();

        return view('admin.showtimes.index', [
            'showtimes' => $showtimes,
            'tab' => $tab,
            'counts' => $counts,
            'screens' => Screen::orderBy('name')->get(['id', 'name']),
            'movies' => DB::table('movies')->orderBy('title')->get(['id', 'title']),
            'surcharge' => Showtime::premiumSurchargeCents(),
        ]);
    }

    public function create()
    {
        return view('admin.showtimes.create', [
            'movies' => DB::table('movies')->where('status', '!=', 'archived')->orderBy('title')->get(['id', 'title', 'duration_minutes']),
            'screens' => Screen::query()
                ->withCount(['seats as bookable_seats_count' => fn($q) => $q->where('is_disabled', false)])
                ->orderBy('name')
                ->get(),
            'surcharge' => Showtime::premiumSurchargeCents(),
        ]);
    }

    public function store(StoreShowtimeRequest $request, CreateShowtimeAction $create)
    {
        $create->execute(
            $request->integer('movie_id'),
            $request->integer('screen_id'),
            $request->startsAt(),
            $request->basePriceCents(),
        );

        return redirect()->route('admin.showtimes.index')
            ->with('status', 'Showtime created and seats generated.');
    }
    public function edit(Showtime $showtime)
    {
      if ($showtime->status->value !== 'scheduled' || $showtime->starts_at->isPast()) {
            return redirect()->route('admin.showtimes.index')
                ->withErrors('Past or cancelled showtimes are read-only.');
        }

        $showtime->load('screen');

        return view('admin.showtimes.edit', [
            'showtime' => $showtime,
            'movies' => DB::table('movies')->where('status', '!=', 'archived')->orderBy('title')->get(['id', 'title', 'duration_minutes']),
            'screens' => Screen::query()
                ->withCount(['seats as bookable_seats_count' => fn($q) => $q->where('is_disabled', false)])
                ->orderBy('name')
                ->get(),
            'locked' => $showtime->hasActiveBookings(),
            'surcharge' => Showtime::premiumSurchargeCents(),
        ]);
    }

    public function update(UpdateShowtimeRequest $request, Showtime $showtime, UpdateShowtimeAction $update)
    {
        $update->execute(
            $showtime,
            $request->integer('movie_id') ?: null,
            $request->integer('screen_id') ?: null,
            $request->filled('show_date') ? $request->startsAt() : null,
            $request->basePriceCents(),
        );

        return redirect()->route('admin.showtimes.index')
            ->with('status', 'Showtime updated.');
    }
}
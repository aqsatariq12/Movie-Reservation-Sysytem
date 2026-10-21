<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Screens\GenerateSeatsAction;
use App\Actions\Screens\SyncDisabledSeatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScreenRequest;
use App\Http\Requests\Admin\SyncSeatsRequest;
use App\Http\Requests\Admin\UpdateScreenRequest;
use App\Models\Screen;

class ScreenController extends Controller
{
    public function index()
    {
        $screens = Screen::query()
            ->withCount([
                'seats',
                'seats as disabled_seats_count' => fn ($q) => $q->where('is_disabled', true),
                'seats as premium_seats_count' => fn ($q) => $q->where('type', 'premium'),
                'showtimes',
            ])
            ->orderBy('name')
            ->get();

        return view('admin.screens.index', compact('screens'));
    }

    public function create()
    {
        return view('admin.screens.create');
    }

    public function store(StoreScreenRequest $request, GenerateSeatsAction $generate)
    {
        $screen = Screen::create($request->safe()->only(['name', 'total_rows', 'seats_per_row']));
        $generate->execute($screen, $request->premiumRows(), $request->disabledSeats());

        return redirect()->route('admin.screens.show', $screen)
            ->with('status', 'Screen created and seats generated.');
    }

    public function show(Screen $screen)
    {
        $screen->loadCount([
            'seats',
            'showtimes',
            'seats as disabled_seats_count' => fn ($q) => $q->where('is_disabled', true),
            'seats as premium_seats_count' => fn ($q) => $q->where('type', 'premium'),
        ]);

        $seats = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        $reserved = $screen->reservedSeats();

        return view('admin.screens.show', compact('screen', 'seats', 'reserved'));
    }

    public function update(UpdateScreenRequest $request, Screen $screen)
    {
        $screen->update($request->validated());

        return redirect()->route('admin.screens.show', $screen)
            ->with('status', 'Screen updated.');
    }

    public function destroy(Screen $screen)
    {
        if ($screen->showtimes()->exists()) {
            return back()->withErrors('This screen has showtimes and cannot be deleted.');
        }

        $screen->delete();

        return redirect()->route('admin.screens.index')
            ->with('status', 'Screen deleted.');
    }

    public function syncSeats(SyncSeatsRequest $request, Screen $screen, SyncDisabledSeatsAction $sync)
    {
        $sync->execute($screen, $request->disabledSeats());

        return redirect()->route('admin.screens.show', $screen)
            ->with('status', 'Seat changes saved.');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Screens\GenerateSeatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScreenRequest;
use App\Http\Requests\Admin\UpdateScreenRequest;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\ShowtimeSeat;

class ScreenController extends Controller
{
    public function index()
    {
        return view('admin.screens.index', [
            'screens' => Screen::withCount('seats')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.screens.create');
    }

    public function store(StoreScreenRequest $request, GenerateSeatsAction $generate)
    {
        $screen = Screen::create($request->validated());
        $generate->execute($screen);

        return redirect()->route('admin.screens.show', $screen)
            ->with('status', 'Screen created and seats generated.');
    }

    public function show(Screen $screen)
    {
        $seats = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        return view('admin.screens.show', compact('screen', 'seats'));
    }

    public function edit(Screen $screen)
    {
        return view('admin.screens.edit', compact('screen'));
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

    public function toggleSeat(Screen $screen, Seat $seat)
    {
        abort_unless($seat->screen_id === $screen->id, 404);

        $inUse = ShowtimeSeat::where('seat_id', $seat->id)
            ->where('status', '!=', 'available')
            ->whereHas('showtime', fn ($q) => $q
                ->where('status', 'scheduled')
                ->where('starts_at', '>', now()))
            ->exists();

        if (! $seat->is_disabled && $inUse) {
            return back()->withErrors('This seat is held or sold for an upcoming showtime and cannot be disabled.');
        }

        $seat->update(['is_disabled' => ! $seat->is_disabled]);

        return back();
    }
}
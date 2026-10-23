<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Models\User;
class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request)
    {
        $customers = User::query()
            ->select('users.*')          // must come before addSelect/withCount
            ->customers()
            ->search($request->validated('search'))
            ->verification($request->validated('verification'))
            ->withCount('bookings')
            ->withSpendTotals()
            ->orderBy('users.name')
            ->orderBy('users.id')        // stable ordering across pages
            ->paginate(6)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
        ]);
    }

    public function show(User $customer)
    {
        // This directory is for customers only; staff/admins live elsewhere.
        abort_unless($customer->isCustomer(), 404);

        // Reuse the same totals logic as the list page.
        $profile = User::query()
            ->select('users.*')
            ->withCount('bookings')
            ->withSpendTotals()
            ->findOrFail($customer->id);

        $bookings = $customer->bookings()
            ->with(['showtime.movie', 'payments.refunds'])
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.show', [
            'customer' => $profile,
            'bookings' => $bookings,
            'netSpentCents' => max(0, $profile->paid_cents - $profile->refunded_cents),
        ]);
    }
}
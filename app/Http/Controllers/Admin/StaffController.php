<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Staff\ChangeUserRoleAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffIndexRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function index(StaffIndexRequest $request)
    {
        $actor = $request->user();
        $search = $request->validated('search');

        // Team only by default; customers show up only when searching (so they can be promoted).
        $users = User::query()
            ->search($search)
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'staff' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $adminCount = User::where('role', Role::Admin->value)->count();
        $lastAdmin = $adminCount === 1 ? User::where('role', Role::Admin->value)->first() : null;

        // The user being edited (?change=ID). Self and the last admin are read-only.
        $candidate = $request->filled('change') ? User::find($request->integer('change')) : null;

        if ($candidate && ($candidate->is($actor) || ($lastAdmin && $candidate->is($lastAdmin)))) {
            $candidate = null;
        }

        $newRole = null;
        $roleOptions = [];
        $copy = null;

        if ($candidate) {
            $roleOptions = array_values(array_filter(
                Role::cases(),
                fn(Role $role) => $role !== $candidate->role,
            ));

            $newRole = $request->filled('role') ? Role::from($request->validated('role')) : null;

            if (!$newRole || $newRole === $candidate->role) {
                $newRole = match ($candidate->role) {
                    Role::Customer => Role::Staff,
                    Role::Staff => Role::Customer,
                    Role::Admin => Role::Staff,
                };
            }

            $copy = $this->copy($candidate, $newRole);
        }

        return view('admin.staff.index', [
            'users' => $users,
            'search' => $search,
            'actor' => $actor,
            'adminCount' => $adminCount,
            'lastAdmin' => $lastAdmin,
            'candidate' => $candidate,
            'newRole' => $newRole,
            'roleOptions' => $roleOptions,
            'copy' => $copy,
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, ChangeUserRoleAction $change)
    {
        $newRole = $request->newRole();

        $change->execute($request->user(), $user, $newRole, $request->ip());

        return redirect()
            ->route('admin.staff.index')
            ->with('status', "{$user->name} is now {$newRole->label()}.");
    }

    /** Confirmation wording for the panel. */
    private function copy(User $target, Role $newRole): array
    {
        $first = Str::before($target->name, ' ');

        return match ($newRole) {
            Role::Staff => [
                'title' => "Give {$first} staff access?",
                'body' => "{$target->name} will gain Today, Scan, Roster and booking-reference lookup access. No revenue, email, payments or refunds are exposed to staff.",
            ],
            Role::Admin => [
                'title' => "Make {$first} an administrator?",
                'body' => "{$target->name} will gain full administrator access, including revenue, payments, refunds and role management.",
            ],
            Role::Customer => [
                'title' => "Remove {$first}’s team access?",
                'body' => "{$target->name} will lose all team access and keep a customer account. Their booking history is unchanged.",
            ],
        };
    }
}
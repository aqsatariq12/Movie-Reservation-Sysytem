<?php

namespace App\Actions\Staff;

use App\Enums\Role;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeUserRoleAction
{
    public function execute(User $actor, User $target, Role $newRole, ?string $ip = null): void
    {
        DB::transaction(function () use ($actor, $target, $newRole, $ip) {
            // Lock admin rows so two admins can't demote each other simultaneously.
            $adminIds = User::query()
                ->where('role', Role::Admin->value)
                ->lockForUpdate()
                ->pluck('id');

            $target = User::query()->lockForUpdate()->findOrFail($target->id);
            $oldRole = $target->role;

            if ($actor->is($target)) {
                $this->fail('You can’t change your own role.');
            }

            if ($oldRole === $newRole) {
                $this->fail("{$target->name} already has the {$newRole->label()} role.");
            }

            if ($oldRole === Role::Admin && $adminIds->count() <= 1) {
                $this->fail('Keep at least one administrator. Demotion is blocked until another administrator exists.');
            }

            // "role" is not mass assignable on purpose, so forceFill is the trusted path.
            $target->forceFill(['role' => $newRole])->save();

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'user.role_changed',
                'entity_type' => 'user',
                'entity_id' => $target->id,
                'before_data' => ['role' => $oldRole->value],
                'after_data' => ['role' => $newRole->value],
                'ip_address' => $ip,
            ]);
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['role' => $message]);
    }
}
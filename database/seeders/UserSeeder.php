<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Development accounts, one per role.
     * They use a known password, so they are NEVER created in production.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $users = [
            ['name' => 'CineBook Admin', 'email' => 'admin@cinebook.test', 'role' => Role::Admin],
            ['name' => 'CineBook Staff', 'email' => 'staff@cinebook.test', 'role' => Role::Staff],
            ['name' => 'Test Customer', 'email' => 'customer@cinebook.test', 'role' => Role::Customer],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            if ($user->exists) {
                continue;
            }

            // role and email_verified_at are not mass assignable, so use forceFill().
            $user->forceFill([
                'name' => $data['name'],
                'password' => 'password',
                'role' => $data['role'],
                'email_verified_at' => now(),
            ])->save();
        }
    }
}

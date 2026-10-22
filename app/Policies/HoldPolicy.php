<?php

namespace App\Policies;

use App\Models\Hold;
use App\Models\User;

class HoldPolicy
{
    public function view(User $user, Hold $hold): bool
    {
        return $hold->user_id === $user->id;
    }

    public function delete(User $user, Hold $hold): bool
    {
        return $hold->user_id === $user->id;
    }
}
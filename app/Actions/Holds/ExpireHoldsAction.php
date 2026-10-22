<?php

namespace App\Actions\Holds;

use App\Enums\HoldStatus;
use App\Models\Hold;

class ExpireHoldsAction
{
    public function __construct(
        private ReleaseHoldAction $release
    ) {
    }

    /**
     * Expire holds whose reservation time has passed.
     */
    public function execute(): int
    {
        $released = 0;

        Hold::query()
            ->where('status', HoldStatus::Active->value)
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($holds) use (&$released) {
                foreach ($holds as $hold) {
                    if ($this->release->execute($hold, 'expired')) {
                        $released++;
                    }
                }
            });

        return $released;
    }
}

<?php

namespace App\Actions\Holds;

use App\Models\Hold;

class ExpireHoldsAction
{
    public function __construct(private ReleaseHoldAction $release)
    {
    }

    /** @return int how many holds were released */
    public function execute(): int
    {
        $released = 0;

        Hold::query()
            ->where('status', 'active')
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
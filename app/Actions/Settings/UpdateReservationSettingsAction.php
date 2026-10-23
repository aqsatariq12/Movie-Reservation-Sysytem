<?php

namespace App\Actions\Settings;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\ReservationSettings;
use Illuminate\Support\Facades\DB;

class UpdateReservationSettingsAction
{
    /**
     * @param  array<string, int>  $new  Setting key => new value
     * @return array<string, int>        Only the values that actually changed
     */
    public function execute(array $new, User $actor, ?string $ip = null): array
    {
        return DB::transaction(function () use ($new, $actor, $ip) {
            $before = ReservationSettings::current();

            $changed = array_filter(
                $new,
                fn (int $value, string $key) => $before[$key] !== $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changed === []) {
                return [];
            }

            foreach ($changed as $key => $value) {
                Setting::write($key, $value);
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'settings.updated',
                'entity_type' => 'settings',
                'entity_id' => null,
                'before_data' => array_intersect_key($before, $changed),
                'after_data' => $changed,
                'ip_address' => $ip,
            ]);

            return $changed;
        });
    }
}
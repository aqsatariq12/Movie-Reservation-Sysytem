<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * New users are always customers. "role" is deliberately NOT mass assignable,
     * so a registration form can never send role=admin.
     * Only trusted code (seeders, an admin action) may use forceFill().
     */
    protected $attributes = [
        'role' => 'customer',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1) . Str::substr($initials, -1)
            : $initials;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    public function isCustomer(): bool
    {
        return $this->role === Role::Customer;
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('users.role', Role::Customer);
    }

    /** Name or email contains the term (LIKE wildcards escaped). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%' . addcslashes(trim($term), '%_\\') . '%';

        return $query->where(fn(Builder $q) => $q
            ->where('users.name', 'like', $like)
            ->orWhere('users.email', 'like', $like));
    }

    public function scopeVerification(Builder $query, ?string $state): Builder
    {
        return match ($state) {
            'verified' => $query->whereNotNull('users.email_verified_at'),
            'unverified' => $query->whereNull('users.email_verified_at'),
            default => $query,
        };
    }

    /**
     * Adds paid_cents and refunded_cents as subqueries, so there are no
     * joins to inflate counts and no N+1 queries.
     */
    /**
     * Adds paid_cents and refunded_cents as subqueries.
     *
     * Gross counts Paid AND Refunded payments: a payment flips to "refunded"
     * after a refund, but the money was still collected first. The succeeded
     * refunds are then subtracted, so nothing is subtracted twice.
     */
    public function scopeWithSpendTotals(Builder $query): Builder
    {
        return $query->addSelect([
            'paid_cents' => Payment::query()
                ->selectRaw('COALESCE(SUM(payments.amount_cents), 0)')
                ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                ->whereColumn('bookings.user_id', 'users.id')
                ->whereIn('payments.status', [
                    PaymentStatus::Paid->value,
                    PaymentStatus::Refunded->value,
                ]),

            'refunded_cents' => Refund::query()
                ->selectRaw('COALESCE(SUM(refunds.amount_cents), 0)')
                ->join('payments', 'payments.id', '=', 'refunds.payment_id')
                ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                ->whereColumn('bookings.user_id', 'users.id')
                ->where('refunds.status', RefundStatus::Succeeded->value),
        ]);
    }
}

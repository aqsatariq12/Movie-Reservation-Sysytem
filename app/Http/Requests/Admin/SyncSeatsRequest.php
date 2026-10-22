<?php

namespace App\Http\Requests\Admin;

use App\Actions\Screens\GenerateSeatsAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: replace with the owner's admin role check once merged
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'disabled_seats' => ['nullable', 'string', 'max:2000', 'regex:/^\s*[A-Za-z]+\d+(\s*,\s*[A-Za-z]+\d+)*\s*$/'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $screen = $this->route('screen');

                $labels = array_map(
                    fn (int $i) => GenerateSeatsAction::rowLabel($i),
                    range(0, $screen->total_rows - 1)
                );

                $unknown = array_filter($this->disabledSeats(), function (string $seat) use ($labels, $screen) {
                    preg_match('/^([A-Z]+)(\d+)$/', $seat, $m);

                    return ! in_array($m[1], $labels, true)
                        || (int) $m[2] < 1
                        || (int) $m[2] > $screen->seats_per_row;
                });

                if ($unknown) {
                    $validator->errors()->add(
                        'disabled_seats',
                        'These seats do not exist on this screen: ' . implode(', ', $unknown)
                    );
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    public function disabledSeats(): array
    {
        return collect(explode(',', (string) $this->input('disabled_seats')))
            ->map(fn ($seat) => strtoupper(trim($seat)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
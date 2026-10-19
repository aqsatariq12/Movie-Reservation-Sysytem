<?php

namespace App\Http\Requests\Admin;

use App\Actions\Screens\GenerateSeatsAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: replace with the owner's admin role check once merged
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:screens,name'],
            'total_rows' => ['required', 'integer', 'min:1', 'max:26'],
            'seats_per_row' => ['required', 'integer', 'min:1', 'max:30'],
            'premium_rows' => ['nullable', 'string', 'max:100', 'regex:/^\s*[A-Za-z]+(\s*,\s*[A-Za-z]+)*\s*$/'],
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

                $rows = (int) $this->input('total_rows');
                $perRow = (int) $this->input('seats_per_row');

                $labels = array_map(
                    fn(int $i) => GenerateSeatsAction::rowLabel($i),
                    range(0, $rows - 1)
                );

                $unknownRows = array_diff($this->premiumRows(), $labels);
                if ($unknownRows) {
                    $validator->errors()->add(
                        'premium_rows',
                        'These rows do not exist in the grid: ' . implode(', ', $unknownRows)
                    );
                }

                $unknownSeats = array_filter($this->disabledSeats(), function (string $seat) use ($labels, $perRow) {
                    preg_match('/^([A-Z]+)(\d+)$/', $seat, $m);

                    return !in_array($m[1], $labels, true) || (int) $m[2] < 1 || (int) $m[2] > $perRow;
                });
                if ($unknownSeats) {
                    $validator->errors()->add(
                        'disabled_seats',
                        'These seats do not exist in the grid: ' . implode(', ', $unknownSeats)
                    );
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    public function premiumRows(): array
    {
        return collect(explode(',', (string) $this->input('premium_rows')))
            ->map(fn($row) => strtoupper(trim($row)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function disabledSeats(): array
    {
        return collect(explode(',', (string) $this->input('disabled_seats')))
            ->map(fn($seat) => strtoupper(trim($seat)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
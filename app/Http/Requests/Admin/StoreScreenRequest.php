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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $existing = array_map(
                    fn(int $i) => GenerateSeatsAction::rowLabel($i),
                    range(0, (int) $this->input('total_rows') - 1)
                );

                $unknown = array_diff($this->premiumRows(), $existing);

                if ($unknown) {
                    $validator->errors()->add(
                        'premium_rows',
                        'These rows do not exist in the grid: ' . implode(', ', $unknown)
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
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 'admin' middleware already protects these routes
    }

    protected function prepareForValidation(): void
    {
        // 'edit' bag when updating an existing genre, 'create' bag otherwise
        $this->errorBag = $this->route('genre') ? 'edit' : 'create';

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug($this->input('slug') ?: (string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $genre = $this->route('genre'); // null on create

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('genres', 'name')->ignore($genre)],
            'slug' => ['required', 'string', 'max:100', Rule::unique('genres', 'slug')->ignore($genre)],
        ];
    }
}
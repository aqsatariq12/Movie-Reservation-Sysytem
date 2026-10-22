<?php

namespace App\Http\Requests\Admin;

use App\Enums\MovieStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MovieRequest extends FormRequest
{
    public const RATINGS = ['G', 'PG', 'PG-13', 'R', 'NC-17'];

    public function authorize(): bool
    {
        return true; // the 'admin' middleware already protects these routes
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: (string) $this->input('title')),
        ]);
    }

    public function rules(): array
    {
        $movie = $this->route('movie'); // null on create, Movie on edit

        return [
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', Rule::unique('movies', 'slug')->ignore($movie)],
            'synopsis'         => ['required', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'rating'           => ['required', Rule::in(self::RATINGS)],
            'release_date'     => ['required', 'date'],
            'status'           => ['required', Rule::enum(MovieStatus::class)],
            'trailer_url'      => ['nullable', 'url:https', 'max:2048'],
            'genre_ids'        => ['required', 'array', 'min:1'],
            'genre_ids.*'      => ['integer', 'exists:genres,id'],
            'poster'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ];
    }

    public function attributes(): array
    {
        return [
            'duration_minutes' => 'duration',
            'genre_ids'        => 'genres',
            'trailer_url'      => 'trailer URL',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:30', 'max:5000'],
            'tag_ids' => ['array', 'max:12'],
            'tag_ids.*' => ['integer', 'exists:categories,id'],
            'photos' => ['array', 'max:4'],
            'photos.*' => ['file', 'max:10240'],
            'website' => ['nullable', 'prohibited'],
        ];
    }
}

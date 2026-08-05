<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'body' => 'required|string|max:20000',
            'is_published' => 'boolean',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['pre_chat_enabled' => $this->boolean('pre_chat_enabled')]);
    }

    public function rules(): array
    {
        return [
            'pre_chat_enabled' => 'boolean',
            'pre_chat_message' => 'nullable|string|max:200',
            'title' => 'nullable|string|max:60',
            'greeting' => 'nullable|string|max:300',
            'launcher_text' => 'required|string|max:30',
            // Hex only — the value is interpolated into the widget's inline CSS,
            // so anything looser would be a stylesheet injection into a page the
            // tenant's own customers load.
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'offline_message' => 'nullable|string|max:300',
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex' => 'The colour must be a six-digit hex value, for example #0d6efd.',
        ];
    }
}

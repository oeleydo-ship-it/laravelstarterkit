<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatCannedResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('canned_response')?->id;

        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            // Shortcuts must be unique per tenant, not globally, so two
            // workspaces can each have their own "/hello".
            'shortcut' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_\-\/]+$/',
                Rule::unique('chat_canned_responses', 'shortcut')
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id))
                    ->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'shortcut.regex' => 'The shortcut may only contain letters, numbers, dashes, underscores and slashes.',
        ];
    }
}

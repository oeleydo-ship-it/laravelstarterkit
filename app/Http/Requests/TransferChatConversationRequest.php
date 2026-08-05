<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Scoped to the acting user's tenant so a conversation can never be
            // handed to an agent belonging to another workspace.
            'to' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $this->user()->tenant_id)
                        ->where('status', 'active')
                ),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $agent = \App\Models\User::withoutGlobalScopes()->find($value);
                    if (! $agent || ! $agent->canActAsChatAgent()) {
                        $fail('The selected user cannot act as a live chat agent.');
                    }
                },
            ],
            'reason' => 'nullable|string|max:500',
        ];
    }
}

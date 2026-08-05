<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:assign,unassign,accept,close,reopen',
            'assigned_to' => [
                'nullable',
                'required_if:action,assign',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $this->user()->tenant_id)
                        ->where('status', 'active')
                ),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('action') === 'accept' && ! $this->user()->canActAsChatAgent()) {
                $validator->errors()->add('action', 'You are not permitted to act as a live chat agent.');
            }

            if ($this->input('action') !== 'assign' || ! $this->filled('assigned_to')) {
                return;
            }

            $agent = User::withoutGlobalScopes()->find($this->input('assigned_to'));
            if (! $agent || ! $agent->canActAsChatAgent()) {
                $validator->errors()->add('assigned_to', 'The selected user cannot act as a live chat agent.');
            }
        });
    }
}

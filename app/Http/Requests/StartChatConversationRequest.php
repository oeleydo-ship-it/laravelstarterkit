<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_token' => 'nullable|uuid',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'page_url' => 'nullable|string|max:2048',
            'page_title' => 'nullable|string|max:255',
            'force_new' => 'nullable|boolean',
        ];
    }
}

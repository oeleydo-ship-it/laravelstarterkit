<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

class RateChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:'.ChatConversation::MIN_RATING.'|max:'.ChatConversation::MAX_RATING,
            'comment' => 'nullable|string|max:1000',
            'visitor_token' => 'required|string',
        ];
    }
}

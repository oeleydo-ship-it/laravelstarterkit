<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'requester_name' => 'nullable|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'category' => 'nullable|string|max:100',
            'chat_conversation_id' => 'nullable|integer',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Extension allow-list rather than a mime allow-list: `mimes` checks
            // the guessed extension against the client name, which is what stops
            // an .exe arriving dressed as image/png.
            'file' => [
                'required',
                'file',
                'max:'.config('chat.attachments.max_kb'),
                'mimes:'.implode(',', config('chat.attachments.extensions')),
            ],
            'caption' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'That file type is not allowed. Allowed types: '
                .implode(', ', config('chat.attachments.extensions')).'.',
        ];
    }
}

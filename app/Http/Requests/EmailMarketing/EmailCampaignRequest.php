<?php

namespace App\Http\Requests\EmailMarketing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['from_email', 'reply_to', 'preview_text', 'text_body', 'from_name'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->input('email_template_id') === '') {
            $this->merge(['email_template_id' => null]);
        }
    }

    public function rules(): array
    {
        $tenantId = currentTenant()?->id;

        return [
            'name' => 'required|string|max:255',
            'email_list_id' => [
                'required',
                'integer',
                Rule::exists('email_lists', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'email_template_id' => [
                'nullable',
                'integer',
                Rule::exists('email_templates', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'html_body' => 'required|string|max:200000',
            'text_body' => 'nullable|string|max:200000',
        ];
    }
}

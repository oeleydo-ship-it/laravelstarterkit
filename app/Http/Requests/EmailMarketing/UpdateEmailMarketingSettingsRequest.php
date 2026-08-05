<?php

namespace App\Http\Requests\EmailMarketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailMarketingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && ($user->hasPrivilege(\App\Support\Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('company_website') === '') {
            $this->merge(['company_website' => null]);
        }

        if ($this->input('reply_to') === '') {
            $this->merge(['reply_to' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'footer_text' => 'nullable|string|max:1000',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_website' => 'nullable|url|max:255',
            'track_opens' => 'nullable|boolean',
            'track_clicks' => 'nullable|boolean',
            'double_opt_in' => 'nullable|boolean',
            'append_compliance_footer' => 'nullable|boolean',
            'batch_size' => 'required|integer|min:1|max:500',
            'batch_delay_seconds' => 'required|integer|min:1|max:60',
        ];
    }

    public function settingsPayload(): array
    {
        return [
            'from_name' => $this->validated('from_name'),
            'from_email' => $this->validated('from_email'),
            'reply_to' => $this->validated('reply_to'),
            'footer_text' => $this->validated('footer_text'),
            'company_name' => $this->validated('company_name'),
            'company_address' => $this->validated('company_address'),
            'company_website' => $this->validated('company_website') ?? '',
            'track_opens' => $this->boolean('track_opens'),
            'track_clicks' => $this->boolean('track_clicks'),
            'double_opt_in' => $this->boolean('double_opt_in'),
            'append_compliance_footer' => $this->boolean('append_compliance_footer'),
            'batch_size' => (int) $this->validated('batch_size'),
            'batch_delay_seconds' => (int) $this->validated('batch_delay_seconds'),
        ];
    }
}

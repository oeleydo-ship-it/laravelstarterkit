<?php

namespace App\Http\Requests\EmailMarketing;

use App\Models\EmailSubscriber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = currentTenant()?->id;
        $subscriberId = $this->route('subscriber')?->id;

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('email_subscribers', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($subscriberId),
            ],
            'first_name' => 'nullable|string|max:120',
            'last_name' => 'nullable|string|max:120',
            'status' => ['required', Rule::in(array_keys(EmailSubscriber::statuses()))],
            'list_ids' => 'nullable|array',
            'list_ids.*' => [
                'integer',
                Rule::exists('email_lists', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Services\Chat\BusinessHoursService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unchecked checkboxes are simply absent from the POST body, so normalise
     * every day to an explicit boolean before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $days = (array) $this->input('days', []);

        foreach (array_keys(BusinessHoursService::DAYS) as $day) {
            $days[$day]['enabled'] = (bool) ($days[$day]['enabled'] ?? false);
        }

        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'days' => $days,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'enabled' => 'boolean',
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'days' => 'required|array',
        ];

        foreach (array_keys(BusinessHoursService::DAYS) as $day) {
            $rules["days.{$day}.enabled"] = 'boolean';
            $rules["days.{$day}.start"] = ['required', 'date_format:H:i'];
            $rules["days.{$day}.end"] = ['required', 'date_format:H:i'];
        }

        return $rules;
    }
}

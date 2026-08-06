<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Validation\Rule;

class FormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->input('fields', []);

        if (! is_array($fields)) {
            return;
        }

        foreach ($fields as $i => $field) {
            if (! empty($field['options_text']) && empty($field['options'])) {
                $fields[$i]['options'] = collect(explode(',', (string) $field['options_text']))
                    ->map(fn ($v) => trim($v))
                    ->filter()
                    ->values()
                    ->all();
            }
            unset($fields[$i]['options_text']);
            $fields[$i]['required'] = ! empty($field['required']);
        }

        $this->merge(['fields' => array_values($fields)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['lead', 'survey', 'nps', 'quiz'])],
            'status' => ['required', Rule::in(['draft', 'live', 'paused'])],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'alpha_dash', 'max:64'],
            'fields.*.label' => ['required', 'string', 'max:160'],
            'fields.*.type' => ['required', Rule::in(['text', 'email', 'textarea', 'select', 'rating', 'nps'])],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['nullable', 'string', 'max:120'],
            'thank_you' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function formPayload(): array
    {
        $data = $this->validated();

        $data['fields'] = array_values(array_map(fn ($f) => [
            'key' => $f['key'],
            'label' => $f['label'],
            'type' => $f['type'],
            'required' => (bool) ($f['required'] ?? false),
            'options' => array_values(array_filter($f['options'] ?? [])),
        ], $data['fields']));

        $data['settings'] = $this->input('settings', []) ?: [];
        $data['thank_you'] = $data['thank_you'] ?? '';

        return $data;
    }
}

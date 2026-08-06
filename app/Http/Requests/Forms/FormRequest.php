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

        if ($this->has('closable')) {
            $this->merge(['closable' => $this->boolean('closable')]);
        }
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
            'display_mode' => ['nullable', Rule::in(['inline', 'popup'])],
            'delay_ms' => ['nullable', 'integer', 'min:0', 'max:120000'],
            'frequency_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'max_displays' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'closable' => ['nullable', 'boolean'],
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

        $data['settings'] = [
            'display_mode' => $data['display_mode'] ?? 'inline',
            'delay_ms' => (int) ($data['delay_ms'] ?? 0),
            'frequency_hours' => (int) ($data['frequency_hours'] ?? 0),
            'max_displays' => (int) ($data['max_displays'] ?? 0),
            'closable' => (bool) ($data['closable'] ?? false),
        ];

        unset(
            $data['display_mode'],
            $data['delay_ms'],
            $data['frequency_hours'],
            $data['max_displays'],
            $data['closable'],
        );

        $data['thank_you'] = $data['thank_you'] ?? '';

        return $data;
    }
}

<?php

namespace App\Http\Requests\Engage;

use App\Models\EngageCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('video_url') === '') {
            $this->merge(['video_url' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(array_keys(EngageCampaign::types()))],
            'status' => ['required', Rule::in(array_keys(EngageCampaign::statuses()))],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'headline' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:2000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', Rule::in(['top', 'bottom', 'center', 'bottom-left', 'bottom-right', 'top-left', 'top-right'])],
            'toast_name' => ['nullable', 'string', 'max:80'],
            'toast_action' => ['nullable', 'string', 'max:160'],
            'toast_location' => ['nullable', 'string', 'max:80'],
            'fields_name' => ['nullable', 'boolean'],
            'fields_email' => ['nullable', 'boolean'],
            'success_message' => ['nullable', 'string', 'max:200'],
            'url_contains' => ['nullable', 'string', 'max:500'],
            'delay_ms' => ['nullable', 'integer', 'min:0', 'max:120000'],
            'frequency_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'max_displays' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'device' => ['nullable', Rule::in(['any', 'desktop', 'mobile'])],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'opens_campaign_id' => ['nullable', 'integer'],
            'launcher_label' => ['nullable', 'string', 'max:40'],
            'video_url' => ['nullable', 'url', 'max:2000', 'regex:/^https:\\/\\//i'],
        ];
    }

    public function campaignPayload(): array
    {
        $type = $this->validated('type');

        $content = [
            'headline' => $this->validated('headline'),
            'body' => $this->validated('body'),
            'cta_label' => $this->validated('cta_label'),
            'cta_url' => $this->validated('cta_url'),
            'position' => $this->validated('position') ?: match ($type) {
                EngageCampaign::TYPE_BAR => 'top',
                EngageCampaign::TYPE_SLIDE_IN => 'bottom-right',
                EngageCampaign::TYPE_TOAST => 'bottom-left',
                EngageCampaign::TYPE_LAUNCHER => 'bottom-right',
                EngageCampaign::TYPE_VIDEO => 'center',
                default => 'center',
            },
            'success_message' => $this->validated('success_message') ?: 'Thanks — we will be in touch.',
            'fields' => [
                'name' => (bool) $this->boolean('fields_name'),
                'email' => $type === EngageCampaign::TYPE_FORM ? true : (bool) $this->boolean('fields_email'),
            ],
            'toast' => [
                'name' => $this->validated('toast_name'),
                'action' => $this->validated('toast_action'),
                'location' => $this->validated('toast_location'),
            ],
            'launcher_label' => $this->validated('launcher_label') ?: 'Updates',
            'opens_campaign_id' => $this->validated('opens_campaign_id'),
            'video_url' => $this->validated('video_url'),
        ];

        $targeting = [
            'url_contains' => $this->validated('url_contains'),
            'delay_ms' => (int) ($this->validated('delay_ms') ?? 0),
            'frequency_hours' => (int) ($this->validated('frequency_hours') ?? 24),
            'max_displays' => (int) ($this->validated('max_displays') ?? 0),
            'device' => $this->validated('device') ?: 'any',
        ];

        $style = [
            'brand_color' => $this->validated('brand_color'),
            'text_color' => $this->validated('text_color') ?: '#ffffff',
        ];

        return [
            'name' => $this->validated('name'),
            'type' => $type,
            'status' => $this->validated('status'),
            'priority' => (int) ($this->validated('priority') ?? 0),
            'content' => $content,
            'targeting' => $targeting,
            'style' => $style,
        ];
    }
}

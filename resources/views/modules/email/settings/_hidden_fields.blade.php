{{-- Preserve settings fields not edited on the current tab --}}
@php
    $except = $except ?? [];
    $fields = [
        'from_name' => $settings['from_name'],
        'from_email' => $settings['from_email'],
        'reply_to' => $settings['reply_to'],
        'footer_text' => $settings['footer_text'],
        'company_name' => $settings['company_name'],
        'company_address' => $settings['company_address'],
        'company_website' => $settings['company_website'],
        'track_opens' => $settings['track_opens'] ? '1' : '0',
        'track_clicks' => $settings['track_clicks'] ? '1' : '0',
        'double_opt_in' => $settings['double_opt_in'] ? '1' : '0',
        'append_compliance_footer' => $settings['append_compliance_footer'] ? '1' : '0',
        'batch_size' => $settings['batch_size'],
        'batch_delay_seconds' => $settings['batch_delay_seconds'],
    ];
@endphp
@foreach($fields as $name => $value)
    @unless(in_array($name, $except, true))
        <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}">
    @endunless
@endforeach

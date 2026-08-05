@extends('layouts.app')

@section('title', 'Email Settings')

@php
    $errorTabs = [
        'from_name' => 'sender',
        'from_email' => 'sender',
        'reply_to' => 'sender',
        'footer_text' => 'compliance',
        'company_name' => 'compliance',
        'company_address' => 'compliance',
        'company_website' => 'compliance',
        'append_compliance_footer' => 'compliance',
        'track_opens' => 'tracking',
        'track_clicks' => 'tracking',
        'double_opt_in' => 'tracking',
        'batch_size' => 'delivery',
        'batch_delay_seconds' => 'delivery',
        'test_email' => 'test',
    ];

    $currentTab = $activeTab;

    if (! $currentTab && $errors->any()) {
        $firstError = \Illuminate\Support\Str::before(array_key_first($errors->getMessages()), '.');
        $currentTab = $errorTabs[$firstError] ?? null;
    }

    $currentTab ??= array_key_first($tabs);
@endphp

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Email Marketing Settings</h4>
            <p class="text-muted mb-0 small">Sender identity, compliance footer, tracking, and delivery options.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="list-group" role="tablist">
                @foreach($tabs as $key => $label)
                    <a class="list-group-item list-group-item-action {{ $currentTab === $key ? 'active' : '' }}"
                       id="tab-{{ $key }}"
                       data-bs-toggle="list"
                       href="#pane-{{ $key }}"
                       role="tab">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content">
                {{-- Sender --}}
                <div class="tab-pane fade {{ $currentTab === 'sender' ? 'show active' : '' }}" id="pane-sender" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Sender defaults</h5>
                            <p class="text-muted small mb-4">Used when a campaign does not override from name / email.</p>

                            <form method="POST" action="{{ route('email.settings.update') }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="tab" value="sender">
                                @include('modules.email.settings._hidden_fields', ['except' => ['from_name', 'from_email', 'reply_to']])

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="from_name">From name *</label>
                                        <input type="text" name="from_name" id="from_name"
                                               class="form-control @error('from_name') is-invalid @enderror"
                                               value="{{ old('from_name', $settings['from_name']) }}" required>
                                        @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="from_email">From email *</label>
                                        <input type="email" name="from_email" id="from_email"
                                               class="form-control @error('from_email') is-invalid @enderror"
                                               value="{{ old('from_email', $settings['from_email']) }}" required>
                                        @error('from_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="reply_to">Reply-to</label>
                                        <input type="email" name="reply_to" id="reply_to"
                                               class="form-control @error('reply_to') is-invalid @enderror"
                                               value="{{ old('reply_to', $settings['reply_to']) }}">
                                        @error('reply_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary">Save sender</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Compliance --}}
                <div class="tab-pane fade {{ $currentTab === 'compliance' ? 'show active' : '' }}" id="pane-compliance" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Compliance & footer</h5>
                            <p class="text-muted small mb-4">Shown at the bottom of campaign emails (CAN-SPAM style).</p>

                            <form method="POST" action="{{ route('email.settings.update') }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="tab" value="compliance">
                                @include('modules.email.settings._hidden_fields', ['except' => ['footer_text', 'company_name', 'company_address', 'company_website', 'append_compliance_footer']])

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="company_name">Company name</label>
                                        <input type="text" name="company_name" id="company_name"
                                               class="form-control @error('company_name') is-invalid @enderror"
                                               value="{{ old('company_name', $settings['company_name']) }}">
                                        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="company_website">Website</label>
                                        <input type="url" name="company_website" id="company_website"
                                               class="form-control @error('company_website') is-invalid @enderror"
                                               value="{{ old('company_website', $settings['company_website']) }}"
                                               placeholder="https://">
                                        @error('company_website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium" for="company_address">Physical address</label>
                                        <input type="text" name="company_address" id="company_address"
                                               class="form-control @error('company_address') is-invalid @enderror"
                                               value="{{ old('company_address', $settings['company_address']) }}">
                                        @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium" for="footer_text">Footer text</label>
                                        <textarea name="footer_text" id="footer_text" rows="2"
                                                  class="form-control @error('footer_text') is-invalid @enderror">{{ old('footer_text', $settings['footer_text']) }}</textarea>
                                        @error('footer_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="hidden" name="append_compliance_footer" value="0">
                                            <input class="form-check-input" type="checkbox" name="append_compliance_footer" value="1"
                                                   id="append_compliance_footer"
                                                   @checked(old('append_compliance_footer', $settings['append_compliance_footer']))>
                                            <label class="form-check-label" for="append_compliance_footer">
                                                Automatically append compliance footer to campaigns
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary">Save compliance</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tracking --}}
                <div class="tab-pane fade {{ $currentTab === 'tracking' ? 'show active' : '' }}" id="pane-tracking" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Tracking & opt-in</h5>

                            <form method="POST" action="{{ route('email.settings.update') }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="tab" value="tracking">
                                @include('modules.email.settings._hidden_fields', ['except' => ['track_opens', 'track_clicks', 'double_opt_in']])

                                <div class="vstack gap-3">
                                    <div class="form-check">
                                        <input type="hidden" name="track_opens" value="0">
                                        <input class="form-check-input" type="checkbox" name="track_opens" value="1" id="track_opens"
                                               @checked(old('track_opens', $settings['track_opens']))>
                                        <label class="form-check-label" for="track_opens">Track opens (1×1 pixel)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="hidden" name="track_clicks" value="0">
                                        <input class="form-check-input" type="checkbox" name="track_clicks" value="1" id="track_clicks"
                                               @checked(old('track_clicks', $settings['track_clicks']))>
                                        <label class="form-check-label" for="track_clicks">Track link clicks</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="hidden" name="double_opt_in" value="0">
                                        <input class="form-check-input" type="checkbox" name="double_opt_in" value="1" id="double_opt_in"
                                               @checked(old('double_opt_in', $settings['double_opt_in']))>
                                        <label class="form-check-label" for="double_opt_in">
                                            Require double opt-in for new public subscribers
                                        </label>
                                        <div class="form-text">Stored for signup forms; imports from CRM/CSV remain single opt-in.</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary">Save tracking</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Delivery --}}
                <div class="tab-pane fade {{ $currentTab === 'delivery' ? 'show active' : '' }}" id="pane-delivery" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Delivery pacing</h5>
                            <p class="text-muted small mb-4">Controls how campaign emails are batched onto the queue.</p>

                            <form method="POST" action="{{ route('email.settings.update') }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="tab" value="delivery">
                                @include('modules.email.settings._hidden_fields', ['except' => ['batch_size', 'batch_delay_seconds']])

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="batch_size">Emails per batch *</label>
                                        <input type="number" name="batch_size" id="batch_size" min="1" max="500"
                                               class="form-control @error('batch_size') is-invalid @enderror"
                                               value="{{ old('batch_size', $settings['batch_size']) }}" required>
                                        @error('batch_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="batch_delay_seconds">Seconds between batches *</label>
                                        <input type="number" name="batch_delay_seconds" id="batch_delay_seconds" min="1" max="60"
                                               class="form-control @error('batch_delay_seconds') is-invalid @enderror"
                                               value="{{ old('batch_delay_seconds', $settings['batch_delay_seconds']) }}" required>
                                        @error('batch_delay_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="alert alert-light border mt-3 mb-0 small">
                                    Mail is sent via Laravel’s configured mailer (<code>MAIL_MAILER</code>). Run
                                    <code>php artisan queue:work</code> for reliable bulk delivery.
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary">Save delivery</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Test --}}
                <div class="tab-pane fade {{ $currentTab === 'test' ? 'show active' : '' }}" id="pane-test" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Send a test email</h5>
                            <p class="text-muted small mb-4">
                                Verifies from name / from email using your current mail configuration.
                            </p>

                            <form method="POST" action="{{ route('email.settings.test') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-medium" for="test_email">Recipient email *</label>
                                    <input type="email" name="test_email" id="test_email"
                                           class="form-control @error('test_email') is-invalid @enderror"
                                           value="{{ old('test_email', auth()->user()->email) }}" required>
                                    @error('test_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button class="btn btn-primary">Send test</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

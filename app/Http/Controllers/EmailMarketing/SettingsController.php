<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailMarketing\UpdateEmailMarketingSettingsRequest;
use App\Mail\TestCampaignMailable;
use App\Services\EmailMarketing\EmailMarketingSettingsService;
use App\Support\Privileges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        protected EmailMarketingSettingsService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeManage();

        $activeTab = $request->query('tab');
        $tabs = $this->settings->tabs();

        if ($activeTab && ! array_key_exists($activeTab, $tabs)) {
            $activeTab = null;
        }

        return view('modules.email.settings.index', [
            'settings' => $this->settings->for(currentTenant()),
            'tabs' => $tabs,
            'activeTab' => $activeTab,
        ]);
    }

    public function update(UpdateEmailMarketingSettingsRequest $request): RedirectResponse
    {
        $this->settings->save(currentTenant(), $request->settingsPayload());

        return redirect()
            ->route('email.settings.index', ['tab' => $request->input('tab', 'sender')])
            ->with('success', 'Email marketing settings saved.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        $settings = $this->settings->for(currentTenant());

        $html = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#111827;max-width:560px;margin:0 auto;padding:24px;">
  <h2 style="margin-top:0;">Test email</h2>
  <p>Your email marketing sender settings are working.</p>
  <p><strong>From:</strong> {$settings['from_name']} &lt;{$settings['from_email']}&gt;</p>
  <p style="color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:24px;">
    {$settings['footer_text']}<br>
    {$settings['company_name']}<br>
    {$settings['company_address']}
  </p>
</body>
</html>
HTML;

        try {
            Mail::to($validated['test_email'])->send(new TestCampaignMailable(
                subjectLine: '['.config('app.name').'] Email marketing test',
                htmlBody: $html,
                fromName: $settings['from_name'],
                fromEmail: $settings['from_email'],
                replyTo: $settings['reply_to'] ?: null,
            ));
        } catch (Throwable $e) {
            return redirect()
                ->route('email.settings.index', ['tab' => 'test'])
                ->withInput()
                ->with('error', 'Test email failed: '.$e->getMessage());
        }

        return redirect()
            ->route('email.settings.index', ['tab' => 'test'])
            ->with('success', 'Test email sent to '.$validated['test_email'].'.');
    }

    protected function authorizeManage(): void
    {
        abort_unless(
            auth()->user()->hasPrivilege(Privileges::EMAIL_MANAGE) || auth()->user()->isOwnerOrAdmin(),
            403
        );
    }
}

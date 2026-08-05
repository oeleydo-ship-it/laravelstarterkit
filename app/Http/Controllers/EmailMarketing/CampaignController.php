<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailMarketing\EmailCampaignRequest;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use App\Services\EmailMarketing\CampaignSendService;
use App\Services\EmailMarketing\ContentRenderer;
use App\Services\EmailMarketing\EmailMarketingSettingsService;
use App\Support\LikeSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignSendService $sendService,
        protected EmailMarketingSettingsService $settings,
    ) {
        $this->authorizeResource(EmailCampaign::class, 'campaign');
    }

    public function index(Request $request): View
    {
        $query = EmailCampaign::query()->with('list')->latest();

        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);
            $query->where(function ($outer) use ($needle) {
                $outer->whereRaw(LikeSearch::clause('name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('subject'), [$needle]);
            });
        }

        if ($request->filled('status') && array_key_exists($request->query('status'), EmailCampaign::statuses())) {
            $query->where('status', $request->query('status'));
        }

        return view('modules.email.campaigns.index', [
            'campaigns' => $query->paginate(15)->withQueryString(),
            'statuses' => EmailCampaign::statuses(),
            'search' => $search,
            'status' => $request->query('status'),
        ]);
    }

    public function create(Request $request): View
    {
        $settings = $this->settings->for(currentTenant());
        $template = null;

        if ($request->filled('template')) {
            $template = EmailTemplate::find($request->query('template'));
        }

        $campaign = new EmailCampaign([
            'from_name' => $settings['from_name'],
            'from_email' => $settings['from_email'],
            'reply_to' => $settings['reply_to'],
            'subject' => $template?->subject,
            'html_body' => $template?->html_body ?? $this->defaultHtml(),
            'text_body' => $template?->text_body,
            'email_template_id' => $template?->id,
        ]);

        return view('modules.email.campaigns.form', [
            'campaign' => $campaign,
            'lists' => EmailList::orderBy('name')->get(),
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(EmailCampaignRequest $request): RedirectResponse
    {
        $campaign = EmailCampaign::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => EmailCampaign::STATUS_DRAFT,
        ]);

        return redirect()->route('email.campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function show(EmailCampaign $campaign): View
    {
        $campaign->load(['list', 'template', 'creator']);
        $recipients = $campaign->recipients()->with('subscriber')->latest()->paginate(25);

        return view('modules.email.campaigns.show', compact('campaign', 'recipients'));
    }

    public function edit(EmailCampaign $campaign): View|RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return redirect()->route('email.campaigns.show', $campaign)
                ->with('error', 'Sent campaigns cannot be edited.');
        }

        return view('modules.email.campaigns.form', [
            'campaign' => $campaign,
            'lists' => EmailList::orderBy('name')->get(),
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function update(EmailCampaignRequest $request, EmailCampaign $campaign): RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return redirect()->route('email.campaigns.show', $campaign)
                ->with('error', 'Sent campaigns cannot be edited.');
        }

        $campaign->update($request->validated());

        return redirect()->route('email.campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('email.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function send(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('send', $campaign);

        $this->sendService->queue($campaign, immediate: true);

        return redirect()->route('email.campaigns.show', $campaign)
            ->with('success', 'Campaign is sending. Recipients are being queued.');
    }

    public function schedule(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('send', $campaign);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $this->sendService->schedule($campaign, $validated['scheduled_at']);

        return redirect()->route('email.campaigns.show', $campaign)
            ->with('success', 'Campaign scheduled.');
    }

    public function cancel(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('send', $campaign);

        $this->sendService->cancel($campaign);

        return redirect()->route('email.campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled.');
    }

    public function preview(EmailCampaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $renderer = app(ContentRenderer::class);

        $sample = EmailSubscriber::query()
            ->when($campaign->email_list_id, function ($q) use ($campaign) {
                $q->whereHas('lists', fn ($lq) => $lq->where('email_lists.id', $campaign->email_list_id));
            })
            ->where('status', EmailSubscriber::STATUS_SUBSCRIBED)
            ->first();

        $previewHtml = $renderer->previewHtml(
            $campaign->html_body,
            $sample,
            ['preview_text' => $campaign->preview_text ?? ''],
        );

        $previewSubject = $campaign->subject;
        if ($sample) {
            $recipient = new EmailCampaignRecipient([
                'email' => $sample->email,
                'tracking_token' => 'preview',
            ]);
            $recipient->setRelation('subscriber', $sample);
            $previewSubject = $renderer->renderSubject($campaign->subject, $recipient);
        }

        return view('modules.email.campaigns.preview', [
            'campaign' => $campaign,
            'previewHtml' => $previewHtml,
            'previewSubject' => $previewSubject,
            'sampleSubscriber' => $sample,
        ]);
    }

    public function applyTemplate(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if (! $campaign->isEditable()) {
            return back()->with('error', 'Campaign cannot be edited.');
        }

        $tenantId = currentTenant()->id;

        $validated = $request->validate([
            'email_template_id' => [
                'required',
                'integer',
                Rule::exists('email_templates', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ]);

        $template = EmailTemplate::findOrFail($validated['email_template_id']);

        $campaign->update([
            'email_template_id' => $template->id,
            'subject' => $template->subject,
            'html_body' => $template->html_body,
            'text_body' => $template->text_body,
        ]);

        return redirect()->route('email.campaigns.edit', $campaign)->with('success', 'Template applied.');
    }

    protected function defaultHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Campaign</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#111827;max-width:600px;margin:0 auto;padding:24px;">
  <p>Hi {{first_name}},</p>
  <p>Your campaign content goes here.</p>
</body>
</html>
HTML;
    }
}

<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailMarketing\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Support\LikeSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EmailTemplate::class, 'template');
    }

    public function index(Request $request): View
    {
        $query = EmailTemplate::query()->latest();

        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);
            $query->where(function ($outer) use ($needle) {
                $outer->whereRaw(LikeSearch::clause('name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('subject'), [$needle]);
            });
        }

        return view('modules.email.templates.index', [
            'templates' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('modules.email.templates.form', [
            'template' => new EmailTemplate([
                'html_body' => $this->defaultHtml(),
            ]),
        ]);
    }

    public function store(EmailTemplateRequest $request): RedirectResponse
    {
        $template = EmailTemplate::create($request->validated());

        return redirect()->route('email.templates.edit', $template)->with('success', 'Template created.');
    }

    public function show(EmailTemplate $template): View
    {
        return view('modules.email.templates.show', compact('template'));
    }

    public function edit(EmailTemplate $template): View
    {
        return view('modules.email.templates.form', compact('template'));
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()->route('email.templates.edit', $template)->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('email.templates.index')->with('success', 'Template deleted.');
    }

    protected function defaultHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Email</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#111827;max-width:600px;margin:0 auto;padding:24px;">
  <p>Hi {{first_name}},</p>
  <p>Write your message here.</p>
  <p>Thanks,<br>{{full_name}}</p>
</body>
</html>
HTML;
    }
}

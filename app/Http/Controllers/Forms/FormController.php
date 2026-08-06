<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\FormRequest;
use App\Models\Form;
use App\Services\Forms\SiteService;
use App\Support\FormTemplates;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->authorizeResource(Form::class, 'form');
    }

    public function index()
    {
        return view('modules.forms.forms.index', [
            'forms' => Form::query()
                ->withCount('submissions')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(Request $request)
    {
        $templateKey = $request->query('template');

        if (! $templateKey) {
            return view('modules.forms.forms.templates', [
                'templates' => FormTemplates::all(),
            ]);
        }

        $template = FormTemplates::get($templateKey);
        $defaults = is_array($template['defaults'] ?? null)
            ? $template['defaults']
            : [
                'name' => 'New form',
                'type' => 'lead',
                'status' => Form::STATUS_DRAFT,
                'fields' => [],
                'settings' => [],
                'thank_you' => 'Thanks — we will be in touch.',
            ];

        $form = new Form([
            'name' => $defaults['name'] ?? 'New form',
            'type' => $defaults['type'] ?? 'lead',
            'status' => $defaults['status'] ?? Form::STATUS_DRAFT,
            'fields' => array_values($defaults['fields'] ?? []),
            'settings' => $defaults['settings'] ?? [],
            'thank_you' => $defaults['thank_you'] ?? '',
        ]);

        return view('modules.forms.forms.form', [
            'form' => $form,
            'templateKey' => $templateKey,
            'templateLabel' => $template['label'] ?? null,
        ]);
    }

    public function store(FormRequest $request)
    {
        $site = $this->sites->defaultFor(currentTenant());

        $form = Form::create([
            ...$request->formPayload(),
            'tenant_id' => currentTenant()->id,
            'form_site_id' => $site->id,
        ]);

        return redirect()
            ->route('forms.forms.edit', $form)
            ->with('success', 'Form created.');
    }

    public function edit(Form $form)
    {
        return view('modules.forms.forms.form', [
            'form' => $form,
            'templateKey' => null,
            'templateLabel' => null,
        ]);
    }

    public function update(FormRequest $request, Form $form)
    {
        $form->update($request->formPayload());

        return back()->with('success', 'Form saved.');
    }

    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()
            ->route('forms.forms.index')
            ->with('success', 'Form deleted.');
    }
}

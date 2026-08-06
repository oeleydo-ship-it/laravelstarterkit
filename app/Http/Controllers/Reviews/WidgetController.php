<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\ReviewWidget;
use App\Services\Reviews\SiteService;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function __construct(protected SiteService $sites) { $this->authorizeResource(ReviewWidget::class, 'widget'); }
    public function index() { return view('modules.reviews.widgets.index', ['widgets' => ReviewWidget::latest()->paginate(20)]); }
    public function create() { return view('modules.reviews.widgets.form', ['widget' => new ReviewWidget()]); }
    public function store(Request $request) { $widget = ReviewWidget::create(['tenant_id' => currentTenant()->id, 'review_site_id' => $this->sites->defaultFor(currentTenant())->id, ...$this->payload($request)]); return redirect()->route('reviews.widgets.edit', $widget)->with('success', 'Widget created.'); }
    public function edit(ReviewWidget $widget) { return view('modules.reviews.widgets.form', compact('widget')); }
    public function update(Request $request, ReviewWidget $widget) { $widget->update($this->payload($request)); return back()->with('success', 'Widget saved.'); }
    public function destroy(ReviewWidget $widget) { $widget->delete(); return redirect()->route('reviews.widgets.index')->with('success', 'Widget deleted.'); }
    protected function payload(Request $request): array
    {
        $data = $request->validate(['name' => ['required','string','max:120'], 'layout' => ['required','in:stacked,carousel'], 'min_rating' => ['required','integer','between:1,5'], 'max_items' => ['required','integer','between:1,50'], 'status' => ['required','in:draft,live'], 'accent_color' => ['nullable','regex:/^#[0-9A-Fa-f]{6}$/']]);
        return [
            ...collect($data)->except('accent_color')->all(),
            'style' => ['accent_color' => $data['accent_color'] ?? null],
        ];
    }
}

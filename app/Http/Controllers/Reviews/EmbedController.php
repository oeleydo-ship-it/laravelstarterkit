<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewSite;
use App\Models\ReviewWidget;
use App\Models\Tenant;
use App\Services\Reviews\PublicAssetService;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\Request;

class EmbedController extends Controller
{
    public function __construct(protected PublicAssetService $assets, protected ReviewService $reviews) {}
    protected function site(Request $request): ReviewSite { return $request->attributes->get('review_site'); }
    protected function tenant(Request $request): Tenant { return $request->attributes->get('tenant'); }
    protected function assertOrigin(Request $request, ReviewSite $site): void
    {
        $origin = $request->headers->get('Origin') ?: ($request->headers->get('Referer') ? parse_url($request->headers->get('Referer'), PHP_URL_SCHEME).'://'.parse_url($request->headers->get('Referer'), PHP_URL_HOST) : null);
        abort_unless($site->allowsOrigin($origin), 403);
    }
    public function boot(Request $request, string $siteKey)
    {
        $site = $this->site($request); $this->assertOrigin($request, $site);
        $payload = 'window.__R='.json_encode(['k' => $site->public_key, 'b' => rtrim(url('/r/'.$site->public_key), '/')], JSON_UNESCAPED_SLASHES).';';
        if ($css = $this->assets->stylesheet()) $payload .= '(function(){var s=document.createElement("style");s.textContent='.json_encode($css, JSON_UNESCAPED_SLASHES).';document.head.appendChild(s);})();';
        return response($payload."\n".$this->assets->javascript(), 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'public, max-age=120', 'Access-Control-Allow-Origin' => '*']);
    }
    public function config(Request $request, string $siteKey)
    {
        $site = $this->site($request); $this->assertOrigin($request, $site);
        $widgets = ReviewWidget::withoutGlobalScopes()->where('review_site_id', $site->id)->where('status', ReviewWidget::STATUS_LIVE)->get()->map(function ($widget) use ($site) {
            $items = Review::withoutGlobalScopes()->where('review_site_id', $site->id)->where('status', Review::STATUS_APPROVED)->where('rating', '>=', $widget->min_rating)->latest()->limit($widget->max_items)->get()->map->toPublicPayload();
            return ['id' => $widget->id, 'l' => $widget->layout, 's' => $widget->style ?? [], 'i' => $items];
        });
        return response()->json(['w' => $widgets], 200, ['Access-Control-Allow-Origin' => '*', 'Cache-Control' => 'no-store']);
    }
    public function submit(Request $request, string $siteKey)
    {
        $site = $this->site($request); $this->assertOrigin($request, $site);
        if (filled($request->input('website')) || filled($request->input('hp'))) return response()->json(['ok' => true]);
        $data = $request->validate(['rating' => ['required','integer','between:1,5'], 'body' => ['required','string','max:5000'], 'author_name' => ['required','string','max:120'], 'author_company' => ['nullable','string','max:120'], 'author_avatar' => ['nullable','url','max:2048'], 'email' => ['nullable','email','max:190']]);
        $this->reviews->submit($this->tenant($request), $site, $data);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true], 201, ['Access-Control-Allow-Origin' => '*']);
        }

        return redirect()
            ->to(url('/r/'.$site->public_key.'/write'))
            ->with('success', 'Thanks — your review was submitted.');
    }
    public function write(Request $request, string $siteKey) { return view('modules.reviews.public.write', ['site' => $this->site($request)]); }
}

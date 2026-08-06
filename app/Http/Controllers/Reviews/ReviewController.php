<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct() { $this->authorizeResource(Review::class, 'review'); }

    public function index(Request $request)
    {
        $query = Review::query()->with('site')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        return view('modules.reviews.reviews.index', ['reviews' => $query->paginate(20)->withQueryString()]);
    }
    public function approve(Review $review) { $this->authorize('update', $review); $review->update(['status' => Review::STATUS_APPROVED]); return back()->with('success', 'Review approved.'); }
    public function reject(Review $review) { $this->authorize('update', $review); $review->update(['status' => Review::STATUS_REJECTED]); return back()->with('success', 'Review rejected.'); }
    public function destroy(Review $review) { $this->authorize('delete', $review); $review->delete(); return back()->with('success', 'Review deleted.'); }
}

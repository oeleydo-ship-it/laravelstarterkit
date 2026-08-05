<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    public function show(string $token): View
    {
        $subscriber = EmailSubscriber::withoutGlobalScopes()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        return view('modules.email.public.unsubscribe', [
            'subscriber' => $subscriber,
            'already' => ! $subscriber->isSubscribed(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $subscriber = EmailSubscriber::withoutGlobalScopes()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        if ($subscriber->isSubscribed()) {
            $subscriber->unsubscribe();
        }

        return redirect()
            ->route('email.unsubscribe.show', $token)
            ->with('success', 'You have been unsubscribed.');
    }
}

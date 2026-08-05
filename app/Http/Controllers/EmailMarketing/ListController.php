<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailMarketing\EmailListRequest;
use App\Models\EmailList;
use App\Support\LikeSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EmailList::class, 'list');
    }

    public function index(Request $request): View
    {
        $query = EmailList::query()->withCount([
            'subscribers as active_subscribers_count' => function ($q) {
                $q->where('email_subscribers.status', 'subscribed')
                    ->where('email_list_subscriber.status', 'subscribed');
            },
        ])->latest();

        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);
            $query->where(function ($outer) use ($needle) {
                $outer->whereRaw(LikeSearch::clause('name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('description'), [$needle]);
            });
        }

        return view('modules.email.lists.index', [
            'lists' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('modules.email.lists.form', [
            'list' => new EmailList,
        ]);
    }

    public function store(EmailListRequest $request): RedirectResponse
    {
        $list = EmailList::create($request->validated());

        return redirect()->route('email.lists.show', $list)->with('success', 'List created.');
    }

    public function show(EmailList $list): View
    {
        $list->loadCount([
            'subscribers as active_subscribers_count' => function ($q) {
                $q->where('email_subscribers.status', 'subscribed')
                    ->where('email_list_subscriber.status', 'subscribed');
            },
        ]);

        $subscribers = $list->subscribers()->latest('email_list_subscriber.created_at')->paginate(20);

        return view('modules.email.lists.show', compact('list', 'subscribers'));
    }

    public function edit(EmailList $list): View
    {
        return view('modules.email.lists.form', compact('list'));
    }

    public function update(EmailListRequest $request, EmailList $list): RedirectResponse
    {
        $list->update($request->validated());

        return redirect()->route('email.lists.show', $list)->with('success', 'List updated.');
    }

    public function destroy(EmailList $list): RedirectResponse
    {
        $list->delete();

        return redirect()->route('email.lists.index')->with('success', 'List deleted.');
    }
}

<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailMarketing\EmailSubscriberRequest;
use App\Models\Client;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Support\LikeSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EmailSubscriber::class, 'subscriber');
    }

    public function index(Request $request): View
    {
        $query = EmailSubscriber::query()->with('lists')->latest();

        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);
            $query->where(function ($outer) use ($needle) {
                $outer->whereRaw(LikeSearch::clause('email'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('first_name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('last_name'), [$needle]);
            });
        }

        if ($request->filled('status') && array_key_exists($request->query('status'), EmailSubscriber::statuses())) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('list')) {
            $query->whereHas('lists', fn ($q) => $q->where('email_lists.id', $request->query('list')));
        }

        return view('modules.email.subscribers.index', [
            'subscribers' => $query->paginate(20)->withQueryString(),
            'lists' => EmailList::orderBy('name')->get(),
            'statuses' => EmailSubscriber::statuses(),
            'search' => $search,
            'status' => $request->query('status'),
            'listId' => $request->query('list'),
        ]);
    }

    public function create(Request $request): View
    {
        $selected = array_map('intval', (array) $request->query('list_ids', []));

        return view('modules.email.subscribers.form', [
            'subscriber' => new EmailSubscriber(['status' => EmailSubscriber::STATUS_SUBSCRIBED]),
            'lists' => EmailList::orderBy('name')->get(),
            'statuses' => EmailSubscriber::statuses(),
            'selectedListIds' => $selected,
        ]);
    }

    public function store(EmailSubscriberRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('list_ids');
        $subscriber = EmailSubscriber::create($data);
        $this->syncLists($subscriber, $request->input('list_ids', []));

        return redirect()->route('email.subscribers.index')->with('success', 'Subscriber added.');
    }

    public function show(EmailSubscriber $subscriber): View
    {
        $subscriber->load('lists');

        return view('modules.email.subscribers.show', compact('subscriber'));
    }

    public function edit(EmailSubscriber $subscriber): View
    {
        return view('modules.email.subscribers.form', [
            'subscriber' => $subscriber,
            'lists' => EmailList::orderBy('name')->get(),
            'statuses' => EmailSubscriber::statuses(),
            'selectedListIds' => $subscriber->lists()->pluck('email_lists.id')->all(),
        ]);
    }

    public function update(EmailSubscriberRequest $request, EmailSubscriber $subscriber): RedirectResponse
    {
        $data = $request->safe()->except('list_ids');

        if (($data['status'] ?? null) === EmailSubscriber::STATUS_UNSUBSCRIBED && $subscriber->status !== EmailSubscriber::STATUS_UNSUBSCRIBED) {
            $data['unsubscribed_at'] = now();
        }

        if (($data['status'] ?? null) === EmailSubscriber::STATUS_SUBSCRIBED) {
            $data['unsubscribed_at'] = null;
            $data['subscribed_at'] = $subscriber->subscribed_at ?? now();
        }

        $subscriber->update($data);
        $this->syncLists($subscriber, $request->input('list_ids', []));

        return redirect()->route('email.subscribers.show', $subscriber)->with('success', 'Subscriber updated.');
    }

    public function destroy(EmailSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return redirect()->route('email.subscribers.index')->with('success', 'Subscriber deleted.');
    }

    public function importForm(): View
    {
        $this->authorize('create', EmailSubscriber::class);

        return view('modules.email.subscribers.import', [
            'lists' => EmailList::orderBy('name')->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', EmailSubscriber::class);

        $tenantId = currentTenant()->id;

        $validated = $request->validate([
            'list_id' => [
                'required',
                'integer',
                Rule::exists('email_lists', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'csv' => 'required|file|mimes:csv,txt|max:5120',
            'resubscribe' => 'nullable|boolean',
        ]);

        $list = EmailList::findOrFail($validated['list_id']);
        $resubscribe = $request->boolean('resubscribe');
        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $header = null;
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            if (! is_array($data)) {
                $skipped++;
                continue;
            }

            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $subscriber = EmailSubscriber::firstOrNew(
                ['tenant_id' => $tenantId, 'email' => $email]
            );

            if ($subscriber->exists && ! $subscriber->isSubscribed() && ! $resubscribe) {
                $skipped++;
                continue;
            }

            if (! $subscriber->exists) {
                $subscriber->fill([
                    'first_name' => $data['first_name'] ?? $data['firstname'] ?? null,
                    'last_name' => $data['last_name'] ?? $data['lastname'] ?? null,
                    'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                ]);
                $subscriber->save();
            } elseif ($resubscribe && ! $subscriber->isSubscribed()) {
                $subscriber->update([
                    'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                    'first_name' => $subscriber->first_name ?: ($data['first_name'] ?? $data['firstname'] ?? null),
                    'last_name' => $subscriber->last_name ?: ($data['last_name'] ?? $data['lastname'] ?? null),
                ]);
            }

            $list->subscribers()->syncWithoutDetaching([
                $subscriber->id => [
                    'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ],
            ]);

            $imported++;
        }

        fclose($handle);

        return redirect()
            ->route('email.subscribers.index', ['list' => $list->id])
            ->with('success', "Imported {$imported} subscriber(s). Skipped {$skipped}.");
    }

    public function importFromClients(Request $request): RedirectResponse
    {
        $this->authorize('create', EmailSubscriber::class);

        $tenantId = currentTenant()->id;

        $validated = $request->validate([
            'list_id' => [
                'required',
                'integer',
                Rule::exists('email_lists', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'resubscribe' => 'nullable|boolean',
        ]);

        $list = EmailList::findOrFail($validated['list_id']);
        $resubscribe = $request->boolean('resubscribe');
        $imported = 0;
        $skipped = 0;

        Client::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($clients) use ($list, $tenantId, $resubscribe, &$imported, &$skipped) {
                foreach ($clients as $client) {
                    $email = strtolower(trim($client->email));
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }

                    $parts = preg_split('/\s+/', trim((string) $client->name), 2);

                    $subscriber = EmailSubscriber::firstOrNew(
                        ['tenant_id' => $tenantId, 'email' => $email]
                    );

                    if ($subscriber->exists && ! $subscriber->isSubscribed() && ! $resubscribe) {
                        $skipped++;
                        continue;
                    }

                    if (! $subscriber->exists) {
                        $subscriber->fill([
                            'first_name' => $parts[0] ?? null,
                            'last_name' => $parts[1] ?? null,
                            'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                            'subscribed_at' => now(),
                        ]);
                        $subscriber->save();
                    } elseif ($resubscribe && ! $subscriber->isSubscribed()) {
                        $subscriber->update([
                            'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                            'subscribed_at' => now(),
                            'unsubscribed_at' => null,
                        ]);
                    }

                    $list->subscribers()->syncWithoutDetaching([
                        $subscriber->id => [
                            'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                            'subscribed_at' => now(),
                            'unsubscribed_at' => null,
                        ],
                    ]);

                    $imported++;
                }
            });

        return redirect()
            ->route('email.lists.show', $list)
            ->with('success', "Imported {$imported} CRM contact(s). Skipped {$skipped}.");
    }

    protected function syncLists(EmailSubscriber $subscriber, array $listIds): void
    {
        $isSubscribed = $subscriber->status === EmailSubscriber::STATUS_SUBSCRIBED;
        $payload = [];

        foreach ($listIds as $listId) {
            $payload[(int) $listId] = [
                'status' => $isSubscribed
                    ? EmailSubscriber::STATUS_SUBSCRIBED
                    : EmailSubscriber::STATUS_UNSUBSCRIBED,
                'subscribed_at' => $isSubscribed ? ($subscriber->subscribed_at ?? now()) : null,
                'unsubscribed_at' => $isSubscribed ? null : ($subscriber->unsubscribed_at ?? now()),
            ];
        }

        $subscriber->lists()->sync($payload);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\ClientNote;
use App\Support\LikeSearch;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request)
    {
        $query = Client::query()->orderBy('name');

        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);

            $query->where(function ($outer) use ($needle) {
                $outer->whereRaw(LikeSearch::clause('name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('company'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('email'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('phone'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('city'), [$needle]);
            });
        }

        if ($request->filled('status') && array_key_exists($request->query('status'), Client::statuses())) {
            $query->where('status', $request->query('status'));
        }

        $clients = $query->paginate(15)->withQueryString();

        return view('modules.clients.index', [
            'clients' => $clients,
            'search' => $request->query('q'),
            'status' => $request->query('status'),
            'statuses' => Client::statuses(),
        ]);
    }

    public function create()
    {
        return view('modules.clients.form', [
            'client' => new Client(['status' => Client::STATUS_LEAD]),
            'statuses' => Client::statuses(),
        ]);
    }

    public function store(ClientRequest $request)
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['crmNotes.author']);

        return view('modules.clients.show', [
            'client' => $client,
            'statuses' => Client::statuses(),
        ]);
    }

    public function edit(Client $client)
    {
        return view('modules.clients.form', [
            'client' => $client,
            'statuses' => Client::statuses(),
        ]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function storeNote(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        ClientNote::create([
            'tenant_id' => $client->tenant_id,
            'client_id' => $client->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Note added.');
    }
}

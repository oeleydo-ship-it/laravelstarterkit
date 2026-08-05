<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatCannedResponseRequest;
use App\Models\ChatCannedResponse;

class CannedResponseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChatCannedResponse::class, 'canned_response');
    }

    public function index()
    {
        $responses = ChatCannedResponse::orderBy('title')->paginate(20);

        return view('modules.chat.canned.index', compact('responses'));
    }

    public function create()
    {
        return view('modules.chat.canned.form', ['response' => new ChatCannedResponse]);
    }

    public function store(ChatCannedResponseRequest $request)
    {
        ChatCannedResponse::create($request->validated());

        return redirect()->route('chat.canned-responses.index')
            ->with('success', 'Canned response created.');
    }

    public function edit(ChatCannedResponse $cannedResponse)
    {
        return view('modules.chat.canned.form', ['response' => $cannedResponse]);
    }

    public function update(ChatCannedResponseRequest $request, ChatCannedResponse $cannedResponse)
    {
        $cannedResponse->update($request->validated());

        return redirect()->route('chat.canned-responses.index')
            ->with('success', 'Canned response updated.');
    }

    public function destroy(ChatCannedResponse $cannedResponse)
    {
        $cannedResponse->delete();

        return redirect()->route('chat.canned-responses.index')
            ->with('success', 'Canned response deleted.');
    }
}

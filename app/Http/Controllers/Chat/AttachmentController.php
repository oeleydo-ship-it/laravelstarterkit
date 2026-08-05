<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatAttachmentRequest;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Services\Chat\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(protected MessageService $messages)
    {
    }

    public function store(SendChatAttachmentRequest $request, ChatConversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $file = $request->file('file');

        $message = $this->messages->sendAsAgent(
            $conversation,
            $request->user(),
            $request->validated('caption') ?: $file->getClientOriginalName(),
            $file,
        );

        return response()->json([
            'id' => $message->id,
            'sender_type' => 'agent',
            'sender_name' => $request->user()->name,
            'body' => $message->body,
            'attachment' => $message->attachment->toPayload(),
            'download_url' => route('chat.attachments.download', $message->attachment),
            'created_at' => $message->created_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Files live on a private disk, so every download is re-authorized here.
     * Route model binding runs before SetTenant binds the tenant, so the model's
     * tenant scope is not reliably applied to `$attachment` — ownership is
     * checked explicitly instead, and answers 404 so a foreign id is not even
     * confirmed to exist.
     */
    public function download(Request $request, ChatAttachment $attachment)
    {
        abort_if($attachment->tenant_id !== $request->user()->tenant_id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}

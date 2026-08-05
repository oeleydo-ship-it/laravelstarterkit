<?php

namespace App\Http\Controllers\Chat;

use App\Events\ChatTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\RateChatConversationRequest;
use App\Http\Requests\SendChatAttachmentRequest;
use App\Http\Requests\SendChatMessageRequest;
use App\Http\Requests\StartChatConversationRequest;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Services\Chat\BusinessHoursService;
use App\Services\Chat\ConversationService;
use App\Services\Chat\KnowledgeBaseService;
use App\Services\Chat\MessageService;
use App\Services\Chat\WidgetSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WidgetController extends Controller
{
    public function __construct(
        protected ConversationService $conversations,
        protected MessageService $messages,
        protected WidgetSettingsService $appearance,
        protected BusinessHoursService $hours,
        protected KnowledgeBaseService $knowledgeBase,
    ) {
    }

    public function show(Request $request, string $tenantSlug)
    {
        $tenant = $this->tenant($request);

        return view('chat.widget', [
            'tenant' => $tenant,
            'tenantSlug' => $tenantSlug,
            'appearance' => $this->appearance->for($tenant),
            'isOnline' => $this->hours->isOpen($tenant),
        ]);
    }

    /**
     * Public help-center feed for the widget: published articles and readable
     * knowledge-base documents uploaded in Chat Settings.
     */
    public function knowledge(Request $request, string $tenantSlug)
    {
        $this->tenant($request);

        $items = $this->knowledgeBase->search($request->query('q'), 20)
            ->map(fn (array $item) => [
                'type' => $item['type'],
                'id' => $item['id'],
                'title' => str_replace(' (document)', '', $item['title']),
                'excerpt' => (string) str($item['body'])->limit(140),
                'body' => $item['body'],
            ])
            ->filter(fn (array $item) => $this->knowledgeBase->isReadableText($item['body']))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function start(StartChatConversationRequest $request, string $tenantSlug)
    {
        $tenant = $this->tenant($request);

        $visitor = $this->resolveVisitor($tenant, $request->validated('visitor_token'));

        if ($request->filled('name') || $request->filled('email')) {
            $visitor->update([
                'name' => $request->validated('name') ?? $visitor->name,
                'email' => $request->validated('email') ?? $visitor->email,
            ]);
        }

        $visitor->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'country' => $visitor->country ?: $request->header('CF-IPCountry'),
            'last_seen_at' => now(),
        ])->save();

        $visitor->recordPageVisit(
            $request->validated('page_url'),
            $request->validated('page_title'),
        );

        $conversation = $this->conversations->startForVisitor(
            $tenant,
            $visitor,
            $request->boolean('force_new'),
        );
        $conversation->loadMissing('assignee');

        // Availability travels with every start so a widget left open across the
        // end of the working day picks up the offline notice on its next open.
        return response()->json([
            'visitor_token' => $visitor->token,
            'conversation_id' => $conversation->id,
            'tenant_id' => $tenant->id,
            'is_online' => $this->hours->isOpen($tenant),
            'assigned_to' => $conversation->assigned_to,
            'assignee_name' => $conversation->assignee?->name,
        ]);
    }

    public function messages(Request $request, string $tenantSlug, int $conversationId)
    {
        $tenant = $this->tenant($request);
        [$conversation] = $this->conversationAndVisitor($tenant, $conversationId, $request);

        return response()->json(
            $conversation->messages()->visibleToVisitor()->with(['sender', 'attachment'])->get()
                ->map(fn (ChatMessage $message) => $this->presentMessage($message))
                ->all()
        );
    }

    public function sendMessage(SendChatMessageRequest $request, string $tenantSlug, int $conversationId)
    {
        $tenant = $this->tenant($request);
        [$conversation] = $this->conversationAndVisitor($tenant, $conversationId, $request);

        $message = $this->messages->sendAsVisitor($conversation, $request->validated('body'));

        // Best-effort AI / knowledge-base reply while the chat is still unassigned.
        $botMessage = app(\App\Services\Chat\KnowledgeBaseAutoReplyService::class)
            ->maybeReply($conversation->fresh(['tenant']), $message);

        $payload = $this->presentMessage($message);

        if ($botMessage) {
            $payload['bot_reply'] = $this->presentMessage($botMessage->loadMissing('sender'));
        }

        return response()->json($payload, 201);
    }

    /**
     * The loader a customer drops onto their own site. It injects the widget in
     * an iframe pinned to the corner, and resizes it on the widget's postMessage
     * — the iframe is cross-origin, so the inner page cannot size itself.
     *
     * Served as a route rather than a static asset so the tenant's slug and the
     * app URL are baked in and nothing has to be configured by hand.
     */
    public function embedScript(Request $request, string $tenantSlug)
    {
        $tenant = $this->tenant($request);
        $appearance = $this->appearance->for($tenant);

        $widgetUrl = route('chat.widget.show', $tenant->slug);

        $script = view('chat.embed', [
            'widgetUrl' => $widgetUrl,
            // Derived from the widget URL itself, not APP_URL. The host page
            // compares this against the iframe's real origin, and the two can
            // differ (port, or a stale APP_URL) — a mismatch silently breaks
            // every resize message with no error anywhere.
            'origin' => $this->originOf($widgetUrl),
            'color' => $appearance['color'],
        ])->render();

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            // The snippet is public by design and changes only when the tenant
            // edits their appearance, so let browsers hold it briefly.
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Private-channel authorization for the widget's websocket subscription.
     *
     * Visitors are unauthenticated and have no Laravel session, so they cannot
     * use the shared /broadcasting/auth route — that one is CSRF-protected and
     * session-backed for agents. This lives under `widget/*` instead, where the
     * visitor token is already the credential and CSRF is already exempt.
     */
    public function authorizeChannel(Request $request, string $tenantSlug)
    {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => 'required|string',
            'visitor_token' => 'required|string',
        ]);

        // Anchored, so the agents-only `…conversation.{id}.internal` channel can
        // never match — a visitor must not be able to subscribe to staff notes.
        if (! preg_match('/^private-tenant\.(\d+)\.conversation\.(\d+)$/', $validated['channel_name'], $matches)) {
            abort(403, 'Unsupported channel.');
        }

        [, $channelTenantId, $conversationId] = $matches;

        abort_if((int) $channelTenantId !== $tenant->id, 403, 'Channel belongs to another workspace.');

        // Reuses the same ownership check as every other visitor endpoint.
        $this->conversationAndVisitor($tenant, (int) $conversationId, $request);

        $connection = config('broadcasting.default');
        $key = config("broadcasting.connections.{$connection}.key");
        $secret = config("broadcasting.connections.{$connection}.secret");

        $signature = hash_hmac(
            'sha256',
            $validated['socket_id'].':'.$validated['channel_name'],
            (string) $secret,
        );

        return response()->json(['auth' => "{$key}:{$signature}"]);
    }

    public function sendAttachment(SendChatAttachmentRequest $request, string $tenantSlug, int $conversationId)
    {
        $tenant = $this->tenant($request);
        [$conversation] = $this->conversationAndVisitor($tenant, $conversationId, $request);

        $file = $request->file('file');

        $message = $this->messages->sendAsVisitor(
            $conversation,
            $request->validated('caption') ?: $file->getClientOriginalName(),
            $file,
        );

        return response()->json($this->presentMessage($message), 201);
    }

    /**
     * The visitor's own download route. Deliberately not route-model bound: the
     * attachment is looked up under the tenant *and* re-checked against the
     * conversation this visitor's token owns, so an id from another chat — or
     * from an internal note — cannot be fetched by guessing.
     */
    public function downloadAttachment(Request $request, string $tenantSlug, int $conversationId, int $attachmentId)
    {
        $tenant = $this->tenant($request);
        [$conversation] = $this->conversationAndVisitor($tenant, $conversationId, $request);

        $attachment = ChatAttachment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereHas('message', fn ($message) => $message
                ->withoutGlobalScopes()
                ->where('chat_conversation_id', $conversation->id)
                ->where('is_internal', false))
            ->findOrFail($attachmentId);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    /**
     * The visitor's satisfaction score. Only offered once a chat is closed —
     * rating a live conversation would be scoring an unfinished job — and only
     * accepted once, so a stray second click cannot overwrite real feedback.
     */
    public function rate(RateChatConversationRequest $request, string $tenantSlug, int $conversationId)
    {
        $tenant = $this->tenant($request);
        [$conversation] = $this->conversationAndVisitor($tenant, $conversationId, $request);

        if ($conversation->status !== 'closed') {
            return response()->json(['message' => 'This chat is still open.'], 422);
        }

        $recorded = $this->conversations->rate(
            $conversation,
            (int) $request->validated('rating'),
            $request->validated('comment'),
        );

        if (! $recorded) {
            return response()->json(['message' => 'This chat has already been rated.'], 409);
        }

        return response()->json(['rating' => $conversation->fresh()->rating], 201);
    }

    public function typing(Request $request, string $tenantSlug, int $conversationId)
    {
        $tenant = $this->tenant($request);
        $this->conversationAndVisitor($tenant, $conversationId, $request);

        broadcast(new ChatTyping($tenant->id, $conversationId, 'visitor'))->toOthers();

        return response()->noContent();
    }

    protected function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }

    /**
     * scheme://host[:port] — the exact string a browser reports as an iframe's
     * origin in a postMessage event.
     */
    protected function originOf(string $url): string
    {
        $parts = parse_url($url);

        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }

    /**
     * Shape a message for the public widget. Visitors are unauthenticated, so they
     * must only ever see the agent's display name — never the underlying user
     * record (email, role, is_superadmin, tenant_id) that serializing the model
     * would expose.
     */
    protected function presentMessage(ChatMessage $message): array
    {
        $attachment = $message->attachment;

        return [
            'id' => $message->id,
            'conversation_id' => $message->chat_conversation_id,
            'sender_type' => $message->sender_type,
            'sender_name' => match ($message->sender_type) {
                'bot' => 'Assistant',
                'agent' => $message->sender?->name ?? 'Support',
                default => null,
            },
            'body' => $message->body,
            'attachment' => $attachment?->toPayload(),
            // Token-free — the widget appends its own visitor_token, which is what
            // the download route actually authorizes on.
            'download_url' => $attachment
                ? route('chat.widget.attachments.download', [
                    request()->route('tenantSlug'),
                    $message->chat_conversation_id,
                    $attachment->id,
                ])
                : null,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function resolveVisitor(Tenant $tenant, ?string $token): ChatVisitor
    {
        if ($token) {
            $visitor = ChatVisitor::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('token', $token)
                ->first();

            if ($visitor) {
                return $visitor;
            }
        }

        return ChatVisitor::create(['tenant_id' => $tenant->id]);
    }

    /**
     * Explicitly load the conversation and validate the requesting visitor owns it,
     * bypassing implicit route-model binding (which would run through the tenant
     * global scope that isn't reliably bound yet on these unauthenticated routes).
     *
     * @return array{0: ChatConversation, 1: ChatVisitor}
     */
    protected function conversationAndVisitor(Tenant $tenant, int $conversationId, Request $request): array
    {
        $conversation = ChatConversation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($conversationId);

        $token = $request->input('visitor_token');
        $visitor = $conversation->visitor;

        abort_if(! $token || $visitor->token !== $token, 403, 'Invalid visitor session.');

        return [$conversation, $visitor];
    }
}

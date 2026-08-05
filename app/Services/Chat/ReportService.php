<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Chat metrics for a date range. Everything here runs through the tenant global
 * scope, so a report can only ever describe the caller's own workspace.
 *
 * Durations are computed in PHP rather than SQL: the timestamp arithmetic
 * needed differs across SQLite, MySQL and Postgres, and a workspace's traffic
 * in one reporting window is small enough to walk.
 */
class ReportService
{
    public function summary(Carbon $from, Carbon $to): array
    {
        $conversations = $this->conversationsIn($from, $to);
        $firstResponses = $this->firstResponseSeconds($conversations);
        $resolutions = $this->resolutionSeconds($conversations);

        $messages = ChatMessage::whereBetween('created_at', [$from, $to])->get();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'conversations' => $conversations->count(),
            'closed' => $conversations->where('status', 'closed')->count(),
            'open' => $conversations->where('status', 'open')->count(),
            'visitor_messages' => $messages->where('sender_type', 'visitor')->count(),
            'agent_messages' => $messages->where('sender_type', 'agent')->where('is_internal', false)->count(),
            'internal_notes' => $messages->where('is_internal', true)->count(),
            // Chats a visitor opened that nobody has replied to at all.
            'unanswered' => $conversations->filter(fn ($c) => $this->firstAgentReply($c) === null)->count(),
            'avg_first_response_seconds' => $this->average($firstResponses),
            'median_first_response_seconds' => $this->median($firstResponses),
            'avg_resolution_seconds' => $this->average($resolutions),
            'rated' => $conversations->filter(fn ($c) => $c->isRated())->count(),
            'avg_rating' => $this->averageRating($conversations),
            // Of the chats that were closed, how many came back with a score —
            // an average from a 5% response rate is worth reading differently
            // from one at 60%.
            'rating_response_rate' => $this->ratingResponseRate($conversations),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function perAgent(Carbon $from, Carbon $to): Collection
    {
        $conversations = $this->conversationsIn($from, $to);

        $replies = ChatMessage::whereBetween('created_at', [$from, $to])
            ->where('sender_type', 'agent')
            ->where('is_internal', false)
            ->get()
            ->groupBy('sender_id');

        return User::orderBy('name')->get()->map(function (User $agent) use ($conversations, $replies) {
            $assigned = $conversations->where('assigned_to', $agent->id);

            return [
                'agent' => $agent->name,
                'conversations' => $assigned->count(),
                'closed' => $assigned->where('status', 'closed')->count(),
                'replies' => ($replies[$agent->id] ?? collect())->count(),
                'avg_first_response_seconds' => $this->average($this->firstResponseSeconds($assigned)),
                'avg_rating' => $this->averageRating($assigned),
                'rated' => $assigned->filter(fn ($c) => $c->isRated())->count(),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{date: string, conversations: int, messages: int}>
     */
    public function daily(Carbon $from, Carbon $to): Collection
    {
        $conversations = $this->conversationsIn($from, $to)
            ->groupBy(fn (ChatConversation $c) => $c->created_at->toDateString());

        $messages = ChatMessage::whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(fn (ChatMessage $m) => $m->created_at->toDateString());

        $days = collect();

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $date = $day->toDateString();

            $days->push([
                'date' => $date,
                'conversations' => ($conversations[$date] ?? collect())->count(),
                'messages' => ($messages[$date] ?? collect())->count(),
            ]);
        }

        return $days;
    }

    protected function conversationsIn(Carbon $from, Carbon $to): Collection
    {
        return ChatConversation::with(['messages' => fn ($q) => $q->where('is_internal', false)])
            ->whereBetween('created_at', [$from, $to])
            ->get();
    }

    protected function firstAgentReply(ChatConversation $conversation): ?ChatMessage
    {
        return $conversation->messages
            ->where('sender_type', 'agent')
            ->sortBy('created_at')
            ->first();
    }

    /**
     * Measured from the conversation opening, not from the visitor's message —
     * that is the wait the visitor actually experienced.
     *
     * @return Collection<int, int>
     */
    protected function firstResponseSeconds(Collection $conversations): Collection
    {
        return $conversations
            ->map(function (ChatConversation $conversation) {
                $reply = $this->firstAgentReply($conversation);

                return $reply ? (int) round($conversation->created_at->diffInSeconds($reply->created_at)) : null;
            })
            ->filter(fn ($seconds) => $seconds !== null)
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    protected function resolutionSeconds(Collection $conversations): Collection
    {
        return $conversations
            ->filter(fn (ChatConversation $c) => $c->status === 'closed' && $c->closed_at)
            ->map(fn (ChatConversation $c) => (int) round($c->created_at->diffInSeconds($c->closed_at)))
            ->values();
    }

    /**
     * Null rather than zero when nothing was rated — "no data" and "everyone
     * scored it zero" would otherwise read the same, and zero is not even a
     * score this scale allows.
     */
    protected function averageRating(Collection $conversations): ?float
    {
        $ratings = $conversations->filter(fn (ChatConversation $c) => $c->isRated())->pluck('rating');

        return $ratings->isEmpty() ? null : round($ratings->avg(), 2);
    }

    protected function ratingResponseRate(Collection $conversations): ?float
    {
        $closed = $conversations->where('status', 'closed');

        if ($closed->isEmpty()) {
            return null;
        }

        return round($closed->filter(fn (ChatConversation $c) => $c->isRated())->count() / $closed->count() * 100, 1);
    }

    protected function average(Collection $values): ?int
    {
        return $values->isEmpty() ? null : (int) round($values->avg());
    }

    protected function median(Collection $values): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $middle = intdiv($sorted->count(), 2);

        return $sorted->count() % 2
            ? (int) $sorted[$middle]
            : (int) round(($sorted[$middle - 1] + $sorted[$middle]) / 2);
    }

    /**
     * Human-friendly duration for the report screen. Null means "no data in this
     * range", which is a different statement from zero seconds.
     */
    public static function humanDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        return intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m';
    }
}

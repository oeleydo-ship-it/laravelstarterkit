<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Chat\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('modules.chat.reports', [
            'from' => $from,
            'to' => $to,
            'summary' => $this->reports->summary($from, $to),
            'perAgent' => $this->reports->perAgent($from, $to),
            'daily' => $this->reports->daily($from, $to),
        ]);
    }

    /**
     * Streamed rather than built in memory — a busy workspace's full transcript
     * export is the one report that can outgrow the request's memory limit.
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $type = $request->query('type') === 'agents' ? 'agents' : 'conversations';
        $filename = "chat-{$type}-{$from->toDateString()}-to-{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($type, $from, $to) {
            $handle = fopen('php://output', 'w');

            if ($type === 'agents') {
                fputcsv($handle, ['Agent', 'Conversations', 'Closed', 'Replies', 'Avg first response', 'Rated', 'Avg rating']);

                foreach ($this->reports->perAgent($from, $to) as $row) {
                    fputcsv($handle, [
                        $row['agent'],
                        $row['conversations'],
                        $row['closed'],
                        $row['replies'],
                        ReportService::humanDuration($row['avg_first_response_seconds']),
                        $row['rated'],
                        $row['avg_rating'] ?? '',
                    ]);
                }
            } else {
                fputcsv($handle, [
                    'ID', 'Visitor', 'Email', 'Status', 'Assigned to',
                    'Messages', 'Started at', 'Closed at', 'Rating', 'Feedback', 'Last message',
                ]);

                ChatConversation::with(['visitor', 'assignee'])
                    ->withCount(['messages as message_count' => fn ($q) => $q->where('is_internal', false)])
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('id')
                    // Chunked so the export does not load a year of chats at once.
                    ->chunk(200, function ($conversations) use ($handle) {
                        foreach ($conversations as $conversation) {
                            fputcsv($handle, [
                                $conversation->id,
                                $conversation->visitor?->name ?? 'Visitor #'.$conversation->chat_visitor_id,
                                $conversation->visitor?->email,
                                $conversation->status,
                                $conversation->assignee?->name ?? 'Unassigned',
                                $conversation->message_count,
                                $conversation->created_at?->toDateTimeString(),
                                $conversation->closed_at?->toDateTimeString(),
                                $conversation->rating,
                                $conversation->rating_comment,
                                $conversation->last_message_preview,
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->subDays(29))->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();

        // A reversed range would silently report zero of everything.
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}

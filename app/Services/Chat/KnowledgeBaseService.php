<?php

namespace App\Services\Chat;

use App\Models\ChatArticle;
use App\Models\ChatDocument;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class KnowledgeBaseService
{
    public const AUTO_REPLY_SETTING = 'chat_kb_auto_reply';

    public function autoReplyEnabled(Tenant $tenant): bool
    {
        return filter_var(
            Setting::get(self::AUTO_REPLY_SETTING, $tenant->id, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function setAutoReply(Tenant $tenant, bool $enabled): void
    {
        Setting::set(self::AUTO_REPLY_SETTING, $enabled ? '1' : '0', $tenant->id);
    }

    /**
     * @return Collection<int, array{type: string, id: int, title: string, body: string}>
     */
    public function search(?string $term, int $limit = 10): Collection
    {
        $articles = ChatArticle::published()
            ->search($term)
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (ChatArticle $article) => [
                'type' => 'article',
                'id' => $article->id,
                'title' => $article->title,
                'body' => $article->body,
            ]);

        $documents = ChatDocument::active()
            ->whereNotNull('extracted_text')
            ->where('extracted_text', '!=', '')
            ->search($term)
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (ChatDocument $document) => [
                'type' => 'document',
                'id' => $document->id,
                'title' => $document->title.' (document)',
                'body' => $document->excerpt(1200),
            ]);

        return $articles->concat($documents)->take($limit)->values();
    }

    /**
     * Ranked snippets used to ground AI drafts and auto-replies.
     *
     * @return Collection<int, object{title: string, body: string}>
     */
    public function relevantSnippets(?string $question, int $limit = 4): Collection
    {
        $queryTerms = collect();
        if (filled($question)) {
            $queryTerms = collect(str($question)->lower()->split('/[^a-z0-9]+/'))
                ->filter(fn ($word) => strlen($word) > 3)
                ->unique()
                ->take(6)
                ->values();
        }

        $articles = ChatArticle::published();
        $documents = ChatDocument::active()
            ->whereNotNull('extracted_text')
            ->where('extracted_text', '!=', '');

        if ($queryTerms->isNotEmpty()) {
            $articles->where(function ($outer) use ($queryTerms) {
                foreach ($queryTerms as $term) {
                    $outer->orWhere(fn ($inner) => $inner->search($term));
                }
            });
            $documents->where(function ($outer) use ($queryTerms) {
                foreach ($queryTerms as $term) {
                    $outer->orWhere(fn ($inner) => $inner->search($term));
                }
            });
        }

        $fromArticles = $articles->limit($limit)->get()
            ->map(fn (ChatArticle $a) => (object) ['title' => $a->title, 'body' => $a->body])
            ->filter(fn ($snippet) => $this->isReadableText($snippet->body));

        $fromDocuments = $documents->limit($limit * 2)->get()
            ->map(fn (ChatDocument $d) => (object) [
                'title' => $d->title,
                'body' => $d->excerpt(2000),
            ])
            ->filter(fn ($snippet) => $this->isReadableText($snippet->body));

        return $fromArticles->concat($fromDocuments)->take($limit)->values();
    }

    /**
     * Reject binary / mojibake PDF scrapes so they never reach visitors.
     */
    public function isReadableText(?string $text): bool
    {
        if (blank($text) || mb_strlen($text) < 20) {
            return false;
        }

        $length = max(mb_strlen($text), 1);
        $printable = preg_match_all('/[\p{L}\p{N}\s\.\,\;\:\!\?\-\'\"\(\)\/\@\%\$]/u', $text) ?: 0;

        return ($printable / $length) >= 0.65;
    }

    public function sanitizeExcerpt(string $text, int $limit = 500): string
    {
        if (! $this->isReadableText($text)) {
            return '';
        }

        $clean = preg_replace('/[^\p{L}\p{N}\s\.\,\;\:\!\?\-\'\"\(\)\/\@\%\$\n]/u', ' ', $text) ?? $text;
        $clean = preg_replace("/[ \t]+/", ' ', $clean) ?? $clean;
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean;

        return (string) str(trim($clean))->limit($limit);
    }
}

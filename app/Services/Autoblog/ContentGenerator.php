<?php
namespace App\Services\Autoblog;
use App\Services\Chat\Ai\AiProvider;
use RuntimeException;
class ContentGenerator {
    public function __construct(private AiProvider $ai) {}
    public function generate(string $topic, string $tone, string $keywords): array {
        if (!$this->ai->isConfigured()) throw new RuntimeException('Configure an OpenAI or Kimi K3 API key in AI Settings first.');
        $raw=$this->ai->complete('You are an expert SEO editor. Return only valid JSON with keys title, slug, excerpt, content. Content must be useful HTML using h2, h3, p, ul and strong tags; never include scripts.', [[
            'role'=>'user','content'=>"Write a complete original blog post about: {$topic}. Tone: {$tone}. SEO keywords: {$keywords}. Target 900-1400 words."
        ]]);
        $raw=preg_replace('/^```(?:json)?\s*|\s*```$/i','',trim($raw)); $data=json_decode($raw,true);
        if (!is_array($data) || empty($data['title']) || empty($data['content'])) throw new RuntimeException('The AI response was not valid article JSON. Please try again.');
        return ['title'=>strip_tags($data['title']),'slug'=>\Illuminate\Support\Str::slug($data['slug'] ?? $data['title']),'excerpt'=>strip_tags($data['excerpt'] ?? ''),'content'=>strip_tags($data['content'],'<h2><h3><h4><p><ul><ol><li><strong><em><a><blockquote>')];
    }
}

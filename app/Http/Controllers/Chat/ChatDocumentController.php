<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatDocument;
use App\Services\Chat\AiSettingsService;
use App\Services\Chat\DocumentTextExtractor;
use App\Services\Chat\KnowledgeBaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatDocumentController extends Controller
{
    public function __construct(
        protected DocumentTextExtractor $extractor,
        protected KnowledgeBaseService $knowledgeBase,
        protected AiSettingsService $aiSettings,
    ) {
    }

    public function store(Request $request)
    {
        $maxKb = (int) config('chat.knowledge_base.max_kb', 10240);
        $extensions = implode(',', config('chat.knowledge_base.extensions', ['pdf', 'txt', 'docx', 'md', 'csv']));

        $validated = $request->validate([
            'title' => 'nullable|string|max:160',
            'document' => "required|file|max:{$maxKb}|mimes:{$extensions}",
            'auto_reply' => 'nullable|boolean',
        ]);

        $file = $request->file('document');
        $disk = config('chat.knowledge_base.disk', config('chat.attachments.disk', 'local'));
        $tenant = currentTenant();

        $path = $file->store("chat/{$tenant->id}/knowledge", $disk);
        $extracted = $this->extractor->extract($file);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'extracted_text' => $extracted !== '' ? $extracted : null,
            'is_active' => true,
        ]);

        if ($request->has('auto_reply')) {
            $this->knowledgeBase->setAutoReply($tenant, $request->boolean('auto_reply'));
        }

        return redirect()
            ->route('chat.settings.index', ['tab' => 'knowledge'])
            ->with('success', 'Document uploaded to the knowledge base.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_reply' => 'nullable|boolean',
            'provider' => ['required', Rule::in(array_keys(AiSettingsService::providers()))],
            'openai_key' => 'nullable|string|max:255',
            'openai_model' => ['nullable', Rule::in(array_keys(AiSettingsService::openaiModels()))],
            'openai_base_url' => 'nullable|url|max:255',
            'kimi_key' => 'nullable|string|max:255',
            'kimi_model' => ['nullable', Rule::in(array_keys(AiSettingsService::kimiModels()))],
            'kimi_base_url' => 'nullable|url|max:255',
            'anthropic_key' => 'nullable|string|max:255',
            'anthropic_model' => 'nullable|string|max:120',
            'anthropic_base_url' => 'nullable|url|max:255',
        ]);

        $tenant = currentTenant();

        $this->knowledgeBase->setAutoReply($tenant, $request->boolean('auto_reply'));
        $this->aiSettings->save($tenant, $validated);

        return redirect()
            ->route('chat.settings.index', ['tab' => 'knowledge'])
            ->with('success', 'Knowledge base & AI settings saved.');
    }

    public function toggle(ChatDocument $document)
    {
        abort_if($document->tenant_id !== currentTenant()->id, 404);

        $document->update(['is_active' => ! $document->is_active]);

        return redirect()
            ->route('chat.settings.index', ['tab' => 'knowledge'])
            ->with('success', $document->is_active ? 'Document activated.' : 'Document deactivated.');
    }

    public function destroy(ChatDocument $document)
    {
        abort_if($document->tenant_id !== currentTenant()->id, 404);

        $document->delete();

        return redirect()
            ->route('chat.settings.index', ['tab' => 'knowledge'])
            ->with('success', 'Document removed from the knowledge base.');
    }

    public function download(ChatDocument $document)
    {
        abort_if($document->tenant_id !== currentTenant()->id, 404);

        return \Illuminate\Support\Facades\Storage::disk($document->disk)
            ->download($document->path, $document->original_name);
    }
}

<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatArticleRequest;
use App\Models\ChatArticle;
use App\Services\Chat\KnowledgeBaseService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(protected KnowledgeBaseService $knowledgeBase)
    {
        $this->authorizeResource(ChatArticle::class, 'article');
    }

    public function index(Request $request)
    {
        $articles = ChatArticle::search($request->query('q'))
            ->orderBy('title')
            ->paginate(20);

        return view('modules.chat.articles.index', [
            'articles' => $articles,
            'search' => $request->query('q'),
        ]);
    }

    /**
     * Lookup for the conversation composer. Searches published articles and
     * uploaded documents — drafts and inactive files stay out of the paste list.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', ChatArticle::class);

        return response()->json(
            $this->knowledgeBase->search($request->query('q'), 10)->all()
        );
    }

    public function create()
    {
        return view('modules.chat.articles.form', ['article' => new ChatArticle]);
    }

    public function store(ChatArticleRequest $request)
    {
        ChatArticle::create($request->validated());

        return redirect()->route('chat.articles.index')->with('success', 'Article created.');
    }

    public function edit(ChatArticle $article)
    {
        return view('modules.chat.articles.form', compact('article'));
    }

    public function update(ChatArticleRequest $request, ChatArticle $article)
    {
        $article->update($request->validated());

        return redirect()->route('chat.articles.index')->with('success', 'Article updated.');
    }

    public function destroy(ChatArticle $article)
    {
        $article->delete();

        return redirect()->route('chat.articles.index')->with('success', 'Article deleted.');
    }
}

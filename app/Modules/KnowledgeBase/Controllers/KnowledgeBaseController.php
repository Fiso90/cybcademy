<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeBase\Controllers;

use App\Modules\KnowledgeBase\Models\KnowledgeBaseArticle;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KnowledgeBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBaseArticle::query();

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $articles = $query->orderBy('title')->paginate(25);

        return response()->json(['data' => $articles->items(), 'meta' => [
            'page' => $articles->currentPage(), 'per_page' => $articles->perPage(), 'total' => $articles->total(),
        ], 'errors' => []]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('knowledge_base.author'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $article = KnowledgeBaseArticle::create([
            'tenant_id' => TenantContext::current(), // always tenant-scoped when authored via the app; platform articles are seeded separately, same pattern as phishing templates
            'title' => $validated['title'],
            'body' => $validated['body'],
            'category' => $validated['category'] ?? null,
            'published' => false, // draft until explicitly published
        ]);

        return response()->json(['data' => $article, 'meta' => [], 'errors' => []], 201);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('knowledge_base.author'), 403);

        $article = KnowledgeBaseArticle::withoutGlobalScope('tenant_or_platform')
            ->where('tenant_id', TenantContext::current()) // explicit re-scoping since the global scope was removed above
            ->findOrFail($id);

        $article->update(['published' => true]);

        return response()->json(['data' => $article, 'meta' => [], 'errors' => []]);
    }
}

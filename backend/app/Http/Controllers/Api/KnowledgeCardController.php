<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeCardRequest;
use App\Models\KnowledgeCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KnowledgeCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        KnowledgeCard::checkExpiry()->get()->each(function ($card) {
            $card->updateStatusByExpiry();
        });

        $query = KnowledgeCard::with(['moderator', 'topic'])
            ->orderBy('created_at', 'desc');

        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $cards = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $cards->items(),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'last_page' => $cards->lastPage(),
            ],
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        KnowledgeCard::checkExpiry()->get()->each(function ($card) {
            $card->updateStatusByExpiry();
        });

        $query = KnowledgeCard::active()
            ->with(['moderator', 'topic'])
            ->orderBy('created_at', 'desc');

        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $cards = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $cards->items(),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'last_page' => $cards->lastPage(),
            ],
        ]);
    }

    public function show(KnowledgeCard $knowledgeCard): JsonResponse
    {
        $knowledgeCard->incrementViewCount();
        $knowledgeCard->load(['moderator', 'topic']);

        return response()->json([
            'data' => $knowledgeCard,
        ]);
    }

    public function store(KnowledgeCardRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => '无权限操作，仅版主可创建知识卡片',
            ], 403);
        }

        $existingCard = KnowledgeCard::where('topic_id', $request->topic_id)->first();
        if ($existingCard) {
            return response()->json([
                'message' => '该帖子已存在知识卡片',
            ], 400);
        }

        $card = KnowledgeCard::create([
            'topic_id' => $request->topic_id,
            'moderator_id' => $user->id,
            'title' => $request->title,
            'summary' => $request->summary,
            'category' => $request->category,
            'tags' => $request->tags,
            'expire_date' => $request->expire_date,
            'last_reviewed_at' => Carbon::today(),
            'status' => KnowledgeCard::STATUS_ACTIVE,
        ]);

        $card->load(['moderator', 'topic']);

        return response()->json([
            'data' => $card,
            'message' => '知识卡片创建成功',
        ], 201);
    }

    public function update(KnowledgeCardRequest $request, KnowledgeCard $knowledgeCard): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => '无权限操作，仅版主可编辑知识卡片',
            ], 403);
        }

        if ($request->topic_id !== $knowledgeCard->topic_id) {
            $existingCard = KnowledgeCard::where('topic_id', $request->topic_id)
                ->where('id', '!=', $knowledgeCard->id)
                ->first();
            if ($existingCard) {
                return response()->json([
                    'message' => '该帖子已存在知识卡片',
                ], 400);
            }
        }

        $knowledgeCard->update([
            'topic_id' => $request->topic_id,
            'title' => $request->title,
            'summary' => $request->summary,
            'category' => $request->category,
            'tags' => $request->tags,
            'expire_date' => $request->expire_date,
            'status' => $request->status ?? $knowledgeCard->status,
        ]);

        $knowledgeCard->updateStatusByExpiry();
        $knowledgeCard->load(['moderator', 'topic']);

        return response()->json([
            'data' => $knowledgeCard,
            'message' => '知识卡片更新成功',
        ]);
    }

    public function destroy(Request $request, KnowledgeCard $knowledgeCard): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => '无权限操作，仅版主可删除知识卡片',
            ], 403);
        }

        $knowledgeCard->delete();

        return response()->json([
            'message' => '知识卡片删除成功',
        ]);
    }

    public function markReviewed(Request $request, KnowledgeCard $knowledgeCard): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => '无权限操作，仅版主可复核知识卡片',
            ], 403);
        }

        $knowledgeCard->markAsReviewed();
        $knowledgeCard->load(['moderator', 'topic']);

        return response()->json([
            'data' => $knowledgeCard,
            'message' => '知识卡片复核完成，状态已更新为正常',
        ]);
    }

    public function searchWithPriority(Request $request): JsonResponse
    {
        if (!$request->has('search')) {
            return response()->json([
                'message' => '请输入搜索关键词',
            ], 400);
        }

        $search = $request->search;
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        KnowledgeCard::checkExpiry()->get()->each(function ($card) {
            $card->updateStatusByExpiry();
        });

        $cardsQuery = KnowledgeCard::active()
            ->with(['moderator', 'topic'])
            ->search($search)
            ->orderBy('created_at', 'desc');

        $cardsCount = $cardsQuery->count();
        $cards = $cardsQuery->limit($perPage)->get();

        $remaining = $perPage - $cards->count();
        $topics = collect();

        if ($remaining > 0) {
            $topicsQuery = \App\Models\Topic::with('user')
                ->where('status', 1)
                ->whereDoesntHave('knowledgeCard')
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc');

            $topics = $topicsQuery->limit($remaining)->get();
        }

        $combined = $cards->map(function ($card) {
            return [
                'type' => 'knowledge_card',
                'data' => $card,
            ];
        })->merge($topics->map(function ($topic) {
            return [
                'type' => 'topic',
                'data' => $topic,
            ];
        }));

        $totalCards = KnowledgeCard::active()->search($search)->count();
        $totalTopics = \App\Models\Topic::where('status', 1)
            ->whereDoesntHave('knowledgeCard')
            ->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            })
            ->count();

        return response()->json([
            'data' => $combined->values(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCards + $totalTopics,
                'knowledge_cards_count' => $totalCards,
                'topics_count' => $totalTopics,
                'last_page' => (int) ceil(($totalCards + $totalTopics) / $perPage),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => KnowledgeCard::categoryLabels(),
        ]);
    }

    public function needsReviewList(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        KnowledgeCard::checkExpiry()->get()->each(function ($card) {
            $card->updateStatusByExpiry();
        });

        $query = KnowledgeCard::whereIn('status', [
            KnowledgeCard::STATUS_NEEDS_REVIEW,
            KnowledgeCard::STATUS_EXPIRED,
        ])->with(['moderator', 'topic'])
          ->orderBy('expire_date', 'asc');

        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        $cards = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $cards->items(),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'last_page' => $cards->lastPage(),
            ],
        ]);
    }
}

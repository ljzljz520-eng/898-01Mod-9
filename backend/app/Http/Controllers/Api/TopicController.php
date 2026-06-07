<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicRequest;
use App\Models\KnowledgeCard;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        if ($request->has('search')) {
            return $this->searchWithPriority($request, $perPage, $page, $user);
        }

        $query = Topic::with('user')
            ->where('status', 1)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        $circleType = $request->input('circle_type');
        if ($circleType && $circleType !== 'all') {
            if (!$user?->canAccessCircle($circleType, $user?->building_id)) {
                return response()->json([
                    'message' => '无权访问该圈层',
                    'circle_type' => $circleType,
                ], 403);
            }
            $query->byCircle($circleType, $user?->building_id);
        } else {
            $query->byAccessibleCircles($user);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('building_id') && $request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        $topics = $query->paginate($perPage);

        $filteredItems = collect($topics->items())->map(function ($topic) use ($user) {
            return $topic->toArrayForUser($user);
        });

        return response()->json([
            'data' => $filteredItems,
            'meta' => [
                'current_page' => $topics->currentPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
                'last_page' => $topics->lastPage(),
            ],
            'user_circles' => $user ? $user->getAccessibleCircleTypes() : ['public'],
        ]);
    }

    protected function searchWithPriority(Request $request, int $perPage, int $page, $user): JsonResponse
    {
        $search = $request->search;

        KnowledgeCard::checkExpiry()->get()->each(function ($card) {
            $card->updateStatusByExpiry();
        });

        $cardsQuery = KnowledgeCard::active()
            ->with(['moderator', 'topic'])
            ->search($search);

        if ($request->has('category') && $request->category !== 'all') {
            $cardsQuery->byCategory($request->category);
        }

        $circleType = $request->input('circle_type');
        if ($circleType && $circleType !== 'all' && $circleType !== 'public') {
            $cardsQuery->whereHas('topic', function ($q) use ($circleType, $user) {
                $q->byCircle($circleType, $user?->building_id);
            });
        }

        $cards = $cardsQuery->orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get();

        $remaining = $perPage - $cards->count();
        $topics = collect();

        if ($remaining > 0) {
            $topicsQuery = Topic::with('user')
                ->where('status', 1)
                ->whereDoesntHave('knowledgeCard')
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });

            if ($request->has('category') && $request->category !== 'all') {
                $topicsQuery->where('category', $request->category);
            }

            if ($circleType && $circleType !== 'all') {
                if (!$user?->canAccessCircle($circleType, $user?->building_id)) {
                    $topicsQuery->where('circle_type', 'public');
                } else {
                    $topicsQuery->byCircle($circleType, $user?->building_id);
                }
            } else {
                $topicsQuery->byAccessibleCircles($user);
            }

            $topics = $topicsQuery->orderBy('created_at', 'desc')
                ->limit($remaining)
                ->get();
        }

        $filteredCards = $cards->map(function ($card) use ($user) {
            $topicData = $card->topic ? $card->topic->toArrayForUser($user) : null;
            return [
                'type' => 'knowledge_card',
                'data' => array_merge($card->toArray(), ['topic' => $topicData]),
            ];
        });

        $filteredTopics = $topics->map(function ($topic) use ($user) {
            return [
                'type' => 'topic',
                'data' => $topic->toArrayForUser($user),
            ];
        });

        $combined = $filteredCards->merge($filteredTopics);

        $cardsTotal = KnowledgeCard::active()->search($search)->count();
        $topicsQuery = Topic::where('status', 1)
            ->whereDoesntHave('knowledgeCard')
            ->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });

        if ($circleType && $circleType !== 'all') {
            if (!$user?->canAccessCircle($circleType, $user?->building_id)) {
                $topicsQuery->where('circle_type', 'public');
            } else {
                $topicsQuery->byCircle($circleType, $user?->building_id);
            }
        } else {
            $topicsQuery->byAccessibleCircles($user);
        }

        $topicsTotal = $topicsQuery->count();

        return response()->json([
            'data' => $combined->values(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $cardsTotal + $topicsTotal,
                'knowledge_cards_count' => $cardsTotal,
                'topics_count' => $topicsTotal,
                'last_page' => (int) ceil(($cardsTotal + $topicsTotal) / $perPage),
            ],
            'user_circles' => $user ? $user->getAccessibleCircleTypes() : ['public'],
        ]);
    }

    public function show(Topic $topic, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user?->canAccessCircle($topic->circle_type, $topic->building_id)) {
            if ($topic->circle_type !== 'public') {
                return response()->json([
                    'message' => '无权访问该圈层话题',
                    'circle_type' => $topic->circle_type,
                ], 403);
            }
        }

        $topic->increment('view_count');
        $topic->load(['user', 'replies.user']);

        $data = $topic->toArrayForUser($user);

        $data['replies'] = $topic->replies->map(function ($reply) {
            return [
                'id' => $reply->id,
                'content' => $reply->content,
                'created_at' => $reply->created_at,
                'user' => [
                    'id' => $reply->user->id,
                    'username' => $reply->user->username,
                    'avatar' => $reply->user->avatar,
                    'resident_type' => $reply->user->resident_type ?? null,
                ],
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(TopicRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => '未认证',
            ], 401);
        }

        $circleType = $request->input('circle_type', 'public');

        if ($circleType !== 'public') {
            if ($user->isMoved()) {
                return response()->json([
                    'message' => '您已搬离小区，无法发布内部话题',
                ], 403);
            }

            if (!$user->isVerified()) {
                return response()->json([
                    'message' => '请先完成业主认证',
                ], 403);
            }

            if (!$user->canAccessCircle($circleType, $user->building_id)) {
                return response()->json([
                    'message' => '无权在该圈层发布话题',
                    'circle_type' => $circleType,
                ], 403);
            }
        }

        $topic = Topic::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? 'general',
            'circle_type' => $circleType,
            'building_id' => $circleType !== 'public' ? $user->building_id : null,
            'extra_fields' => $request->input('extra_fields'),
        ]);

        $topic->load('user');

        return response()->json([
            'data' => $topic->toArrayForUser($user),
            'message' => '发布成功',
        ], 201);
    }

    public function update(TopicRequest $request, Topic $topic): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => '未认证',
            ], 401);
        }

        if ($topic->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $newCircleType = $request->input('circle_type', $topic->circle_type);
        if ($newCircleType !== 'public') {
            if (!$user->canAccessCircle($newCircleType, $user->building_id)) {
                return response()->json([
                    'message' => '无权修改该圈层话题',
                ], 403);
            }
        }

        $topic->update([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? $topic->category,
            'circle_type' => $newCircleType,
            'building_id' => $newCircleType !== 'public' ? $user->building_id : null,
            'extra_fields' => $request->input('extra_fields') ?? $topic->extra_fields,
        ]);

        $topic->load('user');

        return response()->json([
            'data' => $topic->toArrayForUser($user),
            'message' => '更新成功',
        ]);
    }

    public function destroy(Request $request, Topic $topic): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => '未认证',
            ], 401);
        }

        if ($topic->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $topic->delete();

        return response()->json([
            'message' => '删除成功',
        ]);
    }
}

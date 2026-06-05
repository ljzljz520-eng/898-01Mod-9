<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicRequest;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Topic::with('user')
            ->where('status', 1)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $topics = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $topics->items(),
            'meta' => [
                'current_page' => $topics->currentPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
                'last_page' => $topics->lastPage(),
            ],
        ]);
    }

    public function show(Topic $topic): JsonResponse
    {
        $topic->increment('view_count');
        $topic->load(['user', 'replies.user']);

        return response()->json([
            'data' => $topic,
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
        
        $topic = Topic::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? 'general',
        ]);

        $topic->load('user');

        return response()->json([
            'data' => $topic,
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

        $topic->update([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? $topic->category,
        ]);

        $topic->load('user');

        return response()->json([
            'data' => $topic,
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

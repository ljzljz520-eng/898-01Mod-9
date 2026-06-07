<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyRequest;
use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function index(Request $request, Topic $topic): JsonResponse
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

        $replies = Reply::with('user')
            ->where('topic_id', $topic->id)
            ->where('status', 1)
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 20));

        $filteredItems = collect($replies->items())->map(function ($reply) {
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
            'data' => $filteredItems,
            'meta' => [
                'current_page' => $replies->currentPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
                'last_page' => $replies->lastPage(),
            ],
        ]);
    }

    public function store(ReplyRequest $request, Topic $topic): JsonResponse
    {
        $user = $request->user();

        if (!$user?->canAccessCircle($topic->circle_type, $topic->building_id)) {
            if ($topic->circle_type !== 'public') {
                return response()->json([
                    'message' => '无权回复该圈层话题',
                    'circle_type' => $topic->circle_type,
                ], 403);
            }
        }

        if ($topic->circle_type !== 'public') {
            if ($user->isMoved()) {
                return response()->json([
                    'message' => '您已搬离小区，无法回复内部话题',
                ], 403);
            }
        }

        $reply = Reply::create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        $topic->increment('reply_count');
        $reply->load('user');

        $replyData = [
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

        return response()->json([
            'data' => $replyData,
            'message' => '回复成功',
        ], 201);
    }

    public function update(ReplyRequest $request, Reply $reply): JsonResponse
    {
        $user = $request->user();

        if ($reply->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $topic = $reply->topic;
        if (!$user?->canAccessCircle($topic->circle_type, $topic->building_id)) {
            if ($topic->circle_type !== 'public') {
                return response()->json([
                    'message' => '无权操作该圈层话题',
                ], 403);
            }
        }

        $reply->update([
            'content' => $request->content,
        ]);

        $reply->load('user');

        $replyData = [
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

        return response()->json([
            'data' => $replyData,
            'message' => '更新成功',
        ]);
    }

    public function destroy(Request $request, Reply $reply): JsonResponse
    {
        $user = $request->user();

        if ($reply->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $topic = $reply->topic;
        if (!$user?->canAccessCircle($topic->circle_type, $topic->building_id)) {
            if ($topic->circle_type !== 'public') {
                return response()->json([
                    'message' => '无权操作该圈层话题',
                ], 403);
            }
        }

        $reply->topic->decrement('reply_count');
        $reply->delete();

        return response()->json([
            'message' => '删除成功',
        ]);
    }
}

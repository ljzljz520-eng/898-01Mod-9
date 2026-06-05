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
        $replies = Reply::with('user')
            ->where('topic_id', $topic->id)
            ->where('status', 1)
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $replies->items(),
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
        $reply = Reply::create([
            'topic_id' => $topic->id,
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        $topic->increment('reply_count');
        $reply->load('user');

        return response()->json([
            'data' => $reply,
            'message' => '回复成功',
        ], 201);
    }

    public function update(ReplyRequest $request, Reply $reply): JsonResponse
    {
        if ($reply->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $reply->update([
            'content' => $request->content,
        ]);

        $reply->load('user');

        return response()->json([
            'data' => $reply,
            'message' => '更新成功',
        ]);
    }

    public function destroy(Request $request, Reply $reply): JsonResponse
    {
        if ($reply->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $reply->topic->decrement('reply_count');
        $reply->delete();

        return response()->json([
            'message' => '删除成功',
        ]);
    }
}

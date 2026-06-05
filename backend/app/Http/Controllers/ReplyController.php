<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReplyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Topic $topic)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:5|max:5000',
        ], [
            'content.required' => '回复内容不能为空',
            'content.min' => '回复内容至少5个字符',
            'content.max' => '回复内容最多5000个字符',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reply = Reply::create([
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        $topic->increment('reply_count');

        return back()->with('success', '回复成功');
    }

    public function destroy(Reply $reply)
    {
        if ($reply->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        $topic = $reply->topic;
        $reply->delete();
        $topic->decrement('reply_count');

        return back()->with('success', '删除成功');
    }
}

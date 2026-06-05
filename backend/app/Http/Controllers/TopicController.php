<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(Request $request)
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

        $topics = $query->paginate(20)->appends(request()->query());

        return view('topics.index', compact('topics'));
    }

    public function show(Topic $topic)
    {
        $topic->increment('view_count');
        $topic->load(['user', 'replies' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'replies.user']);

        return view('topics.show', compact('topic'));
    }

    public function create()
    {
        return view('topics.create');
    }

    public function store(TopicRequest $request)
    {
        $topic = Topic::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? 'general',
        ]);

        return redirect()->route('topics.show', $topic)->with('success', '发布成功');
    }

    public function edit(Topic $topic)
    {
        if ($topic->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        return view('topics.edit', compact('topic'));
    }

    public function update(TopicRequest $request, Topic $topic)
    {
        if ($topic->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        $topic->update([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? $topic->category,
        ]);

        return redirect()->route('topics.show', $topic)->with('success', '更新成功');
    }

    public function destroy(Topic $topic)
    {
        if ($topic->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        $topic->delete();

        return redirect()->route('topics.index')->with('success', '删除成功');
    }
}

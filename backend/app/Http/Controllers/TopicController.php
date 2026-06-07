<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Topic::with(['user', 'building'])
            ->where('status', 1)
            ->byAccessibleCircles($user)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('circle_type') && $request->circle_type !== 'all') {
            $circleType = $request->circle_type;
            if ($user && !$user->canAccessCircle($circleType)) {
                abort(403, '无权访问该圈层');
            }
            if ($circleType === 'public') {
                $query->where('circle_type', 'public');
            } else {
                $query->where(function ($q) use ($circleType, $user) {
                    $q->where('circle_type', $circleType)
                        ->where('building_id', $user?->building_id);
                });
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $topics = $query->paginate(20)->appends(request()->query());

        $processedTopics = $topics->getCollection()->map(function ($topic) use ($user) {
            return $topic->toArrayForUser($user);
        });
        $topics->setCollection($processedTopics);

        $userCircles = $user ? $user->getAccessibleCircleTypes() : ['public'];
        $buildings = $user ? \App\Models\Building::all() : collect();

        return view('topics.index', compact('topics', 'userCircles', 'buildings', 'user'));
    }

    public function show(Topic $topic)
    {
        $user = auth()->user();

        if ($topic->circle_type !== 'public') {
            if (!$user || !$user->canAccessCircle($topic->circle_type, $topic->building_id)) {
                abort(403, '无权访问该圈层话题');
            }
        }

        $topic->increment('view_count');
        $topic->load(['user', 'replies' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'replies.user']);

        $topicData = $topic->toArrayForUser($user);
        $extraFields = $topicData['extra_fields'] ?? [];

        return view('topics.show', compact('topic', 'topicData', 'extraFields', 'user'));
    }

    public function create()
    {
        $user = auth()->user();
        $userCircles = $user ? $user->getAccessibleCircleTypes() : ['public'];
        $buildings = \App\Models\Building::all();

        return view('topics.create', compact('userCircles', 'buildings', 'user'));
    }

    public function store(TopicRequest $request)
    {
        $user = auth()->user();
        $circleType = $request->circle_type ?? 'public';

        if ($circleType !== 'public') {
            if (!$user) {
                return redirect()->route('login')->with('error', '请先登录');
            }
            if ($user->isMoved()) {
                return back()->with('error', '您已搬离小区，无法发布内部话题')->withInput();
            }
            if (!$user->isVerified()) {
                return back()->with('error', '请先完成业主认证')->withInput();
            }
            if (!$user->canAccessCircle($circleType, $user->building_id)) {
                return back()->with('error', '无权在该圈层发布话题')->withInput();
            }
        }

        $topic = Topic::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? 'general',
            'circle_type' => $circleType,
            'building_id' => $circleType !== 'public' ? $user?->building_id : null,
            'extra_fields' => $request->extra_fields ?? null,
        ]);

        return redirect()->route('topics.show', $topic)->with('success', '发布成功');
    }

    public function edit(Topic $topic)
    {
        if ($topic->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        $user = auth()->user();
        $userCircles = $user ? $user->getAccessibleCircleTypes() : ['public'];
        $buildings = \App\Models\Building::all();

        return view('topics.edit', compact('topic', 'userCircles', 'buildings', 'user'));
    }

    public function update(TopicRequest $request, Topic $topic)
    {
        if ($topic->user_id !== auth()->id()) {
            abort(403, '无权限操作');
        }

        $user = auth()->user();
        $circleType = $request->circle_type ?? $topic->circle_type;

        if ($circleType !== 'public') {
            if ($user->isMoved()) {
                return back()->with('error', '您已搬离小区，无法编辑内部话题')->withInput();
            }
            if (!$user->canAccessCircle($circleType, $user->building_id)) {
                return back()->with('error', '无权在该圈层发布话题')->withInput();
            }
        }

        $topic->update([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category ?? $topic->category,
            'circle_type' => $circleType,
            'building_id' => $circleType !== 'public' ? $user?->building_id : null,
            'extra_fields' => $request->extra_fields ?? $topic->extra_fields,
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

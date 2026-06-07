<?php

namespace App\Http\Controllers;

use App\Http\Requests\KnowledgeCardRequest;
use App\Models\KnowledgeCard;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KnowledgeCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    protected function checkModerator()
    {
        if (!auth()->user() || !auth()->user()->isModerator()) {
            abort(403, '无权限操作，仅版主可管理知识卡片');
        }
    }

    public function index(Request $request)
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

        $cards = $query->paginate(20)->appends(request()->query());
        $categories = KnowledgeCard::categoryLabels();

        return view('knowledge-cards.index', compact('cards', 'categories'));
    }

    public function show(KnowledgeCard $knowledgeCard)
    {
        $knowledgeCard->incrementViewCount();
        $knowledgeCard->load(['moderator', 'topic', 'topic.user', 'topic.replies' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'topic.replies.user']);

        return view('knowledge-cards.show', compact('knowledgeCard'));
    }

    public function create(Request $request)
    {
        $this->checkModerator();

        $topic = null;
        if ($request->has('topic_id')) {
            $topic = Topic::findOrFail($request->topic_id);
        }

        $categories = KnowledgeCard::categoryLabels();
        $eligibleTopics = Topic::eligibleForKnowledgeCard()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('knowledge-cards.create', compact('categories', 'eligibleTopics', 'topic'));
    }

    public function store(KnowledgeCardRequest $request)
    {
        $this->checkModerator();

        $existingCard = KnowledgeCard::where('topic_id', $request->topic_id)->first();
        if ($existingCard) {
            return back()->withErrors(['topic_id' => '该帖子已存在知识卡片'])->withInput();
        }

        $card = KnowledgeCard::create([
            'topic_id' => $request->topic_id,
            'moderator_id' => auth()->id(),
            'title' => $request->title,
            'summary' => $request->summary,
            'category' => $request->category,
            'tags' => $request->tags,
            'expire_date' => $request->expire_date,
            'last_reviewed_at' => Carbon::today(),
            'status' => KnowledgeCard::STATUS_ACTIVE,
        ]);

        return redirect()->route('knowledge-cards.show', $card)->with('success', '知识卡片创建成功');
    }

    public function edit(KnowledgeCard $knowledgeCard)
    {
        $this->checkModerator();

        $categories = KnowledgeCard::categoryLabels();
        $statusLabels = KnowledgeCard::statusLabels();
        $eligibleTopics = Topic::where('id', $knowledgeCard->topic_id)
            ->orWhere(function ($query) {
                $query->eligibleForKnowledgeCard();
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('knowledge-cards.edit', compact('knowledgeCard', 'categories', 'statusLabels', 'eligibleTopics'));
    }

    public function update(KnowledgeCardRequest $request, KnowledgeCard $knowledgeCard)
    {
        $this->checkModerator();

        if ($request->topic_id !== $knowledgeCard->topic_id) {
            $existingCard = KnowledgeCard::where('topic_id', $request->topic_id)
                ->where('id', '!=', $knowledgeCard->id)
                ->first();
            if ($existingCard) {
                return back()->withErrors(['topic_id' => '该帖子已存在知识卡片'])->withInput();
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

        return redirect()->route('knowledge-cards.show', $knowledgeCard)->with('success', '知识卡片更新成功');
    }

    public function destroy(KnowledgeCard $knowledgeCard)
    {
        $this->checkModerator();

        $knowledgeCard->delete();

        return redirect()->route('knowledge-cards.index')->with('success', '知识卡片删除成功');
    }

    public function review(KnowledgeCard $knowledgeCard)
    {
        $this->checkModerator();

        $knowledgeCard->markAsReviewed();

        return back()->with('success', '知识卡片复核完成，状态已更新为正常');
    }

    public function reviewList(Request $request)
    {
        $this->checkModerator();

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

        $cards = $query->paginate(20)->appends(request()->query());
        $categories = KnowledgeCard::categoryLabels();

        return view('knowledge-cards.review-list', compact('cards', 'categories'));
    }
}

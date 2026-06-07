@extends('layouts.app')

@section('title', '知识卡片')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-neutral-800">知识卡片</h1>
    @auth
        @if(auth()->user()->isModerator())
            <a href="{{ route('knowledge-cards.create') }}" class="btn-primary text-sm">
                + 创建知识卡片
            </a>
        @endif
    @endauth
</div>

<div class="mb-4 flex flex-col sm:flex-row gap-3">
    <form method="GET" action="{{ route('knowledge-cards.index') }}" class="flex-1 flex gap-2 items-center">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="搜索知识卡片..." 
               class="flex-1 input-field">
        <select name="category" class="input-field w-auto text-sm">
            <option value="all" {{ request('category') == 'all' ? 'selected' : '' }}>全部分类</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary text-sm px-3">搜索</button>
    </form>
</div>

<div class="space-y-3">
    @forelse($cards as $card)
        <div class="card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge-primary">知识卡片</span>
                        <span class="badge text-[11px]">{{ $card->category_label }}</span>
                        @if($card->status == \App\Models\KnowledgeCard::STATUS_NEEDS_REVIEW)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                待复核
                            </span>
                        @elseif($card->status == \App\Models\KnowledgeCard::STATUS_EXPIRED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                已过期
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('knowledge-cards.show', $card) }}" class="block text-base md:text-lg font-semibold text-neutral-800 hover:text-primary-600 mb-1">
                        {{ $card->title }}
                    </a>
                    <p class="text-neutral-600 text-sm mb-2 line-clamp-2">{{ Str::limit($card->summary, 150) }}</p>
                    @if($card->tags_array)
                        <div class="flex flex-wrap gap-1 mb-2">
                            @foreach($card->tags_array as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                    #{{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                        <span>整理：{{ $card->moderator->username }}</span>
                        <span>更新时间：{{ $card->updated_at->format('Y-m-d') }}</span>
                        <span>浏览：{{ $card->view_count }}</span>
                        <span>来源：<a href="{{ route('topics.show', $card->topic) }}" class="text-primary-600 hover:text-primary-700">原帖</a></span>
                        @if($card->expire_date)
                            <span>有效期至：{{ $card->expire_date->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card text-center py-12">
            <p class="text-gray-500 text-lg">暂无知识卡片</p>
            @auth
                @if(auth()->user()->isModerator())
                    <p class="text-gray-400 text-sm mt-2">
                        <a href="{{ route('knowledge-cards.create') }}" class="text-primary-600 hover:text-primary-700">立即创建第一张知识卡片</a>
                    </p>
                @endif
            @endauth
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $cards->links('pagination.custom') }}
</div>
@endsection

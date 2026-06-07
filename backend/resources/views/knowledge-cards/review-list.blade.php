@extends('layouts.app')

@section('title', '待复核知识卡片')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">待复核知识卡片</h1>
    <p class="text-sm text-gray-500 mt-1">以下知识卡片已过期或即将过期，请及时复核更新</p>
</div>

<div class="mb-4 flex flex-col sm:flex-row gap-3">
    <form method="GET" action="{{ route('knowledge-cards.review-list') }}" class="flex-1 flex gap-2 items-center">
        <select name="category" class="input-field w-auto text-sm">
            <option value="all" {{ request('category') == 'all' ? 'selected' : '' }}>全部分类</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary text-sm px-3">筛选</button>
    </form>
</div>

<div class="space-y-3">
    @forelse($cards as $card)
        <div class="card border-l-4 
            @if($card->status == \App\Models\KnowledgeCard::STATUS_EXPIRED) border-l-red-500 
            @else border-l-yellow-500 @endif">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($card->status == \App\Models\KnowledgeCard::STATUS_EXPIRED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                已过期
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                待复核
                            </span>
                        @endif
                        <span class="badge text-[11px]">{{ $card->category_label }}</span>
                    </div>
                    <a href="{{ route('knowledge-cards.show', $card) }}" class="block text-base md:text-lg font-semibold text-neutral-800 hover:text-primary-600 mb-1">
                        {{ $card->title }}
                    </a>
                    <p class="text-neutral-600 text-sm mb-2 line-clamp-2">{{ Str::limit($card->summary, 150) }}</p>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                        <span>整理：{{ $card->moderator->username }}</span>
                        <span>创建时间：{{ $card->created_at->format('Y-m-d') }}</span>
                        <span>有效期至：{{ $card->expire_date ? $card->expire_date->format('Y-m-d') : '未设置' }}</span>
                        @if($card->last_reviewed_at)
                            <span>上次复核：{{ $card->last_reviewed_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                <div class="ml-4 flex flex-col gap-2">
                    <form method="POST" action="{{ route('knowledge-cards.review', $card) }}">
                        @csrf
                        <button type="submit" class="btn-primary text-xs px-3 py-1.5">
                            标记已复核
                        </button>
                    </form>
                    <a href="{{ route('knowledge-cards.edit', $card) }}" class="btn-secondary text-xs px-3 py-1.5 text-center">
                        编辑
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="card text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-gray-500 text-lg">太棒了！没有需要复核的知识卡片</p>
            <p class="text-gray-400 text-sm mt-2">
                <a href="{{ route('knowledge-cards.index') }}" class="text-primary-600 hover:text-primary-700">返回知识卡片列表</a>
            </p>
        </div>
    @endforelse
</div>

@if($cards->hasPages())
    <div class="mt-6">
        {{ $cards->links('pagination.custom') }}
    </div>
@endif

<div class="mb-4 mt-6">
    <a href="{{ route('knowledge-cards.index') }}" class="text-primary-600 hover:text-primary-700">← 返回知识卡片列表</a>
</div>
@endsection

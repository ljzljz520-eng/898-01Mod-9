@extends('layouts.app')

@section('title', $knowledgeCard->title)

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <span class="badge-primary">知识卡片</span>
        <span class="badge">{{ $knowledgeCard->category_label }}</span>
        @if($knowledgeCard->status == \App\Models\KnowledgeCard::STATUS_NEEDS_REVIEW)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                政策即将过期，请及时复核
            </span>
        @elseif($knowledgeCard->status == \App\Models\KnowledgeCard::STATUS_EXPIRED)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                政策已过期，请联系版主复核更新
            </span>
        @endif
    </div>
    <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $knowledgeCard->title }}</h1>
    <div class="flex items-center gap-4 text-sm text-gray-500 mb-6 flex-wrap">
        <span>整理：{{ $knowledgeCard->moderator->username }}</span>
        <span>创建时间：{{ $knowledgeCard->created_at->format('Y-m-d') }}</span>
        <span>更新时间：{{ $knowledgeCard->updated_at->format('Y-m-d') }}</span>
        <span>浏览：{{ $knowledgeCard->view_count }}</span>
        @if($knowledgeCard->last_reviewed_at)
            <span>上次复核：{{ $knowledgeCard->last_reviewed_at->format('Y-m-d') }}</span>
        @endif
        @if($knowledgeCard->expire_date)
            <span>有效期至：{{ $knowledgeCard->expire_date->format('Y-m-d') }}</span>
        @endif
        @auth
            @if(auth()->user()->isModerator())
                <a href="{{ route('knowledge-cards.edit', $knowledgeCard) }}" class="text-primary-600 hover:text-primary-700">编辑</a>
                @if($knowledgeCard->status != \App\Models\KnowledgeCard::STATUS_ACTIVE)
                    <form method="POST" action="{{ route('knowledge-cards.review', $knowledgeCard) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-green-600 hover:text-green-700">标记已复核</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('knowledge-cards.destroy', $knowledgeCard) }}" class="inline" data-confirm-delete>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-700">删除</button>
                </form>
            @endif
        @endauth
    </div>
</div>

@if($knowledgeCard->tags_array)
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($knowledgeCard->tags_array as $tag)
            <span class="inline-flex items-center px-3 py-1 rounded text-sm font-medium bg-blue-50 text-blue-700">
                #{{ $tag }}
            </span>
        @endforeach
    </div>
@endif

<div class="card mb-6 border-l-4 border-l-primary-500">
    <div class="prose max-w-none">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">📋 知识要点</h2>
        <p class="whitespace-pre-wrap text-gray-700 leading-relaxed">{{ $knowledgeCard->summary }}</p>
    </div>
</div>

<div class="card mb-6 bg-neutral-50">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-medium text-gray-500 mb-1">原始讨论帖</h3>
            <a href="{{ route('topics.show', $knowledgeCard->topic) }}" class="text-base font-medium text-primary-600 hover:text-primary-700">
                {{ $knowledgeCard->topic->title }}
            </a>
            <p class="text-sm text-gray-500 mt-1">
                由 {{ $knowledgeCard->topic->user->username }} 发布于 {{ $knowledgeCard->topic->created_at->format('Y-m-d') }} · 
                {{ $knowledgeCard->topic->reply_count }} 条回复
            </p>
        </div>
    </div>
</div>

@if($knowledgeCard->topic && $knowledgeCard->topic->replies->count() > 0)
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">原帖回复 ({{ $knowledgeCard->topic->replies->count() }})</h2>
        <div class="space-y-4">
            @foreach($knowledgeCard->topic->replies as $reply)
                <div class="card">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-medium text-gray-800">{{ $reply->user->username }}</span>
                                <span class="text-sm text-gray-500">{{ $reply->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $reply->content }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-4">
    <a href="{{ route('knowledge-cards.index') }}" class="text-primary-600 hover:text-primary-700">← 返回知识卡片列表</a>
</div>
@endsection

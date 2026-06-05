@extends('layouts.app')

@section('title', $topic->title)

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 mb-4">
        @if($topic->is_pinned)
            <span class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded">置顶</span>
        @endif
        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ category_name($topic->category) }}</span>
    </div>
    <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $topic->title }}</h1>
    <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
        <span>作者：{{ $topic->user->username }}</span>
        <span>发布时间：{{ $topic->created_at->format('Y-m-d H:i') }}</span>
        <span>浏览：{{ $topic->view_count }}</span>
        <span>回复：{{ $topic->reply_count }}</span>
        @auth
            @if($topic->user_id === auth()->id())
                <a href="{{ route('topics.edit', $topic) }}" class="text-primary-600 hover:text-primary-700">编辑</a>
                <form method="POST" action="{{ route('topics.destroy', $topic) }}" class="inline" data-confirm-delete>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-700">删除</button>
                </form>
            @endif
        @endauth
    </div>
</div>

<div class="card mb-6">
    <div class="prose max-w-none">
        <p class="whitespace-pre-wrap text-gray-700">{{ $topic->content }}</p>
    </div>
</div>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">回复 ({{ $topic->replies->count() }})</h2>
    
    @auth
        <div class="card mb-6">
            <form method="POST" action="{{ route('replies.store', $topic) }}">
                @csrf
                <div class="mb-4">
                    <label for="content" class="block text-gray-700 text-sm font-medium mb-2">发表回复</label>
                    <textarea id="content" name="content" rows="4" required
                              class="input-field @error('content') border-red-500 @enderror"
                              placeholder="请输入回复内容..."></textarea>
                    @error('content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary">发表回复</button>
            </form>
        </div>
    @else
        <div class="card mb-6 text-center py-4">
            <p class="text-gray-600">请 <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700">登录</a> 后发表回复</p>
        </div>
    @endauth

    <div class="space-y-4">
        @forelse($topic->replies as $reply)
            <div class="card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-medium text-gray-800">{{ $reply->user->username }}</span>
                            <span class="text-sm text-gray-500">{{ $reply->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $reply->content }}</p>
                    </div>
                    @auth
                        @if($reply->user_id === auth()->id())
                            <form method="POST" action="{{ route('replies.destroy', $reply) }}" class="ml-4" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700 text-sm">删除</button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        @empty
            <div class="card text-center py-8">
                <p class="text-gray-500">暂无回复</p>
            </div>
        @endforelse
    </div>
</div>

<div class="mb-4">
    <a href="{{ route('topics.index') }}" class="text-primary-600 hover:text-primary-700">← 返回主题列表</a>
</div>
@endsection

@extends('layouts.app')

@section('title', '主题列表')

@section('content')
<div class="mb-4">
    <h1 class="text-2xl font-semibold text-neutral-800">最新主题</h1>
</div>

<div class="mb-4 flex flex-col sm:flex-row gap-3">
    <form method="GET" action="{{ route('topics.index') }}" class="flex-1 flex gap-2 items-center" data-topic-filter>
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="搜索主题..." 
               class="flex-1 input-field">
        <select name="category" class="input-field w-auto text-sm">
            <option value="all" {{ request('category') == 'all' ? 'selected' : '' }}>全部分类</option>
            <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>综合讨论</option>
            <option value="tech" {{ request('category') == 'tech' ? 'selected' : '' }}>技术交流</option>
            <option value="study" {{ request('category') == 'study' ? 'selected' : '' }}>学习心得</option>
            <option value="question" {{ request('category') == 'question' ? 'selected' : '' }}>问题求助</option>
            <option value="maintenance" {{ request('category') == 'maintenance' ? 'selected' : '' }}>物业维修</option>
            <option value="conflict" {{ request('category') == 'conflict' ? 'selected' : '' }}>邻里矛盾</option>
            <option value="fee" {{ request('category') == 'fee' ? 'selected' : '' }}>费用公示</option>
        </select>
        <select name="circle_type" class="input-field w-auto text-sm">
            <option value="all" {{ request('circle_type') == 'all' ? 'selected' : '' }}>全部圈层</option>
            <option value="public" {{ request('circle_type') == 'public' ? 'selected' : '' }}>公共广场</option>
            <option value="building" {{ request('circle_type') == 'building' ? 'selected' : '' }}>楼栋讨论</option>
            <option value="committee" {{ request('circle_type') == 'committee' ? 'selected' : '' }}>业委会</option>
            <option value="tenant" {{ request('circle_type') == 'tenant' ? 'selected' : '' }}>租户专区</option>
        </select>
        <button type="submit" class="btn-secondary text-sm px-3">搜索</button>
    </form>
</div>

<div class="space-y-3" data-topic-list>
    @forelse($topics as $topic)
        <div class="card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($topic['is_pinned'])
                            <span class="badge-primary">置顶</span>
                        @endif
                        <span class="badge text-[11px]">{{ category_name($topic['category']) }}</span>
                        <span class="badge text-[11px] bg-blue-100 text-blue-800">{{ circle_name($topic['circle_type']) }}</span>
                        @if($topic['circle_type'] !== 'public' && isset($topic['building_name']))
                            <span class="badge text-[11px] bg-purple-100 text-purple-800">{{ $topic['building_name'] }}</span>
                        @endif
                    </div>
                    <a href="{{ route('topics.show', $topic['id']) }}" class="block text-base md:text-lg font-semibold text-neutral-800 hover:text-primary-600 mb-1">
                            {{ $topic['title'] }}
                    </a>
                    <p class="text-neutral-600 text-sm mb-2 line-clamp-2">{{ Str::limit($topic['content'], 150) }}</p>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                        <span>作者：{{ $topic['user']['username'] }}</span>
                        <span>发布时间：{{ \Carbon\Carbon::parse($topic['created_at'])->format('Y-m-d H:i') }}</span>
                        <span>浏览：{{ $topic['view_count'] }}</span>
                        <span>回复：{{ $topic['reply_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card text-center py-12">
            <p class="text-gray-500 text-lg">暂无主题</p>
        </div>
    @endforelse
</div>

<div class="mt-6" data-topic-pagination>
    {{ $topics->links('pagination.custom') }}
</div>
@endsection

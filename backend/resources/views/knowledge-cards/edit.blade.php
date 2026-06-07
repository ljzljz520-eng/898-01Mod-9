@extends('layouts.app')

@section('title', '编辑知识卡片')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">编辑知识卡片</h1>
    <p class="text-sm text-gray-500 mt-1">更新知识内容，保持信息准确性</p>
</div>

<div class="card max-w-4xl">
    <form method="POST" action="{{ route('knowledge-cards.update', $knowledgeCard) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="topic_id" class="block text-gray-700 text-sm font-medium mb-2">
                关联帖子 <span class="text-red-500">*</span>
            </label>
            <select id="topic_id" name="topic_id" required class="input-field @error('topic_id') border-red-500 @enderror">
                <option value="">请选择要整理的帖子</option>
                @foreach($eligibleTopics as $t)
                    <option value="{{ $t->id }}" {{ old('topic_id', $knowledgeCard->topic_id) == $t->id ? 'selected' : '' }}>
                        [{{ $t->category }}] {{ $t->title }} ({{ $t->user->username }} · {{ $t->created_at->format('Y-m-d') }})
                    </option>
                @endforeach
            </select>
            @error('topic_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-medium mb-2">
                卡片标题 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title" value="{{ old('title', $knowledgeCard->title) }}" required
                   class="input-field @error('title') border-red-500 @enderror"
                   placeholder="请输入知识卡片标题，简洁明了">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="category" class="block text-gray-700 text-sm font-medium mb-2">
                    分类 <span class="text-red-500">*</span>
                </label>
                <select id="category" name="category" required class="input-field @error('category') border-red-500 @enderror">
                    <option value="">请选择分类</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', $knowledgeCard->category) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-gray-700 text-sm font-medium mb-2">
                    状态
                </label>
                <select id="status" name="status" class="input-field @error('status') border-red-500 @enderror">
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $knowledgeCard->status) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="expire_date" class="block text-gray-700 text-sm font-medium mb-2">
                    过期日期
                </label>
                <input type="date" id="expire_date" name="expire_date" value="{{ old('expire_date', $knowledgeCard->expire_date?->format('Y-m-d')) }}"
                       class="input-field @error('expire_date') border-red-500 @enderror"
                       min="{{ now()->addDay()->format('Y-m-d') }}">
                @error('expire_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                    复核信息
                </label>
                <div class="text-sm text-gray-500">
                    @if($knowledgeCard->last_reviewed_at)
                        上次复核：{{ $knowledgeCard->last_reviewed_at->format('Y-m-d') }}
                    @else
                        尚未复核
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="tags" class="block text-gray-700 text-sm font-medium mb-2">
                标签
            </label>
            <input type="text" id="tags" name="tags" value="{{ old('tags', $knowledgeCard->tags) }}"
                   class="input-field @error('tags') border-red-500 @enderror"
                   placeholder="多个标签用逗号分隔，如：电信, 联通, 移动, 宽带套餐">
            @error('tags')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="summary" class="block text-gray-700 text-sm font-medium mb-2">
                知识摘要 <span class="text-red-500">*</span>
            </label>
            <textarea id="summary" name="summary" rows="10" required
                      class="input-field @error('summary') border-red-500 @enderror"
                      placeholder="请整理帖子中的核心知识要点">{{ old('summary', $knowledgeCard->summary) }}</textarea>
            @error('summary')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('knowledge-cards.show', $knowledgeCard) }}" class="btn-secondary">取消</a>
            <button type="submit" class="btn-primary">保存修改</button>
        </div>
    </form>
</div>

<div class="mb-4 mt-6">
    <a href="{{ route('knowledge-cards.show', $knowledgeCard) }}" class="text-primary-600 hover:text-primary-700">← 返回知识卡片详情</a>
</div>
@endsection

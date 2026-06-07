@extends('layouts.app')

@section('title', '创建知识卡片')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">创建知识卡片</h1>
    <p class="text-sm text-gray-500 mt-1">从高质量帖子中提炼知识，方便社区用户快速获取有效信息</p>
</div>

<div class="card max-w-4xl">
    <form method="POST" action="{{ route('knowledge-cards.store') }}">
        @csrf

        <div class="mb-4">
            <label for="topic_id" class="block text-gray-700 text-sm font-medium mb-2">
                关联帖子 <span class="text-red-500">*</span>
            </label>
            <select id="topic_id" name="topic_id" required class="input-field @error('topic_id') border-red-500 @enderror">
                <option value="">请选择要整理的帖子</option>
                @foreach($eligibleTopics as $t)
                    <option value="{{ $t->id }}" {{ old('topic_id', $topic->id ?? '') == $t->id ? 'selected' : '' }}>
                        [{{ $t->category }}] {{ $t->title }} ({{ $t->user->username }} · {{ $t->created_at->format('Y-m-d') }})
                    </option>
                @endforeach
            </select>
            @error('topic_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">仅显示宽带办理、学区材料、停车证、装修流程分类且未创建知识卡片的帖子</p>
        </div>

        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-medium mb-2">
                卡片标题 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title" value="{{ old('title', $topic->title ?? '') }}" required
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
                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="expire_date" class="block text-gray-700 text-sm font-medium mb-2">
                    过期日期
                </label>
                <input type="date" id="expire_date" name="expire_date" value="{{ old('expire_date') }}"
                       class="input-field @error('expire_date') border-red-500 @enderror"
                       min="{{ now()->addDay()->format('Y-m-d') }}">
                @error('expire_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">政策类内容建议设置过期日期，到期后自动提醒复核</p>
            </div>
        </div>

        <div class="mb-4">
            <label for="tags" class="block text-gray-700 text-sm font-medium mb-2">
                标签
            </label>
            <input type="text" id="tags" name="tags" value="{{ old('tags') }}"
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
                      placeholder="请整理帖子中的核心知识要点，结构清晰，方便用户快速阅读。&#10;&#10;建议格式：&#10;一、办理条件：&#10;1. ...&#10;2. ...&#10;&#10;二、所需材料：&#10;1. ...&#10;2. ...&#10;&#10;三、办理流程：&#10;...">{{ old('summary', $topic->content ?? '') }}</textarea>
            @error('summary')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">请提炼核心内容，避免冗余信息。原帖内容会自动关联供用户查阅详情。</p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('knowledge-cards.index') }}" class="btn-secondary">取消</a>
            <button type="submit" class="btn-primary">创建知识卡片</button>
        </div>
    </form>
</div>

<div class="mb-4 mt-6">
    <a href="{{ route('knowledge-cards.index') }}" class="text-primary-600 hover:text-primary-700">← 返回知识卡片列表</a>
</div>
@endsection

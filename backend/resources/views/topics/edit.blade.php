@extends('layouts.app')

@section('title', '编辑主题')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">编辑主题</h1>
    
    <div class="card">
        <form method="POST" action="{{ route('topics.update', $topic) }}">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-medium mb-2">标题</label>
                <input type="text" id="title" name="title" value="{{ old('title', $topic->title) }}" required
                       class="input-field @error('title') border-red-500 @enderror"
                       placeholder="请输入主题标题" autofocus>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="category" class="block text-gray-700 text-sm font-medium mb-2">分类</label>
                <select id="category" name="category" required
                        class="input-field @error('category') border-red-500 @enderror">
                    <option value="general" {{ old('category', $topic->category) == 'general' ? 'selected' : '' }}>综合讨论</option>
                    <option value="tech" {{ old('category', $topic->category) == 'tech' ? 'selected' : '' }}>技术交流</option>
                    <option value="study" {{ old('category', $topic->category) == 'study' ? 'selected' : '' }}>学习心得</option>
                    <option value="question" {{ old('category', $topic->category) == 'question' ? 'selected' : '' }}>问题求助</option>
                    <option value="maintenance" {{ old('category', $topic->category) == 'maintenance' ? 'selected' : '' }}>物业维修</option>
                    <option value="conflict" {{ old('category', $topic->category) == 'conflict' ? 'selected' : '' }}>邻里矛盾</option>
                    <option value="fee" {{ old('category', $topic->category) == 'fee' ? 'selected' : '' }}>费用公示</option>
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="circle_type" class="block text-gray-700 text-sm font-medium mb-2">圈层</label>
                <select id="circle_type" name="circle_type" required
                        class="input-field @error('circle_type') border-red-500 @enderror">
                    @foreach($userCircles as $circle)
                        <option value="{{ $circle }}" {{ old('circle_type', $topic->circle_type) == $circle ? 'selected' : '' }}>{{ circle_name($circle) }}</option>
                    @endforeach
                </select>
                @error('circle_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="content" class="block text-gray-700 text-sm font-medium mb-2">内容</label>
                <textarea id="content" name="content" rows="12" required
                          class="input-field @error('content') border-red-500 @enderror"
                          placeholder="请输入主题内容...">{{ old('content', $topic->content) }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div data-extra-fields class="mb-6">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="btn-primary">更新</button>
                <a href="{{ route('topics.show', $topic) }}" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<script>
    window.extraFieldValues = @json($topic->extra_fields ?? []);
</script>
@endsection

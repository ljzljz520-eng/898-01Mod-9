@extends('layouts.app')

@section('title', '发布主题')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">发布主题</h1>
    
    <div class="card">
        <form method="POST" action="{{ route('topics.store') }}">
            @csrf
            
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-medium mb-2">标题</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
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
                    <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>综合讨论</option>
                    <option value="tech" {{ old('category') == 'tech' ? 'selected' : '' }}>技术交流</option>
                    <option value="study" {{ old('category') == 'study' ? 'selected' : '' }}>学习心得</option>
                    <option value="question" {{ old('category') == 'question' ? 'selected' : '' }}>问题求助</option>
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="content" class="block text-gray-700 text-sm font-medium mb-2">内容</label>
                <textarea id="content" name="content" rows="12" required
                          class="input-field @error('content') border-red-500 @enderror"
                          placeholder="请输入主题内容...">{{ old('content') }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="btn-primary">发布</button>
                <a href="{{ route('topics.index') }}" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', '申请搬离')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">申请搬离</h1>
    <p class="text-sm text-gray-500 mt-1">确认搬离后将失去内部圈子访问权限</p>
</div>

<div class="max-w-2xl mx-auto">
    <div class="card border-l-4 border-l-red-500">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-red-700 mb-2">重要提示</h3>
                <div class="text-sm text-gray-600 space-y-2">
                    <p>申请搬离后，您的账号将发生以下变化：</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li><span class="text-red-600 font-medium">将失去所有内部圈子的访问权限</span>，包括楼栋内部圈、业委会圈、租户圈等</li>
                        <li>您之前发布的帖子和回复将<span class="text-neutral-800 font-medium">保留</span>在系统中，不会被删除</li>
                        <li>您的用户名和公开信息将保持不变，仍可浏览公开圈子内容</li>
                        <li>如需恢复认证身份，可在个人中心重新提交认证申请</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-yellow-800">
                    <p class="font-medium">请谨慎操作</p>
                    <p class="mt-1">搬离申请一旦提交，将立即生效。您需要重新提交认证申请才能恢复内部圈子访问权限。</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('auth.move-out.submit') }}">
            @csrf

            <div class="mb-6">
                <label for="remark" class="block text-gray-700 text-sm font-medium mb-2">
                    搬离原因（可选）
                </label>
                <textarea id="remark" name="remark" rows="3"
                          class="input-field @error('remark') border-red-500 @enderror"
                          placeholder="请简要说明搬离原因，帮助我们改进服务（可选）"></textarea>
                @error('remark')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('auth.profile') }}" class="btn-secondary">取消</a>
                <button type="submit" class="btn-danger" onclick="return confirm('确认要申请搬离吗？搬离后将失去内部圈子访问权限。')">
                    确认搬离
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6">
        <a href="{{ route('auth.profile') }}" class="text-primary-600 hover:text-primary-700">← 返回个人中心</a>
    </div>
</div>
@endsection

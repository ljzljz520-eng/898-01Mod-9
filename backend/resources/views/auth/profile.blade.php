@extends('layouts.app')

@section('title', '个人中心')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">个人中心</h1>
    <p class="text-sm text-gray-500 mt-1">查看和管理您的个人信息</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <h2 class="text-lg font-semibold text-neutral-800 mb-4">基本信息</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                    <span class="text-gray-600">用户名</span>
                    <span class="font-medium text-neutral-800">{{ $user->username }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                    <span class="text-gray-600">邮箱</span>
                    <span class="font-medium text-neutral-800">{{ $user->email }}</span>
                </div>
                @if($user->isVerified() && !$user->isMoved())
                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                        <span class="text-gray-600">真实姓名</span>
                        <span class="font-medium text-neutral-800">{{ $user->real_name }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                    <span class="text-gray-600">认证状态</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($user->isMoved()) bg-gray-100 text-gray-700
                        @elseif($user->verification_status == 'verified') bg-green-100 text-green-700
                        @elseif($user->verification_status == 'pending') bg-yellow-100 text-yellow-700
                        @elseif($user->verification_status == 'rejected') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-600 @endif">
                        @if($user->isMoved())
                            已搬离
                        @else
                            @switch($user->verification_status)
                                @case('verified') 已认证 @break
                                @case('pending') 审核中 @break
                                @case('rejected') 已拒绝 @break
                                @default 未认证
                            @endswitch
                        @endif
                    </span>
                </div>
                @if($user->isVerified() && !$user->isMoved())
                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                        <span class="text-gray-600">住户类型</span>
                        <span class="badge-primary">
                            @switch($user->resident_type)
                                @case('owner') 业主 @break
                                @case('tenant') 租户 @break
                                @case('committee') 业委会 @break
                            @endswitch
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                        <span class="text-gray-600">楼栋</span>
                        <span class="font-medium text-neutral-800">{{ $user->building->name ?? '未设置' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-600">房间号</span>
                        <span class="font-medium text-neutral-800">{{ $user->unit_number ?? '未设置' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <h2 class="text-lg font-semibold text-neutral-800 mb-4">可访问圈子</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($user->getAccessibleCircleTypes() as $circle)
                    <span class="badge-primary">
                        @switch($circle)
                            @case('public') 公开圈子 @break
                            @case('building') 楼栋内部圈 @break
                            @case('committee') 业委会圈 @break
                            @case('tenant') 租户圈 @break
                        @endswitch
                    </span>
                @endforeach
            </div>
            @if(!$user->isVerified() || $user->isMoved())
                <p class="text-sm text-gray-500 mt-3">完成认证后可访问更多内部圈子</p>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <h2 class="text-lg font-semibold text-neutral-800 mb-4">操作</h2>
            <div class="space-y-3">
                @if($user->verification_status == 'unverified' && !$user->isMoved())
                    <a href="{{ route('auth.verify.apply') }}" class="w-full btn-primary justify-center">
                        申请认证
                    </a>
                @endif

                @if($user->verification_status == 'pending' && !$user->isMoved())
                    <div class="text-center py-3">
                        <p class="text-yellow-600 text-sm">认证申请审核中，请耐心等待</p>
                    </div>
                @endif

                @if($user->verification_status == 'rejected' && !$user->isMoved())
                    <a href="{{ route('auth.verify.apply') }}" class="w-full btn-primary justify-center">
                        重新申请认证
                    </a>
                    @if($user->verification_remark)
                        <p class="text-red-600 text-xs mt-2">拒绝原因：{{ $user->verification_remark }}</p>
                    @endif
                @endif

                @if($user->isVerified() && !$user->isMoved())
                    <a href="{{ route('auth.move-out') }}" class="w-full btn-danger justify-center">
                        申请搬离
                    </a>
                @endif

                @if($user->isMoved())
                    <form method="POST" action="{{ route('auth.verify.cancel-move') }}">
                        @csrf
                        <button type="submit" class="w-full btn-secondary justify-center">
                            取消搬离，重新申请
                        </button>
                    </form>
                @endif

                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('auth.verify.list') }}" class="w-full btn-secondary justify-center mt-4">
                            认证审核管理
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        @if($user->verification_status != 'unverified' && !$user->isMoved())
            <div class="card">
                <h2 class="text-lg font-semibold text-neutral-800 mb-4">认证信息</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">申请时间</span>
                        <span class="text-neutral-800">{{ $user->created_at->format('Y-m-d') }}</span>
                    </div>
                    @if($user->verified_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">认证通过时间</span>
                            <span class="text-neutral-800">{{ $user->verified_at->format('Y-m-d') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

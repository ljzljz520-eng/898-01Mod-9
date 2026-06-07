@extends('layouts.app')

@section('title', '认证审核管理')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">认证审核管理</h1>
    <p class="text-sm text-gray-500 mt-1">审核用户的身份认证申请</p>
</div>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-neutral-800 mb-4 flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
            待审核 {{ $pendingUsers->count() }}
        </span>
        待审核申请
    </h2>

    <div class="space-y-4">
        @forelse($pendingUsers as $user)
            <div class="card border-l-4 border-l-yellow-500">
                <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-semibold text-neutral-800">{{ $user->username }}</span>
                            <span class="text-gray-500 text-sm">{{ $user->email }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">真实姓名：</span>
                                <span class="text-neutral-800">{{ $user->real_name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">身份证号：</span>
                                <span class="text-neutral-800">{{ substr($user->id_card, 0, 6) }}********{{ substr($user->id_card, -4) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">申请楼栋：</span>
                                <span class="text-neutral-800">{{ $user->building->name ?? '未设置' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">房间号：</span>
                                <span class="text-neutral-800">{{ $user->unit_number ?? '未设置' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">申请类型：</span>
                                <span class="badge">
                                    @switch($user->resident_type)
                                        @case('owner') 业主 @break
                                        @case('tenant') 租户 @break
                                        @case('committee') 业委会 @break
                                        @default 未设置
                                    @endswitch
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">申请时间：</span>
                                <span class="text-neutral-800">{{ $user->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-neutral-100">
                    <form method="POST" action="{{ route('auth.verify.review', $user) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="resident_type_{{ $user->id }}" class="block text-gray-700 text-sm font-medium mb-2">
                                    住户类型
                                </label>
                                <select id="resident_type_{{ $user->id }}" name="resident_type" required class="input-field text-sm">
                                    <option value="owner" {{ $user->resident_type == 'owner' ? 'selected' : '' }}>业主</option>
                                    <option value="tenant" {{ $user->resident_type == 'tenant' ? 'selected' : '' }}>租户</option>
                                    <option value="committee" {{ $user->resident_type == 'committee' ? 'selected' : '' }}>业委会</option>
                                </select>
                            </div>
                            <div>
                                <label for="building_id_{{ $user->id }}" class="block text-gray-700 text-sm font-medium mb-2">
                                    楼栋
                                </label>
                                <select id="building_id_{{ $user->id }}" name="building_id" required class="input-field text-sm">
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->id }}" {{ $user->building_id == $building->id ? 'selected' : '' }}>
                                            {{ $building->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="unit_number_{{ $user->id }}" class="block text-gray-700 text-sm font-medium mb-2">
                                    房间号
                                </label>
                                <input type="text" id="unit_number_{{ $user->id }}" name="unit_number" 
                                       value="{{ $user->unit_number }}" 
                                       class="input-field text-sm"
                                       placeholder="房间号">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remark_{{ $user->id }}" class="block text-gray-700 text-sm font-medium mb-2">
                                审核备注
                            </label>
                            <input type="text" id="remark_{{ $user->id }}" name="remark" 
                                   class="input-field text-sm"
                                   placeholder="审核备注（可选，拒绝时请填写原因）">
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="submit" name="action" value="reject" class="btn-danger text-sm px-4 py-1.5">
                                拒绝
                            </button>
                            <button type="submit" name="action" value="approve" class="btn-primary text-sm px-4 py-1.5">
                                通过
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="card text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-gray-500 text-lg">暂无待审核的认证申请</p>
            </div>
        @endforelse
    </div>
</div>

<div>
    <h2 class="text-lg font-semibold text-neutral-800 mb-4 flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
            已通过 {{ $verifiedUsers->count() }}
        </span>
        已认证用户
    </h2>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-neutral-200">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户信息</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">楼栋</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">房间号</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">住户类型</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">认证时间</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($verifiedUsers as $user)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-neutral-800">{{ $user->username }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-800">
                            {{ $user->building->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-neutral-800">
                            {{ $user->unit_number ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="badge-primary text-xs">
                                @switch($user->resident_type)
                                    @case('owner') 业主 @break
                                    @case('tenant') 租户 @break
                                    @case('committee') 业委会 @break
                                @endswitch
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->verified_at ? $user->verified_at->format('Y-m-d H:i') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            暂无已认证用户
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('auth.profile') }}" class="text-primary-600 hover:text-primary-700">← 返回个人中心</a>
</div>
@endsection

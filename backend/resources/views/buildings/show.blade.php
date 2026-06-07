@extends('layouts.app')

@section('title', $building->name)

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-gray-800">{{ $building->name }}</h1>
        @auth
            @if(auth()->user()->isAdmin())
                <div class="flex gap-2">
                    <a href="{{ route('buildings.edit', $building) }}" class="btn-secondary text-sm">
                        编辑
                    </a>
                    <form method="POST" action="{{ route('buildings.destroy', $building) }}" class="inline" data-confirm-delete>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger text-sm">删除</button>
                    </form>
                </div>
            @endif
        @endauth
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-blue-50 border-blue-100">
            <div class="text-sm text-blue-600 mb-1">地址</div>
            <div class="text-lg font-medium text-blue-800">{{ $building->address }}</div>
        </div>
        <div class="card bg-green-50 border-green-100">
            <div class="text-sm text-green-600 mb-1">总户数</div>
            <div class="text-lg font-medium text-green-800">{{ $building->total_units ?? 0 }} 户</div>
        </div>
        <div class="card bg-purple-50 border-purple-100">
            <div class="text-sm text-purple-600 mb-1">已认证居民</div>
            <div class="text-lg font-medium text-purple-800">
                {{ $building->verified_residents_count ?? ($building->verifiedResidents->count() ?? $building->resident_count) }} 位
            </div>
        </div>
    </div>

    @if($building->description)
        <div class="card mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">楼栋描述</h2>
            <p class="text-gray-700 whitespace-pre-wrap">{{ $building->description }}</p>
        </div>
    @endif
</div>

<div class="mb-4">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">已认证居民列表</h2>
    <div class="card overflow-hidden">
        @if(isset($building->verifiedResidents) && $building->verifiedResidents->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-100">
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">用户名</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">居民类型</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">门牌号</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">认证时间</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($building->verifiedResidents as $resident)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-neutral-800">
                                    {{ $resident->username }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($resident->resident_type === 'owner')
                                        <span class="badge bg-green-100 text-green-700">业主</span>
                                    @elseif($resident->resident_type === 'tenant')
                                        <span class="badge bg-blue-100 text-blue-700">租客</span>
                                    @elseif($resident->resident_type === 'committee')
                                        <span class="badge bg-purple-100 text-purple-700">业委会</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-600">
                                    {{ $resident->unit_number ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-500">
                                    {{ $resident->verified_at ? $resident->verified_at->format('Y-m-d H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                暂无已认证居民
            </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('buildings.index') }}" class="text-primary-600 hover:text-primary-700">← 返回楼栋列表</a>
</div>
@endsection

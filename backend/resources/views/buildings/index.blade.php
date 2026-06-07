@extends('layouts.app')

@section('title', '楼栋列表')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-neutral-800">楼栋列表</h1>
    @auth
        @if(auth()->user()->isAdmin())
            <a href="{{ route('buildings.create') }}" class="btn-primary text-sm">
                + 创建楼栋
            </a>
        @endif
    @endauth
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($buildings as $building)
        <a href="{{ route('buildings.show', $building) }}" class="card hover:shadow-md transition-shadow duration-200 block">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-semibold text-neutral-800 hover:text-primary-600">
                    {{ $building->name }}
                </h3>
                <span class="badge-primary">
                    {{ $building->verified_residents_count ?? $building->resident_count }} 位已认证居民
                </span>
            </div>
            <div class="space-y-2 text-sm text-neutral-600">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ $building->address }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>共 {{ $building->total_units ?? 0 }} 户</span>
                </div>
            </div>
        </a>
    @empty
        <div class="card text-center py-12 col-span-full">
            <p class="text-gray-500 text-lg">暂无楼栋信息</p>
            @auth
                @if(auth()->user()->isAdmin())
                    <p class="text-gray-400 text-sm mt-2">
                        <a href="{{ route('buildings.create') }}" class="text-primary-600 hover:text-primary-700">立即创建第一个楼栋</a>
                    </p>
                @endif
            @endauth
        </div>
    @endforelse
</div>
@endsection

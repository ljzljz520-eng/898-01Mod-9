@extends('layouts.app')

@section('title', '身份认证申请')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">身份认证申请</h1>
    <p class="text-sm text-gray-500 mt-1">完成身份认证后可访问更多内部圈子和功能</p>
</div>

<div class="card max-w-2xl mx-auto">
    <form method="POST" action="{{ route('auth.verify.submit') }}">
        @csrf

        <div class="mb-4">
            <label for="building_id" class="block text-gray-700 text-sm font-medium mb-2">
                选择楼栋 <span class="text-red-500">*</span>
            </label>
            <select id="building_id" name="building_id" required class="input-field @error('building_id') border-red-500 @enderror">
                <option value="">请选择您所在的楼栋</option>
                @foreach($buildings as $building)
                    <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                        {{ $building->name }} ({{ $building->community_name }})
                    </option>
                @endforeach
            </select>
            @error('building_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="unit_number" class="block text-gray-700 text-sm font-medium mb-2">
                房间号 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="unit_number" name="unit_number" value="{{ old('unit_number') }}" required
                   class="input-field @error('unit_number') border-red-500 @enderror"
                   placeholder="请输入房间号，如：1001">
            @error('unit_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">
                住户类型 <span class="text-red-500">*</span>
            </label>
            <div class="space-y-2">
                <label class="flex items-center p-3 border border-neutral-200 rounded-md cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" name="resident_type" value="owner" {{ old('resident_type') == 'owner' ? 'checked' : '' }} required
                           class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-neutral-300">
                    <span class="ml-3">
                        <span class="font-medium text-neutral-800">业主</span>
                        <span class="text-sm text-gray-500 ml-2">房屋产权所有人</span>
                    </span>
                </label>
                <label class="flex items-center p-3 border border-neutral-200 rounded-md cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" name="resident_type" value="tenant" {{ old('resident_type') == 'tenant' ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-neutral-300">
                    <span class="ml-3">
                        <span class="font-medium text-neutral-800">租户</span>
                        <span class="text-sm text-gray-500 ml-2">租住房屋的住户</span>
                    </span>
                </label>
                <label class="flex items-center p-3 border border-neutral-200 rounded-md cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" name="resident_type" value="committee" {{ old('resident_type') == 'committee' ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-neutral-300">
                    <span class="ml-3">
                        <span class="font-medium text-neutral-800">业委会成员</span>
                        <span class="text-sm text-gray-500 ml-2">业主委员会成员</span>
                    </span>
                </label>
            </div>
            @error('resident_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="real_name" class="block text-gray-700 text-sm font-medium mb-2">
                真实姓名 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="real_name" name="real_name" value="{{ old('real_name') }}" required
                   class="input-field @error('real_name') border-red-500 @enderror"
                   placeholder="请输入您的真实姓名">
            @error('real_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="id_card" class="block text-gray-700 text-sm font-medium mb-2">
                身份证号 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="id_card" name="id_card" value="{{ old('id_card') }}" required
                   class="input-field @error('id_card') border-red-500 @enderror"
                   placeholder="请输入18位身份证号码">
            <p class="mt-1 text-xs text-gray-500">您的身份证信息将被加密存储，仅用于身份核验，不会公开</p>
            @error('id_card')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('auth.profile') }}" class="btn-secondary">取消</a>
            <button type="submit" class="btn-primary">提交申请</button>
        </div>
    </form>
</div>

<div class="mt-6">
    <a href="{{ route('auth.profile') }}" class="text-primary-600 hover:text-primary-700">← 返回个人中心</a>
</div>
@endsection

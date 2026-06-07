@extends('layouts.app')

@section('title', '编辑楼栋')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-neutral-800">编辑楼栋</h1>
    <p class="text-sm text-gray-500 mt-1">修改楼栋信息</p>
</div>

<div class="card max-w-2xl">
    <form method="POST" action="{{ route('buildings.update', $building) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-medium mb-2">
                楼栋名称 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name', $building->name) }}" required
                   class="input-field @error('name') border-red-500 @enderror"
                   placeholder="请输入楼栋名称，如：1号楼">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="address" class="block text-gray-700 text-sm font-medium mb-2">
                地址 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="address" name="address" value="{{ old('address', $building->address) }}" required
                   class="input-field @error('address') border-red-500 @enderror"
                   placeholder="请输入详细地址，如：XX路XX号">
            @error('address')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="total_units" class="block text-gray-700 text-sm font-medium mb-2">
                总户数
            </label>
            <input type="number" id="total_units" name="total_units" value="{{ old('total_units', $building->total_units) }}" min="0"
                   class="input-field @error('total_units') border-red-500 @enderror"
                   placeholder="请输入总户数">
            @error('total_units')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="description" class="block text-gray-700 text-sm font-medium mb-2">
                楼栋描述
            </label>
            <textarea id="description" name="description" rows="4"
                      class="input-field @error('description') border-red-500 @enderror"
                      placeholder="请输入楼栋描述信息（可选）">{{ old('description', $building->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('buildings.show', $building) }}" class="btn-secondary">取消</a>
            <button type="submit" class="btn-primary">保存修改</button>
        </div>
    </form>
</div>

<div class="mt-6">
    <a href="{{ route('buildings.show', $building) }}" class="text-primary-600 hover:text-primary-700">← 返回楼栋详情</a>
</div>
@endsection

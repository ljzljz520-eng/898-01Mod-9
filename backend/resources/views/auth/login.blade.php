@extends('layouts.app')

@section('title', '登录')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="card">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">登录</h2>
        
        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">账号（邮箱或用户名）</label>
                <input type="text" id="email" name="email" value="{{ old('email') }}" required
                       class="input-field @error('email') border-red-500 @enderror"
                       autofocus>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">密码</label>
                <input type="password" id="password" name="password" required
                       class="input-field @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full btn-primary">登录</button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            还没有账号？<a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 font-medium">立即注册</a>
        </p>
    </div>
</div>
@endsection

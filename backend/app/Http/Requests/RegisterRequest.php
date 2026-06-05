<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名不能为空',
            'username.min' => '用户名至少3个字符',
            'username.max' => '用户名最多50个字符',
            'username.unique' => '用户名已存在',
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已被注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少6个字符',
            'password.confirmed' => '两次密码输入不一致',
        ];
    }
}

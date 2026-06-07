<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'content' => ['required', 'string', 'min:10'],
            'category' => ['nullable', 'string', 'max:50', 'in:general,tech,study,question,maintenance,conflict,fee'],
            'circle_type' => ['nullable', 'string', 'in:public,building,committee,tenant'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'extra_fields' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '标题不能为空',
            'title.min' => '标题至少5个字符',
            'title.max' => '标题最多200个字符',
            'content.required' => '内容不能为空',
            'content.min' => '内容至少10个字符',
            'category.in' => '分类类型不合法',
            'circle_type.in' => '圈层类型不合法',
            'building_id.exists' => '楼栋不存在',
        ];
    }
}

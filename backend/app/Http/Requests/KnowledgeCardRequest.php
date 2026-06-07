<?php

namespace App\Http\Requests;

use App\Models\KnowledgeCard;
use Illuminate\Foundation\Http\FormRequest;

class KnowledgeCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'exists:topics,id'],
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'summary' => ['required', 'string', 'min:10'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(KnowledgeCard::categoryLabels()))],
            'tags' => ['nullable', 'string', 'max:500'],
            'expire_date' => ['nullable', 'date', 'after:today'],
            'status' => ['nullable', 'integer', 'in:0,1,2,3'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => '请选择关联的帖子',
            'topic_id.exists' => '关联的帖子不存在',
            'title.required' => '标题不能为空',
            'title.min' => '标题至少5个字符',
            'title.max' => '标题最多200个字符',
            'summary.required' => '知识摘要不能为空',
            'summary.min' => '知识摘要至少10个字符',
            'category.required' => '请选择分类',
            'category.in' => '分类不正确',
            'tags.max' => '标签最多500个字符',
            'expire_date.date' => '过期日期格式不正确',
            'expire_date.after' => '过期日期必须晚于今天',
            'status.in' => '状态值不正确',
        ];
    }
}

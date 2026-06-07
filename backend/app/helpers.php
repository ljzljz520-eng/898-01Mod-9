<?php

if (!function_exists('category_name')) {
    /**
     * 获取分类的中文名称
     */
    function category_name(string $category): string
    {
        return match($category) {
            'general' => '综合讨论',
            'tech' => '技术交流',
            'study' => '学习心得',
            'question' => '问题求助',
            'broadband' => '宽带办理',
            'school' => '学区材料',
            'parking' => '停车证',
            'renovation' => '装修流程',
            default => $category,
        };
    }
}

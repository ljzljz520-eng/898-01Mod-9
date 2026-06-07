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
            'maintenance' => '物业维修',
            'conflict' => '邻里矛盾',
            'fee' => '费用公示',
            default => $category,
        };
    }
}

if (!function_exists('circle_type_name')) {
    /**
     * 获取圈层类型的中文名称
     */
    function circle_type_name(string $circleType): string
    {
        return match($circleType) {
            'public' => '公共广场',
            'building' => '楼栋专属',
            'committee' => '业委会',
            'tenant' => '租户专区',
            default => $circleType,
        };
    }
}

if (!function_exists('resident_type_name')) {
    /**
     * 获取住户类型的中文名称
     */
    function resident_type_name(?string $residentType): string
    {
        return match($residentType) {
            'owner' => '业主',
            'tenant' => '租户',
            'committee' => '业委会成员',
            null => '未认证',
            default => $residentType,
        };
    }
}

if (!function_exists('verification_status_name')) {
    /**
     * 获取认证状态的中文名称
     */
    function verification_status_name(string $status): string
    {
        return match($status) {
            'unverified' => '未认证',
            'pending' => '审核中',
            'verified' => '已认证',
            'rejected' => '已拒绝',
            default => $status,
        };
    }
}

if (!function_exists('circle_name')) {
    /**
     * 获取圈层类型的中文名称
     */
    function circle_name(string $type): string
    {
        return match($type) {
            'public' => '公共广场',
            'building' => '楼栋讨论',
            'committee' => '业委会',
            'tenant' => '租户专区',
            default => $type,
        };
    }
}

if (!function_exists('extra_field_name')) {
    /**
     * 获取扩展字段的中文标签
     */
    function extra_field_name(string $field): string
    {
        return match($field) {
            'unit_number' => '单元号',
            'description' => '描述',
            'contact_name' => '联系人',
            'contact_phone' => '联系电话',
            'status' => '状态',
            'reported_at' => '上报时间',
            'assigned_to' => '分配给',
            'resolved_at' => '解决时间',
            'cost' => '费用',
            'title' => '标题',
            'involved_parties' => '涉及方',
            'unit_numbers' => '涉及单元',
            'contact_info' => '联系方式',
            'mediator' => '调解人',
            'resolution' => '解决方案',
            'fee_type' => '费用类型',
            'amount' => '金额',
            'due_date' => '截止日期',
            'payment_method' => '支付方式',
            'paid_at' => '支付时间',
            'receipt_number' => '收据编号',
            'late_fee' => '滞纳金',
            'total_amount' => '总金额',
            default => $field,
        };
    }
}

<?php

$baseUrl = 'http://localhost/api';

function request($method, $path, $data = [], $token = null) {
    global $baseUrl;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body' => json_decode($response, true), 'code' => $httpCode];
}

echo "=== 1. 管理员登录 ===\n";
$r = request('POST', '/auth/login', ['email' => 'admin@forum.com', 'password' => 'password']);
$adminToken = $r['body']['data']['token'];
echo "HTTP: {$r['code']}\n";

echo "\n=== 2. 创建2号楼 ===\n";
$r = request('POST', '/buildings', [
    'name' => '2号楼',
    'community_name' => '阳光花园',
    'total_floors' => 25,
    'total_units' => 100,
], $adminToken);
$building2Id = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 楼栋ID: $building2Id\n";

echo "\n=== 3. 注册业主用户 ===\n";
$r = request('POST', '/auth/register', [
    'username' => 'owner_test_' . time(),
    'email' => 'owner_' . time() . '@test.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$ownerToken = $r['body']['data']['token'];
$ownerId = $r['body']['data']['user']['id'];
echo "HTTP: {$r['code']}, 业主ID: $ownerId\n";

echo "\n=== 4. 注册租户用户 ===\n";
$r = request('POST', '/auth/register', [
    'username' => 'tenant_test_' . time(),
    'email' => 'tenant_' . time() . '@test.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$tenantToken = $r['body']['data']['token'];
$tenantId = $r['body']['data']['user']['id'];
echo "HTTP: {$r['code']}, 租户ID: $tenantId\n";

echo "\n=== 5. 注册业委会用户 ===\n";
$r = request('POST', '/auth/register', [
    'username' => 'committee_test_' . time(),
    'email' => 'committee_' . time() . '@test.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$committeeToken = $r['body']['data']['token'];
$committeeId = $r['body']['data']['user']['id'];
echo "HTTP: {$r['code']}, 业委会ID: $committeeId\n";

echo "\n=== 6. 业主提交认证申请 ===\n";
$r = request('POST', '/auth/verify/apply', [
    'building_id' => $building2Id,
    'unit_number' => '501',
    'resident_type' => 'owner',
    'real_name' => '业主A',
    'id_card' => '110101199001010001',
], $ownerToken);
echo "HTTP: {$r['code']}, 状态: " . ($r['body']['data']['verification_status'] ?? 'N/A') . "\n";

echo "\n=== 7. 租户提交认证申请 ===\n";
$r = request('POST', '/auth/verify/apply', [
    'building_id' => $building2Id,
    'unit_number' => '502',
    'resident_type' => 'tenant',
    'real_name' => '租户B',
    'id_card' => '110101199001010002',
], $tenantToken);
echo "HTTP: {$r['code']}, 状态: " . ($r['body']['data']['verification_status'] ?? 'N/A') . "\n";

echo "\n=== 8. 业委会提交认证申请 ===\n";
$r = request('POST', '/auth/verify/apply', [
    'building_id' => $building2Id,
    'unit_number' => '101',
    'resident_type' => 'committee',
    'real_name' => '委员C',
    'id_card' => '110101199001010003',
], $committeeToken);
echo "HTTP: {$r['code']}, 状态: " . ($r['body']['data']['verification_status'] ?? 'N/A') . "\n";

echo "\n=== 9. 管理员审核所有认证申请 ===\n";
foreach ([$ownerId, $tenantId, $committeeId] as $uid) {
    $r = request('POST', "/auth/verify/$uid/review", ['status' => 'verified'], $adminToken);
    echo "用户$uid: HTTP {$r['code']} - " . ($r['body']['message'] ?? 'N/A') . "\n";
}

echo "\n=== 10. 验证各用户可访问圈层 ===\n";
$r = request('GET', '/auth/me', [], $ownerToken);
echo "业主: " . implode(', ', $r['body']['data']['user']['accessible_circles'] ?? []) . "\n";
$r = request('GET', '/auth/me', [], $tenantToken);
echo "租户: " . implode(', ', $r['body']['data']['user']['accessible_circles'] ?? []) . "\n";
$r = request('GET', '/auth/me', [], $committeeToken);
echo "业委会: " . implode(', ', $r['body']['data']['user']['accessible_circles'] ?? []) . "\n";

echo "\n=== 11. 业主发布公共话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '公共广场测试话题',
    'content' => '这是一个公共广场的测试话题，内容足够长以满足验证要求',
    'category' => 'general',
    'circle_type' => 'public',
], $ownerToken);
$publicTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $publicTopicId\n";
echo "扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== 12. 业主发布楼栋维修话题(物业维修) ===\n";
$r = request('POST', '/topics', [
    'title' => '电梯故障需要维修',
    'content' => '2号楼5单元电梯经常出现故障，按钮损坏需要维修，请尽快处理',
    'category' => 'maintenance',
    'circle_type' => 'building',
    'extra_fields' => [
        'unit_number' => '5单元',
        'description' => '电梯按钮损坏',
        'contact_name' => '业主A',
        'contact_phone' => '13800138001',
        'status' => 'pending',
        'reported_at' => '2026-06-06',
        'assigned_to' => '张师傅',
        'resolved_at' => null,
        'cost' => null,
    ],
], $ownerToken);
$maintTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $maintTopicId\n";
echo "业主可见扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== 13. 租户查看维修话题(应看到较少字段) ===\n";
$r = request('GET', "/topics/$maintTopicId", [], $tenantToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    echo "租户可见扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    $fields = array_keys($r['body']['data']['extra_fields'] ?? []);
    echo "可见字段数: " . count($fields) . "\n";
    echo "是否看到联系电话: " . (in_array('contact_phone', $fields) ? '是' : '否') . "\n";
    echo "是否看到费用: " . (in_array('cost', $fields) ? '是' : '否') . "\n";
} else {
    echo "错误: " . ($r['body']['message'] ?? 'N/A') . "\n";
}

echo "\n=== 14. 业委会查看维修话题(应看到全部字段) ===\n";
$r = request('GET', "/topics/$maintTopicId", [], $committeeToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    echo "业委会可见扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    $fields = array_keys($r['body']['data']['extra_fields'] ?? []);
    echo "可见字段数: " . count($fields) . "\n";
    echo "是否看到联系电话: " . (in_array('contact_phone', $fields) ? '是' : '否') . "\n";
    echo "是否看到费用: " . (in_array('cost', $fields) ? '是' : '否') . "\n";
}

echo "\n=== 15. 未登录用户查看维修话题(应403) ===\n";
$r = request('GET', "/topics/$maintTopicId");
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 16. 业委会发布费用公示话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '2026年5月物业费公示',
    'content' => '本月物业费收支情况公示，请各位业主查阅详细内容',
    'category' => 'fee',
    'circle_type' => 'building',
    'extra_fields' => [
        'fee_type' => 'property_fee',
        'amount' => 2.5,
        'due_date' => '2026-06-30',
        'status' => 'pending',
        'unit_number' => '501',
        'payment_method' => 'bank_transfer',
        'paid_at' => null,
        'receipt_number' => 'RCPT202606001',
        'late_fee' => 0,
        'total_amount' => 250.00,
    ],
], $committeeToken);
$feeTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $feeTopicId\n";

echo "\n=== 17. 业主查看费用公示(全部字段) ===\n";
$r = request('GET', "/topics/$feeTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    echo "业主可见字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== 18. 租户查看费用公示(较少字段) ===\n";
$r = request('GET', "/topics/$feeTopicId", [], $tenantToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    echo "租户可见字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== 19. 业主发布邻里矛盾话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '关于楼道杂物堆放问题',
    'content' => '5楼楼道有杂物堆放，影响通行，请相关业主尽快清理',
    'category' => 'conflict',
    'circle_type' => 'building',
    'extra_fields' => [
        'title' => '楼道杂物',
        'description' => '5楼楼道堆放旧家具',
        'status' => 'reported',
        'reported_at' => '2026-06-06',
        'involved_parties' => '501,502',
        'unit_numbers' => '501,502',
        'contact_info' => '电话:13800138000',
        'mediator' => '业委会王主任',
        'resolution' => null,
        'resolved_at' => null,
    ],
], $ownerToken);
$conflictTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $conflictTopicId\n";

echo "\n=== 20. 业主查看矛盾话题(部分字段) ===\n";
$r = request('GET', "/topics/$conflictTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    $fields = array_keys($r['body']['data']['extra_fields'] ?? []);
    echo "业主可见字段: " . json_encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
    echo "是否看到联系方式: " . (in_array('contact_info', $fields) ? '是' : '否') . "\n";
    echo "是否看到调解人: " . (in_array('mediator', $fields) ? '是' : '否') . "\n";
}

echo "\n=== 21. 业委会查看矛盾话题(全部字段) ===\n";
$r = request('GET', "/topics/$conflictTopicId", [], $committeeToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    $fields = array_keys($r['body']['data']['extra_fields'] ?? []);
    echo "业委会可见字段: " . json_encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
    echo "是否看到联系方式: " . (in_array('contact_info', $fields) ? '是' : '否') . "\n";
    echo "是否看到调解人: " . (in_array('mediator', $fields) ? '是' : '否') . "\n";
}

echo "\n=== 22. 业委会发布业委会专属话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '业委会内部会议通知',
    'content' => '本周六下午2点召开业委会会议，讨论年度预算问题',
    'category' => 'general',
    'circle_type' => 'committee',
], $committeeToken);
$committeeTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $committeeTopicId\n";

echo "\n=== 23. 业主尝试访问业委会话题(应403) ===\n";
$r = request('GET', "/topics/$committeeTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 24. 业委会访问业委会话题(应200) ===\n";
$r = request('GET', "/topics/$committeeTopicId", [], $committeeToken);
echo "HTTP: {$r['code']}\n";

echo "\n=== 25. 发布租户专属话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '租户交流活动',
    'content' => '租户周末组织一次交流活动，欢迎大家参加讨论',
    'category' => 'general',
    'circle_type' => 'tenant',
], $tenantToken);
$tenantTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: $tenantTopicId\n";

echo "\n=== 26. 业主尝试访问租户话题(应403) ===\n";
$r = request('GET', "/topics/$tenantTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 27. 租户访问租户话题(应200) ===\n";
$r = request('GET', "/topics/$tenantTopicId", [], $tenantToken);
echo "HTTP: {$r['code']}\n";

echo "\n=== 28. 业委会访问租户话题(应200，因为业委会可以看租户圈层) ===\n";
$r = request('GET', "/topics/$tenantTopicId", [], $committeeToken);
echo "HTTP: {$r['code']}\n";

echo "\n=== 29. 业主标记搬离 ===\n";
$r = request('POST', '/auth/move-out', ['remark' => '出售房屋'], $ownerToken);
echo "HTTP: {$r['code']}\n";
echo "搬离时间: " . ($r['body']['data']['moved_at'] ?? 'N/A') . "\n";
echo "搬离后可访问: " . implode(', ', $r['body']['data']['accessible_circles'] ?? []) . "\n";

echo "\n=== 30. 搬离后尝试发布楼栋话题(应403) ===\n";
$r = request('POST', '/topics', [
    'title' => '搬离后测试发帖',
    'content' => '这是搬离后尝试发布的测试话题内容，内容足够长',
    'category' => 'general',
    'circle_type' => 'building',
], $ownerToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 31. 搬离后访问公共话题(应200) ===\n";
$r = request('GET', "/topics/$publicTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}\n";
if ($r['code'] == 200) {
    echo "标题: {$r['body']['data']['title']}\n";
}

echo "\n=== 32. 搬离后访问楼栋话题(应403) ===\n";
$r = request('GET', "/topics/$maintTopicId", [], $ownerToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 33. 搬离后查看话题列表(只显示公共话题) ===\n";
$r = request('GET', '/topics', [], $ownerToken);
echo "HTTP: {$r['code']}\n";
echo "用户可访问圈层: " . implode(', ', $r['body']['user_circles'] ?? []) . "\n";
echo "返回话题数: " . count($r['body']['data'] ?? []) . "\n";

echo "\n=== 34. 检查搬离后用户信息 ===\n";
$r = request('GET', '/auth/me', [], $ownerToken);
$u = $r['body']['data']['user'];
echo "HTTP: {$r['code']}\n";
echo "已搬离: " . ($u['is_moved'] ? '是' : '否') . "\n";
echo "搬离时间: " . ($u['moved_at'] ?? 'N/A') . "\n";
echo "楼栋: " . ($u['building_name'] ?? 'N/A') . "\n";

echo "\n=== 35. 查看楼栋信息(含住户数) ===\n";
$r = request('GET', "/buildings/$building2Id");
echo "HTTP: {$r['code']}\n";
echo "楼栋名称: " . ($r['body']['data']['name'] ?? 'N/A') . "\n";
echo "认证住户数: " . ($r['body']['data']['verified_resident_count'] ?? 'N/A') . "\n";

echo "\n========== 测试完成 ==========\n";

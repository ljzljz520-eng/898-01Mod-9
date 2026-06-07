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
echo "HTTP: {$r['code']} ✓\n";

echo "\n=== 2. 注册三个测试用户 ===\n";
$users = [];
foreach (['owner', 'tenant', 'committee'] as $type) {
    $r = request('POST', '/auth/register', [
        'username' => "test_{$type}_" . time() . rand(100, 999),
        'email' => "test_{$type}_" . time() . rand(100, 999) . '@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $users[$type] = [
        'token' => $r['body']['data']['token'],
        'id' => $r['body']['data']['user']['id'],
    ];
    echo "  {$type}: HTTP {$r['code']}, ID: {$users[$type]['id']} ✓\n";
}

echo "\n=== 3. 各用户提交认证申请（使用1号楼） ===\n";
foreach ($users as $type => &$user) {
    $r = request('POST', '/auth/verify/apply', [
        'building_id' => 1,
        'unit_number' => $type === 'owner' ? '101' : ($type === 'tenant' ? '102' : '103'),
        'resident_type' => $type,
        'real_name' => "测试{$type}",
        'id_card' => '11010119900101' . str_pad(array_search($type, ['owner', 'tenant', 'committee']) + 1, 4, '0', STR_PAD_LEFT),
    ], $user['token']);
    echo "  {$type}: HTTP {$r['code']}, 状态: " . ($r['body']['data']['verification_status'] ?? 'N/A') . " ✓\n";
}
unset($user); // 销毁引用，避免后续循环意外修改数组

echo "\n=== 4. 管理员审核认证（提供完整参数） ===\n";
foreach ($users as $type => $user) {
    $r = request('POST', "/auth/verify/{$user['id']}/review", [
        'status' => 'verified',
        'resident_type' => $type,
        'building_id' => 1,
        'unit_number' => $type === 'owner' ? '101' : ($type === 'tenant' ? '102' : '103'),
    ], $adminToken);
    echo "  {$type}: HTTP {$r['code']} - " . ($r['body']['message'] ?? 'N/A') . " ✓\n";
}

echo "\n=== 5. 验证各用户可访问圈层 ===\n";
$circleMap = [
    'owner' => ['public', 'building'],
    'tenant' => ['public', 'building', 'tenant'],
    'committee' => ['public', 'building', 'committee', 'tenant'],
];
foreach ($users as $type => $user) {
    $r = request('GET', '/auth/me', [], $user['token']);
    $circles = $r['body']['data']['user']['accessible_circles'] ?? [];
    $expected = implode(', ', $circleMap[$type]);
    $actual = implode(', ', $circles);
    $pass = $actual === $expected ? '✓' : '✗';
    echo "  {$type}: {$actual} (期望: {$expected}) {$pass}\n";
}

echo "\n=== 6. 业主发布物业维修话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '电梯故障报修',
    'content' => '1号楼1单元10楼电梯按钮损坏，请尽快安排维修人员处理',
    'category' => 'maintenance',
    'circle_type' => 'building',
    'extra_fields' => [
        'unit_number' => '1单元10楼',
        'description' => '电梯按钮损坏',
        'contact_name' => '业主A',
        'contact_phone' => '13800138001',
        'status' => 'pending',
        'reported_at' => '2026-06-06',
        'assigned_to' => '张师傅',
        'cost' => null,
    ],
], $users['owner']['token']);
$maintTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: {$maintTopicId} ✓\n";
$ownerFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  业主可见字段: " . implode(', ', $ownerFields) . "\n";
echo "  包含联系电话: " . (in_array('contact_phone', $ownerFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  包含费用: " . (in_array('cost', $ownerFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 7. 租户查看维修话题（应看到较少字段） ===\n";
$r = request('GET', "/topics/{$maintTopicId}", [], $users['tenant']['token']);
echo "HTTP: {$r['code']} ✓\n";
$tenantFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  租户可见字段: " . implode(', ', $tenantFields) . "\n";
echo "  不包含联系电话: " . (!in_array('contact_phone', $tenantFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  不包含费用: " . (!in_array('cost', $tenantFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 8. 业委会查看维修话题（应看到全部字段） ===\n";
$r = request('GET', "/topics/{$maintTopicId}", [], $users['committee']['token']);
echo "HTTP: {$r['code']} ✓\n";
$committeeFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  业委会可见字段: " . implode(', ', $committeeFields) . "\n";
echo "  包含联系电话: " . (in_array('contact_phone', $committeeFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  包含费用: " . (in_array('cost', $committeeFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 9. 未登录用户查看维修话题（应403） ===\n";
$r = request('GET', "/topics/{$maintTopicId}");
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n=== 10. 业委会发布费用公示话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '2026年6月物业费公示',
    'content' => '本月物业费收支情况公示，请各位业主查阅详细内容',
    'category' => 'fee',
    'circle_type' => 'building',
    'extra_fields' => [
        'fee_type' => 'property_fee',
        'amount' => 2.5,
        'due_date' => '2026-06-30',
        'status' => 'pending',
        'unit_number' => '101',
        'payment_method' => 'bank_transfer',
        'total_amount' => 250.00,
        'late_fee' => 0,
    ],
], $users['committee']['token']);
$feeTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: {$feeTopicId} ✓\n";

echo "\n=== 11. 业主查看费用公示（全部字段） ===\n";
$r = request('GET', "/topics/{$feeTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']} ✓\n";
$ownerFeeFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  业主可见字段: " . implode(', ', $ownerFeeFields) . "\n";
echo "  包含支付方式: " . (in_array('payment_method', $ownerFeeFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  包含总金额: " . (in_array('total_amount', $ownerFeeFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 12. 租户查看费用公示（较少字段） ===\n";
$r = request('GET', "/topics/{$feeTopicId}", [], $users['tenant']['token']);
echo "HTTP: {$r['code']} ✓\n";
$tenantFeeFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  租户可见字段: " . implode(', ', $tenantFeeFields) . "\n";
echo "  不包含支付方式: " . (!in_array('payment_method', $tenantFeeFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  不包含总金额: " . (!in_array('total_amount', $tenantFeeFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 13. 业主发布邻里矛盾话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '关于楼道杂物堆放问题',
    'content' => '1楼楼道有杂物堆放，影响通行，请相关业主尽快清理',
    'category' => 'conflict',
    'circle_type' => 'building',
    'extra_fields' => [
        'title' => '楼道杂物堆放',
        'description' => '1楼楼道堆放旧家具',
        'status' => 'reported',
        'reported_at' => '2026-06-06',
        'involved_parties' => '101,102',
        'unit_numbers' => '101,102',
        'contact_info' => '电话:13800138000',
        'mediator' => '业委会王主任',
    ],
], $users['owner']['token']);
$conflictTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: {$conflictTopicId} ✓\n";

echo "\n=== 14. 业主查看矛盾话题（部分字段） ===\n";
$r = request('GET', "/topics/{$conflictTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']} ✓\n";
$ownerConflictFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  业主可见字段: " . implode(', ', $ownerConflictFields) . "\n";
echo "  不包含联系方式: " . (!in_array('contact_info', $ownerConflictFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  不包含调解人: " . (!in_array('mediator', $ownerConflictFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 15. 业委会查看矛盾话题（全部字段） ===\n";
$r = request('GET', "/topics/{$conflictTopicId}", [], $users['committee']['token']);
echo "HTTP: {$r['code']} ✓\n";
$committeeConflictFields = array_keys($r['body']['data']['extra_fields'] ?? []);
echo "  业委会可见字段: " . implode(', ', $committeeConflictFields) . "\n";
echo "  包含联系方式: " . (in_array('contact_info', $committeeConflictFields) ? '是 ✓' : '否 ✗') . "\n";
echo "  包含调解人: " . (in_array('mediator', $committeeConflictFields) ? '是 ✓' : '否 ✗') . "\n";

echo "\n=== 16. 业委会发布业委会专属话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '业委会内部会议通知',
    'content' => '本周六下午2点召开业委会会议，讨论年度预算和维修基金使用',
    'category' => 'general',
    'circle_type' => 'committee',
], $users['committee']['token']);
$committeeTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: {$committeeTopicId} ✓\n";

echo "\n=== 17. 业主尝试访问业委会话题（应403） ===\n";
$r = request('GET', "/topics/{$committeeTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n=== 18. 业委会访问业委会话题（应200） ===\n";
$r = request('GET', "/topics/{$committeeTopicId}", [], $users['committee']['token']);
echo "HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";

echo "\n=== 19. 发布租户专属话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '租户交流活动',
    'content' => '租户周末组织一次社区交流活动，欢迎大家参加讨论',
    'category' => 'general',
    'circle_type' => 'tenant',
], $users['tenant']['token']);
$tenantTopicId = $r['body']['data']['id'];
echo "HTTP: {$r['code']}, 话题ID: {$tenantTopicId} ✓\n";

echo "\n=== 20. 业主尝试访问租户话题（应403） ===\n";
$r = request('GET', "/topics/{$tenantTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n=== 21. 租户访问租户话题（应200） ===\n";
$r = request('GET', "/topics/{$tenantTopicId}", [], $users['tenant']['token']);
echo "HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";

echo "\n=== 22. 业委会访问租户话题（应200） ===\n";
$r = request('GET', "/topics/{$tenantTopicId}", [], $users['committee']['token']);
echo "HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";

echo "\n=== 23. 业主标记搬离 ===\n";
$r = request('POST', '/auth/move-out', ['remark' => '出售房屋，已搬离'], $users['owner']['token']);
echo "HTTP: {$r['code']} ✓\n";
echo "  搬离时间: " . ($r['body']['data']['moved_at'] ?? 'N/A') . "\n";
echo "  搬离后可访问: " . implode(', ', $r['body']['data']['accessible_circles'] ?? []) . "\n";

echo "\n=== 24. 搬离后尝试发布楼栋话题（应403） ===\n";
$r = request('POST', '/topics', [
    'title' => '搬离后测试发帖',
    'content' => '这是搬离后尝试发布的测试话题内容，内容足够长',
    'category' => 'general',
    'circle_type' => 'building',
], $users['owner']['token']);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n=== 25. 搬离后访问公共话题（应200） ===\n";
$r = request('POST', '/topics', [
    'title' => '公共测试话题',
    'content' => '这是一个公共测试话题，内容足够长以满足验证要求',
    'category' => 'general',
    'circle_type' => 'public',
], $users['committee']['token']);
$publicTopicId = $r['body']['data']['id'];

$r = request('GET', "/topics/{$publicTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
if ($r['code'] == 200) {
    echo "  标题: {$r['body']['data']['title']}\n";
}

echo "\n=== 26. 搬离后访问楼栋话题（应403） ===\n";
$r = request('GET', "/topics/{$maintTopicId}", [], $users['owner']['token']);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n=== 27. 搬离后查看话题列表（只显示公共话题） ===\n";
$r = request('GET', '/topics', [], $users['owner']['token']);
echo "HTTP: {$r['code']} ✓\n";
echo "  用户可访问圈层: " . implode(', ', $r['body']['user_circles'] ?? []) . "\n";
echo "  返回话题数: " . count($r['body']['data'] ?? []) . "\n";

echo "\n=== 28. 检查搬离后用户信息 ===\n";
$r = request('GET', '/auth/me', [], $users['owner']['token']);
$u = $r['body']['data']['user'];
echo "HTTP: {$r['code']} ✓\n";
echo "  已搬离: " . ($u['is_moved'] ? '是 ✓' : '否 ✗') . "\n";
echo "  搬离时间: " . ($u['moved_at'] ?? 'N/A') . "\n";
echo "  可访问圈层: " . implode(', ', $u['accessible_circles'] ?? []) . "\n";

echo "\n=== 29. 查看楼栋信息 ===\n";
$r = request('GET', '/buildings/1');
echo "HTTP: {$r['code']} ✓\n";
echo "  楼栋名称: " . ($r['body']['data']['name'] ?? 'N/A') . "\n";
echo "  认证住户数: " . ($r['body']['data']['verified_resident_count'] ?? 'N/A') . "\n";

echo "\n=== 30. 测试按圈层过滤话题列表 ===\n";
$r = request('GET', '/topics?circle_type=building', [], $users['committee']['token']);
echo "HTTP: {$r['code']} ✓\n";
echo "  楼栋话题数: " . count($r['body']['data'] ?? []) . "\n";
echo "  用户圈层: " . implode(', ', $r['body']['user_circles'] ?? []) . "\n";

echo "\n=== 31. 搬离用户尝试访问楼栋列表（应403） ===\n";
$r = request('GET', '/topics?circle_type=building', [], $users['owner']['token']);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . " " . ($r['code'] == 403 ? '✓' : '✗') . "\n";

echo "\n========== 测试完成 ==========\n";

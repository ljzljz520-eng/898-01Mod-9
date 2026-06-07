<?php

$baseUrl = 'http://localhost/api';

function request($method, $path, $data = [], $token = null) {
    global $baseUrl;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['body' => json_decode($response, true), 'code' => $httpCode];
}

echo "=== 1. 管理员登录 ===\n";
$r = request('POST', '/auth/login', ['email' => 'admin@forum.com', 'password' => 'password']);
$adminToken = $r['body']['data']['token'] ?? null;
echo "HTTP: {$r['code']}, Token: " . substr($adminToken, 0, 20) . "...\n";

echo "\n=== 2. 创建楼栋 ===\n";
$r = request('POST', '/buildings', [
    'name' => '1号楼',
    'community_name' => '阳光花园',
    'total_floors' => 30,
    'total_units' => 120,
], $adminToken);
$buildingId = $r['body']['data']['id'] ?? null;
echo "HTTP: {$r['code']}, 楼栋ID: $buildingId, 名称: " . ($r['body']['data']['name'] ?? 'N/A') . "\n";

echo "\n=== 3. 获取楼栋列表 ===";
$r = request('GET', '/buildings');
echo "\nHTTP: {$r['code']}, 楼栋数: " . count($r['body']['data'] ?? []) . "\n";

echo "\n=== 4. 注册新业主 ===\n";
$r = request('POST', '/auth/register', [
    'username' => 'test_owner_' . time(),
    'email' => 'owner_' . time() . '@test.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$userToken = $r['body']['data']['token'] ?? null;
$userId = $r['body']['data']['user']['id'] ?? null;
echo "HTTP: {$r['code']}, 用户ID: $userId, Token: " . substr($userToken, 0, 20) . "...\n";

echo "\n=== 5. 提交认证申请 ===\n";
$r = request('POST', '/auth/verify/apply', [
    'building_id' => $buildingId,
    'unit_number' => '1001',
    'resident_type' => 'owner',
    'real_name' => '张三',
    'id_card' => '110101199001011234',
], $userToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";
echo "认证状态: " . ($r['body']['data']['verification_status'] ?? 'N/A') . "\n";

echo "\n=== 6. 管理员审核认证 ===\n";
$r = request('POST', "/auth/verify/$userId/review", [
    'status' => 'verified',
], $adminToken);
echo "HTTP: {$r['code']}, 消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 7. 查看认证后用户信息 ===\n";
$r = request('GET', '/auth/me', [], $userToken);
$user = $r['body']['data']['user'] ?? [];
echo "HTTP: {$r['code']}\n";
echo "认证状态: " . ($user['verification_status'] ?? 'N/A') . "\n";
echo "住户类型: " . ($user['resident_type'] ?? 'N/A') . "\n";
echo "楼栋ID: " . ($user['building_id'] ?? 'N/A') . "\n";
echo "房间号: " . ($user['unit_number'] ?? 'N/A') . "\n";
echo "已搬离: " . ($user['is_moved'] ? '是' : '否') . "\n";
echo "可访问圈层: " . implode(', ', $user['accessible_circles'] ?? []) . "\n";

echo "\n=== 8. 发布楼栋专属话题（物业维修） ===\n";
$r = request('POST', '/topics', [
    'title' => '电梯故障报修',
    'content' => '1单元10楼电梯按钮损坏，请尽快维修',
    'category' => 'maintenance',
    'circle_type' => 'building',
    'extra_fields' => [
        'unit_number' => '1单元10楼',
        'description' => '电梯按钮损坏',
        'contact_name' => '张三',
        'contact_phone' => '13800138000',
        'status' => 'pending',
        'reported_at' => '2026-06-06',
        'cost' => 500,
    ],
], $userToken);
$topicId = $r['body']['data']['id'] ?? null;
echo "HTTP: {$r['code']}, 话题ID: $topicId\n";
echo "消息: " . ($r['body']['message'] ?? 'N/A') . "\n";
echo "扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== 9. 未登录用户尝试访问楼栋话题 ===\n";
$r = request('GET', "/topics/$topicId");
echo "HTTP: {$r['code']}\n";
echo "消息: " . ($r['body']['message'] ?? 'N/A') . "\n";
echo "圈层类型: " . ($r['body']['circle_type'] ?? 'N/A') . "\n";

echo "\n=== 10. 认证业主访问楼栋话题 ===\n";
$r = request('GET', "/topics/$topicId", [], $userToken);
echo "HTTP: {$r['code']}\n";
echo "话题标题: " . ($r['body']['data']['title'] ?? 'N/A') . "\n";
echo "可见扩展字段: " . json_encode($r['body']['data']['extra_fields'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== 11. 业主标记搬离 ===\n";
$r = request('POST', '/auth/move-out', [
    'remark' => '出售房屋，已搬离',
], $userToken);
echo "HTTP: {$r['code']}\n";
echo "消息: " . ($r['body']['message'] ?? 'N/A') . "\n";
echo "搬离时间: " . ($r['body']['data']['moved_at'] ?? 'N/A') . "\n";
echo "搬离后可访问圈层: " . implode(', ', $r['body']['data']['accessible_circles'] ?? []) . "\n";

echo "\n=== 12. 搬离后尝试发布楼栋话题 ===\n";
$r = request('POST', '/topics', [
    'title' => '测试搬离后发帖',
    'content' => '测试内容',
    'category' => 'general',
    'circle_type' => 'building',
], $userToken);
echo "HTTP: {$r['code']}\n";
echo "消息: " . ($r['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 13. 搬离后查看用户信息 ===\n";
$r = request('GET', '/auth/me', [], $userToken);
$user = $r['body']['data']['user'] ?? [];
echo "HTTP: {$r['code']}\n";
echo "已搬离: " . ($user['is_moved'] ? '是' : '否') . "\n";
echo "可访问圈层: " . implode(', ', $user['accessible_circles'] ?? []) . "\n";

echo "\n=== 14. 搬离后查看话题列表（历史帖子保留） ===\n";
$r = request('GET', '/topics?circle_type=public', [], $userToken);
echo "HTTP: {$r['code']}\n";
echo "返回话题数: " . count($r['body']['data'] ?? []) . "\n";
echo "用户圈层: " . implode(', ', $r['body']['user_circles'] ?? []) . "\n";

echo "\n=== 测试完成 ===\n";

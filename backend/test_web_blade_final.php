<?php

$baseUrl = 'http://localhost:80';

function requestWeb($method, $path, $data = [], $cookies = [], $addCsrf = true) {
    global $baseUrl;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if (!empty($cookies)) {
        $cookieStr = implode('; ', array_map(function($k, $v) { return "$k=$v"; }, array_keys($cookies), $cookies));
        curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
    }
    
    if ($addCsrf && $method !== 'GET' && isset($cookies['XSRF-TOKEN'])) {
        $data['_token'] = $cookies['XSRF-TOKEN'];
    }
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $headers, $matches);
    $newCookies = [];
    foreach ($matches[1] as $i => $name) {
        $newCookies[$name] = urldecode($matches[2][$i]);
    }
    
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $body, $matches)) {
        $newCookies['_token'] = $matches[1];
    }
    
    curl_close($ch);
    return ['body' => $body, 'code' => $httpCode, 'cookies' => $newCookies];
}

echo "=== Blade 前台功能测试 ===\n\n";

echo "1. 话题列表页面（未登录）\n";
$r = requestWeb('GET', '/topics', [], [], false);
$allCookies = $r['cookies'];
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'最新主题': " . (strpos($r['body'], '最新主题') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'圈层'过滤器: " . (strpos($r['body'], '全部圈层') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'楼栋'导航: " . (strpos($r['body'], '楼栋') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   未登录只显示公共话题: " . (strpos($r['body'], 'public') !== false || strpos($r['body'], '公共') !== false ? '可能 ✓' : '需要验证') . "\n";

echo "\n2. 登录页面\n";
$r = requestWeb('GET', '/login', [], $allCookies, false);
$allCookies = array_merge($allCookies, $r['cookies']);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'登录': " . (strpos($r['body'], '登录') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n3. 登录管理员账户\n";
$csrfToken = $allCookies['_token'] ?? ($allCookies['XSRF-TOKEN'] ?? '');
$r = requestWeb('POST', '/login', [
    'email' => 'admin@forum.com', 
    'password' => 'password',
    '_token' => $csrfToken,
], $allCookies, false);
$adminCookies = array_merge($allCookies, $r['cookies']);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   登录成功（包含'最新主题'或'讨论'）: " . (strpos($r['body'], '最新主题') !== false || strpos($r['body'], '讨论') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n4. 个人资料页面（管理员登录）\n";
$r = requestWeb('GET', '/profile', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'个人资料': " . (strpos($r['body'], '个人资料') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'可访问圈层': " . (strpos($r['body'], '可访问圈层') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'认证状态': " . (strpos($r['body'], '认证状态') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n5. 楼栋列表页面\n";
$r = requestWeb('GET', '/buildings', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'楼栋列表': " . (strpos($r['body'], '楼栋列表') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'1号楼': " . (strpos($r['body'], '1号楼') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n6. 楼栋详情页面\n";
$r = requestWeb('GET', '/buildings/1', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'1号楼': " . (strpos($r['body'], '1号楼') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'已认证居民': " . (strpos($r['body'], '已认证居民') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n7. 认证审核列表（管理员）\n";
$r = requestWeb('GET', '/verification-list', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'认证审核': " . (strpos($r['body'], '认证审核') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n8. 发布话题页面（管理员登录）\n";
$r = requestWeb('GET', '/topics/create', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'发布主题': " . (strpos($r['body'], '发布主题') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'圈层'选择器: " . (strpos($r['body'], '圈层') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'业委会'选项: " . (strpos($r['body'], '业委会') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'楼栋讨论'选项: " . (strpos($r['body'], '楼栋讨论') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n9. 话题详情页面（公共话题）\n";
$r = requestWeb('GET', '/topics/1', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含话题内容: " . (strpos($r['body'], 'public') !== false || strpos($r['body'], '讨论') !== false || strpos($r['body'], '主题') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n10. 话题详情页面（楼栋圈层话题，测试字段过滤）\n";
$r = requestWeb('GET', '/topics/52', [], $adminCookies, false);  // 电梯故障报修，maintenance类型
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'详细信息': " . (strpos($r['body'], '详细信息') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'单元号' (管理员可见): " . (strpos($r['body'], '单元号') !== false || strpos($r['body'], 'unit_number') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'联系电话' (管理员可见): " . (strpos($r['body'], '联系电话') !== false || strpos($r['body'], 'contact_phone') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n11. 导航栏验证\n";
$r = requestWeb('GET', '/topics', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   导航栏包含'楼栋': " . (strpos($r['body'], '楼栋') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'讨论': " . (strpos($r['body'], '讨论') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'知识库': " . (strpos($r['body'], '知识库') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'认证审核': " . (strpos($r['body'], '认证审核') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'已认证'徽章: " . (strpos($r['body'], '已认证') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n12. 测试圈层过滤功能\n";
$r = requestWeb('GET', '/topics?circle_type=building', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   显示楼栋圈层话题: " . (strpos($r['body'], '电梯故障') !== false || strpos($r['body'], '业主大会') !== false || strpos($r['body'], 'circle_type') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n13. 测试认证申请页面\n";
$r = requestWeb('GET', '/verify-apply', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'业主认证申请': " . (strpos($r['body'], '认证申请') !== false || strpos($r['body'], '业主认证') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'住户类型'选择: " . (strpos($r['body'], '住户类型') !== false || strpos($r['body'], 'resident_type') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n14. 测试搬离页面\n";
$r = requestWeb('GET', '/move-out', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'搬离确认': " . (strpos($r['body'], '搬离') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n========== 测试完成 ==========\n";

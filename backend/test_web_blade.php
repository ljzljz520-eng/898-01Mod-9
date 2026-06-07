<?php

$baseUrl = 'http://localhost';

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

echo "=== Blade 前台测试 ===\n\n";

echo "1. 获取 CSRF token（访问首页）\n";
$r = requestWeb('GET', '/topics', [], [], false);
$allCookies = $r['cookies'];
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'最新主题': " . (strpos($r['body'], '最新主题') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'圈层': " . (strpos($r['body'], '圈层') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   Cookie数: " . count($allCookies) . "\n";

echo "\n2. 测试登录页面\n";
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
echo "   登录成功（重定向到首页）: " . (strpos($r['body'], '最新主题') !== false || strpos($r['body'], '讨论') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   Cookie数: " . count($adminCookies) . "\n";

echo "\n4. 测试个人资料页面（管理员登录）\n";
$r = requestWeb('GET', '/profile', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'个人资料': " . (strpos($r['body'], '个人资料') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'可访问圈层': " . (strpos($r['body'], '可访问圈层') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n5. 测试楼栋列表页面\n";
$r = requestWeb('GET', '/buildings', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'楼栋列表': " . (strpos($r['body'], '楼栋列表') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'1号楼': " . (strpos($r['body'], '1号楼') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n6. 测试楼栋详情页面\n";
$r = requestWeb('GET', '/buildings/1', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'1号楼': " . (strpos($r['body'], '1号楼') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'已认证居民': " . (strpos($r['body'], '已认证居民') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n7. 测试认证审核列表（管理员）\n";
$r = requestWeb('GET', '/verification-list', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'认证审核': " . (strpos($r['body'], '认证审核') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'待审核': " . (strpos($r['body'], '待审核') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n8. 测试发布话题页面（管理员登录）\n";
$r = requestWeb('GET', '/topics/create', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含'发布主题': " . (strpos($r['body'], '发布主题') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'圈层': " . (strpos($r['body'], '圈层') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'业委会'选项: " . (strpos($r['body'], '业委会') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n9. 测试话题详情页面（公共话题）\n";
$r = requestWeb('GET', '/topics/68', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   包含话题内容: " . (strpos($r['body'], '电梯故障报修') !== false || strpos($r['body'], '讨论') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   包含'详细信息': " . (strpos($r['body'], '详细信息') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n10. 验证导航栏包含楼栋链接\n";
$r = requestWeb('GET', '/topics', [], $adminCookies, false);
echo "   HTTP: {$r['code']} " . ($r['code'] == 200 ? '✓' : '✗') . "\n";
echo "   导航栏包含'楼栋': " . (strpos($r['body'], '楼栋') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'讨论': " . (strpos($r['body'], '讨论') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'知识库': " . (strpos($r['body'], '知识库') !== false ? '是 ✓' : '否 ✗') . "\n";
echo "   导航栏包含'认证审核': " . (strpos($r['body'], '认证审核') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n11. 验证用户状态徽章显示\n";
echo "   包含'已认证'徽章: " . (strpos($r['body'], '已认证') !== false ? '是 ✓' : '否 ✗') . "\n";

echo "\n========== 测试完成 ==========\n";

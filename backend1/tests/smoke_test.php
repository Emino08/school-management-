<?php

// Simple smoke test script for SABITECK backend running on http://localhost:8080
// Usage: php backend1/tests/smoke_test.php

function call_api($method, $url, $token = null, $body = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    $headers = [
        'Accept: application/json'
    ];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if (!is_null($body)) {
        $payload = is_string($body) ? $body : json_encode($body);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, $resp, $err];
}

$base = 'http://localhost:8080/api';

echo "=== SABITECK API Smoke Test ===\n";

// 1) Health
[$code, $resp] = call_api('GET', "$base/health");
echo "Health: HTTP $code\n";
echo "$resp\n\n";

// 2) Login as admin (must be seeded)
$adminEmail = 'koromaemmanuel66@gmail.com';
$adminPass = '11111111';
[$code, $resp] = call_api('POST', "$base/admin/login", null, ['email' => $adminEmail, 'password' => $adminPass]);
echo "Admin Login: HTTP $code\n";
echo "$resp\n\n";
$data = json_decode($resp, true);
if (!($data['success'] ?? false)) {
    echo "Login failed; aborting smoke tests.\n";
    exit(1);
}
$token = $data['token'] ?? null;
if (!$token) {
    echo "No token returned; aborting smoke tests.\n";
    exit(1);
}

// 3) Admin endpoints
$checks = [
    ['GET', '/user-management/stats'],
    ['GET', '/user-management/users?user_type=all'],
    ['GET', '/admin/notifications'],
    ['GET', '/notices'],
    ['GET', '/complaints'],
    ['GET', '/admin/reports/overview'],
    ['GET', '/admin/settings'],
    ['GET', '/admin/activity-logs'],
    ['GET', '/teachers'],
    ['GET', '/academic-years'],
];

foreach ($checks as [$method, $path]) {
    [$code, $resp, $err] = call_api($method, $base . $path, $token);
    $ok = $code >= 200 && $code < 300;
    $body = json_decode($resp, true);
    $success = is_array($body) ? ($body['success'] ?? $ok) : $ok;
    echo sprintf("%s %s: HTTP %d success=%s\n", $method, $path, $code, $success ? 'true' : 'false');
    if (!$success) {
        echo "Response: $resp\n";
    }
}

echo "\nSmoke tests completed.\n";

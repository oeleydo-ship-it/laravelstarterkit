<?php
// Test login flow via HTTP
$baseUrl = 'http://localhost/laravelstarterkit/public';
$cookieJar = tempnam(sys_get_temp_dir(), 'cookie');

// Step 1: GET login page to get CSRF token
$ch = curl_init($baseUrl . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_FOLLOWLOCATION => false,
]);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$token = $m[1] ?? '';
echo "CSRF token: " . ($token ? substr($token, 0, 10) . '...' : 'NOT FOUND') . "\n";

// Step 2: POST login
$ch = curl_init($baseUrl . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'email' => 'owner@demo.com',
        'password' => 'password',
    ]),
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "Login HTTP code: $httpCode\n";
echo "Redirect URL: $redirectUrl\n";

// Step 3: Follow redirect to dashboard
if ($httpCode >= 300 && $httpCode < 400) {
    $target = $redirectUrl ?: $baseUrl . '/dashboard';
    echo "Following redirect to: $target\n";

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HEADER => false,
    ]);
    $dashHtml = curl_exec($ch);
    $dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    echo "Dashboard HTTP code: $dashCode\n";
    echo "Final URL: $finalUrl\n";

    preg_match('/<title>(.*?)<\/title>/s', $dashHtml, $tm);
    echo "Page title: " . trim($tm[1] ?? 'N/A') . "\n";

    if (strpos($dashHtml, 'Whoops') !== false || strpos($dashHtml, 'exception') !== false) {
        preg_match('/class="exception-message[^"]*"[^>]*>\s*(.*?)\s*<\/div>/s', $dashHtml, $em);
        echo "Exception: " . ($em[1] ?? 'see below') . "\n";
        // Print first 3000 chars
        echo "\n--- Response (first 3000 chars) ---\n";
        echo substr(strip_tags($dashHtml), 0, 3000) . "\n";
    } elseif (strpos($dashHtml, 'Dashboard') !== false || strpos($dashHtml, 'Total Users') !== false) {
        echo "Dashboard loaded OK!\n";
    } else {
        echo "\n--- Response (first 2000 chars) ---\n";
        echo substr(strip_tags($dashHtml), 0, 2000) . "\n";
    }
}

unlink($cookieJar);

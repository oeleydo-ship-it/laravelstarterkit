<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Simulate a GET /dashboard request as logged-in user
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// First, get a session with the user logged in
$user = \App\Models\User::withoutGlobalScopes()->where('email', 'owner@demo.com')->first();

// Create a request to /dashboard
$request = Illuminate\Http\Request::create('/dashboard', 'GET');

// Manually set auth
$app->make('auth')->guard()->setUser($user);

try {
    $response = $kernel->handle($request);
    echo "HTTP Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    
    // Check for error indicators
    if (strpos($content, 'Whoops') !== false || strpos($content, 'Exception') !== false || strpos($content, 'Error') !== false) {
        // Extract error message
        preg_match('/<title>(.*?)<\/title>/s', $content, $titleMatch);
        echo "Page title: " . ($titleMatch[1] ?? 'N/A') . "\n";
        
        // Look for exception message
        preg_match('/class="exception-message[^"]*"[^>]*>(.*?)<\/div>/s', $content, $exMatch);
        echo "Exception: " . ($exMatch[1] ?? 'N/A') . "\n";
        
        echo "\nFirst 2000 chars of response:\n";
        echo substr($content, 0, 2000) . "\n";
    } else {
        preg_match('/<title>(.*?)<\/title>/s', $content, $titleMatch);
        echo "Page title: " . ($titleMatch[1] ?? 'N/A') . "\n";
        echo "Dashboard loaded successfully (content length: " . strlen($content) . " bytes)\n";
    }
} catch (Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

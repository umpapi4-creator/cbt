<?php

// Exercise the Vercel entry point without database access or remote services.
foreach ([
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('x', 32)),
    'APP_URL' => 'http://localhost', 'SESSION_DRIVER' => 'array',
    'CACHE_DRIVER' => 'array', 'LOG_CHANNEL' => 'stderr',
    'SUPABASE_STORAGE_ENABLED' => 'false',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SCRIPT_NAME'] = '/api/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../api/index.php';
ob_start();
require __DIR__.'/../api/index.php';
$html = ob_get_clean();
if (http_response_code() !== 200 || !str_contains($html, 'password')) {
    throw new RuntimeException('Vercel login request failed.');
}
foreach (['APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE'] as $name) {
    if (!is_file($_ENV[$name]) || !is_writable($_ENV[$name])) {
        throw new RuntimeException('Runtime manifest missing: '.$name);
    }
}
echo "Vercel bootstrap and login passed (no database access).\n";

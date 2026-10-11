<?php
function bookbang_app_base(): string
{
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $appPath = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');

    if (in_array(basename($appPath), ['cart', 'checkout', 'orders', 'user', 'auth', 'account'], true)) {
        $appPath = rtrim(str_replace('\\', '/', dirname($appPath)), '/');
    }

    return $appPath === '' ? '' : $appPath;
}

function bookbang_url(string $path = ''): string
{
    return bookbang_app_base() . '/' . ltrim($path, '/');
}
?>

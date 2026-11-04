<?php

// Writable, disposable files only. Sessions and uploads live in Supabase.
$runtimeStorage = sys_get_temp_dir().'/cbt-campus';
foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $directory) {
    $path = $runtimeStorage.'/'.$directory;
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to initialize runtime storage.');
    }
}
$_ENV['LARAVEL_STORAGE_PATH'] = $runtimeStorage;
$_SERVER['LARAVEL_STORAGE_PATH'] = $runtimeStorage;
putenv('LARAVEL_STORAGE_PATH='.$runtimeStorage);
putenv('VIEW_COMPILED_PATH='.$runtimeStorage.'/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = $runtimeStorage.'/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = $runtimeStorage.'/framework/views';

require __DIR__.'/../public/index.php';

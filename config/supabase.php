<?php

return [
    'storage_enabled' => env('SUPABASE_STORAGE_ENABLED', false),
    'url' => env('SUPABASE_URL'),
    'key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    'bucket' => env('SUPABASE_STORAGE_BUCKET', 'cbt-uploads'),
];

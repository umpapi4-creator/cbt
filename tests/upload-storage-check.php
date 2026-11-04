<?php

// No database access. Run: php tests/upload-storage-check.php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

function check($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

config(['supabase.storage_enabled' => true, 'supabase.url' => 'https://example.supabase.co',
    'supabase.key' => 'server-test-key', 'supabase.bucket' => 'cbt-uploads']);
Http::preventStrayRequests();
Http::fake([
    '*/object/sign/*' => Http::response(['signedURL' => '/object/sign/cbt-uploads/questions/a.png?token=test']),
    'https://example.supabase.co/storage/v1/object/*' => Http::response(['Key' => 'test']),
]);
$file = UploadedFile::fake()->create('photo.png', 1, 'image/png');
$path = UploadStorage::store($file, 'profile_photos');
check(str_starts_with($path, 'profile_photos/'), 'Upload path incorrect');
Http::assertSent(fn ($request) => $request->method() === 'POST'
    && str_contains($request->url(), '/object/cbt-uploads/profile_photos/')
    && $request->hasHeader('Authorization', 'Bearer server-test-key'));
check(UploadStorage::url('questions/a.png') === 'https://example.supabase.co/storage/v1/object/sign/cbt-uploads/questions/a.png?token=test', 'Signed URL incorrect');
check(UploadStorage::delete($path), 'Delete failed');
Http::assertSent(fn ($request) => $request->method() === 'DELETE' && $request['prefixes'] === [$path]);
Http::swap(new Illuminate\Http\Client\Factory());
Http::preventStrayRequests();
Http::fake(['*' => Http::response(['message' => 'denied'], 403)]);
try {
    UploadStorage::store($file, 'questions');
    throw new RuntimeException('Upload failure was ignored');
} catch (Illuminate\Http\Client\RequestException $expected) {}
config(['supabase.storage_enabled' => false]);
check(str_ends_with(UploadStorage::url('questions/a.png'), '/storage/questions/a.png'), 'Local storage URL changed');
echo "Upload storage checks passed (no database access).\n";

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        if (!config('supabase.storage_enabled')) {
            $path = $file->store($directory, 'public');
            if ($path === false) {
                throw new RuntimeException('Unable to save upload.');
            }
            return $path;
        }

        $path = $directory.'/'.$file->hashName();
        self::client()->withBody(file_get_contents($file->getRealPath()), $file->getMimeType() ?: 'application/octet-stream')
            ->post(self::endpoint('/object/').self::objectPath($path))->throw();
        return $path;
    }

    public static function delete($paths): bool
    {
        if (!config('supabase.storage_enabled')) {
            return Storage::disk('public')->delete($paths);
        }
        self::client()->delete(self::endpoint('/object/').rawurlencode(config('supabase.bucket')), [
            'prefixes' => array_values((array) $paths),
        ])->throw();
        return true;
    }

    public static function url(string $path): string
    {
        if (!config('supabase.storage_enabled')) {
            return Storage::disk('public')->url($path);
        }

        // The bucket stays private, including question images. Do not expose the key.
        $signed = self::client()->post(self::endpoint('/object/sign/').self::objectPath($path), [
            'expiresIn' => 86400,
        ])->throw()->json('signedURL');
        if (!is_string($signed) || !str_starts_with($signed, '/object/sign/')) {
            throw new RuntimeException('Unable to sign upload URL.');
        }
        return rtrim(config('supabase.url'), '/').'/storage/v1'.$signed;
    }

    private static function client()
    {
        if (!config('supabase.url') || !config('supabase.key') || !config('supabase.bucket')) {
            throw new RuntimeException('Supabase Storage configuration is incomplete.');
        }
        return Http::withToken(config('supabase.key'))->withHeaders(['apikey' => config('supabase.key')])
            ->connectTimeout(5)->timeout(20);
    }

    private static function endpoint(string $suffix): string
    {
        return rtrim(config('supabase.url'), '/').'/storage/v1'.$suffix;
    }

    private static function objectPath(string $path): string
    {
        return rawurlencode(config('supabase.bucket')).'/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}

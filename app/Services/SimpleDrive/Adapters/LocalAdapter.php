<?php
namespace App\Services\SimpleDrive\Adapters;

use App\Services\SimpleDrive\StorageAdapterInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class LocalAdapter implements StorageAdapterInterface
{
    protected $base;

    public function __construct()
    {
        $this->base = config('simpledrive.local.path');
        if (!File::exists($this->base)) {
            File::makeDirectory($this->base, 0755, true);
        }
    }

    public function save(string $id, string $binary): string
    {
        // store files using id as filename safe-ified
        $name = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '_', $id);
        // allow nested paths if id contains slash
        $full = rtrim($this->base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
        $dir = dirname($full);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($full, $binary);
        return $full;
    }

    public function get(string $storagePath): string
    {
        if (!File::exists($storagePath)) {
            throw new \RuntimeException("Not found");
        }
        return File::get($storagePath);
    }

    public function delete(string $storagePath): void
    {
        if (File::exists($storagePath)) {
            File::delete($storagePath);
        }
    }
}

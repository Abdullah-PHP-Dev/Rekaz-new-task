<?php
namespace App\Services\SimpleDrive;

use App\Services\SimpleDrive\Adapters\LocalAdapter;
use App\Services\SimpleDrive\Adapters\DatabaseAdapter;
use App\Services\SimpleDrive\Adapters\S3Adapter;
use App\Services\SimpleDrive\Adapters\FtpAdapter;

class AdapterFactory
{
    public static function make(): StorageAdapterInterface
    {
        $backend = config('simpledrive.default');
    
        return match ($backend) {
            'local' => new LocalAdapter(),
            'db' => new DatabaseAdapter(),
            's3' => new S3Adapter(),
            'ftp' => new FtpAdapter(),
            default => new LocalAdapter()
        };
    }
}

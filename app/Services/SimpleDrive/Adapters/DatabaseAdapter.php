<?php
namespace App\Services\SimpleDrive\Adapters;

use App\Services\SimpleDrive\StorageAdapterInterface;
use Illuminate\Support\Facades\DB;

class DatabaseAdapter implements StorageAdapterInterface
{
    public function save(string $id, string $binary): string
    {
        DB::table('blobs_data')->updateOrInsert(
            ['blob_id' => $id],
            ['data' => $binary, 'updated_at' => now(), 'created_at' => now()]
        );
        return $id; // storage_path is the id
    }

    public function get(string $storagePath): string
    {
        $row = DB::table('blobs_data')->where('blob_id', $storagePath)->first();
        if (!$row) throw new \RuntimeException('Not found');
        return $row->data;
    }

    public function delete(string $storagePath): void
    {
        DB::table('blobs_data')->where('blob_id', $storagePath)->delete();
    }
}

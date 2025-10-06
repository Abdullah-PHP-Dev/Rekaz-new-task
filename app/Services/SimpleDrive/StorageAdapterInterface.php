<?php
namespace App\Services\SimpleDrive;

interface StorageAdapterInterface
{
    /**
     * Save binary data. Return storage path/key on success.
     *
     * @param string $id
     * @param string $binary
     * @return string storage_path
     */
    public function save(string $id, string $binary): string;

    /**
     * Retrieve binary by storage path or key.
     *
     * @param string $storagePath
     * @return string binary
     */
    public function get(string $storagePath): string;

    /**
     * Delete storage object (optional).
     */
    public function delete(string $storagePath): void;
}

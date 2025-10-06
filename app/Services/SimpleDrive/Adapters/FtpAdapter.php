<?php
namespace App\Services\SimpleDrive\Adapters;

use App\Services\SimpleDrive\StorageAdapterInterface;

class FtpAdapter implements StorageAdapterInterface
{
    protected $conn;
    protected $basePath;

    public function __construct()
    {
        $cfg = config('simpledrive.ftp');
        $this->basePath = $cfg['base_path'] ?? '/';
        $this->conn = ftp_connect($cfg['host']);
        if (!$this->conn) {
            throw new \RuntimeException('Cannot connect to FTP host');
        }
        if (!@ftp_login($this->conn, $cfg['username'], $cfg['password'])) {
            throw new \RuntimeException('FTP auth failed');
        }
    }

    public function save(string $id, string $binary): string
    {
        $name = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '_', $id);
        $remote = rtrim($this->basePath, '/') . '/' . ltrim($name, '/');

        // ensure remote dirs exist (simple approach)
        $dir = dirname($remote);
        $this->ensureRemoteDir($dir);

        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];
        fwrite($tmp, $binary);
        fseek($tmp, 0);

        $stream = fopen($tmpPath, 'r');
        $ok = ftp_fput($this->conn, $remote, $stream, FTP_BINARY);
        fclose($stream);
        fclose($tmp);
        if (!$ok) throw new \RuntimeException('FTP upload failed');
        return $remote;
    }

    protected function ensureRemoteDir($dir)
    {
        $parts = explode('/', trim($dir, '/'));
        $path = '';
        foreach ($parts as $p) {
            $path .= '/'.$p;
            @ftp_mkdir($this->conn, $path);
        }
    }

    public function get(string $storagePath): string
    {
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];
        $stream = fopen($tmpPath, 'w+');
        $ok = ftp_fget($this->conn, $stream, $storagePath, FTP_BINARY);
        if (!$ok) {
            fclose($stream);
            fclose($tmp);
            throw new \RuntimeException('FTP get failed');
        }
        fseek($stream, 0);
        $data = stream_get_contents($stream);
        fclose($stream);
        fclose($tmp);
        return $data;
    }

    public function delete(string $storagePath): void
    {
        @ftp_delete($this->conn, $storagePath);
    }
}

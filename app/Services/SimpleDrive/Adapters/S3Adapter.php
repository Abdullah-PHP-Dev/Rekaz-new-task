<?php
namespace App\Services\SimpleDrive\Adapters;

use App\Services\SimpleDrive\StorageAdapterInterface;
use GuzzleHttp\Client;

class S3Adapter implements StorageAdapterInterface
{
    protected $endpoint;
    protected $bucket;
    protected $key;
    protected $secret;
    protected $region;
    protected $pathStyle;
    protected $http;

    public function __construct()
    {
        $cfg = config('simpledrive.s3');
        $this->endpoint = rtrim($cfg['endpoint'], '/');
        $this->bucket = $cfg['bucket'];
        $this->key = $cfg['access_key'];
        $this->secret = $cfg['secret_key'];
        $this->region = $cfg['region'];
        $this->pathStyle = $cfg['path_style'] ?? true;
        $this->http = new Client(['base_uri' => $this->endpoint, 'http_errors' => false]);
    }

    public function save(string $id, string $binary): string
    {
        $objectKey = ltrim($id, '/');
        // build URL
        if ($this->pathStyle) {
            $url = "{$this->endpoint}/{$this->bucket}/{$objectKey}";
        } else {
            // virtual host style
            $host = "{$this->bucket}." . parse_url($this->endpoint, PHP_URL_HOST);
            $scheme = parse_url($this->endpoint, PHP_URL_SCHEME);
            $url = "{$scheme}://{$host}/{$objectKey}";
        }

        $method = 'PUT';
        $payload = $binary;
        $headers = $this->signHeaders($method, "/{$this->bucket}/{$objectKey}", $payload, ['Host' => parse_url($url, PHP_URL_HOST)]);

        $resp = $this->http->request('PUT', $url, [
            'headers' => $headers,
            'body' => $payload,
        ]);
        $status = $resp->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return $objectKey;
        }
        throw new \RuntimeException("S3 PUT failed: {$status} " . $resp->getBody());
    }

    public function get(string $storagePath): string
    {
        $objectKey = ltrim($storagePath, '/');
        if ($this->pathStyle) {
            $url = "{$this->endpoint}/{$this->bucket}/{$objectKey}";
        } else {
            $host = "{$this->bucket}." . parse_url($this->endpoint, PHP_URL_HOST);
            $scheme = parse_url($this->endpoint, PHP_URL_SCHEME);
            $url = "{$scheme}://{$host}/{$objectKey}";
        }

        $method = 'GET';
        $headers = $this->signHeaders($method, "/{$this->bucket}/{$objectKey}", '', ['Host' => parse_url($url, PHP_URL_HOST)]);
        $resp = $this->http->request('GET', $url, ['headers' => $headers]);
        $status = $resp->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return (string) $resp->getBody();
        }
        throw new \RuntimeException("S3 GET failed: {$status} " . $resp->getBody());
    }

    public function delete(string $storagePath): void
    {
        // similar to get/put
        $objectKey = ltrim($storagePath, '/');
        if ($this->pathStyle) $url = "{$this->endpoint}/{$this->bucket}/{$objectKey}";
        else {
            $host = "{$this->bucket}." . parse_url($this->endpoint, PHP_URL_HOST);
            $scheme = parse_url($this->endpoint, PHP_URL_SCHEME);
            $url = "{$scheme}://{$host}/{$objectKey}";
        }
        $headers = $this->signHeaders('DELETE', "/{$this->bucket}/{$objectKey}", '', ['Host' => parse_url($url, PHP_URL_HOST)]);
        $resp = $this->http->request('DELETE', $url, ['headers' => $headers]);
        if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) return;
        throw new \RuntimeException('S3 delete failed: ' . $resp->getStatusCode());
    }

    /**
     * Minimal SigV4 implementation for simple PUT/GET without extra headers.
     * For full correctness across all S3 features you'd need complete canonicalization.
     */
    protected function signHeaders($method, $canonicalUri, $payload, $extraHeaders = [])
    {
        $t = new \DateTime('now', new \DateTimeZone('UTC'));
        $amzDate = $t->format('Ymd\THis\Z');
        $dateStamp = $t->format('Ymd');
        $service = 's3';
        $region = $this->region;
        $host = $extraHeaders['Host'] ?? parse_url($this->endpoint, PHP_URL_HOST);

        $headers = [
            'Host' => $host,
            'x-amz-content-sha256' => hash('sha256', $payload),
            'x-amz-date' => $amzDate,
        ];
        $headers = array_merge($headers, $extraHeaders);

        // canonical headers
        $sorted = $headers;
        uksort($sorted, 'strnatcasecmp');
        $canonicalHeaders = '';
        $signedHeadersArr = [];
        foreach ($sorted as $k => $v) {
            $canonicalHeaders .= strtolower($k) . ':' . trim($v) . "\n";
            $signedHeadersArr[] = strtolower($k);
        }
        $signedHeaders = implode(';', $signedHeadersArr);
        $canonicalRequest = "{$method}\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n" . hash('sha256', $payload);

        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign = $algorithm . "\n" . $amzDate . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);

        // signing key
        $kSecret = 'AWS4' . $this->secret;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = $algorithm .
            ' Credential=' . $this->key . '/' . $credentialScope .
            ', SignedHeaders=' . $signedHeaders .
            ', Signature=' . $signature;

        // final headers
        $final = $headers;
        $final['Authorization'] = $authorization;
        return $final;
    }
}

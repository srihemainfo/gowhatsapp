<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AwsS3Service
{
    private string $accessKey;
    private string $secretKey;
    private string $region;
    private string $bucket;
    private string $baseUrl;

    public function __construct()
    {
        $this->accessKey = env('AWS_ACCESS_KEY_ID');
        $this->secretKey = env('AWS_SECRET_ACCESS_KEY');
        $this->region    = env('AWS_DEFAULT_REGION');
        $this->bucket    = env('AWS_BUCKET');
        $this->baseUrl   = rtrim(env('AWS_URL'));
    }

    /**
     * Get the full public URL for a given S3 key.
     */
    public function getUrl(string $key): string
    {
        $cleanKey = ltrim($key, '/');
        return "{$this->baseUrl}/{$cleanKey}";
    }

    /**
     * Determine meaningful folder based on media type.
     */
    public function getFolderForType(?string $type): string
    {
        $map = [
            'image'    => 'whatsapp/images',
            'video'    => 'whatsapp/videos',
            'audio'    => 'whatsapp/audio',
            'document' => 'whatsapp/documents',
            'sticker'  => 'whatsapp/stickers',
        ];
        return $map[strtolower(trim($type ?? ''))] ?? 'whatsapp/media';
    }

    /**
     * Check whether a file exists in S3 (via HEAD request).
     */
    public function exists(string $key): bool
    {
        $res = $this->request('HEAD', $key);
        return ($res['http_code'] === 200);
    }

    /**
     * Check multiple candidate locations for an existing file.
     */
    public function findExistingKey(string $waMessageId, ?string $type = null, ?string $ext = null): ?string
    {
        $folder = $this->getFolderForType($type);

        $candidates = [];
        if (!empty($ext)) {
            $candidates[] = "{$folder}/{$waMessageId}.{$ext}";
            $candidates[] = "whatsapp_media/{$waMessageId}.{$ext}";
            $candidates[] = "whatsapp/{$waMessageId}.{$ext}";
        }
        $candidates[] = "{$folder}/{$waMessageId}";
        $candidates[] = "whatsapp_media/{$waMessageId}";
        $candidates[] = "whatsapp/{$waMessageId}";

        foreach ($candidates as $cand) {
            if ($this->exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Retrieve a file binary and content-type from S3 (via GET request).
     */
    public function get(string $key): ?array
    {
        $res = $this->request('GET', $key);
        if ($res['http_code'] === 200) {
            return [
                'data'         => $res['body'],
                'content_type' => $res['content_type'] ?? 'application/octet-stream'
            ];
        }
        return null;
    }

    /**
     * Upload binary data to S3 (via PUT request with SigV4).
     */
    public function put(string $key, string $binaryData, string $contentType = 'application/octet-stream'): bool
    {
        $res = $this->request('PUT', $key, $binaryData, $contentType);
        if ($res['http_code'] === 200 || $res['http_code'] === 204) {
            return true;
        }

        Log::error('AWS S3 PUT Failed', [
            'key'       => $key,
            'http_code' => $res['http_code'],
            'body'      => $res['body'],
        ]);

        return false;
    }

    /**
     * Execute AWS SigV4 signed request to S3.
     */
    private function request(string $method, string $key, string $body = '', ?string $contentType = null): array
    {
        $cleanKey = ltrim($key, '/');
        $host = "{$this->bucket}.s3.{$this->region}.amazonaws.com";

        // S3 URI Encoding
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $cleanKey)));
        $uri = '/' . $encodedKey;
        $endpoint = "https://{$host}{$uri}";

        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', $body);

        $headers = [
            'host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];

        if ($method === 'PUT' && !empty($contentType)) {
            $headers['content-type'] = $contentType;
        }

        ksort($headers);

        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= strtolower($k) . ':' . trim($v) . "\n";
            $signedHeadersList[] = strtolower($k);
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalRequest = "{$method}\n{$uri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/{$this->region}/s3/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "{$algorithm} Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "{$k}: {$v}";
        }
        $curlHeaders[] = "Authorization: {$authHeader}";

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $respContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('AWS S3 Curl Error', ['error' => $curlError, 'endpoint' => $endpoint]);
        }

        return [
            'http_code'    => $httpCode,
            'body'         => $response ?: '',
            'content_type' => $respContentType
        ];
    }
}

<?php

declare(strict_types=1);

namespace QDH\DurianPay\Http;

use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

class HttpClient
{
    private readonly string $authorization;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {
        $this->authorization = 'Basic ' . base64_encode($this->apiKey . ':');
    }

    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->send('GET', $url);
    }

    public function post(string $path, array $body = []): array
    {
        $url = $this->baseUrl . ltrim($path, '/');

        return $this->send('POST', $url, $body);
    }

    private function send(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init();

        $headers = [
            'Authorization: ' . $this->authorization,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) json_encode($body ?? []));
        }

        $rawResponse = curl_exec($ch);
        $statusCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new DurianPayException('cURL error: ' . $curlError);
        }

        $decoded = json_decode((string) $rawResponse, associative: true) ?? [];

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ApiException($statusCode, $decoded);
        }

        return $decoded;
    }
}

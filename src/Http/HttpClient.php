<?php

declare(strict_types=1);

namespace QDH\DurianPay\Http;

use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

class HttpClient
{
    public const API_PREFIX      = '/v1.0/';
    public const TOKEN_PATH      = 'access-token/b2b';
    public const DEFAULT_CHANNEL = '95221';

    // Tokens live 900s; refresh a minute early so a request never goes out with a token about to expire.
    private const TOKEN_TTL    = 900;
    private const TOKEN_LEEWAY = 60;

    private ?string $accessToken = null;
    private int $tokenExpiresAt  = 0;

    /** @var (callable(array): void)|null */
    private $exchangeListener = null;

    public function __construct(
        private readonly string $clientKey,
        private readonly string $clientSecret,
        private readonly string $privateKey,
        private readonly string $baseUrl,
        private readonly string $channelId = self::DEFAULT_CHANNEL,
    ) {}

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, $body);
    }

    public function put(string $path, array $body = []): array
    {
        return $this->request('PUT', $path, $body);
    }

    public function accessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        $timestamp = $this->timestamp();
        $body      = Signature::encodeBody(['grantType' => 'AUTHORIZATION_CODE']);

        [$status, $decoded] = $this->send('POST', self::TOKEN_PATH, $body, [
            'X-TIMESTAMP: ' . $timestamp,
            'X-CLIENT-KEY: ' . $this->clientKey,
            'X-SIGNATURE: ' . Signature::asymmetric($this->clientKey, $timestamp, $this->privateKey),
        ]);

        if ($status < 200 || $status >= 300) {
            throw new ApiException($status, $decoded);
        }

        $token = $decoded['accessToken'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new DurianPayException('DurianPay access token response did not contain an accessToken.');
        }

        $this->accessToken    = $token;
        $this->tokenExpiresAt = time() + self::TOKEN_TTL - self::TOKEN_LEEWAY;

        return $token;
    }

    private function request(string $method, string $path, array $body, bool $retried = false): array
    {
        $path      = ltrim($path, '/');
        $token     = $this->accessToken();
        $timestamp = $this->timestamp();
        $json      = Signature::encodeBody($body);

        [$status, $decoded] = $this->send($method, $path, $json, [
            'Authorization: Bearer ' . $token,
            'X-TIMESTAMP: ' . $timestamp,
            'X-SIGNATURE: ' . Signature::symmetric(
                $method,
                self::API_PREFIX . $path,
                $token,
                $json,
                $timestamp,
                $this->clientSecret,
            ),
            'X-PARTNER-ID: ' . $this->clientKey,
            'X-EXTERNAL-ID: ' . $this->externalId(),
            'CHANNEL-ID: ' . $this->channelId,
        ]);

        // Token revoked or expired server-side before our local TTL: fetch a fresh one and retry once.
        if ($status === 401 && !$retried) {
            $this->accessToken = null;

            return $this->request($method, $path, $body, true);
        }

        if ($status < 200 || $status >= 300) {
            throw new ApiException($status, $decoded);
        }

        return $decoded;
    }

    /**
     * Receives every HTTP exchange, the access-token call included, after it completes:
     * ['method', 'url', 'request_headers' (name => value), 'request_body', 'status',
     *  'response_body', 'duration_ms', 'error' (cURL failure, else null)].
     *
     * Headers are passed unredacted (Authorization, X-SIGNATURE), so keep this off in production.
     * A listener that throws never affects the API call.
     *
     * @param (callable(array): void)|null $listener
     */
    public function onExchange(?callable $listener): void
    {
        $this->exchangeListener = $listener;
    }

    /** @return array{0: int, 1: array} */
    private function send(string $method, string $path, string $body, array $headers): array
    {
        $url       = rtrim($this->baseUrl, '/') . self::API_PREFIX . $path;
        $headers   = array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers);
        $startedAt = microtime(true);

        try {
            [$status, $raw] = $this->transport($method, $url, $headers, $body);
        } catch (\Throwable $e) {
            $this->notifyExchange($method, $url, $headers, $body, 0, '', $startedAt, $e->getMessage());
            throw $e;
        }

        $this->notifyExchange($method, $url, $headers, $body, $status, $raw, $startedAt, null);

        $decoded = json_decode($raw, associative: true);

        return [$status, is_array($decoded) ? $decoded : []];
    }

    private function notifyExchange(
        string $method,
        string $url,
        array $headers,
        string $body,
        int $status,
        string $raw,
        float $startedAt,
        ?string $error,
    ): void {
        if ($this->exchangeListener === null) {
            return;
        }

        $named = [];
        foreach ($headers as $header) {
            [$name, $value] = array_pad(explode(':', $header, 2), 2, '');
            $named[trim($name)] = trim($value);
        }

        try {
            ($this->exchangeListener)([
                'method'          => $method,
                'url'             => $url,
                'request_headers' => $named,
                'request_body'    => $body,
                'status'          => $status,
                'response_body'   => $raw,
                'duration_ms'     => (int) round((microtime(true) - $startedAt) * 1000),
                'error'           => $error,
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * Performs the raw HTTP call. Overridable so tests can capture requests without network access.
     *
     * @return array{0: int, 1: string}
     */
    protected function transport(string $method, string $url, array $headers, string $body): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw       = curl_exec($ch);
        $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new DurianPayException('cURL error: ' . $curlError);
        }

        return [$status, (string) $raw];
    }

    protected function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d\TH:i:sP');
    }

    protected function externalId(): string
    {
        // Must be unique per merchant per day; 128 random bits makes collisions a non-issue.
        return bin2hex(random_bytes(16));
    }
}

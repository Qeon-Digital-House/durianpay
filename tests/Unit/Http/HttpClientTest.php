<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;
use QDH\DurianPay\Http\Signature;

class HttpClientTest extends TestCase
{
    private const TIMESTAMP   = '2024-05-13T15:07:07+07:00';
    private const EXTERNAL_ID = 'ext-123';

    private string $privateKey;

    protected function setUp(): void
    {
        $this->privateKey = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/private_key.pem');
    }

    /** @param list<array{0: int, 1: array}> $responses */
    private function client(array $responses): HttpClient
    {
        return new class ('client-key', 'client-secret', $this->privateKey, 'https://api-sandbox.durianpay.id', '95221', $responses) extends HttpClient {
            public array $requests = [];

            public function __construct(string $k, string $s, string $p, string $u, string $c, private array $responses)
            {
                parent::__construct($k, $s, $p, $u, $c);
            }

            protected function transport(string $method, string $url, array $headers, string $body): array
            {
                $parsed = [];
                foreach ($headers as $h) {
                    [$name, $value]  = explode(': ', $h, 2);
                    $parsed[$name] = $value;
                }
                $this->requests[] = compact('method', 'url', 'body') + ['headers' => $parsed];

                [$status, $json] = array_shift($this->responses);

                return [$status, (string) json_encode($json)];
            }

            protected function timestamp(): string
            {
                return '2024-05-13T15:07:07+07:00';
            }

            protected function externalId(): string
            {
                return 'ext-123';
            }
        };
    }

    private function tokenResponse(string $token = 'token-1'): array
    {
        return [200, ['responseCode' => '2007300', 'accessToken' => $token, 'tokenType' => 'BearerToken']];
    }

    public function test_access_token_request_is_signed_asymmetrically(): void
    {
        $client = $this->client([$this->tokenResponse()]);

        $this->assertSame('token-1', $client->accessToken());

        $req = $client->requests[0];
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://api-sandbox.durianpay.id/v1.0/access-token/b2b', $req['url']);
        $this->assertSame('{"grantType":"AUTHORIZATION_CODE"}', $req['body']);
        $this->assertSame('client-key', $req['headers']['X-CLIENT-KEY']);
        $this->assertSame(self::TIMESTAMP, $req['headers']['X-TIMESTAMP']);

        $publicKey = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/public_key.pem');
        $this->assertSame(1, openssl_verify(
            'client-key|' . self::TIMESTAMP,
            base64_decode($req['headers']['X-SIGNATURE']),
            $publicKey,
            OPENSSL_ALGO_SHA256,
        ));
    }

    public function test_transaction_request_sends_snap_headers_and_symmetric_signature(): void
    {
        $client = $this->client([$this->tokenResponse(), [200, ['responseCode' => '2004700']]]);

        $result = $client->post('qr/qr-mpm-generate', ['partnerReferenceNo' => 'ref_1']);

        $this->assertSame(['responseCode' => '2004700'], $result);

        $req = $client->requests[1];
        $this->assertSame('https://api-sandbox.durianpay.id/v1.0/qr/qr-mpm-generate', $req['url']);
        $this->assertSame('{"partnerReferenceNo":"ref_1"}', $req['body']);
        $this->assertSame('Bearer token-1', $req['headers']['Authorization']);
        $this->assertSame(self::TIMESTAMP, $req['headers']['X-TIMESTAMP']);
        $this->assertSame('client-key', $req['headers']['X-PARTNER-ID']);
        $this->assertSame(self::EXTERNAL_ID, $req['headers']['X-EXTERNAL-ID']);
        $this->assertSame('95221', $req['headers']['CHANNEL-ID']);
        $this->assertSame('application/json', $req['headers']['Content-Type']);
        $this->assertSame(
            Signature::symmetric('POST', '/v1.0/qr/qr-mpm-generate', 'token-1', $req['body'], self::TIMESTAMP, 'client-secret'),
            $req['headers']['X-SIGNATURE'],
        );
    }

    public function test_put_uses_put_method_in_request_and_signature(): void
    {
        $client = $this->client([$this->tokenResponse(), [200, []]]);

        $client->put('transfer-va/update-va', ['trxId' => 't1']);

        $req = $client->requests[1];
        $this->assertSame('PUT', $req['method']);
        $this->assertSame(
            Signature::symmetric('PUT', '/v1.0/transfer-va/update-va', 'token-1', $req['body'], self::TIMESTAMP, 'client-secret'),
            $req['headers']['X-SIGNATURE'],
        );
    }

    public function test_access_token_is_cached_between_requests(): void
    {
        $client = $this->client([$this->tokenResponse(), [200, []], [200, []]]);

        $client->post('qr/qr-mpm-query');
        $client->post('qr/qr-mpm-query');

        $this->assertCount(3, $client->requests);
        $this->assertStringEndsWith('access-token/b2b', $client->requests[0]['url']);
        $this->assertStringEndsWith('qr/qr-mpm-query', $client->requests[2]['url']);
    }

    public function test_401_refreshes_token_and_retries_once(): void
    {
        $client = $this->client([
            $this->tokenResponse('old'),
            [401, ['responseCode' => '4014701', 'responseMessage' => 'Invalid Token (B2B)']],
            $this->tokenResponse('new'),
            [200, ['ok' => true]],
        ]);

        $this->assertSame(['ok' => true], $client->post('qr/qr-mpm-query'));
        $this->assertSame('Bearer new', $client->requests[3]['headers']['Authorization']);
    }

    public function test_second_401_throws(): void
    {
        $client = $this->client([
            $this->tokenResponse('old'),
            [401, ['responseCode' => '4014700']],
            $this->tokenResponse('new'),
            [401, ['responseCode' => '4014700']],
        ]);

        $this->expectException(ApiException::class);

        $client->post('qr/qr-mpm-query');
    }

    public function test_error_response_throws_api_exception_with_snap_code(): void
    {
        $client = $this->client([
            $this->tokenResponse(),
            [400, ['responseCode' => '4004702', 'responseMessage' => 'Invalid Mandatory Field amount']],
        ]);

        try {
            $client->post('qr/qr-mpm-generate');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('4004702', $e->responseCode);
            $this->assertSame('Invalid Mandatory Field amount', $e->responseMessage);
        }
    }

    public function test_failed_token_request_throws_api_exception(): void
    {
        $client = $this->client([[401, ['responseCode' => '4017300', 'responseMessage' => 'Unauthorized']]]);

        $this->expectException(ApiException::class);

        $client->accessToken();
    }

    public function test_token_response_without_access_token_throws(): void
    {
        $client = $this->client([[200, ['responseCode' => '2007300']]]);

        $this->expectException(DurianPayException::class);

        $client->accessToken();
    }
}

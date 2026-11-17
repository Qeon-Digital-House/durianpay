# CLAUDE.md

This file helps Claude Code understand the project and make consistent changes.

## Project Overview

`qdh/durianpay` is a minimal, zero-dependency PHP 8.1+ Composer package for the
[DurianPay](https://durianpay.id) payment gateway, built on DurianPay's **SNAP**
APIs (Bank Indonesia open API standard): QRIS, e-wallet (direct debit) and virtual accounts.
The legacy non-SNAP API (Basic Auth, `/v1/orders`, `/v1/payments`) is no longer used.

Docs: https://docs.durianpay.id/reference/snap-payment-apis

- **Repo**: `qeon-digital-house/durianpay`
- **Packagist name**: `qdh/durianpay`
- **PHP requirement**: `^8.1`
- **Runtime dependencies**: `vlucas/phpdotenv ^5.5`, `ext-curl`, `ext-json`, `ext-openssl`
- **Dev dependencies**: `phpunit/phpunit ^10.5`

---

## Directory Structure

```
src/
  DurianPay.php          # Entry point: qris(), ewallet(), virtualAccounts(); fromEnv()
  Webhook.php            # Webhook::verify() for inbound SNAP notifications
  Http/
    HttpClient.php       # cURL client: B2B token fetch + cache, SNAP headers, signing, 401 retry
    Signature.php        # asymmetric (token), symmetric (transactions), verifyNotification (webhooks)
  Support/
    Amount.php           # Amount::of(10000) → ['value' => '10000.00', 'currency' => 'IDR']
  Exceptions/
    DurianPayException.php  # Base exception (extends RuntimeException)
    ApiException.php        # HTTP 4xx/5xx: statusCode, responseBody, responseCode, responseMessage, traceId()
  Api/
    AbstractApi.php      # Base class: post() / put(), toStatus()
    Qris.php             # qr/qr-mpm-generate | query | cancel | refund (serviceCode 47)
    Ewallet.php          # debit/payment-host-to-host | status | cancel | refund (serviceCode 54)
    VirtualAccounts.php  # transfer-va/create-va | inquiry-va | update-va (PUT)
  Enums/
    Environment.php      # Sandbox | Live
    PaymentStatus.php    # SNAP latestTransactionStatus codes '00'..'09'
    WalletType.php       # DANA | OVO | LINKAJA | GOPAY | SHOPEEPAY
    BankCode.php         # BCA | BNI | BRI | MANDIRI | PERMATA | CIMB | NOBU | INA
    VirtualAccountType.php # C (closed) | O (open)
    Currency.php         # IDR (only)
tests/
  Unit/                  # mirrors src/ (Api, Enums, Exceptions, Http, Support, DurianPayTest, WebhookTest)
  fixtures/
    .env                 # fixture SNAP credentials; DURIANPAY_PRIVATE_KEY=private_key.pem
    private_key.pem      # TEST-ONLY RSA key pair used by signature tests (never a real credential)
    public_key.pem
```

---

## Base URLs

| Environment | Base URL |
|---|---|
| `Environment::Live` | `https://api.durianpay.id` |
| `Environment::Sandbox` | `https://api-sandbox.durianpay.id` |

`HttpClient` appends `/v1.0/` + path. The base URL is chosen in the `DurianPay` constructor via
`BASE_URLS[environment->value]`. Never hardcode a base URL elsewhere.

---

## Key Conventions

### PHP 8.1+ features in use
- `readonly` properties, backed string enums, constructor property promotion, named arguments
- `??=` for lazy API instances

### No comments on obvious code
Only add a comment when the WHY is non-obvious. Do not document what the code does.

### All enums are backed string enums
```php
enum FooType: string
{
    case Bar = 'BAR';
}
```

### SNAP HTTP client
- Token: `POST /v1.0/access-token/b2b`, body `{"grantType":"AUTHORIZATION_CODE"}`, headers `X-CLIENT-KEY`,
  `X-TIMESTAMP`, `X-SIGNATURE = base64(SHA256withRSA(privateKey, "{clientKey}|{timestamp}"))`. Cached 900s - 60s.
- Transactions: `Authorization: Bearer {token}`, `X-TIMESTAMP`, `X-PARTNER-ID` (= client key), `X-EXTERNAL-ID`
  (random, unique per day), `CHANNEL-ID` (default `95221`), and
  `X-SIGNATURE = base64(HMAC-SHA512(clientSecret, "METHOD:/v1.0/{path}:{token}:{sha256hex(body)}:{timestamp}"))`.
- The body is encoded once with `Signature::encodeBody()`, and that exact string is both hashed and sent.
- A `401` drops the cached token and retries once.
- Throws `ApiException` for HTTP 4xx/5xx and `DurianPayException` for cURL/key/config errors.
- `transport()`, `timestamp()` and `externalId()` are protected so tests can override them (see `HttpClientTest`).

### API classes
- Extend `AbstractApi` and use `$this->post(path, body)` / `$this->put(path, body)`
- Path strings have **no** leading slash and **no** `v1.0` prefix: `"qr/qr-mpm-generate"`
- Amounts are always built with `Amount::of()`; never send raw ints
- Always return `array`, except `status()`, which returns `PaymentStatus` (from `latestTransactionStatus`)

### Environment
- Constructor default: `Environment::Sandbox`
- `fromEnv()` reads `DURIANPAY_CLIENT_KEY`, `DURIANPAY_CLIENT_SECRET`, `DURIANPAY_PRIVATE_KEY`
  (PEM or file path, relative paths also resolved against `envPath`), `DURIANPAY_PRODUCTION`, `DURIANPAY_CHANNEL_ID`

---

## Adding a New API Endpoint

1. **Add the method** to the relevant class in `src/Api/`:
   ```php
   public function myMethod(string $id): array
   {
       return $this->post('resource/action', ['originalReferenceNo' => $id]);
   }
   ```

2. **Add a test** in the matching `tests/Unit/Api/*Test.php`:
   ```php
   public function test_my_method_calls_correct_endpoint(): void
   {
       $response = ['data' => ['id' => 'res_123']];

       $this->http->expects($this->once())
           ->method('post')
           ->with('resource/action', ['originalReferenceNo' => 'res_123'])
           ->willReturn($response);

       $this->assertSame($response, $this->subject->myMethod('res_123'));
   }
   ```

3. **Update README.md** with the method signature, example call, and annotated return shape.

---

## Adding a New Enum

1. Create `src/Enums/MyEnum.php`:
   ```php
   <?php
   declare(strict_types=1);
   namespace QDH\DurianPay\Enums;
   enum MyEnum: string
   {
       case Foo = 'FOO';
       case Bar = 'BAR';
   }
   ```

2. Create `tests/Unit/Enums/MyEnumTest.php` using the data provider pattern:
   ```php
   /** @dataProvider caseProvider */
   public function test_case_value(MyEnum $case, string $expected): void
   {
       $this->assertSame($expected, $case->value);
   }
   public static function caseProvider(): array
   {
       return [
           [MyEnum::Foo, 'FOO'],
           [MyEnum::Bar, 'BAR'],
       ];
   }
   public function test_from_throws_on_unknown_value(): void
   {
       $this->expectException(\ValueError::class);
       MyEnum::from('UNKNOWN');
   }
   ```

3. Add the enum to the **Enums** section of README.md.

---

## Adding a New API Class (e.g. Refunds)

1. Create `src/Api/Refunds.php` extending `AbstractApi`.
2. Expose it from `DurianPay.php` with a lazy property:
   ```php
   private ?Refunds $refunds = null;
   public function refunds(): Refunds
   {
       return $this->refunds ??= new Refunds($this->http);
   }
   ```
3. Add `tests/Unit/Api/RefundsTest.php`.
4. Document in README.

---

## Running Tests

```bash
composer install
composer test          # runs phpunit
./vendor/bin/phpunit   # equivalent
```

Tests are pure unit tests with no network calls. API tests mock `HttpClient` via
`$this->createMock(HttpClient::class)`. `HttpClientTest` subclasses it and overrides `transport()`.

---

## Environment Variables

| Variable | Required | Values | Default |
|---|---|---|---|
| `DURIANPAY_CLIENT_KEY` | yes | dashboard Client Key | — |
| `DURIANPAY_CLIENT_SECRET` | yes | dashboard Client Secret | — |
| `DURIANPAY_PRIVATE_KEY` | yes | PEM (`\n`-escaped) or path to PEM | — |
| `DURIANPAY_PRODUCTION` | no | `true` / `false` | `false` (Sandbox) |
| `DURIANPAY_CHANNEL_ID` | no | numeric string | `95221` |

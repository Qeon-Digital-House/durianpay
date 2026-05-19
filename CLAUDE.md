# CLAUDE.md

This file helps Claude Code understand the project and make consistent changes.

## Project Overview

`qdh/durianpay` is a minimal, zero-dependency PHP 8.1+ Composer package for the
[DurianPay](https://durianpay.id) payment gateway. It covers the **Payment API**
and **QRIS API**.

- **Repo**: `qeon-digital-house/durianpay`
- **Branch**: `claude/durianpay-composer-package-l0uzk`
- **Packagist name**: `qdh/durianpay`
- **PHP requirement**: `^8.1`
- **Runtime dependencies**: `vlucas/phpdotenv ^5.5`, `ext-curl`, `ext-json`
- **Dev dependencies**: `phpunit/phpunit ^10.5`

---

## Directory Structure

```
src/
  DurianPay.php          # Main entry point — exposes orders(), payments(), qris()
  Http/
    HttpClient.php       # cURL HTTP client, sets Basic Auth on every request
    Response.php         # readonly value object (unused directly, kept for future use)
  Exceptions/
    DurianPayException.php  # Base exception (extends RuntimeException)
    ApiException.php        # HTTP 4xx/5xx — holds statusCode + responseBody
  Api/
    AbstractApi.php      # Base class: holds HttpClient, exposes get() / post()
    Orders.php           # Order endpoints
    Payments.php         # Payment endpoints
    Qris.php             # QRIS endpoints (wraps /payments with type=QRIS)
  Enums/
    Environment.php      # Sandbox | Live
    PaymentType.php      # EWALLET | VA | RETAILSTORE | ONLINE_BANKING | BNPL | QRIS
    PaymentStatus.php    # STARTED | PROCESSING | COMPLETED | FAILED | CANCELLED | EXPIRED | PENDING
    WalletType.php       # DANA | OVO | LINKAJA | GOPAY | SHOPEEPAY | JENIUSPAY | ASTRAPAY
    BankCode.php         # BCA | BNI | BRI | MANDIRI | PERMATA | BSI | CIMB | DANAMON | BTN | MAYBANK
    RetailStore.php      # ALFAMART | INDOMARET
    BnplType.php         # KREDIVO | AKULAKU | INDODANA | SPAYLATER
    OnlineBankingType.php# BRI_EPAY | CIMB_CLICKS | DANAMON_ONLINE | MANDIRI_CLICKPAY | PERMATA_NET
    Currency.php         # IDR (only)
tests/
  Unit/
    DurianPayTest.php
    Exceptions/
      ApiExceptionTest.php
    Api/
      OrdersTest.php
      PaymentsTest.php
      QrisTest.php
    Enums/
      PaymentTypeTest.php
      WalletTypeTest.php
      BankCodeTest.php
      RetailStoreTest.php
      BnplTypeTest.php
      OnlineBankingTypeTest.php
      CurrencyTest.php
      EnvironmentTest.php
      PaymentStatusTest.php
  fixtures/
    .env                 # DURIANPAY_API_KEY=fixture-api-key + DURIANPAY_PRODUCTION=false
```

---

## Base URLs

| Environment | Base URL |
|---|---|
| `Environment::Live` | `https://api.durianpay.id/v1/` |
| `Environment::Sandbox` | `https://api-sandbox.durianpay.id/v1/` |

The correct URL is selected automatically in the `DurianPay` constructor via
`BASE_URLS[environment->value]`. Never hardcode a base URL elsewhere.

---

## Key Conventions

### PHP 8.1+ features in use
- `readonly` properties (`HttpClient`, `Response`, `DurianPay`, `ApiException`)
- Backed string enums for all value types
- Constructor property promotion
- Named arguments (`json_decode(..., associative: true)`)
- Nullsafe assignment operator (`??=`) for lazy API instances

### No comments on obvious code
Only add a comment when the WHY is non-obvious. Do not document what the code does.

### All enums are backed string enums
```php
enum FooType: string
{
    case Bar = 'BAR';
}
```
Callers use `->value` when building request arrays: `FooType::Bar->value`.

### HTTP client
- Base URL chosen per environment (see table above)
- Auth: `Authorization: Basic base64("{apiKey}:")` (key as username, empty password)
- Both `get()` and `post()` return `array` (decoded JSON)
- Throws `ApiException` for HTTP 4xx/5xx, `DurianPayException` for cURL errors

### API classes
- Extend `AbstractApi`
- Use `$this->get(path, query)` and `$this->post(path, body)`
- Path strings do **not** have a leading slash — `"payments/{$id}/status"`, not `"/payments/{$id}/status"`
- Always return `array` except `status()` which returns `PaymentStatus`

### Environment
- Constructor default: `Environment::Sandbox` (safe for local dev)
- `fromEnv()` reads `DURIANPAY_API_KEY` and `DURIANPAY_PRODUCTION`
- `DURIANPAY_PRODUCTION=true` → `Environment::Live` → `https://api.durianpay.id/v1/`
- `DURIANPAY_PRODUCTION=false` (or missing) → `Environment::Sandbox` → `https://api-sandbox.durianpay.id/v1/`

---

## Adding a New API Endpoint

1. **Add the method** to the relevant class in `src/Api/`:
   ```php
   public function myMethod(string $id): array
   {
       return $this->get("resource/{$id}");
   }
   ```

2. **Add a test** in the matching `tests/Unit/Api/*Test.php`:
   ```php
   public function test_my_method_calls_correct_endpoint(): void
   {
       $response = ['data' => ['id' => 'res_123']];

       $this->http->expects($this->once())
           ->method('get')
           ->with('resource/res_123', [])
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

Tests are pure unit tests — no network calls. `HttpClient` is mocked via
`$this->createMock(HttpClient::class)` in every API test.

---

## Environment Variables

| Variable | Required | Values | Default |
|---|---|---|---|
| `DURIANPAY_API_KEY` | yes | any string | — |
| `DURIANPAY_PRODUCTION` | no | `true` / `false` | `false` (Sandbox) |

See `tests/fixtures/.env` for the fixture values used in `DurianPayTest::test_from_env_loads_dotenv_file()`.

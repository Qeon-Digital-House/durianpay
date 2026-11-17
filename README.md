# qdh/durianpay

Minimal PHP 8.1+ SDK for the [DurianPay](https://durianpay.id) **SNAP** payment APIs
(Bank Indonesia's *Standar Nasional Open API Pembayaran*): QRIS, e-wallet and virtual accounts.

Docs: [SNAP API reference](https://docs.durianpay.id/reference/snap-payment-apis) ·
[Integration guide](https://docs.durianpay.id/docs/preparation-prerequisite-for-snap-1) ·
[Test & go live](https://docs.durianpay.id/docs/test-go-live-with-snap)

## Requirements

- PHP 8.1+
- `ext-curl`, `ext-json`, `ext-openssl`

## Installation

```bash
composer require qdh/durianpay
```

---

## Configuration

### 1. Credentials

SNAP uses three credentials from the DurianPay dashboard (**Settings > API Key**). Sandbox and live credentials are different.

| Credential | Used for |
|---|---|
| Client Key | `X-CLIENT-KEY` on the token request, `X-PARTNER-ID` on every call |
| Client Secret | HMAC-SHA512 key for transaction signatures |
| Merchant private key | SHA256withRSA signature on the access-token request |

Generate the merchant key pair and upload **the public key** to the dashboard:

```bash
openssl genrsa -out rsa_private_key.pem 2048
openssl rsa -in rsa_private_key.pem -pubout -out rsa_public_key.pem
```

For webhook verification, also download **DurianPay's public key** from the dashboard.

### 2. `.env`

```env
DURIANPAY_CLIENT_KEY=your-client-key
DURIANPAY_CLIENT_SECRET=your-client-secret
# Path to the PEM file, or the PEM itself with newlines written as \n
DURIANPAY_PRIVATE_KEY=storage/durianpay/private_key.pem
DURIANPAY_PRODUCTION=false
# Optional, defaults to 95221
# DURIANPAY_CHANNEL_ID=95221
```

| `DURIANPAY_PRODUCTION` | Environment | Base URL |
|---|---|---|
| `true` / `1` | Live | `https://api.durianpay.id/v1.0/` |
| `false` / `0` / missing | Sandbox | `https://api-sandbox.durianpay.id/v1.0/` |

A relative `DURIANPAY_PRIVATE_KEY` path is resolved against the current directory, then against `envPath`.

### 3. Instantiate

```php
use QDH\DurianPay\DurianPay;
use QDH\DurianPay\Enums\Environment;

$dp = DurianPay::fromEnv();                 // framework already loaded .env
$dp = DurianPay::fromEnv(envPath: __DIR__); // plain PHP: load .env from a directory

$dp = new DurianPay(
    clientKey:    'your-client-key',
    clientSecret: 'your-client-secret',
    privateKey:   file_get_contents('/path/to/private_key.pem'),
    environment:  Environment::Live,
);
```

### What the SDK does for you

- Fetches a B2B access token (`POST /v1.0/access-token/b2b`), caches it in memory for its 15-minute lifetime and re-fetches once on a `401`.
- Signs each request: `X-SIGNATURE = base64(HMAC-SHA512(clientSecret, "METHOD:/v1.0/path:token:sha256(body):timestamp"))`.
- Sends `X-TIMESTAMP` (ISO 8601, `+07:00`), a fresh random `X-EXTERNAL-ID`, `X-PARTNER-ID` and `CHANNEL-ID`.
- Formats amounts as SNAP amount objects: `10000` becomes `{"value": "10000.00", "currency": "IDR"}`.

> The token cache lives in the `DurianPay` instance. In PHP-FPM each request gets a new token. Reuse one instance in long-running workers.

---

## Enums

| Enum | Cases |
|---|---|
| `Environment` | `Sandbox`, `Live` |
| `PaymentStatus` (SNAP `latestTransactionStatus`) | `Completed` `00`, `Initiated` `01`, `Processing` `02`, `Pending` `03`, `Refunded` `04`, `Cancelled` `05`, `Failed` `06`, `NotFound` `07`, `Expired` `08`, `Rejected` `09` |
| `WalletType` | `Dana`, `Ovo`, `LinkAja`, `GoPay`, `ShopeePay` |
| `BankCode` | `Bca`, `Bni`, `Bri`, `Mandiri`, `Permata`, `Cimb`, `Nobu`, `Ina` |
| `VirtualAccountType` | `Closed` `C`, `Open` `O` |
| `Currency` | `Idr` |

`PaymentStatus::isPaid()` is true only for `Completed`. `isFinal()` is false for `Initiated`, `Processing` and `Pending`.

---

## Usage

All methods return the decoded SNAP response `array`, except `status()`, which returns a `PaymentStatus`.

### QRIS — `$dp->qris()`

#### `generate(string $partnerReferenceNo, int|float|string $amount, array $additionalInfo = []): array`

`POST /v1.0/qr/qr-mpm-generate`

```php
$qr = $dp->qris()->generate('ORDER-1001', 50000, [
    'expiryDate'   => '2026-12-01T10:00:00+07:00', // optional, default 1 day
    'customerInfo' => ['given_name' => 'Jane Doe', 'email' => 'jane@example.com'],
]);

// [
//   'responseCode'       => '2004700',
//   'responseMessage'    => 'Successful',
//   'referenceNo'        => 'pay_xxx',        // DurianPay ID: keep it for query/cancel/refund
//   'partnerReferenceNo' => 'ORDER-1001',
//   'qrContent'          => '00020101021226...', // QRIS string, render as QR
//   'qrImage'            => 'data:image/png;base64,...',
//   'additionalInfo'     => ['expiryDate' => '...', 'customerInfo' => [...]],
// ]
```

#### `query(string $referenceNo): array` / `status(string $referenceNo): PaymentStatus`

`POST /v1.0/qr/qr-mpm-query` (serviceCode `47`)

```php
$dp->qris()->query('pay_xxx');
// ['latestTransactionStatus' => '00', 'transactionStatusDesc' => '...', 'paidTime' => '...', 'amount' => [...]]

if ($dp->qris()->status('pay_xxx')->isPaid()) { /* fulfil */ }
```

#### `cancel(string $referenceNo, string $partnerReferenceNo, string $merchantId, string $reason = 'cancelled by merchant'): array`

`POST /v1.0/qr/qr-mpm-cancel`. Works only on payments that aren't completed yet. A `202` response means the request is still in progress, so confirm it with `query()`.

#### `refund(string $referenceNo, string $partnerReferenceNo, string $partnerRefundNo, int|float|string $amount): array`

`POST /v1.0/qr/qr-mpm-refund`. Works only on completed payments.

---

### E-wallet — `$dp->ewallet()`

#### `create(string $partnerReferenceNo, int|float|string $amount, WalletType $channel, string $phoneNumber, array $additionalInfo = []): array`

`POST /v1.0/debit/payment-host-to-host`

```php
use QDH\DurianPay\Enums\WalletType;

$payment = $dp->ewallet()->create('ORDER-1002', 75000, WalletType::ShopeePay, '081234567890', [
    'customerInfo' => ['given_name' => 'Jane Doe'],
]);

// [
//   'responseCode'   => '2005400',
//   'referenceNo'    => 'pay_xxx',
//   'webRedirectUrl' => 'https://...', // redirect the customer (not returned for OVO: push to the app)
//   'additionalInfo' => ['expiryDate' => '...', 'channelCode' => 'SHOPEEPAY', ...],
// ]
```

#### `query(string $referenceNo): array` / `status(string $referenceNo): PaymentStatus`

`POST /v1.0/debit/status` (serviceCode `54`)

#### `cancel(string $referenceNo, string $partnerReferenceNo, ?string $reason = null): array`

`POST /v1.0/debit/cancel`

#### `refund(string $referenceNo, string $partnerReferenceNo, string $partnerRefundNo, int|float|string $amount): array`

`POST /v1.0/debit/refund`

---

### Virtual account — `$dp->virtualAccounts()`

#### `create(BankCode $bank, string $trxId, string $name, int|float|string|null $amount = null, array $options = []): array`

`POST /v1.0/transfer-va/create-va`. Passing an amount creates a **closed** VA (`C`). Passing `null` creates an **open** VA (`O`).

```php
use QDH\DurianPay\Enums\BankCode;

$va = $dp->virtualAccounts()->create(BankCode::Bca, 'ORDER-1003', 'Jane Doe', 150000, [
    'expiredDate'    => '2026-12-01T10:00:00+07:00',
    'customerNo'     => '12345678',              // optional VA suffix
    'additionalInfo' => ['isReusable' => false, 'customerInfo' => ['email' => 'jane@example.com']],
]);

// [
//   'responseCode'       => '2002700',
//   'virtualAccountData' => [
//     'partnerServiceId' => '   12345',
//     'customerNo'       => '12345678',
//     'virtualAccountNo' => '   1234512345678',
//     'trxId'            => 'ORDER-1003',
//     'additionalInfo'   => ['bankCode' => 'BCA', 'payment_instruction' => [...], ...],
//   ],
// ]
```

#### `inquiry(string $partnerServiceId, string $customerNo, string $virtualAccountNo, string $trxId): array`

`POST /v1.0/transfer-va/inquiry-va`. Pass the identifiers exactly as `create()` returned them (`partnerServiceId` may have leading spaces).

#### `update(array $params): array`

`PUT /v1.0/transfer-va/update-va`. `$params` must contain `partnerServiceId`, `customerNo`, `virtualAccountNo`, `virtualAccountName` and `trxId`, plus the fields you want to change (`expiredDate`, `totalAmount`). Setting `expiredDate` to the current time expires the VA.

```php
use QDH\DurianPay\Support\Amount;

$dp->virtualAccounts()->update([
    ...array_intersect_key($va['virtualAccountData'], array_flip(
        ['partnerServiceId', 'customerNo', 'virtualAccountNo', 'virtualAccountName', 'trxId']
    )),
    'totalAmount' => Amount::of(175000),
]);
```

---

### Webhooks (payment notifications)

DurianPay sends `POST` requests to the webhook URL set in **Settings > Webhook**, with `X-SIGNATURE` and `X-TIMESTAMP` headers. The signature is SHA256withRSA over `POST:{path}:{sha256(body)}:{timestamp}`, made with DurianPay's private key.

```php
use QDH\DurianPay\Webhook;

$raw = file_get_contents('php://input');

$valid = Webhook::verify(
    path:      parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), // e.g. /callback/v1.0/qr/qr-mpm-notify
    rawBody:   $raw,
    timestamp: $_SERVER['HTTP_X_TIMESTAMP'] ?? '',
    signature: $_SERVER['HTTP_X_SIGNATURE'] ?? '',
    publicKey: file_get_contents('/path/to/durianpay_public_key.pem'),
);

if (!$valid) {
    http_response_code(401);
    exit;
}

$event = json_decode($raw, true);
// $event['latestTransactionStatus'] === '00' → paid. Dedupe on originalReferenceNo (QRIS/e-wallet)
// or paymentRequestId (VA): DurianPay retries until it gets a 200.

http_response_code(200);
echo json_encode(['responseCode' => '2005200', 'responseMessage' => 'Successful']); // QRIS: 2005200, e-wallet: 2005600
```

Reply within 5 seconds. Only status `00` means the payment is paid. `05`, `08` and `09` must not be fulfilled.

---

### Debug logging (raw request/response)

`onExchange()` receives every HTTP exchange, including the access-token call. Use it to capture
request/response evidence, e.g. for DurianPay's go-live functional testing:

```php
$dp->onExchange(function (array $e) {
    // $e: method, url, request_headers (name => value), request_body,
    //     status, response_body, duration_ms, error
    error_log(json_encode($e, JSON_UNESCAPED_SLASHES));
});
```

Headers are passed **unredacted** (`Authorization`, `X-SIGNATURE`). Enable this in sandbox only.

---

### Error Handling

```php
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

try {
    $dp->qris()->generate('ORDER-1001', 50000);
} catch (ApiException $e) {
    $e->statusCode;      // 400
    $e->responseCode;    // "4004702" (HTTP status + service code + case code)
    $e->responseMessage; // "Invalid Mandatory Field amount"
    $e->traceId();       // send this to DurianPay support
    $e->responseBody;    // full decoded body
} catch (DurianPayException $e) {
    // cURL failure, bad key, unknown status, missing config
}
```

Common codes: `409xx00` means `X-EXTERNAL-ID` was reused on the same day. `409xx01` means a duplicate `partnerReferenceNo`. `401xx01` means an invalid token (the SDK retries once automatically).

---

## Migrating from the legacy (non-SNAP) API

| Legacy | SNAP |
|---|---|
| `new DurianPay($apiKey, $env)` | `new DurianPay($clientKey, $clientSecret, $privateKey, $env)` |
| `DURIANPAY_API_KEY` | `DURIANPAY_CLIENT_KEY`, `DURIANPAY_CLIENT_SECRET`, `DURIANPAY_PRIVATE_KEY` |
| `orders()->create()` + `payments()->charge()` | No orders in SNAP. Call `qris()->generate()`, `ewallet()->create()` or `virtualAccounts()->create()` directly |
| `qris()->charge($orderId, $name, $amount)` | `qris()->generate($partnerReferenceNo, $amount, $additionalInfo)` |
| `payments()->status($id)` / `qris()->status($id)` | `qris()->status($referenceNo)` / `ewallet()->status($referenceNo)` |
| `PaymentStatus::Completed` = `'completed'` | `PaymentStatus::Completed` = `'00'` |
| Retail store, BNPL, online banking | Not available in DurianPay SNAP |

## License

MIT

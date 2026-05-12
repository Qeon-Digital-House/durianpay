# qdh/durianpay

Minimal PHP 8.1+ SDK for the [DurianPay](https://durianpay.id) payment gateway.

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require qdh/durianpay
```

---

## Configuration

### Option 1 — Environment variable (recommended)

Add your API key to your `.env` file:

```env
DURIANPAY_API_KEY=your-api-key-here
```

Then instantiate via `fromEnv()`. It reads `DURIANPAY_API_KEY` by default and
throws a `DurianPayException` if the variable is missing or empty.

```php
use QDH\DurianPay\DurianPay;

$dp = DurianPay::fromEnv();

// Custom variable name
$dp = DurianPay::fromEnv('MY_DURIANPAY_KEY');
```

If you are using plain PHP (no framework), load the `.env` file first with
[vlucas/phpdotenv](https://github.com/vlucas/phpdotenv):

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dp = DurianPay::fromEnv();
```

Frameworks such as Laravel and Symfony load `.env` automatically, so
`fromEnv()` works out of the box.

### Option 2 — Pass the key directly

```php
$dp = new DurianPay('your-api-key-here');
```

---

## Usage

### Payment Types

All payment type channels are defined in the `PaymentType` enum. Passing an
undefined type is a compile-time error — PHP will reject it before the request
is ever made.

```php
use QDH\DurianPay\Enums\PaymentType;

PaymentType::EWallet;        // 'EWALLET'
PaymentType::VirtualAccount; // 'VA'
PaymentType::RetailStore;    // 'RETAILSTORE'
PaymentType::OnlineBanking;  // 'ONLINE_BANKING'
PaymentType::BuyNowPayLater; // 'BNPL'
PaymentType::Qris;           // 'QRIS'
```

---

### Orders

```php
// Create an order (required before charging)
$order = $dp->orders()->create([
    'amount'       => '50000.00',
    'currency'     => 'IDR',
    'order_ref_id' => 'order-001',
    'customer'     => [
        'customer_ref_id' => 'cust-001',
        'given_names'     => 'John Doe',
        'email'           => 'john@example.com',
        'mobile'          => '+6281234567890',
    ],
]);

$orderId = $order['data']['id']; // e.g. "ord_xxxx"

// Fetch a single order
$order = $dp->orders()->fetch('ord_xxxx');

// List orders (paginated)
$orders = $dp->orders()->list(skip: 0, limit: 25);

// Fetch order line items
$items = $dp->orders()->fetchItems('ord_xxxx');
```

---

### Payments

The first argument to `charge()` must be a `PaymentType` enum case.
Any undefined type channel is rejected by PHP at the call site.

```php
use QDH\DurianPay\Enums\PaymentType;

// E-wallet (DANA)
$payment = $dp->payments()->charge(PaymentType::EWallet, [
    'order_id'    => 'ord_xxxx',
    'amount'      => '50000.00',
    'wallet_type' => 'DANA',
    'mobile'      => '+6281234567890',
]);

// Virtual Account
$payment = $dp->payments()->charge(PaymentType::VirtualAccount, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bank_code' => 'BCA',
]);

// Retail store (Alfamart / Indomaret)
$payment = $dp->payments()->charge(PaymentType::RetailStore, [
    'order_id'   => 'ord_xxxx',
    'amount'     => '50000.00',
    'store_type' => 'ALFAMART',
]);

// Online banking
$payment = $dp->payments()->charge(PaymentType::OnlineBanking, [
    'order_id' => 'ord_xxxx',
    'amount'   => '50000.00',
    'type'     => 'BRI_EPAY',
]);

// Buy Now Pay Later
$payment = $dp->payments()->charge(PaymentType::BuyNowPayLater, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bnpl_type' => 'KREDIVO',
]);

// Fetch payment status
$payment = $dp->payments()->fetch('pay_xxxx');

// List payments (paginated)
$payments = $dp->payments()->list(skip: 0, limit: 25);

// Verify a payment signature (after receiving a webhook)
$result = $dp->payments()->verify('pay_xxxx', [
    'verification_signature' => 'signature-from-webhook',
]);

// Capture a previously authorised payment
$dp->payments()->capture('pay_xxxx');
```

---

### QRIS

```php
// Create a QRIS charge
$qris = $dp->qris()->charge(
    orderId: 'ord_xxxx',
    name:    'John Doe',
    amount:  50000,
);

// $qris['data']['qr_code_string'] — QR payload to display
// $qris['data']['qr_code_image']  — base64 QR image

// Poll for payment status
$status = $dp->qris()->fetch('pay_xxxx');
```

---

### Error Handling

```php
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

try {
    $payment = $dp->payments()->charge(PaymentType::EWallet, [...]);
} catch (ApiException $e) {
    // HTTP 4xx / 5xx from DurianPay
    echo $e->statusCode;       // e.g. 422
    print_r($e->responseBody); // decoded JSON response
} catch (DurianPayException $e) {
    // Network / cURL error, or missing env variable
    echo $e->getMessage();
}
```

## License

MIT

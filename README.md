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

## Usage

### Bootstrap

```php
use QDH\DurianPay\DurianPay;

$dp = new DurianPay('your-api-key');
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

```php
// Charge via e-wallet (e.g. DANA)
$payment = $dp->payments()->charge([
    'type'    => 'EWALLET',
    'request' => [
        'order_id'    => 'ord_xxxx',
        'amount'      => '50000.00',
        'wallet_type' => 'DANA',
        'mobile'      => '+6281234567890',
    ],
]);

// Charge via Virtual Account
$payment = $dp->payments()->charge([
    'type'    => 'VA',
    'request' => [
        'order_id'    => 'ord_xxxx',
        'amount'      => '50000.00',
        'bank_code'   => 'BCA',
    ],
]);

// Fetch payment status
$payment = $dp->payments()->fetch('pay_xxxx');

// List payments (paginated)
$payments = $dp->payments()->list(skip: 0, limit: 25);

// Verify a payment signature (after webhook)
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
    $order = $dp->orders()->create([...]);
} catch (ApiException $e) {
    // HTTP 4xx / 5xx from DurianPay
    echo $e->statusCode;       // e.g. 422
    print_r($e->responseBody); // decoded JSON response
} catch (DurianPayException $e) {
    // Network / cURL error
    echo $e->getMessage();
}
```

## License

MIT

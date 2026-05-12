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

Add your API key and environment flag to your `.env` file:

```env
DURIANPAY_API_KEY=your-api-key-here
DURIANPAY_PRODUCTION=false
```

| `DURIANPAY_PRODUCTION` | Environment |
|---|---|
| `true` / `1` | Live (production) |
| `false` / `0` / missing | Sandbox |

**With a framework (Laravel, Symfony, etc.)** — the `.env` is already loaded:

```php
use QDH\DurianPay\DurianPay;

$dp = DurianPay::fromEnv();
```

**Plain PHP** — pass the directory that contains your `.env` file:

```php
$dp = DurianPay::fromEnv(envPath: __DIR__);
```

**Direct instantiation:**

```php
use QDH\DurianPay\Enums\Environment;

$dp = new DurianPay('dp_test_xxxxx', Environment::Sandbox);
$dp = new DurianPay('dp_live_xxxxx', Environment::Live);
```

---

## Enums

### Environment

```php
Environment::Sandbox; // 'sandbox'
Environment::Live;    // 'live'
```

### Payment type

```php
PaymentType::EWallet;        // 'EWALLET'
PaymentType::VirtualAccount; // 'VA'
PaymentType::RetailStore;    // 'RETAILSTORE'
PaymentType::OnlineBanking;  // 'ONLINE_BANKING'
PaymentType::BuyNowPayLater; // 'BNPL'
PaymentType::Qris;           // 'QRIS'
```

### Payment status

```php
PaymentStatus::Started;    // 'STARTED'
PaymentStatus::Processing; // 'PROCESSING'
PaymentStatus::Completed;  // 'COMPLETED'
PaymentStatus::Failed;     // 'FAILED'
PaymentStatus::Cancelled;  // 'CANCELLED'
PaymentStatus::Expired;    // 'EXPIRED'
PaymentStatus::Pending;    // 'PENDING'
```

### E-wallet (`WalletType`)

```php
WalletType::Dana;      // 'DANA'
WalletType::Ovo;       // 'OVO'
WalletType::LinkAja;   // 'LINKAJA'
WalletType::GoPay;     // 'GOPAY'
WalletType::ShopeePay; // 'SHOPEEPAY'
WalletType::JeniusPay; // 'JENIUSPAY'
WalletType::AstraPay;  // 'ASTRAPAY'
```

### Virtual Account bank (`BankCode`)

```php
BankCode::Bca;     // 'BCA'
BankCode::Bni;     // 'BNI'
BankCode::Bri;     // 'BRI'
BankCode::Mandiri; // 'MANDIRI'
BankCode::Permata; // 'PERMATA'
BankCode::Bsi;     // 'BSI'
BankCode::Cimb;    // 'CIMB'
BankCode::Danamon; // 'DANAMON'
BankCode::Btn;     // 'BTN'
BankCode::Maybank; // 'MAYBANK'
```

### Retail store (`RetailStore`)

```php
RetailStore::Alfamart;  // 'ALFAMART'
RetailStore::Indomaret; // 'INDOMARET'
```

### Buy Now Pay Later (`BnplType`)

```php
BnplType::Kredivo;   // 'KREDIVO'
BnplType::Akulaku;   // 'AKULAKU'
BnplType::Indodana;  // 'INDODANA'
BnplType::SpayLater; // 'SPAYLATER'
```

### Online banking (`OnlineBankingType`)

```php
OnlineBankingType::BriEpay;         // 'BRI_EPAY'
OnlineBankingType::CimbClicks;      // 'CIMB_CLICKS'
OnlineBankingType::DanamonOnline;   // 'DANAMON_ONLINE'
OnlineBankingType::MandiriClickpay; // 'MANDIRI_CLICKPAY'
OnlineBankingType::PermataNet;      // 'PERMATA_NET'
```

### Currency

DurianPay operates exclusively in Indonesia. **IDR is the only supported currency.**

```php
Currency::Idr; // 'IDR'
```

---

## Usage

### Orders

#### `create(array $params): array`

```php
$order = $dp->orders()->create([
    'amount'       => '50000.00',
    'currency'     => Currency::Idr->value,
    'order_ref_id' => 'order-001',
    'customer'     => [
        'customer_ref_id' => 'cust-001',
        'given_names'     => 'John Doe',
        'email'           => 'john@example.com',
        'mobile'          => '+6281234567890',
    ],
]);
// returns:
// [
//   'data' => [
//     'id'           => 'ord_gQFDFtQVb80836',
//     'customer_id'  => 'cust_XgXAFtBVc00041',
//     'amount'       => '50000.00',
//     'currency'     => 'IDR',
//     'status'       => 'CREATED',
//     'order_ref_id' => 'order-001',
//     'created_at'   => '2024-01-15T10:00:00.000Z',
//     'updated_at'   => '2024-01-15T10:00:00.000Z',
//   ]
// ]
```

#### `fetch(string $id): array`

```php
$order = $dp->orders()->fetch('ord_gQFDFtQVb80836');
// returns: (same shape as create)
// [
//   'data' => [
//     'id'           => 'ord_gQFDFtQVb80836',
//     'amount'       => '50000.00',
//     'currency'     => 'IDR',
//     'status'       => 'COMPLETED',
//     'order_ref_id' => 'order-001',
//     'created_at'   => '2024-01-15T10:00:00.000Z',
//     'updated_at'   => '2024-01-15T10:05:00.000Z',
//   ]
// ]
```

#### `list(int $skip, int $limit): array`

```php
$orders = $dp->orders()->list(skip: 0, limit: 25);
// returns:
// [
//   'data' => [
//     ['id' => 'ord_gQFDFtQVb80836', 'amount' => '50000.00', 'status' => 'COMPLETED', ...],
//     ['id' => 'ord_hRGEGuRWc91947', 'amount' => '75000.00', 'status' => 'CREATED',   ...],
//   ],
//   'total' => 2,
// ]
```

#### `fetchItems(string $id): array`

```php
$items = $dp->orders()->fetchItems('ord_gQFDFtQVb80836');
// returns:
// [
//   'data' => [
//     [
//       'id'       => 'item_001',
//       'name'     => 'Product A',
//       'price'    => '50000.00',
//       'qty'      => 1,
//       'logo'     => 'https://example.com/product-a.jpg',
//       'sku'      => 'SKU-001',
//     ],
//   ]
// ]
```

---

### Payments

#### `charge(PaymentType $type, array $request): array`

```php
use QDH\DurianPay\Enums\{PaymentType, WalletType, BankCode, RetailStore, BnplType, OnlineBankingType};

// E-wallet
$payment = $dp->payments()->charge(PaymentType::EWallet, [
    'order_id'    => 'ord_gQFDFtQVb80836',
    'amount'      => '50000.00',
    'wallet_type' => WalletType::Dana->value,
    'mobile'      => '+6281234567890',
]);
// returns:
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'order_id'   => 'ord_gQFDFtQVb80836',
//     'amount'     => '50000.00',
//     'currency'   => 'IDR',
//     'status'     => 'STARTED',
//     'method_id'  => 'EWALLET_DANA',
//     'mobile'     => '+6281234567890',
//     'checkout_url' => 'https://checkout.dana.id/pay?token=xxxxx',
//     'created_at' => '2024-01-15T10:00:00.000Z',
//     'is_live'    => false,
//   ]
// ]

// Virtual Account
$payment = $dp->payments()->charge(PaymentType::VirtualAccount, [
    'order_id'  => 'ord_gQFDFtQVb80836',
    'amount'    => '50000.00',
    'bank_code' => BankCode::Bca->value,
]);
// returns:
// [
//   'data' => [
//     'id'             => 'pay_AaBbCcDd001122',
//     'order_id'       => 'ord_gQFDFtQVb80836',
//     'amount'         => '50000.00',
//     'currency'       => 'IDR',
//     'status'         => 'STARTED',
//     'method_id'      => 'VA_BCA',
//     'va_number'      => '1234567890123456',
//     'bank_code'      => 'BCA',
//     'expiration_time'=> '2024-01-15T11:00:00.000Z',
//     'created_at'     => '2024-01-15T10:00:00.000Z',
//     'is_live'        => false,
//   ]
// ]

// Retail store
$payment = $dp->payments()->charge(PaymentType::RetailStore, [
    'order_id'   => 'ord_gQFDFtQVb80836',
    'amount'     => '50000.00',
    'store_type' => RetailStore::Alfamart->value,
]);
// returns:
// [
//   'data' => [
//     'id'           => 'pay_AaBbCcDd001122',
//     'order_id'     => 'ord_gQFDFtQVb80836',
//     'amount'       => '50000.00',
//     'currency'     => 'IDR',
//     'status'       => 'STARTED',
//     'method_id'    => 'RETAILSTORE_ALFAMART',
//     'payment_code' => '85012345678',
//     'store_type'   => 'ALFAMART',
//     'expiration_time' => '2024-01-15T11:00:00.000Z',
//     'created_at'   => '2024-01-15T10:00:00.000Z',
//     'is_live'      => false,
//   ]
// ]

// Online banking
$payment = $dp->payments()->charge(PaymentType::OnlineBanking, [
    'order_id' => 'ord_gQFDFtQVb80836',
    'amount'   => '50000.00',
    'type'     => OnlineBankingType::BriEpay->value,
]);
// returns:
// [
//   'data' => [
//     'id'           => 'pay_AaBbCcDd001122',
//     'order_id'     => 'ord_gQFDFtQVb80836',
//     'amount'       => '50000.00',
//     'currency'     => 'IDR',
//     'status'       => 'STARTED',
//     'method_id'    => 'ONLINE_BANKING_BRI_EPAY',
//     'checkout_url' => 'https://briapi.co.id/pay?token=xxxxx',
//     'created_at'   => '2024-01-15T10:00:00.000Z',
//     'is_live'      => false,
//   ]
// ]

// Buy Now Pay Later
$payment = $dp->payments()->charge(PaymentType::BuyNowPayLater, [
    'order_id'  => 'ord_gQFDFtQVb80836',
    'amount'    => '50000.00',
    'bnpl_type' => BnplType::Kredivo->value,
]);
// returns:
// [
//   'data' => [
//     'id'           => 'pay_AaBbCcDd001122',
//     'order_id'     => 'ord_gQFDFtQVb80836',
//     'amount'       => '50000.00',
//     'currency'     => 'IDR',
//     'status'       => 'STARTED',
//     'method_id'    => 'BNPL_KREDIVO',
//     'checkout_url' => 'https://checkout.kredivo.com/pay?token=xxxxx',
//     'created_at'   => '2024-01-15T10:00:00.000Z',
//     'is_live'      => false,
//   ]
// ]
```

#### `checkStatus(string $id): array`

Calls `GET /payments/{id}/status` — returns a focused status response.

```php
$response = $dp->payments()->checkStatus('pay_AaBbCcDd001122');
// returns:
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'status'     => 'COMPLETED',
//     'updated_at' => '2024-01-15T10:05:00.000Z',
//   ]
// ]
```

#### `status(string $id): PaymentStatus`

Convenience wrapper around `checkStatus()` — returns a typed enum.

```php
use QDH\DurianPay\Enums\PaymentStatus;

$status = $dp->payments()->status('pay_AaBbCcDd001122');
// returns one of:
//   PaymentStatus::Started    — payment initiated, awaiting user action
//   PaymentStatus::Processing — being processed by the payment provider
//   PaymentStatus::Completed  — payment successful
//   PaymentStatus::Failed     — payment failed
//   PaymentStatus::Cancelled  — cancelled by user or merchant
//   PaymentStatus::Expired    — payment window closed
//   PaymentStatus::Pending    — awaiting further confirmation

if ($status === PaymentStatus::Completed) {
    // fulfil the order
}

if (in_array($status, [PaymentStatus::Failed, PaymentStatus::Expired, PaymentStatus::Cancelled])) {
    // notify the user and allow retry
}
```

#### `fetch(string $id): array`

```php
$payment = $dp->payments()->fetch('pay_AaBbCcDd001122');
// returns: (full payment object)
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'order_id'   => 'ord_gQFDFtQVb80836',
//     'amount'     => '50000.00',
//     'currency'   => 'IDR',
//     'status'     => 'COMPLETED',
//     'method_id'  => 'EWALLET_DANA',
//     'created_at' => '2024-01-15T10:00:00.000Z',
//     'updated_at' => '2024-01-15T10:05:00.000Z',
//     'is_live'    => false,
//   ]
// ]
```

#### `list(int $skip, int $limit): array`

```php
$payments = $dp->payments()->list(skip: 0, limit: 25);
// returns:
// [
//   'data' => [
//     ['id' => 'pay_AaBbCcDd001122', 'amount' => '50000.00', 'status' => 'COMPLETED', ...],
//     ['id' => 'pay_BbCcDdEe002233', 'amount' => '75000.00', 'status' => 'FAILED',    ...],
//   ],
//   'total' => 2,
// ]
```

#### `verify(string $id, array $params): array`

```php
$result = $dp->payments()->verify('pay_AaBbCcDd001122', [
    'verification_signature' => 'sha256-signature-from-webhook',
]);
// returns:
// [
//   'data' => [
//     'is_valid' => true,
//   ]
// ]
```

#### `capture(string $id): array`

```php
$result = $dp->payments()->capture('pay_AaBbCcDd001122');
// returns:
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'status'     => 'COMPLETED',
//     'updated_at' => '2024-01-15T10:05:00.000Z',
//   ]
// ]
```

---

### QRIS

#### `charge(string $orderId, string $name, int $amount): array`

```php
$qris = $dp->qris()->charge(
    orderId: 'ord_gQFDFtQVb80836',
    name:    'John Doe',
    amount:  50000,
);
// returns:
// [
//   'data' => [
//     'id'              => 'pay_AaBbCcDd001122',
//     'order_id'        => 'ord_gQFDFtQVb80836',
//     'amount'          => 50000,
//     'currency'        => 'IDR',
//     'status'          => 'STARTED',
//     'method_id'       => 'QRIS',
//     'qr_code_string'  => '00020101021226....',  // QRIS payload (display in QR renderer)
//     'qr_code_image'   => 'data:image/png;base64,iVBOR...',  // base64 QR image
//     'expiration_time' => '2024-01-15T10:15:00.000Z',
//     'created_at'      => '2024-01-15T10:00:00.000Z',
//     'is_live'         => false,
//   ]
// ]
```

#### `checkStatus(string $id): array`

Calls `GET /payments/{id}/status` — use this to poll until the customer scans the QR.

```php
$response = $dp->qris()->checkStatus('pay_AaBbCcDd001122');
// returns:
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'status'     => 'PROCESSING',
//     'updated_at' => '2024-01-15T10:02:00.000Z',
//   ]
// ]
```

#### `status(string $id): PaymentStatus`

Convenience wrapper — returns a typed enum for easy comparison.

```php
$status = $dp->qris()->status('pay_AaBbCcDd001122');
// returns one of:
//   PaymentStatus::Started    — QR generated, not yet scanned
//   PaymentStatus::Processing — scanned, awaiting bank confirmation
//   PaymentStatus::Completed  — payment confirmed
//   PaymentStatus::Failed     — payment failed
//   PaymentStatus::Cancelled  — cancelled
//   PaymentStatus::Expired    — QR code expired
//   PaymentStatus::Pending    — awaiting further confirmation

if ($status === PaymentStatus::Completed) {
    // QRIS payment confirmed, fulfil the order
}
```

#### `fetch(string $id): array`

```php
$detail = $dp->qris()->fetch('pay_AaBbCcDd001122');
// returns: (full payment object, same shape as Payments::fetch)
// [
//   'data' => [
//     'id'         => 'pay_AaBbCcDd001122',
//     'order_id'   => 'ord_gQFDFtQVb80836',
//     'amount'     => 50000,
//     'currency'   => 'IDR',
//     'status'     => 'COMPLETED',
//     'method_id'  => 'QRIS',
//     'created_at' => '2024-01-15T10:00:00.000Z',
//     'updated_at' => '2024-01-15T10:05:00.000Z',
//     'is_live'    => false,
//   ]
// ]
```

---

### Error Handling

```php
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

try {
    $status = $dp->payments()->status('pay_xxxx');
} catch (ApiException $e) {
    // HTTP 4xx / 5xx from DurianPay
    $e->statusCode;       // int,   e.g. 404
    $e->responseBody;     // array, e.g. ['error' => 'payment_not_found']
    $e->getMessage();     // string of the JSON-encoded body
    $e->getCode();        // same as statusCode
} catch (DurianPayException $e) {
    // network / cURL error, or unrecognised status value from status()
    $e->getMessage();
}
```

## License

MIT

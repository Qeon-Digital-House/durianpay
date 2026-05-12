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

```php
use QDH\DurianPay\Enums\Currency;

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

$orderId = $order['data']['id'];

$order   = $dp->orders()->fetch('ord_xxxx');
$orders  = $dp->orders()->list(skip: 0, limit: 25);
$items   = $dp->orders()->fetchItems('ord_xxxx');
```

---

### Payments

```php
use QDH\DurianPay\Enums\{PaymentType, PaymentStatus, WalletType, BankCode,
                          RetailStore, BnplType, OnlineBankingType};

// Charge
$payment = $dp->payments()->charge(PaymentType::EWallet, [
    'order_id'    => 'ord_xxxx',
    'amount'      => '50000.00',
    'wallet_type' => WalletType::Dana->value,
    'mobile'      => '+6281234567890',
]);

$payment = $dp->payments()->charge(PaymentType::VirtualAccount, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bank_code' => BankCode::Bca->value,
]);

$payment = $dp->payments()->charge(PaymentType::RetailStore, [
    'order_id'   => 'ord_xxxx',
    'amount'     => '50000.00',
    'store_type' => RetailStore::Alfamart->value,
]);

$payment = $dp->payments()->charge(PaymentType::OnlineBanking, [
    'order_id' => 'ord_xxxx',
    'amount'   => '50000.00',
    'type'     => OnlineBankingType::BriEpay->value,
]);

$payment = $dp->payments()->charge(PaymentType::BuyNowPayLater, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bnpl_type' => BnplType::Kredivo->value,
]);

// Check status — returns a typed PaymentStatus enum
$status = $dp->payments()->status('pay_xxxx');

if ($status === PaymentStatus::Completed) {
    // payment successful
}

if ($status === PaymentStatus::Failed || $status === PaymentStatus::Expired) {
    // handle failure
}

// Full payment details
$payment  = $dp->payments()->fetch('pay_xxxx');
$payments = $dp->payments()->list(skip: 0, limit: 25);

$dp->payments()->verify('pay_xxxx', ['verification_signature' => 'sig']);
$dp->payments()->capture('pay_xxxx');
```

---

### QRIS

```php
use QDH\DurianPay\Enums\PaymentStatus;

// Create a QRIS charge
$qris = $dp->qris()->charge(
    orderId: 'ord_xxxx',
    name:    'John Doe',
    amount:  50000,
);

// $qris['data']['qr_code_string'] — QR payload to display
// $qris['data']['qr_code_image']  — base64 QR image

// Check status — returns a typed PaymentStatus enum
$status = $dp->qris()->status('pay_xxxx');

if ($status === PaymentStatus::Completed) {
    // QRIS payment confirmed
}

// Full payment details
$detail = $dp->qris()->fetch('pay_xxxx');
```

---

### Error Handling

```php
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

try {
    $status = $dp->payments()->status('pay_xxxx');
} catch (ApiException $e) {
    echo $e->statusCode;       // e.g. 404
    print_r($e->responseBody);
} catch (DurianPayException $e) {
    // network error or unknown status value
    echo $e->getMessage();
}
```

## License

MIT

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

echo $dp->environment->value; // 'sandbox' or 'live'
```

**Plain PHP** — pass the directory that contains your `.env` file:

```php
$dp = DurianPay::fromEnv(envPath: __DIR__);
```

**Direct instantiation** — pass the key and environment explicitly:

```php
use QDH\DurianPay\Enums\Environment;

$dp = new DurianPay('dp_test_xxxxx', Environment::Sandbox);
$dp = new DurianPay('dp_live_xxxxx', Environment::Live);
```

---

## Enums

### Environment

```php
use QDH\DurianPay\Enums\Environment;

Environment::Sandbox; // 'sandbox'
Environment::Live;    // 'live'
```

### Payment type

```php
use QDH\DurianPay\Enums\PaymentType;

PaymentType::EWallet;        // 'EWALLET'
PaymentType::VirtualAccount; // 'VA'
PaymentType::RetailStore;    // 'RETAILSTORE'
PaymentType::OnlineBanking;  // 'ONLINE_BANKING'
PaymentType::BuyNowPayLater; // 'BNPL'
PaymentType::Qris;           // 'QRIS'
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

$orderId = $order['data']['id']; // e.g. "ord_xxxx"

$order   = $dp->orders()->fetch('ord_xxxx');
$orders  = $dp->orders()->list(skip: 0, limit: 25);
$items   = $dp->orders()->fetchItems('ord_xxxx');
```

---

### Payments

```php
use QDH\DurianPay\Enums\{PaymentType, WalletType, BankCode, RetailStore, BnplType, OnlineBankingType};

// E-wallet
$payment = $dp->payments()->charge(PaymentType::EWallet, [
    'order_id'    => 'ord_xxxx',
    'amount'      => '50000.00',
    'wallet_type' => WalletType::Dana->value,
    'mobile'      => '+6281234567890',
]);

// Virtual Account
$payment = $dp->payments()->charge(PaymentType::VirtualAccount, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bank_code' => BankCode::Bca->value,
]);

// Retail store
$payment = $dp->payments()->charge(PaymentType::RetailStore, [
    'order_id'   => 'ord_xxxx',
    'amount'     => '50000.00',
    'store_type' => RetailStore::Alfamart->value,
]);

// Online banking
$payment = $dp->payments()->charge(PaymentType::OnlineBanking, [
    'order_id' => 'ord_xxxx',
    'amount'   => '50000.00',
    'type'     => OnlineBankingType::BriEpay->value,
]);

// Buy Now Pay Later
$payment = $dp->payments()->charge(PaymentType::BuyNowPayLater, [
    'order_id'  => 'ord_xxxx',
    'amount'    => '50000.00',
    'bnpl_type' => BnplType::Kredivo->value,
]);

$payment  = $dp->payments()->fetch('pay_xxxx');
$payments = $dp->payments()->list(skip: 0, limit: 25);

$dp->payments()->verify('pay_xxxx', [
    'verification_signature' => 'signature-from-webhook',
]);

$dp->payments()->capture('pay_xxxx');
```

---

### QRIS

```php
$qris = $dp->qris()->charge(
    orderId: 'ord_xxxx',
    name:    'John Doe',
    amount:  50000,
);

// $qris['data']['qr_code_string'] — QR payload to display
// $qris['data']['qr_code_image']  — base64 QR image

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
    echo $e->statusCode;       // e.g. 422
    print_r($e->responseBody); // decoded JSON response
} catch (DurianPayException $e) {
    echo $e->getMessage();     // network / cURL / missing env var
}
```

## License

MIT

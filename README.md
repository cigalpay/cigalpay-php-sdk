# CigalPay PHP SDK

Official PHP library for [CigalPay](https://cigalpay.com): accept crypto payments (BTC, ETH, USDT, SOL, LTC, DOGE and more) in your app, SaaS or website. Payments are priced in USD or EUR, and the customer picks the coin on the CigalPay checkout page.

- PHP 7.4+ with `curl` and `json`. No other dependencies.
- Hosted checkout in one call.
- Webhook signature check that matches CigalPay byte for byte.

## Install

```bash
composer require cigalpay/cigalpay-php-sdk
```

## Credentials

Get these from your dashboard under **Settings → API credentials**:

| Value | Used for |
| --- | --- |
| Account ID | `createCheckout()` |
| API key | the payment API (`createPayment()`, `getPayment()`…) |
| Notification signing secret | `verifyWebhook()` |

Keep them on your server, in environment variables. Never put them in front-end code.

## 1. Create a checkout

```php
use Cigalpay\CigalPay;

$cigalpay = new CigalPay([
    'accountId' => getenv('CIGALPAY_ACCOUNT_ID'),
    'apiKey'    => getenv('CIGALPAY_API_KEY'),
]);

$checkout = $cigalpay->createCheckout([
    'amount'      => 49.90,
    'currency'    => 'USD',                // or 'EUR'
    'orderId'     => 'order-1042',         // your reference, sent back as extraId
    'description' => 'Pro plan - 1 month',
    'returnUrl'   => 'https://example.com/thanks',
    'notifyUrl'   => 'https://example.com/cigalpay/webhook',
    'lang'        => 'en',                 // en, fr, es, de
]);

// save $checkout['id'] with the order, then:
header('Location: ' . $checkout['chargeLink']);
```

## 2. Receive the payment notification

CigalPay POSTs the payment to `notifyUrl` each time its status changes. The `x-cigalpay-sig` header signs the body. Pass the body **exactly as received**:

```php
use Cigalpay\CigalPay;

$raw = file_get_contents('php://input');
$sig = isset($_SERVER['HTTP_X_CIGALPAY_SIG']) ? $_SERVER['HTTP_X_CIGALPAY_SIG'] : '';

if (!CigalPay::verifyWebhook($raw, $sig, getenv('CIGALPAY_IPN_SECRET'))) {
    http_response_code(401);
    exit;
}

$payment = CigalPay::parseWebhook($raw);

$order = find_order($payment['extraId']);

if (CigalPay::isPaid($payment['status']) && $order
    && isset($payment['checkoutId']) && hash_equals($order->cigalpay_checkout_id, $payment['checkoutId'])) {
    // CONFIRMED or FINISHED, for the checkout you created: mark the order paid (only once)
}

http_response_code(200);
```

Statuses: `WAITING`, `CONFIRMING`, `CONFIRMED`, `SENDING`, `FINISHED`, `PARTIALLY_PAID`, `EXPIRED`, `FAILED` (constants `CigalPay::STATUS_*`). Ship only on `CONFIRMED` or `FINISHED`.

The same payment can be notified more than once, so make your handler idempotent.

Always compare `checkoutId` with the `id` that `createCheckout()` returned for that order. Anyone can create a checkout with your account ID and one of your order references, so `extraId` alone doesn't prove the order was paid in full. `checkoutAmount` and `checkoutCurrency` give the checkout's price.

## Payment API

Use the payment API if you build your own payment page. It needs `apiKey`.

```php
$payment = $cigalpay->createPayment([
    'amount'    => 0.0015,
    'currency'  => 'BTC',
    'orderId'   => 'order-1042',
    'notifyUrl' => 'https://example.com/cigalpay/webhook',
    'returnUrl' => 'https://example.com/thanks',
]);

$cigalpay->getPayment($payment['id']);
$cigalpay->getPaymentsByOrder('order-1042');
$cigalpay->getActivePayments();
$cigalpay->getSupportedCoins();
```

## Refunds

Refund a paid payment from your server. It needs `apiKey`.

```php
$res = $cigalpay->createRefund([
    'paymentId'      => $payment['id'],   // the `id` in the payment notification
    'checkoutAmount' => 20.00,            // in the checkout's USD / EUR, or 'amount' in the coin paid
    'customerEmail'  => 'customer@example.com',
    'reason'         => 'Returned item',
]);
$res['link'];                              // the customer enters their address here

$cigalpay->getRefunds($payment['id']);     // status: awaiting_address, pending_review, sent...
```

- The refund and its network fee come out of your CigalPay balance in the payment's coin. A payment forwarded to your own wallet can't be refunded this way: refund it from that wallet.
- `checkoutAmount` is converted at the payment's rate, so the customer gets back the same share of the coin they sent.
- CigalPay's fee on the payment is not refunded.

## Errors

Every API failure throws `Cigalpay\Exceptions\CigalpayException`:
- `getStatus()` is the HTTP status (`0` = network error).
- `getBody()` is the parsed reply.

```php
try {
    $cigalpay->getActivePayments();
} catch (\Cigalpay\Exceptions\CigalpayException $e) {
    error_log($e->getStatus() . ' ' . $e->getMessage());
}
```

## Upgrading from 1.x

- `Cigalpay\CigalpaySDK` still works. It now points to the current API (the old `api.cigalpay.icu` host is gone).
- New code should use `Cigalpay\CigalPay`.
- `getInvoiceStatus()` was removed: use `getPayment()`.

## Tests

```bash
composer test
```

The tests need Node.js to sign the test notifications the same way CigalPay does.

## License

MIT

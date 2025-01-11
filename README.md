# cigalpay-php-sdk

CigalPay-PHP-SDK is a robust PHP library for integrating the CigalPay cryptocurrency payment gateway. It simplifies handling transactions, creating invoices, fetching payment statuses, and managing crypto payments with a secure and developer-friendly approach.

## Features

- Accept multiple cryptocurrencies including BTC, ETH, LTC, DOGE, DASH, SOL, MATIC and BCH.
- Real-time payment tracking.
- Secure API with AES-256 encryption.
- Easy-to-use PHP SDK with Composer support.

## Installation

To install the CigalPay PHP SDK using Composer:

```bash
composer require cigalpay/cigalpay-php-sdk
```

## Example Usage

```php
$sdk = new \Cigalpay\CigalpaySDK('https://api.cigalpay.icu', 'your-api-key');
```

```php
$response = $sdk->createPayment(0.05, 'LTC', 'order123', 'https://callback.url/ipn', 'https://callback.url/invoice');
```

```php
print_r($response);
```

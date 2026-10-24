<?php

namespace Cigalpay;

/**
 * @deprecated v1 client, kept so existing integrations keep working. Use CigalPay.
 */
class CigalpaySDK extends CigalPay
{
    public function __construct($baseUrl, $apiKey)
    {
        $base = rtrim((string) $baseUrl, '/');
        // The v1 default host no longer exists.
        if ($base === '' || strpos($base, 'cigalpay.icu') !== false) $base = self::API_URL;
        parent::__construct(['apiKey' => $apiKey, 'apiUrl' => $base]);
    }

    public function createPayment($amount, $currency = null, $extraId = null, $ipnCallbackUrl = null, $invoiceCallbackUrl = null)
    {
        if (is_array($amount)) return parent::createPayment($amount);
        return parent::createPayment([
            'amount' => $amount,
            'currency' => $currency,
            'orderId' => $extraId,
            'notifyUrl' => $ipnCallbackUrl,
            'returnUrl' => $invoiceCallbackUrl,
        ]);
    }

    public function getPaymentStatus($id)
    {
        return $this->getPayment($id);
    }

    public function getPaymentsByExtraId($extraId)
    {
        return $this->getPaymentsByOrder($extraId);
    }
}

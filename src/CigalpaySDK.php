<?php
namespace Cigalpay;

class CigalpaySDK {
    private $baseUrl;
    private $apiKey;

    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    private function request($method, $endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'cigalpay-api-key: ' . $this->apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }

    public function createPayment($amount, $currency, $extraId, $ipnCallbackUrl, $invoiceCallbackUrl) {
        return $this->request('POST', '/createPayment', [
            'amount' => $amount,
            'currency' => $currency,
            'extraId' => $extraId,
            'ipnCallbackUrl' => $ipnCallbackUrl,
            'invoiceCallbackUrl' => $invoiceCallbackUrl
        ]);
    }

    public function getInvoiceStatus($invoiceId) {
        if (!is_string($invoiceId) || empty($invoiceId)) {
            throw new \InvalidArgumentException("Invoice ID must be a non-empty string.");
        }
        return $this->request('GET', '/getInvoiceStatus?invoiceId=' . urlencode($invoiceId));
    }

    public function getPaymentStatus($id) {
        return $this->request('GET', '/getPaymentStatus?id=' . urlencode($id));
    }

    public function getPaymentsByExtraId($extraId) {
        return $this->request('GET', '/getPaymentsByExtraId?extraId=' . urlencode($extraId));
    }

    public function getActivePayments() {
        return $this->request('GET', '/activePayments');
    }
}

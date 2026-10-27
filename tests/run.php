<?php
// php tests/run.php  (needs node to generate the signed fixtures)
require __DIR__ . '/../src/Exceptions/CigalpayException.php';
require __DIR__ . '/../src/Webhook.php';
require __DIR__ . '/../src/CigalPay.php';
require __DIR__ . '/../src/CigalpaySDK.php';

use Cigalpay\CigalPay;

$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? "ok   " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

$fixtures = json_decode(shell_exec('node ' . escapeshellarg(__DIR__ . '/fixtures.mjs')), true);
check('fixtures generated', is_array($fixtures) && count($fixtures) === 3);
foreach ($fixtures as $i => $f) {
    check("signature #$i verifies", CigalPay::verifyWebhook($f['body'], $f['sig'], 'test-secret'));
    check("signature #$i with surrounding whitespace verifies", CigalPay::verifyWebhook("  " . $f["body"] . "\n", $f["sig"], "test-secret"));
    check("signature #$i wrong secret", !CigalPay::verifyWebhook($f['body'], $f['sig'], 'nope'));
}
$tampered = str_replace('"amountPaid":49.5', '"amountPaid":4950', $fixtures[0]['body']);
check('tampered body rejected', $tampered !== $fixtures[0]['body'] && !CigalPay::verifyWebhook($tampered, $fixtures[0]['sig'], 'test-secret'));
check('invalid JSON rejected', !CigalPay::verifyWebhook('{"a":', $fixtures[0]['sig'], 'test-secret'));
check('array root rejected', !CigalPay::verifyWebhook('[1]', $fixtures[0]['sig'], 'test-secret'));
check('missing signature rejected', !CigalPay::verifyWebhook($fixtures[0]['body'], null, 'test-secret'));
check('isPaid', CigalPay::isPaid('CONFIRMED') && CigalPay::isPaid('FINISHED') && !CigalPay::isPaid('CONFIRMING'));
check('parseWebhook', CigalPay::parseWebhook($fixtures[0]['body'])['extraId'] === '1042');

try { (new CigalPay())->createCheckout(['amount' => 1, 'currency' => 'USD', 'orderId' => 1, 'description' => 'x', 'returnUrl' => 'https://a.b', 'notifyUrl' => 'https://a.b']); check('needs accountId', false); }
catch (\Cigalpay\Exceptions\CigalpayException $e) { check('needs accountId', strpos($e->getMessage(), 'accountId') !== false); }

try { (new CigalPay())->createRefund(['paymentId' => 'x', 'amount' => 1]); check('createRefund needs apiKey', false); }
catch (\Cigalpay\Exceptions\CigalpayException $e) { check('createRefund needs apiKey', strpos($e->getMessage(), 'apiKey') !== false); }
try { (new CigalPay(['apiKey' => 'k']))->createRefund(['amount' => 1]); check('createRefund needs paymentId', false); }
catch (\Cigalpay\Exceptions\CigalpayException $e) { check('createRefund needs paymentId', strpos($e->getMessage(), 'paymentId') !== false); }

if (getenv('CIGALPAY_LIVE')) {
    $c = new CigalPay(['accountId' => getenv('CIGALPAY_ACCOUNT_ID')]);
    $coins = $c->getSupportedCoins();
    check('live supportedCoins', is_array($coins) && count($coins) > 0);
    $co = $c->createCheckout(['amount' => 12, 'currency' => 'USD', 'orderId' => 'php-sdk-test', 'description' => 'SDK test', 'returnUrl' => 'https://cigalpay.com', 'notifyUrl' => 'https://cigalpay.com']);
    check('live createCheckout', isset($co['chargeLink']) && strpos($co['chargeLink'], 'https://') === 0);
    try { (new CigalPay(['apiKey' => 'wrong-key']))->getActivePayments(); check('live bad key -> 401', false); }
    catch (\Cigalpay\Exceptions\CigalpayException $e) { check('live bad key -> 401', $e->getStatus() === 401); }
    $old = new \Cigalpay\CigalpaySDK('https://api.cigalpay.icu', 'wrong-key');
    try { $old->getActivePayments(); check('v1 class reaches the new host', false); }
    catch (\Cigalpay\Exceptions\CigalpayException $e) { check('v1 class reaches the new host', $e->getStatus() === 401); }
}

echo $fail ? "\n$fail failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);

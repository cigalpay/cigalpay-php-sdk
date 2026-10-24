// Prints notifications signed exactly as core-api does, for tests/run.php.
import { createHmac } from 'node:crypto';
const secret = 'test-secret';
const payloads = [
    { status: 'CONFIRMED', id: 'b3f7688f', amount: 49, amountPaid: 49.5, currency: 'USDT_TRX', extraId: '1042', expiresAt: new Date('2026-10-20T07:48:00Z'), swapFromCurrency: 'BSC' },
    { status: 'CONFIRMING', amount: 0.00012345, tiny: 1e-7, big: 1e21, note: 'Café ☕ "quoted" / slash  ', callbackUrl: 'https://shop.example/ipn?a=1&b=2', zebra: null, alpha: true },
    { b: 1, a: { b: 2, z: 3, a: [1, { a: 1, q: 9 }] } },
];
console.log(JSON.stringify(payloads.map((d) => ({ body: JSON.stringify(d), sig: createHmac('sha512', secret).update(JSON.stringify(d, Object.keys(d).sort())).digest('hex') }))));

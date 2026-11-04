<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260929_010000_payment_contracts;
use verbb\formie\models\Payment;
use verbb\formie\models\PaymentMoney;

it('rounds a historical amount half up to the currency precision', function (string $amount, string $currency, string $minor): void {
    expect(PaymentMoney::fromHistoricalDecimal($amount, $currency)->minor)->toBe($minor);
})->with([
    'rounds up' => ['25.015', 'USD', '2502'],
    'rounds down' => ['25.0149', 'USD', '2501'],
    'carries through nines' => ['1.999', 'USD', '200'],
    'carries past the first digit' => ['99.995', 'USD', '10000'],
    'keeps a negative amount' => ['-25.015', 'USD', '-2502'],
    'has no minor unit' => ['100.5', 'JPY', '101'],
    'has three decimals' => ['25.0155', 'BHD', '25016'],
    'is valid already' => ['25.0150', 'BHD', '25015'],
    'only has trailing zeros' => ['25.0100', 'USD', '2501'],
    'rounds to zero' => ['0.004', 'USD', '0'],
    'is too long for an integer' => ['99999999999999999999.995', 'USD', '10000000000000000000000'],
]);

it('still rejects a value that is not a decimal', function (): void {
    expect(fn() => PaymentMoney::fromHistoricalDecimal('not-money', 'USD'))->toThrow(InvalidArgumentException::class);
});

it('keeps new payments exact, rejecting more decimals than the currency has', function (): void {
    expect(fn() => PaymentMoney::fromDecimal('25.015', 'USD'))->toThrow(InvalidArgumentException::class);
});

it('migrates a historical fractional payment without stopping, keeping its status and original amount', function (string $status, string $expected): void {
    $db = Craft::$app->getDb();
    $payment = new Payment(['amount' => '25.00', 'currency' => 'USD', 'status' => 'failed']);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $db->createCommand()->update(Table::FORMIE_PAYMENTS, [
        'amount' => '25.0150',
        'amountMinor' => null,
        'status' => $status,
        'history' => Json::encode([['status' => $status, 'reason' => 'legacy migration', 'legacyStatus' => $status]]),
        'scope' => Json::encode(['legacy' => true]),
    ], ['id' => $payment->id])->execute();

    try {
        $migration = new m260929_010000_payment_contracts();
        expect($migration->safeUp())->toBeTrue();

        $row = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id])->one();
        expect($row['amountMinor'])->toBe('2502')
            ->and($row['amount'])->toBe('25.0150')
            ->and($row['status'])->toBe($expected);

        $history = Json::decode($row['history']);
        expect(end($history))->toBe(['status' => $expected, 'reason' => 'legacy amount rounded', 'legacyAmount' => '25.0150']);

        // Running again changes nothing.
        expect($migration->safeUp())->toBeTrue();
        $again = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id])->one();
        expect($again['amountMinor'])->toBe('2502')
            ->and(Json::decode($again['history']))->toBe($history);

        $loaded = Formie::$plugin->getPayments()->getPaymentById($payment->id);
        expect($loaded->status)->toBe($expected)->and($loaded->amountMinor)->toBe('2502');
    } finally {
        $db->createCommand()->delete(Table::FORMIE_PAYMENTS, ['id' => $payment->id])->execute();
    }
})->with([
    'failed' => ['failed', 'failed'],
    'success' => ['success', 'succeeded'],
]);

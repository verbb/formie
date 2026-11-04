<?php

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_000000_payment_status_constraint;
use verbb\formie\models\Payment;

function paymentStatusConstraintNames(): array
{
    $db = Craft::$app->getDb();

    return (new Query())
        ->select(['conname'])
        ->from('pg_constraint')
        ->where(['contype' => 'c'])
        ->andWhere('[[conrelid]] = to_regclass(:table)', [':table' => $db->quoteTableName($db->getSchema()->getRawTableName(Table::FORMIE_PAYMENTS))])
        ->column();
}

it('removes the retained PostgreSQL status constraint so current payment statuses can be saved', function (): void {
    $db = Craft::$app->getDb();

    if (!$db->getIsPgsql()) {
        $this->markTestSkipped('The retained enum constraint only exists on PostgreSQL.');
    }

    // The definition older installs created for the enum column.
    $db->createCommand()->addCheck('legacy_payment_status_check', Table::FORMIE_PAYMENTS, "[[status]] IN ('pending', 'redirect', 'success', 'failed', 'processing')")->execute();
    $ids = [];

    try {
        $rejected = new Payment(['amount' => '1.00', 'currency' => 'USD', 'status' => 'succeeded']);
        expect(fn() => Formie::$plugin->getPayments()->savePayment($rejected))->toThrow(Exception::class);

        (new m261104_000000_payment_status_constraint())->safeUp();
        expect(paymentStatusConstraintNames())->not->toContain('legacy_payment_status_check');

        foreach (['pending', 'processing', 'requiresAction', 'succeeded', 'failed', 'cancelled', 'unknown'] as $status) {
            $payment = new Payment(['amount' => '1.00', 'currency' => 'USD', 'status' => $status]);
            expect(Formie::$plugin->getPayments()->savePayment($payment), $status)->toBeTrue();
            $ids[] = $payment->id;
            expect((new Query())->select('status')->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id])->scalar())->toBe($status);
        }

        // Running again changes nothing.
        (new m261104_000000_payment_status_constraint())->safeUp();
        expect((int)(new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $ids])->count())->toBe(7);
    } finally {
        if (in_array('legacy_payment_status_check', paymentStatusConstraintNames(), true)) {
            $db->createCommand()->dropCheck('legacy_payment_status_check', Table::FORMIE_PAYMENTS)->execute();
        }

        $db->createCommand()->delete(Table::FORMIE_PAYMENTS, ['id' => $ids])->execute();
    }
});

it('saves every current payment status without the migration changing other databases', function (): void {
    (new m261104_000000_payment_status_constraint())->safeUp();
    $ids = [];

    try {
        foreach (['pending', 'processing', 'requiresAction', 'succeeded', 'failed', 'cancelled', 'unknown'] as $status) {
            $payment = new Payment(['amount' => '2.50', 'currency' => 'USD', 'status' => $status]);
            expect(Formie::$plugin->getPayments()->savePayment($payment), $status)->toBeTrue();
            $ids[] = $payment->id;
        }

        expect((new Query())->select('status')->from(Table::FORMIE_PAYMENTS)->where(['id' => $ids])->orderBy(['id' => SORT_ASC])->column())
            ->toBe(['pending', 'processing', 'requiresAction', 'succeeded', 'failed', 'cancelled', 'unknown']);
    } finally {
        Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_PAYMENTS, ['id' => $ids])->execute();
    }
});

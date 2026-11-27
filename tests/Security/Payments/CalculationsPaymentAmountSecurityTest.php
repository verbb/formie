<?php

declare(strict_types=1);

use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ServerCalculations;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\RichText;

// Calculations are worked out in the browser. When one sets the payment amount, the server works it out
// again, so a visitor can't pay less by posting a different answer.

function calculationsPaymentForm(): Form
{
    $form = formie()->form(['title' => 'Calculations payment ' . uniqid()])
        ->numberField('quantity')
        ->calculationsField('subtotal')
        ->calculationsField('total')
        ->create();

    $form = Form::find()->id($form->id)->one();
    $quantity = $form->getFieldByHandle('quantity')->reference;
    $subtotal = $form->getFieldByHandle('subtotal');
    $subtotal->formula = RichText::from('{field:' . $quantity . '} * 12.5');

    // The total reads the subtotal's number-formatted answer, so both have to be worked out on the server.
    $subtotal->formatting = 'number';
    $subtotal->prefix = '$';
    $subtotal->decimals = 2;

    $total = $form->getFieldByHandle('total');
    $total->formula = RichText::from('{field:' . $subtotal->reference . '} + 5');
    $total->formatting = 'number';
    $total->prefix = '$';
    $total->decimals = 2;

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return Form::find()->id($form->id)->one();
}

function calculationsPaymentIntegration(Form $form, string $handle): Stripe
{
    $reference = '{field:' . $form->getFieldByHandle($handle)->reference . '}';

    return new class($reference) extends Stripe {
        public function __construct(private string $amountReference)
        {
            parent::__construct();
        }

        public function getFieldSetting(string $setting, mixed $default = null): mixed
        {
            return ['amountType' => Payment::VALUE_TYPE_DYNAMIC, 'amountVariable' => $this->amountReference, 'currency' => 'USD'][$setting] ?? $default;
        }
    };
}

function calculationsPaymentSubmission(Form $form, array $values): Submission
{
    $submission = new Submission(['title' => 'Calculations payment']);
    $submission->setForm($form);

    foreach ($values as $handle => $value) {
        $submission->setFieldValueFromRequest($handle, $value);
    }

    return $submission;
}

it('charges the calculated amount when the posted answer matches', function (): void {
    $form = calculationsPaymentForm();
    $submission = calculationsPaymentSubmission($form, ['quantity' => '3', 'subtotal' => '$37.50', 'total' => '$42.50']);

    expect(calculationsPaymentIntegration($form, 'total')->getPaymentAmount($submission))->toBe('42.50');
});

it('refuses a payment when the posted answer differs from the calculation', function (array $values): void {
    $form = calculationsPaymentForm();
    $submission = calculationsPaymentSubmission($form, $values);

    expect(fn() => calculationsPaymentIntegration($form, 'total')->getPaymentAmount($submission))
        ->toThrow(RuntimeException::class, 'The payment amount doesn’t match its calculation.');
})->with([
    'a lower total' => [['quantity' => '3', 'subtotal' => '$37.50', 'total' => '$1.00']],
    'a lower subtotal behind a matching total' => [['quantity' => '3', 'subtotal' => '$0.50', 'total' => '$5.50']],
]);

it('keeps amounts from fields other than Calculations as posted', function (): void {
    $form = calculationsPaymentForm();
    $submission = calculationsPaymentSubmission($form, ['quantity' => '7']);

    expect(calculationsPaymentIntegration($form, 'quantity')->getPaymentAmount($submission))->toBe('7');
});

it('formats numbers as the browser does', function (float $value, int $decimals, string $expected): void {
    expect(ServerCalculations::toFixed($value, $decimals))->toBe($expected);
})->with([
    [1.005, 2, '1.00'],
    [2.675, 2, '2.67'],
    [0.25, 1, '0.3'],
    [2.5, 0, '3'],
    [-1.5, 0, '-2'],
    [1.45, 1, '1.4'],
    [9.995, 2, '9.99'],
    [99.5, 0, '100'],
    [-0.001, 2, '-0.00'],
    [37.5, 2, '37.50'],
]);

it('prints numbers as the browser does', function (float|int $value, string $expected): void {
    expect(ServerCalculations::toJsString($value))->toBe($expected);
})->with([
    [0.1 + 0.2, '0.30000000000000004'],
    [10.0, '10'],
    [42, '42'],
    [37.5, '37.5'],
    [-0.5, '-0.5'],
    [1.0e21, '1e+21'],
    [1.0e-7, '1e-7'],
    [0.000001, '0.000001'],
    [123456789.125, '123456789.125'],
]);

it('works out Repeater row calculations behind the amount', function (string $postedLineTotal, bool $matches): void {
    $ref = 'rows' . substr(md5(uniqid('', true)), 0, 8);
    $form = formie()->form(['title' => 'Calculations payment rows ' . uniqid()])
        ->repeaterField('items', ['reference' => $ref . 'Items', 'rows' => [['fields' => [
            ['type' => verbb\formie\fields\Number::class, 'handle' => 'price', 'label' => 'Price', 'reference' => $ref . 'Price'],
            ['type' => verbb\formie\fields\Number::class, 'handle' => 'quantity', 'label' => 'Quantity', 'reference' => $ref . 'Quantity'],
            ['type' => verbb\formie\fields\Calculations::class, 'handle' => 'lineTotal', 'label' => 'Line total', 'reference' => $ref . 'LineTotal', 'formula' => '{field:' . $ref . 'Price} * {field:' . $ref . 'Quantity}'],
        ]]]])
        ->calculationsField('total', ['formula' => '{field:' . $ref . 'Items:lineTotal;scope=all}'])
        ->create();
    $form = Form::find()->id($form->id)->one();
    $submission = calculationsPaymentSubmission($form, [
        'items' => [
            ['price' => '10', 'quantity' => '2', 'lineTotal' => $postedLineTotal],
            ['price' => '4.5', 'quantity' => '1', 'lineTotal' => '4.5'],
        ],
        'total' => (string)((float)$postedLineTotal + 4.5),
    ]);
    $integration = calculationsPaymentIntegration($form, 'total');

    if ($matches) {
        expect($integration->getPaymentAmount($submission))->toBe('24.5');
    } else {
        expect(fn() => $integration->getPaymentAmount($submission))->toThrow(RuntimeException::class);
    }
})->with([
    'matching rows' => ['20', true],
    'a lower row' => ['1', false],
]);

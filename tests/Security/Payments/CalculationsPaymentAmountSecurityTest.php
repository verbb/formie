<?php

declare(strict_types=1);

use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
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

function calculationsFormulaForm(array $numbers, string $formula, array $format = []): Form
{
    $factory = formie()->form(['title' => 'Calculations formula ' . uniqid()]);

    foreach ($numbers as $handle) {
        $factory = $factory->numberField($handle);
    }

    $form = Form::find()->id($factory->calculationsField('total')->create()->id)->one();
    $total = $form->getFieldByHandle('total');
    $total->formula = RichText::from(preg_replace_callback('/\{(\w+)\}/', static fn(array $match) => '{field:' . $form->getFieldByHandle($match[1])->reference . '}', $formula));

    foreach ($format as $setting => $value) {
        $total->$setting = $value;
    }

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return Form::find()->id($form->id)->one();
}

it('accepts the browser’s answer when an optional source is empty', function (): void {
    // The browser joins `37.5 + ''` as text, then formats it as a number.
    $form = calculationsFormulaForm(['quantity', 'addon'], '{quantity} * 12.5 + {addon}', ['formatting' => 'number', 'prefix' => '$', 'decimals' => 2]);
    $submission = calculationsPaymentSubmission($form, ['quantity' => '3', 'addon' => '', 'total' => '$37.50']);

    expect(calculationsPaymentIntegration($form, 'total')->getPaymentAmount($submission))->toBe('37.50');
});

it('works out logical operators as the browser does', function (string $postedTotal, ?string $charged): void {
    $form = calculationsFormulaForm(['quantity', 'price'], '({quantity} || 1) * {price}');
    $submission = calculationsPaymentSubmission($form, ['quantity' => '3', 'price' => '10', 'total' => $postedTotal]);
    $integration = calculationsPaymentIntegration($form, 'total');

    if ($charged === null) {
        expect(fn() => $integration->getPaymentAmount($submission))->toThrow(RuntimeException::class);
    } else {
        expect($integration->getPaymentAmount($submission))->toBe($charged);
    }
})->with([
    'the browser’s answer' => ['30', '30'],
    'PHP’s answer' => ['10', null],
]);

it('refuses a payment when another posted calculation differs', function (): void {
    // Conditions on the server read the posted subtotal, so it has to agree even when the total does.
    $form = calculationsPaymentForm();
    $submission = calculationsPaymentSubmission($form, ['quantity' => '3', 'subtotal' => '$150.00', 'total' => '$42.50']);

    expect(fn() => calculationsPaymentIntegration($form, 'total')->getPaymentAmount($submission))
        ->toThrow(RuntimeException::class, 'The payment amount doesn’t match its calculation.');
});

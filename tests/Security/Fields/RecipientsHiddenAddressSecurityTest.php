<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RecipientTokenHelper;

// A hidden Recipients field posts a token of its default, so visitors can't choose who receives the
// notification. Raw posted addresses are a Formie 3 template pattern that only compatibility mode keeps.

function recipientsHiddenForm(): Form
{
    return formie()->form(['title' => 'Recipients hidden address ' . uniqid()])
        ->recipientsField('team', ['displayType' => 'hidden', 'defaultValue' => 'staff@example.test'])
        ->create();
}

function recipientsHiddenSavedValue(Form $form, mixed $posted): mixed
{
    $submission = new Submission(['title' => 'Recipients hidden address']);
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('team', $posted);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    return Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('team')->value;
}

function withRecipientsCompatibilityMode(bool $enabled, callable $callback): mixed
{
    $settings = Formie::$plugin->getSettings();
    $original = $settings->compatibilityMode;
    $settings->compatibilityMode = $enabled;

    try {
        return $callback();
    } finally {
        $settings->compatibilityMode = $original;
    }
}

it('uses the default when compatibility mode is off and an address is posted', function (string $posted): void {
    $form = recipientsHiddenForm();

    expect(withRecipientsCompatibilityMode(false, fn() => recipientsHiddenSavedValue($form, $posted)))
        ->toBe('staff@example.test');
})->with([
    'another address' => 'attacker@example.test',
    'several addresses' => 'attacker@example.test, other@example.test',
    'an empty value' => '',
]);

it('keeps the default when the field posts its rendered token', function (bool $compatibilityMode): void {
    $form = recipientsHiddenForm();
    $token = RecipientTokenHelper::encodeHidden('staff@example.test');

    expect(withRecipientsCompatibilityMode($compatibilityMode, fn() => recipientsHiddenSavedValue($form, $token)))
        ->toBe('staff@example.test');
})->with(['compatibility mode on' => true, 'compatibility mode off' => false]);

it('keeps a posted address while compatibility mode is on', function (): void {
    $form = recipientsHiddenForm();

    expect(withRecipientsCompatibilityMode(true, fn() => recipientsHiddenSavedValue($form, 'legacy@example.test')))
        ->toBe('legacy@example.test');
});

it('keeps recipients populated by the template', function (bool $compatibilityMode): void {
    $form = recipientsHiddenForm();
    Formie::$plugin->getRendering()->populateFormValues($form, ['team' => 'populated@example.test']);
    $token = RecipientTokenHelper::encodeHidden('populated@example.test');

    expect(withRecipientsCompatibilityMode($compatibilityMode, fn() => recipientsHiddenSavedValue($form, $token)))
        ->toBe('populated@example.test');
})->with(['compatibility mode on' => true, 'compatibility mode off' => false]);

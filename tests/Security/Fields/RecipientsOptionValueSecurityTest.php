<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RecipientOptionSelectionHelper;

// Option ids are public, because the browser receives them with each option. Selecting an option by
// its id must store that option's configured address, never an address posted alongside the id.

function recipientsOptionValueForm(string $displayType): Form
{
    return formie()->form(['title' => 'Recipients option value ' . uniqid()])
        ->recipientsField('team', ['displayType' => $displayType, 'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.test'],
            ['label' => 'Support', 'value' => 'support@example.test'],
        ]])
        ->create();
}

function recipientsOptionId(Form $form, string $value): string
{
    foreach (RecipientOptionSelectionHelper::optionRows($form->getFieldByHandle('team')->getResolvedOptions()) as $option) {
        if ($option['value'] === $value) {
            return $option['id'];
        }
    }

    throw new RuntimeException("No option for $value");
}

function recipientsSavedValues(Form $form, mixed $posted): array
{
    $submission = new Submission(['title' => 'Recipients option value']);
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('team', $posted);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $value = Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('team');

    return $value->multiple
        ? array_map(static fn($option) => [$option->value, $option->label], $value->all())
        : [[$value->value, $value->label]];
}

it('stores the option address when a posted id is paired with another address', function (string $displayType): void {
    $form = recipientsOptionValueForm($displayType);
    $selection = ['id' => recipientsOptionId($form, 'sales@example.test'), 'value' => 'attacker@example.test', 'label' => 'Attacker'];

    expect(recipientsSavedValues($form, $displayType === 'checkboxes' ? [$selection] : $selection))
        ->toBe([['sales@example.test', 'Sales']]);
})->with(['dropdown', 'radio', 'checkboxes']);

it('still rejects an address that matches no option', function (string $displayType): void {
    $form = recipientsOptionValueForm($displayType);
    $submission = new Submission(['title' => 'Recipients option value']);
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('team', $displayType === 'checkboxes' ? ['attacker@example.test'] : 'attacker@example.test');

    expect($submission->validate())->toBeFalse()
        ->and($submission->getErrors('team'))->not->toBeEmpty();
})->with(['dropdown', 'radio', 'checkboxes']);

it('keeps a selection posted as the option address', function (string $displayType): void {
    $form = recipientsOptionValueForm($displayType);

    expect(recipientsSavedValues($form, $displayType === 'checkboxes' ? ['support@example.test'] : 'support@example.test'))
        ->toBe([['support@example.test', 'Support']]);
})->with(['dropdown', 'radio', 'checkboxes']);

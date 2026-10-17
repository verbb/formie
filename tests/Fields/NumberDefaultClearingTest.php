<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('keeps an explicitly cleared number empty through saving and reloading despite a configured default', function (): void {
    $form = formie()->form()->numberField('quantity', ['defaultValue' => 7])->create();
    $field = $form->getFieldByHandle('quantity');
    expect($field->getInitialValue($form))->toBe('7');

    $submission = formie()->submission($form)->with(['quantity' => ''])->save();
    $saved = Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->getFieldValue('quantity'))->toBeNull();

    $saved->setFieldValueFromRequest('quantity', '0');
    expect(Craft::$app->getElements()->saveElement($saved))->toBeTrue();
    expect(Submission::find()->id($saved->id)->status(null)->one()->getFieldValue('quantity'))->toBe('0');
});

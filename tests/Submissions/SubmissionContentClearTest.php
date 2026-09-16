<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

it('applies nested handle edits over stored UID values while retaining siblings', function (mixed $replacement): void {
    $form = formie()->form()->groupField('group', ['rows' => [['fields' => [
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'message', 'label' => 'Message'],
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'untouched', 'label' => 'Untouched'],
    ]]]])->create();
    $submission = formie()->submission($form)->with(['group' => ['message' => 'Before', 'untouched' => 'Keep']])->save();
    $loaded = Submission::find()->id($submission->id)->status(null)->one();
    $loaded->setFieldValue('group.message', $replacement);
    expect(Craft::$app->getElements()->saveElement($loaded))->toBeTrue();
    $saved = Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->getFieldValue('group.message'))->toBe($replacement)
        ->and($saved->getFieldValue('group.untouched'))->toBe('Keep');
})->with(['replacement' => ['After'], 'clear' => [null]]);

it('persists explicit null clears without losing untouched or historical values', function (): void {
    $form = formie()->form()->singleLineTextField('message')->singleLineTextField('untouched')->create();
    $submission = formie()->submission($form)->with(['message' => 'Before', 'untouched' => 'Keep'])->save();
    $uid = $form->getFieldByHandle('message')->uid;
    $otherUid = $form->getFieldByHandle('untouched')->uid;
    $unknown = 'historical-removed-field';
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'content' => \craft\helpers\Json::encode([$uid => 'Before', $otherUid => 'Keep', $unknown => 'Retained']),
    ], ['id' => $submission->id])->execute();
    $loaded = Submission::find()->id($submission->id)->status(null)->one();
    $loaded->setFieldValue('message', null);
    expect(Craft::$app->getElements()->saveElement($loaded))->toBeTrue();
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();
    expect($reloaded->getFieldValue('message'))->toBeNull()
        ->and($reloaded->getFieldValue('untouched'))->toBe('Keep')
        ->and($reloaded->serializeFieldValues()[$unknown] ?? null)->toBe('Retained');
    // A subsequent metadata-only edit must retain the same content.
    $reloaded->title = 'Metadata only';
    expect(Craft::$app->getElements()->saveElement($reloaded))->toBeTrue();
    $again = Submission::find()->id($submission->id)->status(null)->one();
    expect($again->getFieldValue('message'))->toBeNull()
        ->and($again->getFieldValue('untouched'))->toBe('Keep')
        ->and($again->serializeFieldValues()[$unknown] ?? null)->toBe('Retained');
});

it('persists empty input clears through reload and a subsequent metadata save', function (string $method, array $config, mixed $filled, mixed $empty): void {
    $form = formie()->form()->$method('answer', $config)->singleLineTextField('untouched')->create();
    $submission = formie()->submission($form)->with(['answer' => $filled, 'untouched' => 'Keep'])->save();
    $loaded = Submission::find()->id($submission->id)->status(null)->one();
    $field = $form->getFieldByHandle('answer');
    $loaded->setFieldValue('answer', $empty);
    $expected = $field->serializeValue($loaded->getFieldValue('answer'), $loaded);
    expect(Craft::$app->getElements()->saveElement($loaded))->toBeTrue();
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();
    expect($field->serializeValue($reloaded->getFieldValue('answer'), $reloaded))->toBe($expected)
        ->and($reloaded->getFieldValue('untouched'))->toBe('Keep');
    $reloaded->title = 'Metadata only';
    expect(Craft::$app->getElements()->saveElement($reloaded))->toBeTrue();
    $again = Submission::find()->id($submission->id)->status(null)->one();
    expect($field->serializeValue($again->getFieldValue('answer'), $again))->toBe($expected)
        ->and($again->getFieldValue('untouched'))->toBe('Keep');
})->with([
    'checkboxes empty array' => ['checkboxesField', ['options' => [['label' => 'One', 'value' => 'one']]], ['one'], []],
    'checkboxes empty string' => ['checkboxesField', ['options' => [['label' => 'One', 'value' => 'one']]], ['one'], ''],
    'table empty array' => ['tableField', ['columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']]], [['col1' => 'Before']], []],
    'table with defaults' => ['tableField', ['columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']], 'defaults' => [['col1' => 'Default']]], [['col1' => 'Before']], []],
    'repeater empty array' => ['repeaterField', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'message', 'label' => 'Message']]]]], [['message' => 'Before']], []],
    'date empty string' => ['dateField', [], '2026-01-15', ''],
]);

it('retains table defaults when normalizing a fresh value', function (): void {
    $field = new \verbb\formie\fields\Table([
        'handle' => 'items',
        'columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']],
        'defaults' => [['col1' => 'Default']],
    ]);
    expect($field->serializeValue($field->normalizeValue(null, null), null))->toBe([['col1' => 'Default']]);
});

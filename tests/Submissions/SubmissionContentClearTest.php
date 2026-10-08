<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;

it('preserves rich child values when clearing disabled parts inside containers', function (string $method, bool $repeated): void {
    $rows = [['fields' => [
        ['type' => Name::class, 'handle' => 'name', 'label' => 'Name', 'useMultipleFields' => true, 'rows' => (new Name(['useMultipleFields' => true]))->getSubFields()],
        ['type' => SingleLineText::class, 'handle' => 'message', 'label' => 'Message'],
    ]]];
    $form = formie()->form()->$method('people', ['rows' => $rows])->create();
    $person = ['name' => ['firstName' => 'Jane', 'lastName' => 'Doe'], 'message' => 'Keep'];
    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Nested name edit';
    $submission->setFieldValueFromRequest('people', $repeated ? [$person, $person] : $person);

    // Disabled prefix/middle-name parts are cleared during ordinary submission.
    (new ConditionVisibility())->clear($submission);
    $path = $repeated ? 'people.0' : 'people';
    expect($submission->getFieldValue($path . '.name.firstName'))->toBe('Jane')
        ->and($submission->getFieldValue($path . '.name.lastName'))->toBe('Doe');

    $submission->setFieldValue($path . '.name.firstName', 'Janet');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $saved = Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->getFieldValue($path . '.name.firstName'))->toBe('Janet')
        ->and($saved->getFieldValue($path . '.name.lastName'))->toBe('Doe')
        ->and($saved->getFieldValue($path . '.message'))->toBe('Keep');

    if ($repeated) {
        expect($saved->getFieldValue('people.1.name.firstName'))->toBe('Jane')
            ->and($saved->getFieldValue('people.1.message'))->toBe('Keep');
    }
})->with(['group' => ['groupField', false], 'repeater' => ['repeaterField', true]]);

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
    expect($saved->getFieldValue('group.message'))->toBe($replacement ?? '')
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
    expect($reloaded->getFieldValue('message'))->toBe('')
        ->and($reloaded->getFieldValue('untouched'))->toBe('Keep')
        ->and($reloaded->serializeFieldValues()[$unknown] ?? null)->toBe('Retained');
    // A subsequent metadata-only edit must retain the same content.
    $reloaded->title = 'Metadata only';
    expect(Craft::$app->getElements()->saveElement($reloaded))->toBeTrue();
    $again = Submission::find()->id($submission->id)->status(null)->one();
    expect($again->getFieldValue('message'))->toBe('')
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

<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\fields\{Agree, Number, SingleLineText};
use verbb\formie\models\IntegrationField;

it('preserves nested zero values in public and integration projections', function (string $placement): void {
    $method = $placement === 'group' ? 'groupField' : 'repeaterField';
    $form = formie()->form()->$method('details', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'code', 'label' => 'Code'],
        ['type' => Number::class, 'handle' => 'quantity', 'label' => 'Quantity'],
        ['type' => Agree::class, 'handle' => 'accepted', 'label' => 'Accepted'],
        ['type' => SingleLineText::class, 'handle' => 'empty', 'label' => 'Empty'],
    ]]]])->create();
    $row = ['code' => '0', 'quantity' => '0', 'accepted' => false, 'empty' => ''];
    $submission = formie()->submission($form)->with(['details' => $placement === 'group' ? $row : [$row]])->save();
    $loaded = Submission::find()->id($submission->id)->one();
    $field = $loaded->getFieldByHandle('details');
    $value = $loaded->getFieldValue('details');
    $expectedRow = ['code' => '0', 'quantity' => '0'];
    $expected = $placement === 'group' ? $expectedRow : [$expectedRow];
    expect($loaded->getValuesAsArray()['details'])->toBe($expected);
    expect($loaded->getValuesAsString()['details'])->toStartWith('0, 0');
    $integration = new class extends Integration {};
    $arrayTarget = new IntegrationField(['handle' => 'target', 'name' => 'Target', 'type' => IntegrationField::TYPE_ARRAY]);
    $stringTarget = new IntegrationField(['handle' => 'target', 'name' => 'Target', 'type' => IntegrationField::TYPE_STRING]);
    expect($field->getValueForIntegration($value, $arrayTarget, $integration, $loaded))->toBe($expected);
    expect($field->getValueForIntegration($value, $stringTarget, $integration, $loaded))->toStartWith('0, 0');
})->with(['group', 'repeater']);

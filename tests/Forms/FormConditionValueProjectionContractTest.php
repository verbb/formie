<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\References;

it('projects rich normalized values to stable comparable condition subjects by default', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Projection Defaults'])
        ->dateField('eventDate', [
            'displayType' => 'inputs',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'eventDate' => [
            'year' => '2026',
            'month' => '02',
            'day' => '03',
        ],
    ])->save();

    $field = $form->getFieldByHandle('eventDate');
    expect($field)->not->toBeNull();

    $normalized = $submission->getFieldValue('eventDate');
    $conditionValue = $submission->getFieldValueForCondition('eventDate');

    expect($normalized)->toBeInstanceOf(DateFieldValue::class)
        ->and($conditionValue)->toBeArray()
        ->and($conditionValue)->toMatchArray([
            'year' => '2026',
            'month' => '2',
            'day' => '3',
        ]);
});

it('obscures recipients condition values instead of exposing raw recipient payloads', function (): void {
    $form = formie()
        ->form(['title' => 'Recipient Condition Projection'])
        ->recipientsField('department', [
            'displayType' => 'dropdown',
            'options' => [
                ['label' => 'Sales', 'value' => 'sales@example.test'],
                ['label' => 'Support', 'value' => 'support@example.test'],
            ],
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'department' => 'sales@example.test',
    ])->save();

    $field = $form->getFieldByHandle('department');
    expect($field)->not->toBeNull();

    $normalized = $submission->getFieldValue('department');
    $conditionValue = $submission->getFieldValueForCondition('department');

    expect($conditionValue)->toBe('id:0')
        ->and($field->serializeValue($normalized, $submission))->toBe('sales@example.test');

    $reference = $field->reference ?? 'department';

    expect(ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => References::field($reference),
            'condition' => '=',
            'value' => 'id:0',
        ]],
    ], $submission))->toBeTrue();
});

it('uses handle keyed condition subjects for container fields', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Container Condition Projection'])
        ->groupField('groupContent', ['rows' => $rows])
        ->create();

    $submission = formie()->submission($form)->with([
        'groupContent' => ['innerText' => 'Group Value'],
    ])->save();

    $field = $form->getFieldByHandle('groupContent');
    expect($field)->not->toBeNull();

    $normalized = $submission->getFieldValue('groupContent');
    $conditionValue = $submission->getFieldValueForCondition('groupContent');
    $serialized = $field->serializeValue($normalized, $submission);
    $reference = $field->reference ?? 'groupContent';

    expect($conditionValue)->toBe([
        'innerText' => 'Group Value',
    ])
        ->and(array_key_exists('innerText', $serialized))->toBeFalse()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($reference, 'innerText'),
                'condition' => '=',
                'value' => 'Group Value',
            ]],
        ], $submission))->toBeTrue();
});

it('uses handle keyed row values for repeatable field conditions', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeatable Condition Projection'])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $submission = formie()->submission($form)->with([
        'lineItems' => [
            ['innerText' => 'Row One'],
            ['innerText' => 'Row Two'],
        ],
    ])->save();

    $field = $form->getFieldByHandle('lineItems');
    expect($field)->not->toBeNull();

    $normalized = $submission->getFieldValue('lineItems');
    $conditionValue = $submission->getFieldValueForCondition('lineItems');
    $serialized = $field->serializeValue($normalized, $submission);
    $reference = $field->reference ?? 'lineItems';

    expect($conditionValue)->toBe([
        ['innerText' => 'Row One'],
        ['innerText' => 'Row Two'],
    ])
        ->and(array_key_exists('innerText', $serialized[0] ?? []))->toBeFalse()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($reference, '0:innerText'),
                'condition' => '=',
                'value' => 'Row One',
            ]],
        ], $submission))->toBeTrue()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($reference),
                'condition' => 'contains',
                'value' => 'Row Two',
            ]],
        ], $submission))->toBeTrue();
});

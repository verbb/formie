<?php

declare(strict_types=1);

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\References;

it('evaluates condition operators with native engine', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Operators'])
        ->singleLineTextField('message')
        ->create();

    $submission = formie()->submission($form)->with([
        'message' => 'hello world',
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'message');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? 'message';

    $operatorMatrix = [
        ['condition' => '=', 'value' => 'hello world', 'expected' => true],
        ['condition' => '!=', 'value' => 'goodbye', 'expected' => true],
        ['condition' => 'contains', 'value' => 'hello', 'expected' => true],
        ['condition' => 'startsWith', 'value' => 'hello', 'expected' => true],
        ['condition' => 'endsWith', 'value' => 'world', 'expected' => true],
        ['condition' => 'empty', 'value' => '', 'expected' => false],
        ['condition' => 'notEmpty', 'value' => '', 'expected' => true],
        ['condition' => '>', 'value' => 'a', 'expected' => true],
        ['condition' => '<', 'value' => 'z', 'expected' => true],
    ];

    foreach ($operatorMatrix as $row) {
        $result = ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($ref),
                'condition' => $row['condition'],
                'value' => $row['value'],
            ]],
        ], $submission);

        expect($result)->toBe($row['expected']);
    }
});

it('supports all and any condition rules', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Rule Modes'])
        ->singleLineTextField('status')
        ->create();

    $submission = formie()->submission($form)->with([
        'status' => 'active',
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'status');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? 'status';

    $conditions = [
        [
            'field' => References::field($ref),
            'condition' => '=',
            'value' => 'active',
        ],
        [
            'field' => References::field($ref),
            'condition' => '=',
            'value' => 'disabled',
        ],
    ];

    $allResult = ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => $conditions,
    ], $submission);

    $anyResult = ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'any',
        'conditions' => $conditions,
    ], $submission);

    expect($allResult)->toBeFalse()
        ->and($anyResult)->toBeTrue();
});

it('resolves submission and field references for conditions', function (): void {
    $form = formie()
        ->form(['title' => 'Condition References'])
        ->singleLineTextField('tokenField')
        ->create();

    $submission = formie()->submission($form)->with([
        'tokenField' => 'token-value',
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'tokenField');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? 'tokenField';

    $result = ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => [
            [
                'field' => References::submission('id'),
                'condition' => '>',
                'value' => 0,
            ],
            [
                'field' => References::field($ref),
                'condition' => '=',
                'value' => 'token-value',
            ],
        ],
    ], $submission);

    expect($result)->toBeTrue();
});

it('treats array equality as contains for checkbox-style values', function (): void {
    $form = formie()
        ->form(['title' => 'Array Equality'])
        ->checkboxesField('topics', [
            'options' => [
                ['label' => 'A', 'value' => 'a'],
                ['label' => 'B', 'value' => 'b'],
            ],
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'topics' => ['a'],
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? 'topics';

    $equalsMatch = ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => References::field($ref),
            'condition' => '=',
            'value' => 'a',
        ]],
    ], $submission);

    $notEqualsMatch = ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => References::field($ref),
            'condition' => '!=',
            'value' => 'a',
        ]],
    ], $submission);

    expect($equalsMatch)->toBeTrue()
        ->and($notEqualsMatch)->toBeFalse();
});

it('supports callback collection mode used by notification recipients', function (): void {
    $form = formie()
        ->form(['title' => 'Callback Collection'])
        ->singleLineTextField('department')
        ->create();

    $submission = formie()->submission($form)->with([
        'department' => 'sales',
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'department');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? 'department';

    $recipients = [
        [
            'field' => References::field($ref),
            'condition' => '=',
            'value' => 'sales',
            'email' => 'sales@example.com',
        ],
        [
            'field' => References::field($ref),
            'condition' => '=',
            'value' => 'support',
            'email' => 'support@example.com',
        ],
    ];

    $matchedEmails = ConditionsHelper::evaluateConditions($recipients, $submission, function($result, $condition) {
        if ($result) {
            return $condition['email'];
        }
    });

    expect($matchedEmails)->toBe(['sales@example.com']);
});

it('coerces numeric and boolean-like condition comparisons explicitly', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Coercion'])
        ->numberField('score')
        ->agreeField('accepted', [
            'checkedValue' => 'yes',
            'uncheckedValue' => 'no',
        ])
        ->create();

    $truthySubmission = formie()->submission($form)->with([
        'score' => '10',
        'accepted' => true,
    ])->save();

    $falsySubmission = formie()->submission($form)->with([
        'score' => '2',
        'accepted' => false,
    ])->save();

    $scoreField = ArrayHelper::firstWhere($truthySubmission->getFields(), 'handle', 'score');
    $acceptedField = ArrayHelper::firstWhere($truthySubmission->getFields(), 'handle', 'accepted');
    expect($scoreField)->not->toBeNull()
        ->and($acceptedField)->not->toBeNull();

    $scoreRef = $scoreField->reference ?? 'score';
    $acceptedRef = $acceptedField->reference ?? 'accepted';

    expect(ConditionsHelper::getConditionalTestResult([
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => References::field($scoreRef),
            'condition' => '>',
            'value' => '2',
        ]],
    ], $truthySubmission))->toBeTrue()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($scoreRef),
                'condition' => '=',
                'value' => 10,
            ]],
        ], $truthySubmission))->toBeTrue()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($acceptedRef),
                'condition' => '=',
                'value' => 'yes',
            ]],
        ], $truthySubmission))->toBeTrue()
        ->and(ConditionsHelper::getConditionalTestResult([
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => References::field($acceptedRef),
                'condition' => '=',
                'value' => 'no',
            ]],
        ], $falsySubmission))->toBeTrue();
});

<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\models\ValueContext;

it('keeps getFieldValue context projections aligned with wrapper methods', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Projection Context Contract'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Context Contract',
        'email' => 'context@example.test',
    ])->save();

    $notification = new Notification(['name' => 'n', 'handle' => 'n' . uniqid()]);

    expect($submission->getFieldValue('fullName', ValueContext::string()))->toBe($submission->getFieldValueAsString('fullName'))
        ->and($submission->getFieldValue('fullName', ValueContext::json()))->toBe($submission->getFieldValueAsArray('fullName'))
        ->and($submission->getFieldValue('fullName', ValueContext::export()))->toBe($submission->getFieldValueForExport('fullName'))
        ->and($submission->getFieldValue('fullName', ValueContext::summary()))->toBe($submission->getFieldValueForSummary('fullName'))
        ->and($submission->getFieldValue('fullName', ValueContext::reference($notification)))->toBe($submission->getFieldValueForReference('fullName', $notification))
        ->and($submission->getFieldValue('fullName', ValueContext::referenceBlock($notification)))->toBe($submission->getFieldValueForReferenceBlock('fullName', $notification))
        ->and($submission->getFieldValue('fullName', ValueContext::email($notification)))->toBe($submission->getFieldValueForEmail('fullName', $notification))
        ->and($submission->getFieldValue('fullName', ValueContext::variable($notification)))->toBe($submission->getFieldValueForVariable('fullName', $notification));
});

it('resolves nested group and repeater dot paths from submission values', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Submission Nested Dot Path Contract'])
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $submission = formie()->submission($form)->with([
        'groupContent' => ['innerText' => 'Group Value'],
        'lineItems' => [
            ['innerText' => 'Row One'],
            ['innerText' => 'Row Two'],
        ],
    ])->save();

    expect($submission->getFieldValue('groupContent.innerText'))->toBe('Group Value')
        ->and($submission->getFieldValue('lineItems.0.innerText'))->toBe('Row One')
        ->and($submission->getFieldValue('lineItems.1.innerText'))->toBe('Row Two');
});

it('resolves reference tokens and applies transforms via getFieldValue', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Submission Reference Token Contract'])
        ->singleLineTextField('fullName')
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $fullNameField = $form->getFieldByHandle('fullName');
    $groupField = $form->getFieldByHandle('groupContent');
    $repeaterField = $form->getFieldByHandle('lineItems');

    expect($fullNameField)->not->toBeNull()
        ->and($groupField)->not->toBeNull()
        ->and($repeaterField)->not->toBeNull();

    $submission = formie()->submission($form)->with([
        'fullName' => 'JOHN SMITH',
        'groupContent' => ['innerText' => 'Group Token Value'],
        'lineItems' => [
            ['innerText' => 'Row One Token Value'],
        ],
    ])->save();

    $fullNameToken = References::field((string)$fullNameField->reference);
    $fullNameLowerToken = '{field:' . $fullNameField->reference . ';transform=lower}';
    $groupToken = '{field:' . $groupField->reference . ':innerText}';
    $repeaterToken = '{field:' . $repeaterField->reference . ':0:innerText}';

    expect($submission->getFieldValue($fullNameToken))->toBe('JOHN SMITH')
        ->and($submission->getFieldValue($fullNameLowerToken))->toBe('john smith')
        ->and($submission->getFieldValue($groupToken))->toBe('Group Token Value')
        ->and($submission->getFieldValue($repeaterToken))->toBe('Row One Token Value')
        ->and($submission->getFieldValue('{submission:id}'))->toBe($submission->id)
        ->and($submission->getFieldValue('{form:handle}'))->toBe($form->handle);
});

it('keeps bulk projection helpers aligned with single-field wrappers', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Bulk Projection Contract'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Bulk Contract',
            'email' => 'bulk@example.test',
        ])
        ->save();

    $asString = $submission->getValuesAsString();
    $asJson = $submission->getValuesAsArray();
    $asExport = $submission->getValuesForExport();
    $asSummary = $submission->getValuesForSummary();

    expect($asString)->toHaveKey('fullName')
        ->and($asString)->toHaveKey('email')
        ->and($asJson)->toHaveKey('fullName')
        ->and($asJson)->toHaveKey('email')
        ->and($asString['fullName'])->toBe($submission->getFieldValueAsString('fullName'))
        ->and($asString['email'])->toBe($submission->getFieldValueAsString('email'))
        ->and($asJson['fullName'])->toBe($submission->getFieldValueAsArray('fullName'))
        ->and($asJson['email'])->toBe($submission->getFieldValueAsArray('email'));

    $fullNameLabel = $form->getFieldByHandle('fullName')?->label;
    $emailLabel = $form->getFieldByHandle('email')?->label;

    expect($fullNameLabel)->toBeString()
        ->and($emailLabel)->toBeString()
        ->and($asExport)->toBeArray()
        ->and($asExport)->toHaveKey($fullNameLabel)
        ->and($asExport)->toHaveKey($emailLabel)
        ->and($asExport[$fullNameLabel])->toBe($submission->getFieldValueForExport('fullName'))
        ->and($asExport[$emailLabel])->toBe($submission->getFieldValueForExport('email'))
        ->and($asSummary)->toBeArray()
        ->and($asSummary)->not->toBeEmpty();

    $summaryByHandle = [];

    foreach ($asSummary as $row) {
        $field = $row['field'] ?? null;
        $handle = $field->handle ?? null;

        if ($handle) {
            $summaryByHandle[$handle] = $row['value'] ?? null;
        }
    }

    expect($summaryByHandle)->toHaveKey('fullName')
        ->and($summaryByHandle)->toHaveKey('email')
        ->and($summaryByHandle['fullName'])->toBe($submission->getFieldValueForSummary('fullName'))
        ->and($summaryByHandle['email'])->toBe($submission->getFieldValueForSummary('email'));
});

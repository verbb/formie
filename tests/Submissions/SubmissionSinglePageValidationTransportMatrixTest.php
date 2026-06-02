<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

dataset('single_page_submit_methods', ['ajax', 'page-reload']);

dataset('single_page_validation_cases', [
    'required text' => ['required text', ['requiredText']],
    'required email missing' => ['required email missing', ['requiredEmail']],
    'invalid email format' => ['invalid email format', ['formatEmail']],
    'single name required' => ['single name required', ['singleName']],
    'multi name first required' => ['multi name first required', ['multiName', 'firstName']],
    'address child required' => ['address child required', ['shippingAddress', 'address1']],
    'group child required' => ['group child required', ['groupBlock', 'groupRequiredText']],
    'repeater child required' => ['repeater child required', ['repeatBlock', 'repeatRequiredText']],
]);

it('blocks invalid single-page submissions for required field contracts across submit methods', function (
    string $submitMethod,
    string $case,
    array $expectedErrorFragments
): void {
    $form = createSinglePageValidationForm($submitMethod);
    $initialSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $values = buildValidSinglePagePayload();
    $invalidValues = applyValidationCase($values, $case);

    $submission = new Submission();
    $submission->setForm($form);

    foreach ($invalidValues as $handle => $value) {
        $submission->setFieldValueFromRequest((string)$handle, $value);
    }

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $errors = $response->submission->getErrors();
    $flattenedErrors = json_encode($errors);
    $finalSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($response->success)->toBeFalse()
        ->and($response->submission->id)->toBeNull()
        ->and($errors)->not->toBeEmpty()
        ->and($finalSubmissionCount)->toBe($initialSubmissionCount);

    foreach ($expectedErrorFragments as $fragment) {
        expect($flattenedErrors)->toContain($fragment);
    }
})->with('single_page_submit_methods')->with('single_page_validation_cases');

function applyValidationCase(array $values, string $case): array
{
    return match ($case) {
        'required text' => array_replace($values, ['requiredText' => '']),
        'required email missing' => array_replace($values, ['requiredEmail' => '']),
        'invalid email format' => array_replace($values, ['formatEmail' => 'not-an-email']),
        'single name required' => array_replace($values, ['singleName' => '']),
        'multi name first required' => array_replace($values, ['multiName' => array_replace($values['multiName'], ['firstName' => ''])]),
        'address child required' => array_replace($values, ['shippingAddress' => array_replace($values['shippingAddress'], ['address1' => ''])]),
        'group child required' => array_replace($values, ['groupBlock' => array_replace($values['groupBlock'], ['groupRequiredText' => ''])]),
        'repeater child required' => array_replace($values, ['repeatBlock' => [[
            'repeatRequiredText' => '',
        ]]]),
        default => $values,
    };
}

function createSinglePageValidationForm(string $submitMethod): mixed
{
    $nameRows = markNestedSubFieldRequired((new Name(['useMultipleFields' => true]))->getSubFields(), 'firstName');
    $addressRows = markNestedSubFieldRequired((new Address())->getSubFields(), 'address1');

    return formie()
        ->form([
            'title' => 'Single Page Validation Matrix ' . $submitMethod . ' ' . uniqid(),
            'handle' => transportMatrixHandle(),
        ])
        ->singleLineTextField('requiredText', ['required' => true])
        ->emailField('requiredEmail', ['required' => true])
        ->emailField('formatEmail', ['required' => true])
        ->nameField('singleName', ['required' => true, 'useMultipleFields' => false])
        ->nameField('multiName', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->addressField('shippingAddress', ['rows' => $addressRows])
        ->groupField('groupBlock', [
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineText::class,
                    'handle' => 'groupRequiredText',
                    'label' => 'Group Required',
                    'required' => true,
                ]],
            ]],
        ])
        ->repeaterField('repeatBlock', [
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineText::class,
                    'handle' => 'repeatRequiredText',
                    'label' => 'Repeater Required',
                    'required' => true,
                ]],
            ]],
        ])
        ->submitAction('message', ['method' => $submitMethod])
        ->create();
}

function buildValidSinglePagePayload(): array
{
    return [
        'requiredText' => 'Required text value',
        'requiredEmail' => 'required@example.test',
        'formatEmail' => 'valid@example.test',
        'singleName' => 'Single Name',
        'multiName' => [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ],
        'shippingAddress' => [
            'address1' => '1 Main Street',
            'city' => 'Nashville',
            'state' => 'TN',
            'zip' => '37011',
            'country' => 'US',
        ],
        'groupBlock' => [
            'groupRequiredText' => 'Group value',
        ],
        'repeatBlock' => [[
            'repeatRequiredText' => 'Repeater value',
        ]],
    ];
}

function markNestedSubFieldRequired(array $rows, string $handle): array
{
    foreach ($rows as $rowIndex => $rowConfig) {
        foreach (($rowConfig['fields'] ?? []) as $fieldIndex => $fieldConfig) {
            if (($fieldConfig['handle'] ?? null) === $handle) {
                $rows[$rowIndex]['fields'][$fieldIndex]['required'] = true;
            }
        }
    }

    return $rows;
}

function transportMatrixHandle(): string
{
    static $counter = 0;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = 'transport' . $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}

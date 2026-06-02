<?php

declare(strict_types=1);

use Faker\Factory as FakerFactory;
use verbb\formie\base\Integration;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\factories\FormFactory;

it('covers getValueAs* and integration conversion contracts for all form factory field types with empty and populated values', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $notification = new Notification(['name' => 'n', 'handle' => 'n' . uniqid()]);
    $integration = new class extends Integration {};
    $faker = FakerFactory::create();

    $integrationTypes = [
        IntegrationField::TYPE_STRING,
        IntegrationField::TYPE_NUMBER,
        IntegrationField::TYPE_FLOAT,
        IntegrationField::TYPE_BOOLEAN,
        IntegrationField::TYPE_DATE,
        IntegrationField::TYPE_DATETIME,
        IntegrationField::TYPE_DATECLASS,
        IntegrationField::TYPE_ARRAY,
        IntegrationField::TYPE_PHONE,
    ];

    $nestedRows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $profiles = [
        'singleLineTextField' => ['handle' => 'singleValue', 'config' => [], 'filled' => 'Text Value'],
        'multiLineTextField' => ['handle' => 'multiValue', 'config' => [], 'filled' => 'Longer text body'],
        'emailField' => ['handle' => 'emailValue', 'config' => [], 'filled' => 'person@example.test'],
        'addressField' => ['handle' => 'addressValue', 'config' => ['rows' => (new Address())->getSubFields()], 'filled' => [
            'address1' => '123 Main St',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'zip' => '3000',
            'country' => 'AU',
        ]],
        'agreeField' => ['handle' => 'agreeValue', 'config' => [], 'filled' => true],
        'calculationsField' => ['handle' => 'calcValue', 'config' => [], 'filled' => '42'],
        'categoriesField' => ['handle' => 'categoriesValue', 'config' => [], 'filled' => [1]],
        'checkboxesField' => ['handle' => 'checkboxValue', 'config' => ['options' => $options], 'filled' => ['one', 'two']],
        'numberField' => ['handle' => 'numberValue', 'config' => [], 'filled' => '42'],
        'dateField' => ['handle' => 'dateValue', 'config' => [], 'filled' => '2026-02-01'],
        'dropdownField' => ['handle' => 'dropdownValue', 'config' => ['options' => $options], 'filled' => 'one'],
        'entriesField' => ['handle' => 'entriesValue', 'config' => [], 'filled' => [1]],
        'fileUploadField' => ['handle' => 'fileValue', 'config' => [], 'filled' => []],
        'groupField' => ['handle' => 'groupValue', 'config' => ['rows' => $nestedRows], 'filled' => ['innerText' => 'Nested Group']],
        'headingField' => ['handle' => 'headingValue', 'config' => [], 'filled' => null],
        'hiddenField' => ['handle' => 'hiddenValue', 'config' => [], 'filled' => 'Hidden Value'],
        'htmlField' => ['handle' => 'htmlValue', 'config' => [], 'filled' => null],
        'nameField' => ['handle' => 'nameValue', 'config' => ['useMultipleFields' => false], 'filled' => 'Full Name'],
        'passwordField' => ['handle' => 'passwordValue', 'config' => [], 'filled' => 'MySecret123'],
        'paymentField' => ['handle' => 'paymentValue', 'config' => [], 'filled' => ['amount' => '10.00', 'currency' => 'USD']],
        'phoneField' => ['handle' => 'phoneValue', 'config' => [], 'filled' => '0400000000'],
        'productsField' => ['handle' => 'productsValue', 'config' => [], 'filled' => [1]],
        'radioField' => ['handle' => 'radioValue', 'config' => ['options' => $options], 'filled' => 'one'],
        'recipientsField' => ['handle' => 'recipientsValue', 'config' => ['displayType' => 'dropdown', 'options' => $options], 'filled' => 'one'],
        'repeaterField' => ['handle' => 'repeaterValue', 'config' => ['rows' => $nestedRows], 'filled' => [['innerText' => 'Nested Repeater']]],
        'sectionField' => ['handle' => 'sectionValue', 'config' => [], 'filled' => null],
        'signatureField' => ['handle' => 'signatureValue', 'config' => [], 'filled' => 'data:image/png;base64,Zm9v'],
        'summaryField' => ['handle' => 'summaryValue', 'config' => [], 'filled' => null],
        'tableField' => ['handle' => 'tableValue', 'config' => [], 'filled' => [['col1' => 'row1']]],
        'tagsField' => ['handle' => 'tagsValue', 'config' => [], 'filled' => [1]],
        'usersField' => ['handle' => 'usersValue', 'config' => [], 'filled' => [1]],
        'variantsField' => ['handle' => 'variantsValue', 'config' => [], 'filled' => [1]],
    ];

    $unsupportedProfiles = [
        'formsField' => 'FormFactory formsField() maps to a class that does not implement FieldInterface.',
        'missingField' => 'Missing field placeholder cannot be instantiated as a concrete field in this matrix harness.',
        'submissionsField' => 'FormFactory submissionsField() maps to a class that does not implement FieldInterface.',
    ];

    $factoryMethods = array_values(array_filter(
        get_class_methods(FormFactory::class),
        static fn(string $method) => str_ends_with($method, 'Field') && $method !== 'addField'
    ));

    sort($factoryMethods);
    $profileMethods = array_merge(array_keys($profiles), array_keys($unsupportedProfiles));
    sort($profileMethods);

    expect($profileMethods)->toEqual($factoryMethods);

    foreach ($profiles as $method => $profile) {
        try {
            $builder = formie()->form(['title' => "Field Conversion Matrix {$method}"]);
            $builder->{$method}($profile['handle'], $profile['config']);
            $form = $builder->create();

            $field = $form->getFieldByHandle($profile['handle']);
            expect($field)->not->toBeNull();

            $emptySubmission = formie()
                ->submission($form)
                ->allowValidationFailure()
                ->save();

            $filledSubmission = formie()
                ->submission($form)
                ->with([$profile['handle'] => $profile['filled']])
                ->allowValidationFailure()
                ->save();

            foreach ([$emptySubmission, $filledSubmission] as $submission) {
                $value = $submission->getFieldValue($profile['handle']);
                $valueAsArray = $field?->getValueAsArray($value, $submission);

                $fieldSummary = $field?->getValueForSummary($value, $submission);
                expect($field?->getValueAsString($value, $submission))->toBeString()
                    ->and($valueAsArray)->toBeArray()
                    ->and($field?->getValueAsArray($value, $submission))->toEqual($valueAsArray);
                expect(is_string($fieldSummary) || $fieldSummary instanceof \Twig\Markup)->toBeTrue();

                $submissionSummary = $submission->getFieldValueForSummary($profile['handle']);

                if ($method === 'groupField' && $submission === $filledSubmission) {
                    expect($valueAsArray['innerText'] ?? null)->toBeString();
                }

                if ($method === 'repeaterField' && $submission === $filledSubmission) {
                    expect($valueAsArray[0]['innerText'] ?? null)->toBeString();
                }

                $field?->getValueForReference($value, $submission);
                $field?->getValueForReferenceBlock($value, $notification, $submission);
                $field?->getValueForReferenceBlock($value, $notification, $submission);
                $field?->getValueForEmailPreview($faker);

                expect($submission->getFieldValueAsString($profile['handle']))->toBeString()
                    ->and($submission->getFieldValueAsArray($profile['handle']))->toBeArray()
                    ->and($submission->getFieldValueAsArray($profile['handle']))->toEqual($submission->getFieldValueAsArray($profile['handle']));
                expect(is_string($submissionSummary) || $submissionSummary instanceof \Twig\Markup)->toBeTrue();

                $submission->getFieldValueForReference($profile['handle'], $notification);
                $submission->getFieldValueForReferenceBlock($profile['handle'], $notification);
                $submission->getFieldValueForEmail($profile['handle'], $notification);
                $submission->getFieldValueForVariable($profile['handle'], $notification);

                $field->getValueForExport($value, $submission);
                $field->getValueForCondition($value, $submission);
                $submission->getFieldValueForExport($profile['handle']);
                $submission->getFieldValueForCondition($profile['handle']);

                foreach ($integrationTypes as $integrationType) {
                    $integrationField = new IntegrationField([
                        'handle' => 'target',
                        'name' => 'Target',
                        'type' => $integrationType,
                    ]);

                    $resolved = $field?->getValueForIntegration($value, $integrationField, $integration, $submission);
                    $submissionResolved = $submission->getFieldValueForIntegration($profile['handle'], $integrationField, $integration);

                    assertIntegrationValueContract($integrationType, $resolved);
                    assertIntegrationValueContract($integrationType, $submissionResolved);
                }
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Field conversion matrix failed for {$method}: {$e->getMessage()}", 0, $e);
        }
    }
});

function assertIntegrationValueContract(string $integrationType, mixed $value): void
{
    switch ($integrationType) {
        case IntegrationField::TYPE_STRING:
            expect($value)->toBeString();
            return;
        case IntegrationField::TYPE_NUMBER:
            expect($value === null || is_int($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_FLOAT:
            expect($value === null || is_float($value) || is_int($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_BOOLEAN:
            expect($value === null || is_bool($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_DATE:
        case IntegrationField::TYPE_DATETIME:
            expect($value === null || is_string($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_DATECLASS:
            expect($value === null || $value instanceof DateTimeInterface)->toBeTrue();
            return;
        case IntegrationField::TYPE_ARRAY:
            expect($value)->toBeArray();
            return;
        case IntegrationField::TYPE_PHONE:
            expect($value === null || is_string($value))->toBeTrue();
            return;
        default:
            throw new \RuntimeException("Unhandled IntegrationField type in assertIntegrationValueContract: {$integrationType}");
    }
}

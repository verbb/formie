<?php

declare(strict_types=1);

use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use Tests\Support\IntegrationTestHelper;
use verbb\formie\helpers\ArrayHelper;

// Opt-in uses boolean conversion: only values that StringHelper::toBoolean treats as true (e.g. "yes", "1", "true") count as opted in. Arbitrary strings (email, date, phone number) are not truthy.
$optInFieldConfigs = [
    ['method' => 'singleLineTextField', 'handle' => 'text', 'truthy' => 'yes', 'falsy' => ''],
    ['method' => 'numberField', 'handle' => 'num', 'truthy' => 1, 'falsy' => 0],
    ['method' => 'emailField', 'handle' => 'email', 'truthy' => 'yes@example.com', 'falsy' => ''],
    ['method' => 'agreeField', 'handle' => 'agree', 'truthy' => true, 'falsy' => false, 'checkedValue' => 'yes'],
    ['method' => 'dropdownField', 'handle' => 'choice', 'truthy' => 'yes', 'falsy' => '', 'config' => ['options' => [['label' => 'Yes', 'value' => 'yes'], ['label' => 'No', 'value' => '']]]],
    ['method' => 'checkboxesField', 'handle' => 'topics', 'truthy' => ['a'], 'falsy' => [], 'config' => ['options' => [['label' => 'A', 'value' => 'a']]]],
    ['method' => 'dateField', 'handle' => 'dob', 'truthy' => '2026-01-01', 'falsy' => ''],
    ['method' => 'phoneField', 'handle' => 'phone', 'truthy' => 'yes', 'falsy' => ''],
];

foreach ($optInFieldConfigs as $fieldConfig) {
    $method = $fieldConfig['method'];
    $handle = $fieldConfig['handle'];
    $truthy = $fieldConfig['truthy'];
    $falsy = $fieldConfig['falsy'];
    $config = $fieldConfig['config'] ?? [];

    it(
        $handle === 'email'
            ? 'enforceOptInField returns false when email contains truthy value'
            : ($handle === 'dob'
                ? 'enforceOptInField returns false when dob has truthy value'
                : "enforceOptInField returns true when {$handle} has truthy value"),
        function () use ($method, $handle, $truthy, $config): void {
            $builder = formie()->form(['title' => 'Opt-in Truthy ' . $handle]);
            $builder->$method($handle, $config);
            $form = $builder->create();

            $submission = formie()->submission($form)->with([$handle => $truthy])->save();

            $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', $handle);
            expect($field)->not->toBeNull();
            $ref = $field->reference ?? $field->handle;

            $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
            $integration->optInField = References::field($ref);

            if (in_array($handle, ['email', 'dob'], true)) {
                expect($integration->enforceOptInField($submission))->toBeFalse();
            } else {
                expect($integration->enforceOptInField($submission))->toBeTrue();
            }
        }
    );

    it("enforceOptInField returns false when {$handle} has falsy value", function () use ($method, $handle, $falsy, $config): void {
        $builder = formie()->form(['title' => 'Opt-in Falsy ' . $handle]);
        $builder->$method($handle, $config);
        $form = $builder->create();

        $submission = formie()->submission($form)->with([$handle => $falsy])->save();
        IntegrationTestHelper::primeVariableCacheForSubmission($submission);

        $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', $handle);
        expect($field)->not->toBeNull();
        $ref = $field->reference ?? $field->handle;

        $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
        $integration->optInField = References::field($ref);

        expect($integration->enforceOptInField($submission))->toBeFalse();
    });
}

it('enforceOptInField returns true when optInField is not set', function (): void {
    $form = formie()->form(['title' => 'No Opt-in'])->singleLineTextField('x')->create();
    $submission = formie()->submission($form)->with(['x' => 'y'])->save();

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integration->optInField = null;

    expect($integration->enforceOptInField($submission))->toBeTrue();
});

it('enforceOptInField returns false when opt-in field is empty or unmapped', function (): void {
    $form = formie()->form(['title' => 'Empty Opt-in'])->singleLineTextField('x')->create();
    $submission = formie()->submission($form)->with(['x' => ''])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'x');
    $ref = $field->reference ?? 'x';

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integration->optInField = References::field($ref);

    expect($integration->enforceOptInField($submission))->toBeFalse();
});

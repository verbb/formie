<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\SubmissionStatus;

function integrationConditionsTestIntegration(array $config = []): Integration
{
    return new class($config) extends Integration {
        public static function displayName(): string
        {
            return 'Test Integration';
        }

        public function fetchFormSettings(): IntegrationFormSettings
        {
            return new IntegrationFormSettings();
        }

        public function sendPayload(Submission $submission): bool
        {
            return true;
        }
    };
}

function integrationConditionsStatusSubmission(string $handle): Submission
{
    $status = new SubmissionStatus([
        'name' => 'Integration Conditions ' . $handle,
        'handle' => $handle,
        'color' => 'green',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $submission = new Submission();
    $submission->statusId = $status->id;

    return $submission;
}

it('triggers integrations when conditions are disabled', function (): void {
    $integration = integrationConditionsTestIntegration([
        'enableConditions' => false,
    ]);

    expect($integration->shouldTrigger(new Submission()))->toBeTrue();
});

it('triggers integrations when matching trigger conditions', function (): void {
    $handle = 'integrationConditions' . uniqid();
    $integration = integrationConditionsTestIntegration([
        'enableConditions' => true,
        'conditions' => [
            'triggerRule' => 'trigger',
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => '{submission:status}',
                'condition' => ConditionOperator::EQ,
                'value' => $handle,
            ]],
        ],
    ]);

    expect($integration->shouldTrigger(integrationConditionsStatusSubmission($handle)))->toBeTrue()
        ->and($integration->shouldTrigger(integrationConditionsStatusSubmission($handle . 'Other')))->toBeFalse();
});

it('inverts integration trigger conditions for the notTrigger rule', function (): void {
    $handle = 'integrationNotTrigger' . uniqid();
    $integration = integrationConditionsTestIntegration([
        'enableConditions' => true,
        'conditions' => [
            'triggerRule' => 'notTrigger',
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => '{submission:status}',
                'condition' => ConditionOperator::EQ,
                'value' => $handle,
            ]],
        ],
    ]);

    expect($integration->shouldTrigger(integrationConditionsStatusSubmission($handle)))->toBeFalse()
        ->and($integration->shouldTrigger(integrationConditionsStatusSubmission($handle . 'Other')))->toBeTrue();
});

it('includes integration condition settings in the form settings schema', function (): void {
    $integration = integrationConditionsTestIntegration([
        'name' => 'Test Integration',
        'handle' => 'testIntegration',
    ]);

    $schema = $integration->getFormSettingsSchema(new \verbb\formie\models\Stencil(['title' => 'Integration Conditions Schema']));
    $fieldNames = array_map(static fn(array $field) => $field['name'] ?? null, $schema);

    expect($fieldNames)->toContain('enabled')
        ->and($fieldNames)->toContain('enableConditions')
        ->and($fieldNames)->toContain('conditions');
});

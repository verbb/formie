<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use Craft;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\Integrations;
use verbb\formie\services\SubmissionWorkflow;

use yii\base\Event;

function cpSaveIntegrationTestIntegration(string $handle = 'cpSaveTest'): Integration
{
    return new class(['name' => 'CP Save Test', 'handle' => $handle]) extends Integration {
        public static function displayName(): string
        {
            return 'CP Save Test';
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

function withCpSaveTestIntegration(object $form, Integration $integration, callable $callback): mixed
{
    $handler = function(ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ((int)($event->form?->id ?? 0) === (int)$form->id) {
            $event->integrations[] = $integration;
        }
    };

    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);

    try {
        return $callback();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
}

it('detects when a form has integrations that allow cp save re-runs', function (): void {
    $form = formie()
        ->form(['title' => 'CP Save Policy Detection'])
        ->create();

    $form->settings->integrationPolicies = [
        'rerun' => [
            'entry' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $integration = cpSaveIntegrationTestIntegration('entry');

    withCpSaveTestIntegration($form, $integration, function () use ($form): void {
        expect(IntegrationRerunPolicies::formHasIntegrationAllowingEvent(
            $form,
            IntegrationTriggerEvents::CP_SAVE,
        ))->toBeTrue();
    });
});

it('applies cp submission sidebar attributes during managed saves', function (): void {
    $status = new \verbb\formie\models\Status([
        'name' => 'Accepted',
        'handle' => 'acceptedCpSave',
        'color' => 'green',
    ]);

    expect(Formie::$plugin->getStatuses()->saveStatus($status))->toBeTrue();

    $form = formie()
        ->form(['title' => 'CP Save Attributes'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Before Status'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission, $status): void {
        $request->setIsCpRequest(true);
        $request->setBodyParams([
            'handle' => $form->handle,
            'submissionId' => (int)$submission->id,
            'siteId' => (int)$submission->siteId,
            'title' => 'Updated Title',
            'statusId' => (int)$status->id,
            'fields' => [
                'fullName' => 'After Status',
            ],
        ]);

        (new verbb\formie\controllers\SubmissionsController('formie-submissions-test', Craft::$app))->actionSaveSubmission();
    }, [
        'method' => 'POST',
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
    ]);

    $reloaded = Submission::find()->id($submission->id)->status(null)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->title)->toBe('Updated Title')
        ->and($reloaded->statusId)->toBe($status->id)
        ->and($reloaded->getFieldValue('fullName'))->toBe('After Status');
});

it('dispatches cp element saves through the integration coordinator', function (): void {
    $form = formie()
        ->form(['title' => 'CP Coordinator Element Save'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->integrationPolicies = [
        'rerun' => [
            'cpSaveTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Before'])
        ->save();

    $integration = cpSaveIntegrationTestIntegration();
    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withCpSaveTestIntegration($form, $integration, function () use ($submission): void {
            Formie::$plugin->getIntegrationTriggers()->dispatchCpElementSave($submission);
        });
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }

    expect($triggerCount)->toBe(1);
});

it('does not double-trigger integrations when cp saves go through the submission workflow', function (): void {
    $form = formie()
        ->form(['title' => 'CP Workflow Save Integrations'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->integrationPolicies = [
        'rerun' => [
            'cpSaveTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Before Workflow'])
        ->save();

    $existing->setFieldValueFromRequest('fullName', 'After Workflow');

    $integration = cpSaveIntegrationTestIntegration();
    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withCpSaveTestIntegration($form, $integration, function () use ($form, $existing): void {
            WebRequestTestHelper::withWebRequestContext(function (): void {
                Craft::$app->getRequest()->setIsCpRequest(true);
            }, [
                'method' => 'POST',
                'hostInfo' => 'https://craft.example.test',
                'httpHost' => 'craft.example.test',
            ]);

            $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
                'form' => $form,
                'submission' => $existing,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            ]));

            expect($response->success)->toBeTrue();
        });
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }

    expect($triggerCount)->toBe(1);
});

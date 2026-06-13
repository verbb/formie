<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\services\Integrations;
use verbb\formie\services\IntegrationExecutor;
use verbb\formie\services\SubmissionWorkflow;

use yii\base\Event;

function executorTestIntegration(string $handle): Integration
{
    return new class(['name' => 'Executor Test', 'handle' => $handle]) extends Integration {
        public static function displayName(): string
        {
            return 'Executor Test';
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

function withExecutorTestIntegrations(object $form, array $integrations, callable $callback): mixed
{
    $handler = function(ModifyFormIntegrationsEvent $event) use ($form, $integrations): void {
        if ((int)($event->form?->id ?? 0) === (int)$form->id) {
            foreach ($integrations as $integration) {
                $event->integrations[] = $integration;
            }
        }
    };

    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);

    try {
        return $callback();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
}

it('resolves legacy integration handles for payload integrations', function (): void {
    $form = new Form();
    $form->id = 1001;

    $executor = new IntegrationExecutor();

    withExecutorTestIntegrations($form, [
        executorTestIntegration('first'),
        executorTestIntegration('second'),
    ], function () use ($executor, $form): void {
        expect($executor->resolveLegacyHandles($form))->toBe(['first', 'second']);
    });
});

it('runs integration steps synchronously with trigger context', function (): void {
    $form = new Form();
    $form->id = 1011;

    $submission = new Submission();
    $submission->id = 1012;
    $submission->setForm($form);

    $triggered = [];
    $beforeHandler = function ($event) use (&$triggered): void {
        $triggered[] = $event->integration->handle ?? null;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withExecutorTestIntegrations($form, [
            executorTestIntegration('alpha'),
            executorTestIntegration('beta'),
        ], function () use ($submission, &$triggered): void {
            Formie::$plugin->getIntegrationExecutor()->runSteps(
                $submission,
                ['alpha', 'beta'],
                [
                    'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                    'isSubmissionEdit' => false,
                    'triggerEvent' => IntegrationTriggerEvents::SUBMIT,
                    'operatorInitiated' => false,
                ],
            );
        });

        expect($triggered)->toBe(['alpha', 'beta']);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

it('builds batched TriggerIntegration jobs for queued steps', function (): void {
    $job = new TriggerIntegration([
        'submissionId' => 1022,
        'stepHandles' => ['queuedOne'],
        'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
        'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
        'runAfterNotifications' => true,
        'formHandle' => 'executorForm',
    ]);

    expect($job->stepHandles)->toBe(['queuedOne'])
        ->and($job->processMode)->toBe(SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING)
        ->and($job->triggerEvent)->toBe(IntegrationTriggerEvents::CP_SAVE)
        ->and($job->runAfterNotifications)->toBeTrue();
});

it('sets manual trigger context for operator-initiated integration runs', function (): void {
    $form = new Form();
    $form->id = 1031;

    $submission = new Submission();
    $submission->id = 1032;
    $submission->setForm($form);

    $integration = executorTestIntegration('manualTest');

    withExecutorTestIntegrations($form, [$integration], function () use ($integration, $submission): void {
        Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission);

        expect($integration->context['triggerEvent'] ?? null)->toBe(IntegrationTriggerEvents::MANUAL)
            ->and($integration->context['operatorInitiated'] ?? null)->toBeTrue();
    });
});

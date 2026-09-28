<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\Integrations;
use verbb\formie\services\SubmissionWorkflow;

use yii\base\Event;

function integrationTriggersTestIntegration(string $handle = 'coordinatorTest'): Integration
{
    return new class(['name' => 'Coordinator Test', 'handle' => $handle]) extends Integration {
        public static function displayName(): string
        {
            return 'Coordinator Test';
        }

        public function fetchConfig(): IntegrationConfig
        {
            return new IntegrationConfig();
        }

        public function sendPayload(Submission $submission): bool
        {
            return true;
        }
    };
}

function withIntegrationTriggersSyncQueue(callable $callback): mixed
{
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->useQueueForIntegrations;
    $settings->useQueueForIntegrations = false;

    try {
        return $callback();
    } finally {
        $settings->useQueueForIntegrations = $previous;
    }
}

function withCoordinatorTestIntegration(object $form, Integration $integration, callable $callback): mixed
{
    // Synthetic form IDs can overlap persisted fixtures cached by earlier tests.
    $originalIntegrations = Formie::$plugin->getIntegrations();
    Formie::$plugin->set('integrations', new Integrations());

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
        Formie::$plugin->set('integrations', $originalIntegrations);
    }
}

it('routes workflow integration dispatch through the coordinator', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    $submission = formie()->submission($form)->save();

    $integration = integrationTriggersTestIntegration('workflowTest');
    $form->settings->integrationPolicies = [
        'rerun' => [
            'workflowTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withIntegrationTriggersSyncQueue(function () use ($form, $integration, $submission, &$triggerCount): void {
            WebRequestTestHelper::withWebRequestContext(function () use ($form, $integration, $submission): void {
                withCoordinatorTestIntegration($form, $integration, function () use ($submission): void {
                    Formie::$plugin->getIntegrationTriggers()->dispatchFromWorkflow(
                        $submission,
                        \verbb\formie\enums\SubmissionOperation::REVISE,
                        IntegrationTriggerEvents::FRONTEND_EDIT,
                    );
                });
            }, [
                'method' => 'POST',
                'hostInfo' => 'https://craft.example.test',
                'httpHost' => 'craft.example.test',
            ]);
        });

        expect($triggerCount)->toBe(1);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

it('dispatches cp element saves only when re-run policy allows cp save', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->integrationPolicies = [
        'rerun' => [
            'coordinatorTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $submission = formie()->submission($form)->save();

    $integration = integrationTriggersTestIntegration();
    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withIntegrationTriggersSyncQueue(function () use ($form, $integration, $submission, &$triggerCount): void {
            WebRequestTestHelper::withWebRequestContext(function () use ($form, $integration, $submission, &$triggerCount): void {
                withCoordinatorTestIntegration($form, $integration, function () use ($submission): void {
                    Formie::$plugin->getIntegrationTriggers()->dispatchCpElementSave($submission);
                });

                expect($triggerCount)->toBe(1);

                $triggerCount = 0;
                $submission->isSpam = true;

                withCoordinatorTestIntegration($form, $integration, function () use ($submission): void {
                    Formie::$plugin->getIntegrationTriggers()->dispatchCpElementSave($submission);
                });

                expect($triggerCount)->toBe(0);
            }, [
                'method' => 'POST',
                'hostInfo' => 'https://craft.example.test',
                'httpHost' => 'craft.example.test',
            ]);
        });
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

it('unifies spam unmark notifications and integration dispatch', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    $submission = formie()->submission($form)->save();

    $integration = integrationTriggersTestIntegration('spamTest');
    $form->settings->integrationPolicies = [
        'rerun' => [
            'spamTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_SUBMIT_ONLY,
            ],
        ],
    ];

    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        withIntegrationTriggersSyncQueue(function () use ($form, $integration, $submission, &$triggerCount): void {
            WebRequestTestHelper::withWebRequestContext(function () use ($form, $integration, $submission, &$triggerCount): void {
                withCoordinatorTestIntegration($form, $integration, function () use ($submission): void {
                    Formie::$plugin->getIntegrationTriggers()->dispatchSpamUnmark($submission, false, true);
                });

                expect($triggerCount)->toBe(1);

                withCoordinatorTestIntegration($form, $integration, function () use ($submission): void {
                    Formie::$plugin->getIntegrationTriggers()->dispatchSpamUnmark($submission, false, false);
                });

                expect($triggerCount)->toBe(1);
            }, [
                'method' => 'POST',
                'hostInfo' => 'https://craft.example.test',
                'httpHost' => 'craft.example.test',
            ]);
        });
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

it('does not dispatch administrative follow-ups without an explicit unmark action', function () {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $command = submissionCommand(['operation' => \verbb\formie\enums\SubmissionOperation::REVISE, 'form' => $form, 'submission' => $submission]);
    $context = new \verbb\formie\workflow\WorkflowContext($command);
    $context->taskState['dispatch.state'] = new \verbb\formie\workflow\tasks\dispatch\DispatchState($submission, $command->operation, true);
    $count = 0;
    $handler = function () use (&$count) { $count++; };
    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $handler);
    try {
        (new \verbb\formie\workflow\tasks\dispatch\RevisionFollowUpsTask())->execute($context);
        expect($count)->toBe(0);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $handler);
    }
});

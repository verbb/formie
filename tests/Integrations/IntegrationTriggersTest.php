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
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\SubmissionRequest;
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

it('routes workflow integration dispatch through the coordinator', function (): void {
    $form = new Form();
    $form->id = 801;

    $submission = new Submission();
    $submission->id = 802;
    $submission->setForm($form);

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
                        SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
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
    $form = new Form();
    $form->id = 901;
    $form->settings->integrationPolicies = [
        'rerun' => [
            'coordinatorTest' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $submission = new Submission();
    $submission->id = 902;
    $submission->setForm($form);

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
    $form = new Form();
    $form->id = 911;

    $submission = new Submission();
    $submission->id = 912;
    $submission->setForm($form);

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

it('does not dispatch cp follow-ups when the submission was not unmarked as not spam', function (): void {
    $submission = new Submission();
    $submission->id = 920;

    $triggerCount = 0;
    $beforeHandler = function () use (&$triggerCount): void {
        $triggerCount++;
    };

    Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);

    try {
        Formie::$plugin->getIntegrationTriggers()->dispatchCpSubmissionFollowUps(
            $submission,
            new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
                'form' => new Form(),
                'submission' => $submission,
            ]),
        );

        expect($triggerCount)->toBe(0);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

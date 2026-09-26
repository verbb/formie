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
use verbb\formie\services\IntegrationRunner;
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

        public bool $succeeds = true;
        public static array $calls = [];

        public function sendPayload(Submission $submission): \verbb\formie\models\IntegrationResult
        {
            self::$calls[$this->handle] = (self::$calls[$this->handle] ?? 0) + 1;
            return $this->succeeds ? \verbb\formie\models\IntegrationResult::succeeded() : \verbb\formie\models\IntegrationResult::failed('definite_test_failure', true);
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
    $form = formie()->form()->singleLineTextField('name')->create();

    $executor = new IntegrationRunner();

    withExecutorTestIntegrations($form, [
        executorTestIntegration('first'),
        executorTestIntegration('second'),
    ], function () use ($executor, $form): void {
        expect($executor->resolveLegacyHandles($form))->toBe(['first', 'second']);
    });
});

it('runs integration steps synchronously with trigger context', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    $submission = formie()->submission($form)->save();

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
            $result = Formie::$plugin->getIntegrationRunner()->runSteps(
                $submission,
                ['alpha', 'beta'],
                [
                    'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
                    'isSubmissionEdit' => false,
                    'triggerEvent' => IntegrationTriggerEvents::SUBMIT,
                    'operatorInitiated' => false,
                ],
            );

            expect($result->accepts())->toBeTrue()
                ->and(count($result->results()))->toBe(2)
                ->and(count(array_filter($result->results(), fn($item) => $item['result']->isSuccessful())))->toBe(2);
        });

        expect($triggered)->toBe(['alpha', 'beta']);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $beforeHandler);
    }
});

it('builds thin TriggerIntegration locators for queued steps', function (): void {
    $job = new TriggerIntegration(['deliveryAttemptUid' => '17854078-7aec-4843-a91b-8a9e1a21f7c0']);
    $serialized = Craft::$app->getQueue()->serializer->serialize($job);
    expect(strlen($serialized))->toBeLessThan(1024)->and($serialized)->not->toContain('stepHandles', 'integrationContext');
});

it('sets manual trigger context for operator-initiated integration runs', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    $submission = formie()->submission($form)->save();

    $integration = executorTestIntegration('manualTest');

    withExecutorTestIntegrations($form, [$integration], function () use ($integration, $submission): void {
        Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission);

        expect($integration->context)->toBe([]);
        $history = Formie::$plugin->getDeliveryAttempts()->history((int)$submission->id);
        expect(Formie::$plugin->getDeliveryAttempts()->context($history[0]['uid'])->reason)->toBe('manual');
    });
});


it('retries failed queued steps without repeating completed steps', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $form->settings->integrationDispatch = ['enabled' => true];
    $submission = formie()->submission($form)->with(['fullName' => 'Retry'])->save();
    $first = executorTestIntegration('completedStep');
    $second = executorTestIntegration('retryStep');
    $second->succeeds = false;
    $context = [
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'isSubmissionEdit' => false,
        'triggerEvent' => IntegrationTriggerEvents::SUBMIT,
        'operatorInitiated' => false,
    ];

    withExecutorTestIntegrations($form, [$first, $second], function () use ($submission, $first, $second, $context): void {
        $executor = Formie::$plugin->getIntegrationRunner();
        $run = fn(string $key) => $executor->runSteps($submission, ['completedStep', 'retryStep'], $context, null, $key);
        expect($run('retry-job')->accepts())->toBeFalse();
        // Simulate a stale submission object loaded by another worker.
        $submission->integrationDispatchContext = null;
        $second->succeeds = true;
        expect($run('retry-job')->accepts())->toBeTrue();
        expect($first::$calls[$first->handle])->toBe(1)->and($second::$calls[$second->handle])->toBe(2);
        expect($run('retry-job')->accepts())->toBeTrue();
        expect($first::$calls[$first->handle])->toBe(1)->and($second::$calls[$second->handle])->toBe(2);
        expect($run('separate-job')->accepts())->toBeTrue();
        expect($first::$calls[$first->handle])->toBe(2)->and($second::$calls[$second->handle])->toBe(3);
    });
});

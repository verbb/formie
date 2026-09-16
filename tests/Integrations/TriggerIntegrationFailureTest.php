<?php

declare(strict_types=1);


use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\Formie;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\services\Integrations;

use yii\base\Event;

function integrationFailureTestIntegration(bool $shouldSucceed): Integration
{
    $integration = new class(['name' => 'Test Integration', 'handle' => 'testIntegration']) extends Integration {
        public bool $shouldSucceed = false;

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
            return $this->shouldSucceed;
        }
    };

    $integration->shouldSucceed = $shouldSucceed;

    return $integration;
}

it('preserves the original failure event and current job ID for queued integrations', function (): void {
    $integration = integrationFailureTestIntegration(false);
    $integration->setQueueJob(new \verbb\formie\jobs\TriggerIntegration());
    Formie::$plugin->getSettings()->sendIntegrationAlerts = false;
    $exception = new RuntimeException('Controlled integration failure');
    $captured = null;
    $service = Formie::$plugin->getIntegrations();
    $handler = function ($event) use (&$captured): void { $captured = $event; };
    $service->on(Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
    // The host exposes a job ID only while a queue delivery is executing.
    $queue = Craft::$app->getQueue();
    $currentJobId = new ReflectionProperty($queue, '_executingJobId');
    $previousJobId = $currentJobId->getValue($queue);
    $currentJobId->setValue($queue, '42');

    try {
        $service->handleTriggerIntegrationFailed($integration, new Submission(), $exception);
        expect($captured?->exception)->toBe($exception);
        expect($captured?->fromQueue)->toBeTrue();
        expect($captured?->queueJobId)->toBe(42);
    } finally {
        $service->off(Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
        $currentJobId->setValue($queue, $previousJobId);
    }
});

it('fires EVENT_AFTER_TRIGGER_INTEGRATION_FAILED when sendIntegrationPayload returns false', function (): void {
    $integration = integrationFailureTestIntegration(false);
    $submission = new Submission();
    $submission->id = 123;

    $fired = false;
    $handler = function () use (&$fired): void {
        $fired = true;
    };

    Event::on(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);

    try {
        expect(Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission))->toBeFalse()
            ->and($fired)->toBeTrue();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
    }
});

it('fires EVENT_AFTER_TRIGGER_INTEGRATION_FAILED when sendIntegrationPayload throws', function (): void {
    $integration = new class(['name' => 'Throwing Integration', 'handle' => 'throwingIntegration']) extends Integration {
        public static function displayName(): string
        {
            return 'Throwing Integration';
        }

        public function fetchFormSettings(): IntegrationFormSettings
        {
            return new IntegrationFormSettings();
        }

        public function sendPayload(Submission $submission): bool
        {
            throw new IntegrationException('Boom');
        }
    };

    $submission = new Submission();
    $submission->id = 456;

    $event = null;
    $handler = function ($triggeredEvent) use (&$event): void {
        $event = $triggeredEvent;
    };

    Event::on(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);

    try {
        Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
    } catch (IntegrationException) {
        expect($event)->not->toBeNull()
            ->and($event->integration->handle)->toBe('throwingIntegration')
            ->and($event->submission->id)->toBe(456)
            ->and($event->exception->getMessage())->toBe('Boom');
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
    }
});

it('fires EVENT_AFTER_TRIGGER_INTEGRATION_FAILED when sendIntegrationPayload returns a failed response', function (): void {
    $integration = new class(['name' => 'Response Integration', 'handle' => 'responseIntegration']) extends Integration {
        public static function displayName(): string
        {
            return 'Response Integration';
        }

        public function fetchFormSettings(): IntegrationFormSettings
        {
            return new IntegrationFormSettings();
        }

        public function sendPayload(Submission $submission): IntegrationResponse
        {
            return new IntegrationResponse(false, ['error' => 'Invalid payload']);
        }
    };

    $submission = new Submission();
    $submission->id = 789;

    $event = null;
    $handler = function ($triggeredEvent) use (&$event): void {
        $event = $triggeredEvent;
    };

    Event::on(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);

    try {
        $response = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);

        expect($response)->toBeInstanceOf(IntegrationResponse::class)
            ->and($response->success)->toBeFalse()
            ->and($event)->not->toBeNull()
            ->and($event->integrationResponse)->toBeInstanceOf(IntegrationResponse::class);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
    }
});

it('does not fire EVENT_AFTER_TRIGGER_INTEGRATION_FAILED when sendIntegrationPayload succeeds', function (): void {
    $integration = integrationFailureTestIntegration(true);
    $submission = new Submission();
    $submission->id = 321;

    $fired = false;
    $handler = function () use (&$fired): void {
        $fired = true;
    };

    Event::on(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);

    try {
        expect(Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission))->toBeTrue()
            ->and($fired)->toBeFalse();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $handler);
    }
});

<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use craft\base\Model;

class IntegrationDispatchPlan extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromFormSettings(mixed $settings): self
    {
        if (is_object($settings)) {
            $settings = (array)$settings;
        }

        if (!is_array($settings)) {
            $settings = [];
        }

        $steps = $settings['steps'] ?? [];

        if (!is_array($steps)) {
            $steps = [];
        }

        return new self([
            'enabled' => (bool)($settings['enabled'] ?? false),
            'notificationTiming' => ($settings['notificationTiming'] ?? '') === 'afterIntegrations' ? self::NOTIFICATION_TIMING_AFTER : (string)($settings['notificationTiming'] ?? self::NOTIFICATION_TIMING_BEFORE),
            'failurePolicy' => (string)($settings['failurePolicy'] ?? self::FAILURE_CONTINUE),
            'steps' => array_values(array_filter(array_map(static function($step) {
                if (!is_array($step)) {
                    return null;
                }

                $handle = trim((string)($step['handle'] ?? ''));

                if ($handle === '') {
                    return null;
                }

                $execution = (string)($step['execution'] ?? (($step['mode'] ?? '') === 'immediate' ? self::EXECUTION_SYNCHRONOUS : self::EXECUTION_QUEUED));

                if (!in_array($execution, [self::EXECUTION_SYNCHRONOUS, self::EXECUTION_QUEUED], true)) {
                    $execution = self::EXECUTION_QUEUED;
                }

                return [
                    'handle' => $handle,
                    'execution' => $execution,
                ];
            }, $steps))),
        ]);
    }


    // Constants
    // =========================================================================

    public const NOTIFICATION_TIMING_BEFORE = 'beforeIntegrations';
    public const NOTIFICATION_TIMING_AFTER = 'afterFinalizedDeliveryAttempts';
    public const NOTIFICATION_TIMING_SYNCHRONOUS = 'afterSynchronousIntegrations';

    public const EXECUTION_SYNCHRONOUS = 'synchronous';
    public const EXECUTION_QUEUED = 'queued';

    public const FAILURE_CONTINUE = 'continue';
    public const FAILURE_STOP = 'stop';


    // Properties
    // =========================================================================

    public bool $enabled = false;
    public string $notificationTiming = self::NOTIFICATION_TIMING_BEFORE;
    public string $failurePolicy = self::FAILURE_CONTINUE;
    /** @var array<int, array{handle: string, execution: string}> */
    public array $steps = [];


    // Public Methods
    // =========================================================================

    public function shouldOrchestrate(): bool
    {
        return $this->enabled;
    }

    public function getStepExecution(string $handle): string
    {
        foreach ($this->steps as $step) {
            if (($step['handle'] ?? '') === $handle) {
                return (string)($step['execution'] ?? self::EXECUTION_QUEUED);
            }
        }

        return self::EXECUTION_QUEUED;
    }

    public function resolveSteps(Form $form): array
    {
        if ($this->steps) {
            return $this->steps;
        }

        $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);
        $steps = [];

        foreach ($integrations as $integration) {
            if (!$integration->supportsPayloadSending()) {
                continue;
            }

            $steps[] = [
                'handle' => $integration->handle,
                'execution' => self::EXECUTION_QUEUED,
            ];
        }

        usort($steps, function(array $a, array $b) use ($integrations) {
            $sortOrders = [];

            foreach ($integrations as $integration) {
                $sortOrders[$integration->handle] = (int)($integration->sortOrder ?? 0);
            }

            return ($sortOrders[$a['handle']] ?? 0) <=> ($sortOrders[$b['handle']] ?? 0);
        });

        return $steps;
    }

    public function getOrderedHandles(Form $form): array
    {
        return array_values(array_map(static fn(array $step) => (string)$step['handle'], $this->resolveSteps($form)));
    }

    public function getSynchronousHandles(Form $form): array
    {
        return array_values(array_filter($this->getOrderedHandles($form), fn(string $handle) => $this->getStepExecution($handle) === self::EXECUTION_SYNCHRONOUS));
    }

    public function getQueuedHandles(Form $form): array
    {
        return array_values(array_filter($this->getOrderedHandles($form), fn(string $handle) => $this->getStepExecution($handle) === self::EXECUTION_QUEUED));
    }

    public function shouldStopOnFailure(): bool
    {
        return $this->failurePolicy === self::FAILURE_STOP;
    }

    public function toSettingsArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'notificationTiming' => $this->notificationTiming,
            'failurePolicy' => $this->failurePolicy,
            'steps' => $this->steps,
        ];
    }
}

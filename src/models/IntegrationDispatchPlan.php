<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\Formie;

use craft\base\Model;

class IntegrationDispatchPlan extends Model
{
    // Constants
    // =========================================================================

    public const NOTIFICATION_TIMING_BEFORE = 'beforeIntegrations';
    public const NOTIFICATION_TIMING_AFTER = 'afterIntegrations';

    public const MODE_IMMEDIATE = 'immediate';
    public const MODE_QUEUED = 'queued';

    public const FAILURE_CONTINUE = 'continue';
    public const FAILURE_STOP = 'stop';


    // Properties
    // =========================================================================

    public bool $enabled = false;
    public string $notificationTiming = self::NOTIFICATION_TIMING_BEFORE;
    public string $failurePolicy = self::FAILURE_CONTINUE;

    /** @var array<int, array{handle: string, mode: string}> */
    public array $steps = [];


    // Public Methods
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
            'notificationTiming' => (string)($settings['notificationTiming'] ?? self::NOTIFICATION_TIMING_BEFORE),
            'failurePolicy' => (string)($settings['failurePolicy'] ?? self::FAILURE_CONTINUE),
            'steps' => array_values(array_filter(array_map(static function($step) {
                if (!is_array($step)) {
                    return null;
                }

                $handle = trim((string)($step['handle'] ?? ''));

                if ($handle === '') {
                    return null;
                }

                $mode = (string)($step['mode'] ?? self::MODE_QUEUED);

                if (!in_array($mode, [self::MODE_IMMEDIATE, self::MODE_QUEUED], true)) {
                    $mode = self::MODE_QUEUED;
                }

                return [
                    'handle' => $handle,
                    'mode' => $mode,
                ];
            }, $steps))),
        ]);
    }

    public function shouldOrchestrate(): bool
    {
        return $this->enabled;
    }

    public function getStepMode(string $handle): string
    {
        foreach ($this->steps as $step) {
            if (($step['handle'] ?? '') === $handle) {
                return (string)($step['mode'] ?? self::MODE_QUEUED);
            }
        }

        return self::MODE_QUEUED;
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
                'mode' => self::MODE_QUEUED,
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

    public function getImmediateHandles(Form $form): array
    {
        return array_values(array_filter($this->getOrderedHandles($form), fn(string $handle) => $this->getStepMode($handle) === self::MODE_IMMEDIATE));
    }

    public function getQueuedHandles(Form $form): array
    {
        return array_values(array_filter($this->getOrderedHandles($form), fn(string $handle) => $this->getStepMode($handle) === self::MODE_QUEUED));
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

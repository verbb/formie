<?php
namespace verbb\formie\helpers;

use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\integrations\elements\Entry;

class IntegrationRerunPolicies
{
    // Constants
    // =========================================================================

    public const POLICY_SUBMIT_ONLY = 'submitOnly';
    public const POLICY_ON_EDIT = 'onEdit';
    public const POLICY_CUSTOM = 'custom';


    // Public Methods
    // =========================================================================

    public static function getStoredConfig(Form $form, string $handle): ?array
    {
        $policies = $form->settings->integrationPolicies ?? [];

        if (is_object($policies)) {
            $policies = (array)$policies;
        }

        $rerun = $policies['rerun'] ?? [];

        if (is_object($rerun)) {
            $rerun = (array)$rerun;
        }

        $config = $rerun[$handle] ?? null;

        if (!is_array($config)) {
            return null;
        }

        return $config;
    }

    public static function getPolicy(Form $form, Integration $integration): string
    {
        $stored = self::getStoredConfig($form, (string)$integration->handle);

        if ($stored && !empty($stored['policy'])) {
            return (string)$stored['policy'];
        }

        if ($integration instanceof Entry && self::_hasLegacyEntryEditPolicy($integration)) {
            return self::POLICY_ON_EDIT;
        }

        return self::POLICY_SUBMIT_ONLY;
    }

    public static function getAllowedEvents(Form $form, Integration $integration): array
    {
        $stored = self::getStoredConfig($form, (string)$integration->handle);

        if ($stored && !empty($stored['policy'])) {
            return self::resolveEventsFromPolicy(
                (string)$stored['policy'],
                is_array($stored['events'] ?? null) ? $stored['events'] : [],
            );
        }

        if ($integration instanceof Entry && self::_hasLegacyEntryEditPolicy($integration)) {
            return self::resolveEventsFromPolicy(self::POLICY_ON_EDIT);
        }

        return self::resolveEventsFromPolicy(self::POLICY_SUBMIT_ONLY);
    }

    public static function resolveEventsFromPolicy(string $policy, array $customEvents = []): array
    {
        return match ($policy) {
            self::POLICY_ON_EDIT => [
                IntegrationTriggerEvents::SUBMIT,
                IntegrationTriggerEvents::FRONTEND_EDIT,
                IntegrationTriggerEvents::CP_SAVE,
            ],
            self::POLICY_CUSTOM => self::_normalizeEvents($customEvents),
            default => [IntegrationTriggerEvents::SUBMIT],
        };
    }

    public static function isEventAllowed(
        Form $form,
        Integration $integration,
        string $triggerEvent,
        bool $operatorInitiated = false,
    ): bool {
        $allowed = self::getAllowedEvents($form, $integration);

        if (in_array($triggerEvent, $allowed, true)) {
            return true;
        }

        if ($operatorInitiated && $triggerEvent === IntegrationTriggerEvents::MANUAL) {
            return true;
        }

        // Explicit operator unmark actions preserve prior behaviour for submit-only integrations.
        if (
            $operatorInitiated
            && $triggerEvent === IntegrationTriggerEvents::UNMARK_SPAM
            && in_array(IntegrationTriggerEvents::SUBMIT, $allowed, true)
        ) {
            return true;
        }

        return false;
    }

    public static function hasNonDefaultPolicy(Form $form, array $handles): bool
    {
        foreach ($handles as $handle) {
            $stored = self::getStoredConfig($form, (string)$handle);

            if (!$stored || empty($stored['policy'])) {
                continue;
            }

            if ((string)$stored['policy'] !== self::POLICY_SUBMIT_ONLY) {
                return true;
            }
        }

        return false;
    }

    public static function formHasIntegrationAllowingEvent(Form $form, string $triggerEvent): bool
    {
        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form) as $integration) {
            if (!$integration->supportsPayloadSending()) {
                continue;
            }

            if (self::isEventAllowed($form, $integration, $triggerEvent)) {
                return true;
            }
        }

        return false;
    }

    public static function policyLabels(): array
    {
        return [
            self::POLICY_SUBMIT_ONLY => \Craft::t('formie', 'Once on submit'),
            self::POLICY_ON_EDIT => \Craft::t('formie', 'Also when submission is edited'),
            self::POLICY_CUSTOM => \Craft::t('formie', 'Custom…'),
        ];
    }


    // Private Methods
    // =========================================================================

    private static function _hasLegacyEntryEditPolicy(Entry $integration): bool
    {
        return (bool)$integration->updateElement && (bool)$integration->updateOnSubmissionEdit;
    }

    private static function _normalizeEvents(array $events): array
    {
        $normalized = [];

        foreach ($events as $event) {
            $event = (string)$event;

            if (!in_array($event, IntegrationTriggerEvents::ALL, true)) {
                continue;
            }

            $normalized[] = $event;
        }

        if (!$normalized) {
            return [IntegrationTriggerEvents::SUBMIT];
        }

        return array_values(array_unique($normalized));
    }
}

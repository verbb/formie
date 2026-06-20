<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPageSettings;
use verbb\formie\models\FormSettings;
use verbb\formie\services\SubmissionWorkflow;

class ClientEventsHelper
{
    // Public Methods
    // =========================================================================

    public static function migrateLegacyEventFields(array $legacyRows): array
    {
        if ($legacyRows === []) {
            return [];
        }

        $eventName = 'formPageSubmission';
        $payload = [];

        foreach ($legacyRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = trim((string)($row['label'] ?? ''));
            $value = (string)($row['value'] ?? '');

            if ($key === '') {
                continue;
            }

            if ($key === 'event' && $value !== '') {
                $eventName = $value;
                continue;
            }

            $payload[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return [[
            'event' => $eventName,
            'payload' => $payload,
        ]];
    }

    public static function normalizeStoredEvents(FieldLayoutPageSettings|FormSettings|array $settings): array
    {
        if (is_array($settings)) {
            $events = $settings['clientEvents'] ?? $settings['defaultClientEvents'] ?? [];

            return self::_sanitizeEventDefinitions(is_array($events) ? $events : []);
        }

        if ($settings instanceof FormSettings) {
            return self::_sanitizeEventDefinitions(is_array($settings->defaultClientEvents) ? $settings->defaultClientEvents : []);
        }

        $events = $settings->clientEvents ?? [];

        if (is_array($events) && $events !== []) {
            return self::_sanitizeEventDefinitions($events);
        }

        if ($settings instanceof FieldLayoutPageSettings && !$settings->enableClientEvents) {
            return [];
        }

        $legacyRows = is_array($settings->clientEventFields) ? $settings->clientEventFields : [];

        if ($legacyRows === []) {
            return [];
        }

        return self::migrateLegacyEventFields($legacyRows);
    }

    public static function resolveEvents(array $eventDefinitions, Submission $submission): array
    {
        $resolved = [];

        foreach (self::_sanitizeEventDefinitions($eventDefinitions) as $eventDefinition) {
            if (!self::_eventMatchesConditions($eventDefinition, $submission)) {
                continue;
            }

            $eventName = trim((string)($eventDefinition['event'] ?? ''));

            if ($eventName === '') {
                continue;
            }

            $payload = ['event' => $eventName];
            $payloadRows = $eventDefinition['payload'] ?? [];

            if (!is_array($payloadRows)) {
                $payloadRows = [];
            }

            foreach ($payloadRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $key = trim((string)($row['key'] ?? $row['label'] ?? ''));

                if ($key === '' || $key === 'event') {
                    continue;
                }

                $rawValue = (string)($row['value'] ?? '');
                $payload[$key] = self::_stringifyResolvedValue(References::parseValue($rawValue, $submission));
            }

            $resolved[] = [
                'event' => $eventName,
                'payload' => $payload,
            ];
        }

        return $resolved;
    }

    public static function resolveEventsFromSettings(FieldLayoutPageSettings $settings, Submission $submission): array
    {
        return self::resolveEvents(self::normalizeStoredEvents($settings), $submission);
    }

    public static function resolveForSubmittedPage(
        Form $form,
        Submission $submission,
        ?int $pageId,
        string $submitAction,
    ): array {
        if ($submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return [];
        }

        $page = self::_findPageById($form, $pageId);

        if (!$page) {
            return [];
        }

        $settings = $page->getPageSettings();

        if (!$settings instanceof FieldLayoutPageSettings) {
            return [];
        }

        $eventDefinitions = self::_resolveEventDefinitionsForPage($form, $settings);

        if ($eventDefinitions === []) {
            return [];
        }

        return self::resolveEvents($eventDefinitions, $submission);
    }


    // Private Methods
    // =========================================================================

    private static function _resolveEventDefinitionsForPage(Form $form, FieldLayoutPageSettings $settings): array
    {
        if (!$settings->enableClientEvents) {
            return [];
        }

        $pageEvents = self::normalizeStoredEvents($settings);

        if ($pageEvents !== []) {
            return $pageEvents;
        }

        $formSettings = $form->getSettings();

        if (!$formSettings instanceof FormSettings || !$formSettings->enableDefaultClientEvents) {
            return [];
        }

        return self::normalizeStoredEvents($formSettings);
    }

    private static function _eventMatchesConditions(array $eventDefinition, Submission $submission): bool
    {
        if (empty($eventDefinition['enableConditions'])) {
            return true;
        }

        $conditions = $eventDefinition['conditions'] ?? [];

        if (!is_array($conditions) || $conditions === []) {
            return true;
        }

        return ConditionsHelper::getConditionalTestResult($conditions, $submission);
    }

    private static function _sanitizeEventDefinitions(array $events): array
    {
        $sanitized = [];

        foreach ($events as $eventDefinition) {
            if (!is_array($eventDefinition)) {
                continue;
            }

            $eventName = trim((string)($eventDefinition['event'] ?? ''));

            if ($eventName === '') {
                continue;
            }

            $payload = [];
            $payloadRows = $eventDefinition['payload'] ?? [];

            if (is_array($payloadRows)) {
                foreach ($payloadRows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $key = trim((string)($row['key'] ?? $row['label'] ?? ''));

                    if ($key === '' || $key === 'event') {
                        continue;
                    }

                    $payload[] = [
                        'key' => $key,
                        'value' => (string)($row['value'] ?? ''),
                    ];
                }
            }

            $sanitizedEvent = [
                'event' => $eventName,
                'payload' => $payload,
            ];

            if (!empty($eventDefinition['enableConditions'])) {
                $sanitizedEvent['enableConditions'] = true;
                $sanitizedEvent['conditions'] = is_array($eventDefinition['conditions'] ?? null)
                    ? $eventDefinition['conditions']
                    : [];
            }

            if (!empty($eventDefinition['templateHandle'])) {
                $sanitizedEvent['templateHandle'] = (string)$eventDefinition['templateHandle'];
            }

            if (!empty($eventDefinition['templateLabel'])) {
                $sanitizedEvent['templateLabel'] = (string)$eventDefinition['templateLabel'];
            }

            $sanitized[] = $sanitizedEvent;
        }

        return $sanitized;
    }

    private static function _findPageById(Form $form, ?int $pageId): ?\verbb\formie\models\FieldLayoutPage
    {
        if (!$pageId) {
            return null;
        }

        foreach ($form->getPages() as $page) {
            if ((int)$page->id === $pageId) {
                return $page;
            }
        }

        return null;
    }

    private static function _stringifyResolvedValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}

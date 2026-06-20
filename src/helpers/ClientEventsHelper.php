<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutPageSettings;
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

    public static function normalizeStoredEvents(FieldLayoutPageSettings $settings): array
    {
        $events = $settings->clientEvents ?? [];

        if (is_array($events) && $events !== []) {
            return self::_sanitizeEventDefinitions($events);
        }

        if (!$settings->enableClientEvents) {
            return [];
        }

        $legacyRows = is_array($settings->clientEventFields) ? $settings->clientEventFields : [];

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

    public static function resolveEvents(FieldLayoutPageSettings $settings, Submission $submission): array
    {
        $resolved = [];

        foreach (self::normalizeStoredEvents($settings) as $eventDefinition) {
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

        if (!$settings->enableClientEvents && self::normalizeStoredEvents($settings) === []) {
            return [];
        }

        return self::resolveEvents($settings, $submission);
    }


    // Private Methods
    // =========================================================================

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

            $sanitized[] = [
                'event' => $eventName,
                'payload' => $payload,
            ];
        }

        return $sanitized;
    }

    private static function _findPageById(Form $form, ?int $pageId): ?FieldLayoutPage
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

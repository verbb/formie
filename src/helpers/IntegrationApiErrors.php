<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;

use Craft;

use Throwable;

final class IntegrationApiErrors
{
    // Static Methods
    // =========================================================================

    public static function defaultHandlingRows(): array
    {
        return [
            ['severity' => self::SEVERITY_REJECTED, 'action' => self::ACTION_LOG_WARNING],
            ['severity' => self::SEVERITY_RATE_LIMITED, 'action' => self::ACTION_FAIL_QUEUE],
            ['severity' => self::SEVERITY_FAILURE, 'action' => self::ACTION_FAIL_QUEUE],
        ];
    }

    public static function severityOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Known subscriber rejections'), 'value' => self::SEVERITY_REJECTED],
            ['label' => Craft::t('formie', 'API rate limits'), 'value' => self::SEVERITY_RATE_LIMITED],
            ['label' => Craft::t('formie', 'Other errors'), 'value' => self::SEVERITY_FAILURE],
        ];
    }

    public static function actionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Fail delivery'), 'value' => self::ACTION_FAIL_QUEUE],
            ['label' => Craft::t('formie', 'Skip and log warning'), 'value' => self::ACTION_LOG_WARNING],
            ['label' => Craft::t('formie', 'Skip and log info'), 'value' => self::ACTION_LOG_INFO],
            ['label' => Craft::t('formie', 'Skip without logging'), 'value' => self::ACTION_IGNORE],
        ];
    }

    public static function handlingActions(IntegrationInterface $integration): array
    {
        $actions = [];

        foreach (self::severityOptions() as $option) {
            $actions[$option['value']] = self::action($integration, $option['value']);
        }

        return $actions;
    }

    public static function action(IntegrationInterface $integration, string $severity): string
    {
        $handling = $integration->getSettings()['apiErrorHandling'] ?? [];

        // Connections without a saved choice retain the former global policy.
        if (!array_key_exists($severity, $handling)) {
            return Formie::$plugin->getSettings()->getIntegrationApiErrorAction($severity);
        }

        $action = $handling[$severity];

        return in_array($action, array_column(self::actionOptions(), 'value'), true) ? $action : self::ACTION_FAIL_QUEUE;
    }

    public static function applySubmissionErrorAction(
        IntegrationInterface $integration,
        Throwable $exception,
        Submission $submission,
        string $severity,
    ): bool {
        $action = self::action($integration, $severity);
        $message = self::_formatMessage($integration, $exception, $submission, $severity, $action);

        if ($action === self::ACTION_FAIL_QUEUE) {
            Integration::error($integration, $message);

            throw new IntegrationException($message, 0, $exception);
        }

        if ($action === self::ACTION_LOG_WARNING) {
            Integration::warning($integration, $message);
        } elseif ($action === self::ACTION_LOG_INFO) {
            Integration::info($integration, $message);
        }

        return true;
    }

    private static function _formatMessage(
        IntegrationInterface $integration,
        Throwable $exception,
        Submission $submission,
        string $severity,
        string $action,
    ): string {
        $messageText = Integration::getExceptionLogMessage($exception);
        $context = Integration::formatSubmissionLogContext($submission);

        $categories = array_column(self::severityOptions(), 'label', 'value');
        $actions = array_column(self::actionOptions(), 'label', 'value');

        // Put the outcome and locator before the provider response, which may be truncated or masked.
        return Craft::t('formie', '{context} {provider}: {outcome} by Integration API Error Handling ({category}; {action}). Provider error: {message}', [
            'provider' => $integration::displayName(),
            'outcome' => $action === self::ACTION_FAIL_QUEUE ? Craft::t('formie', 'Delivery failed') : Craft::t('formie', 'Delivery skipped'),
            'category' => $categories[$severity] ?? $severity,
            'action' => $actions[$action] ?? $action,
            'message' => $messageText,
            'context' => $context,
        ]);
    }


    // Constants
    // =========================================================================

    public const SEVERITY_FAILURE = 'failure';
    public const SEVERITY_REJECTED = 'rejected';
    public const SEVERITY_RATE_LIMITED = 'rate_limited';

    public const ACTION_FAIL_QUEUE = 'failQueue';
    public const ACTION_LOG_WARNING = 'logWarning';
    public const ACTION_LOG_INFO = 'logInfo';
    public const ACTION_IGNORE = 'ignore';
}

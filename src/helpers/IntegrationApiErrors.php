<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;

use Craft;

use Throwable;

final class IntegrationApiErrors
{
    // Constants
    // =========================================================================

    public const SEVERITY_FAILURE = 'failure';
    public const SEVERITY_REJECTED = 'rejected';
    public const SEVERITY_RATE_LIMITED = 'rate_limited';

    public const ACTION_FAIL_QUEUE = 'failQueue';
    public const ACTION_LOG_WARNING = 'logWarning';
    public const ACTION_LOG_INFO = 'logInfo';
    public const ACTION_IGNORE = 'ignore';


    // Static Methods
    // =========================================================================

    public static function defaultHandlingRows(): array
    {
        return [
            ['severity' => self::SEVERITY_RATE_LIMITED, 'action' => self::ACTION_LOG_WARNING],
            ['severity' => self::SEVERITY_REJECTED, 'action' => self::ACTION_LOG_WARNING],
            ['severity' => self::SEVERITY_FAILURE, 'action' => self::ACTION_FAIL_QUEUE],
        ];
    }

    public static function severityOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Rate limited'), 'value' => self::SEVERITY_RATE_LIMITED],
            ['label' => Craft::t('formie', 'Rejected payload'), 'value' => self::SEVERITY_REJECTED],
            ['label' => Craft::t('formie', 'Failure'), 'value' => self::SEVERITY_FAILURE],
        ];
    }

    public static function actionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Fail queue job'), 'value' => self::ACTION_FAIL_QUEUE],
            ['label' => Craft::t('formie', 'Log warning'), 'value' => self::ACTION_LOG_WARNING],
            ['label' => Craft::t('formie', 'Log info'), 'value' => self::ACTION_LOG_INFO],
            ['label' => Craft::t('formie', 'Ignore'), 'value' => self::ACTION_IGNORE],
        ];
    }

    public static function applySubmissionErrorAction(
        IntegrationInterface $integration,
        Throwable $exception,
        Submission $submission,
        string $severity,
    ): bool {
        $action = Formie::$plugin->getSettings()->getIntegrationApiErrorAction($severity);
        $message = self::_formatMessage($integration, $exception, $submission, $severity);

        if ($action === self::ACTION_FAIL_QUEUE) {
            Formie::error($integration->name . ': ' . $message);
            throw new IntegrationException($message, 0, $exception);
        }

        if ($action === self::ACTION_LOG_WARNING) {
            Formie::warning($integration->name . ': ' . $message);
        } elseif ($action === self::ACTION_LOG_INFO) {
            Formie::info($integration->name . ': ' . $message);
        }

        return true;
    }

    private static function _formatMessage(
        IntegrationInterface $integration,
        Throwable $exception,
        Submission $submission,
        string $severity,
    ): string {
        $messageText = \verbb\formie\base\Integration::getExceptionLogMessage($exception);
        $context = \verbb\formie\base\Integration::formatSubmissionLogContext($submission);

        return Craft::t('formie', 'API {severity} (handled): “{message}”{context}', [
            'severity' => $severity,
            'message' => $messageText,
            'context' => $context !== '' ? ' ' . $context : '',
        ]);
    }
}

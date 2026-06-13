<?php
namespace verbb\formie\helpers;

use verbb\formie\services\SubmissionWorkflow;

use Craft;

class IntegrationTriggerEvents
{
    // Constants
    // =========================================================================

    public const SUBMIT = 'submit';
    public const FRONTEND_EDIT = 'frontendEdit';
    public const CP_SAVE = 'cpSave';
    public const UNMARK_SPAM = 'unmarkSpam';
    public const MANUAL = 'manual';

    public const ALL = [
        self::SUBMIT,
        self::FRONTEND_EDIT,
        self::CP_SAVE,
        self::UNMARK_SPAM,
        self::MANUAL,
    ];


    // Public Methods
    // =========================================================================

    public static function resolveFromProcessMode(string $processMode, ?bool $isCpRequest = null): string
    {
        if ($processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING) {
            $isCpRequest ??= Craft::$app->getRequest()->getIsCpRequest();

            return $isCpRequest ? self::CP_SAVE : self::FRONTEND_EDIT;
        }

        return self::SUBMIT;
    }

    public static function labels(): array
    {
        return [
            self::SUBMIT => Craft::t('formie', 'Initial submission'),
            self::FRONTEND_EDIT => Craft::t('formie', 'Front-end edit'),
            self::CP_SAVE => Craft::t('formie', 'Control panel save'),
            self::UNMARK_SPAM => Craft::t('formie', 'Unmarked as not spam'),
            self::MANUAL => Craft::t('formie', 'Manual trigger'),
        ];
    }
}

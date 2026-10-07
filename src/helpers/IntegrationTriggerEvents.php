<?php
namespace verbb\formie\helpers;

use verbb\formie\enums\SubmissionOperation;

use Craft;

class IntegrationTriggerEvents
{
    // Static Methods
    // =========================================================================

    public static function resolveFromOperation(SubmissionOperation $operation, bool $isCpRequest = false): string
    {
        if ($operation === SubmissionOperation::REVISE) {

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
}

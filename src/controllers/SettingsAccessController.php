<?php
namespace verbb\formie\controllers;

use Craft;
use craft\web\Controller;

use verbb\base\models\EnvironmentSettingsViolation;

class SettingsAccessController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->requirePermission('formie-accessSettings');

        return parent::beforeAction($action);
    }


    // Protected Methods
    // =========================================================================

    protected function environmentSettingsError(EnvironmentSettingsViolation $violation): string
    {
        if ($violation->reason === EnvironmentSettingsViolation::REFERENCE) {
            return Craft::t('formie', 'Only admins can use environment variables here.');
        }

        return Craft::t('formie', 'Enter the credentials again after changing where this connects.');
    }
}

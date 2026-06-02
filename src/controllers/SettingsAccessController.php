<?php
namespace verbb\formie\controllers;

use craft\web\Controller;

class SettingsAccessController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->requirePermission('formie-accessSettings');

        return parent::beforeAction($action);
    }
}

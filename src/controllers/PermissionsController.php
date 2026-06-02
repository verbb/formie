<?php
namespace verbb\formie\controllers;

use yii\web\Response;

class PermissionsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/permissions', []);
    }

}

<?php
namespace verbb\formie\controllers;

use yii\web\Response;

class SecurityController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/security', []);
    }

}

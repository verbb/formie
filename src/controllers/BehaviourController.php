<?php
namespace verbb\formie\controllers;

use yii\web\Response;

class BehaviourController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/behaviour', []);
    }

}

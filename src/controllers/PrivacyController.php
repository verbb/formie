<?php
namespace verbb\formie\controllers;


use yii\web\Response;

class PrivacyController extends AdminController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/privacy', []);
    }

}

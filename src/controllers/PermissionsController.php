<?php
namespace verbb\formie\controllers;


use yii\web\Response;

class PermissionsController extends AdminController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/permissions', []);
    }

}

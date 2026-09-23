<?php
namespace verbb\formie\controllers;


use yii\web\Response;

class BehaviourController extends AdminController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/behaviour', []);
    }

}

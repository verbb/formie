<?php
namespace verbb\formie\controllers;

use craft\web\Controller;

use yii\web\Response;

class BehaviourController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/behaviour', []);
    }

}

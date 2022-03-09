<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;

use craft\web\Controller;

use yii\web\Response;

class NotificationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/notifications', compact('settings'));
    }
}

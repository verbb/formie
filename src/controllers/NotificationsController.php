<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Settings;

use yii\web\Response;

class NotificationsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/notifications', compact('settings'));
    }
}

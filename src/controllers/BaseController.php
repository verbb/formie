<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Settings;

use craft\web\Controller;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->redirect("formie/{$settings->defaultPage}");
    }
}

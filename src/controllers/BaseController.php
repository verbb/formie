<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $url = "formie/{$settings->defaultPage}";

        // Check if they have permission to the page they're going to
        if ($settings->defaultPage === 'forms' && !Craft::$app->getUser()->checkPermission('formie-viewForms')) {
            $url = $this->_getFirstAvailablePage();
        }

        if ($settings->defaultPage === 'submissions' && !Craft::$app->getUser()->checkPermission('formie-viewSubmissions')) {
            $url = $this->_getFirstAvailablePage();
        }

        return $this->redirect($url);
    }


    // Private Methods
    // =========================================================================

    private function _getFirstAvailablePage()
    {
        $subnav = Formie::$plugin->getCpNavItem()['subnav'] ?? [];
        $firstNavUrl = reset($subnav)['url'];

        return $firstNavUrl ?? 'formie/forms';
    }
}

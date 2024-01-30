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
        if ($settings->defaultPage === 'forms' && !Craft::$app->getUser()->checkPermission('formie-accessForms')) {
            $url = $this->_getFirstAvailablePage();
        }

        if ($settings->defaultPage === 'submissions' && !Craft::$app->getUser()->checkPermission('formie-accessSubmissions')) {
            $url = $this->_getFirstAvailablePage();
        }

        return $this->redirect($url);
    }


    // Private Methods
    // =========================================================================

    private function _getFirstAvailablePage(): string
    {
        $subnav = Formie::$plugin->getCpNavItem()['subnav'] ?? [];
        $firstNavUrl = $subnav ? reset($subnav)['url'] : null;

        return $firstNavUrl ?? 'formie/forms';
    }
}

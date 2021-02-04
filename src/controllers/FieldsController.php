<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class FieldsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/fields', []);
    }

    public function actionRenderElements()
    {
        $elementsInfo = Craft::$app->getRequest()->getParam('elements');
        $elements = [];

        foreach ($elementsInfo as $info) {
            $elements[] = Craft::$app->getElements()->getElementById($info['id'], null, $info['siteId']);
        }

        return Craft::$app->getView()->renderTemplate('formie/_includes/elementSelect-elements', ['elements' => $elements]);
    }

}

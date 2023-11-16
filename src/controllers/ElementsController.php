<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Plugin;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\Response;

class ElementsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSections(): Response
    {
        $this->requireAcceptsJson();

        $sections = Craft::$app->getEntries()->getAllSections();

        return $this->asJson(['success' => true, 'sections' => $sections]);
    }

    public function actionEntryTypes(): Response
    {
        $this->requireAcceptsJson();

        $entryTypes = [];

        $sectionId = $this->request->getParam('sectionId');

        if ($sectionId) {
            $entryTypes = Craft::$app->getEntries()->getEntryTypesBySectionId($sectionId);
        }

        return $this->asJson(['success' => true, 'entryTypes' => $entryTypes]);
    }

    public function actionCategoryGroups(): Response
    {
        $this->requireAcceptsJson();

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        return $this->asJson(['success' => true, 'categoryGroups' => $categoryGroups]);
    }

    public function actionTagGroups(): Response
    {
        $this->requireAcceptsJson();

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        return $this->asJson(['success' => true, 'tagGroups' => $tagGroups]);
    }

    public function actionProductTypes(): Response
    {
        $this->requireAcceptsJson();

        $productTypes = [];

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $productTypes = Commerce::getInstance()->getProductTypes()->getAllProductTypes();

            // Reset keys so it's an array
            $productTypes = array_values($productTypes);
        }

        return $this->asJson(['success' => true, 'productTypes' => $productTypes]);
    }

}

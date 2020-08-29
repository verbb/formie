<?php
namespace verbb\formie\controllers;

use Craft;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;

use verbb\formie\Formie;

class ElementsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSections()
    {
        $this->requireAcceptsJson();

        $sections = Craft::$app->getSections()->getAllSections();

        return $this->asJson(['success' => true, 'sections' => $sections]);
    }

    public function actionEntryTypes()
    {
        $this->requireAcceptsJson();

        $entryTypes = [];

        $sectionId = Craft::$app->getRequest()->getParam('sectionId');

        if ($sectionId) {
            $entryTypes = Craft::$app->getSections()->getEntryTypesBySectionId($sectionId);
        }

        return $this->asJson(['success' => true, 'entryTypes' => $entryTypes]);
    }

    public function actionCategoryGroups()
    {
        $this->requireAcceptsJson();

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        return $this->asJson(['success' => true, 'categoryGroups' => $categoryGroups]);
    }

    public function actionTagGroups()
    {
        $this->requireAcceptsJson();

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        return $this->asJson(['success' => true, 'tagGroups' => $tagGroups]);
    }

    public function actionProductTypes()
    {
        $this->requireAcceptsJson();

        $productTypes = [];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce') && Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $productTypes = Commerce::getInstance()->getProductTypes()->getAllProductTypes();

            // Reset keys so its an array
            $productTypes = array_values($productTypes);
        }

        return $this->asJson(['success' => true, 'productTypes' => $productTypes]);
    }

}

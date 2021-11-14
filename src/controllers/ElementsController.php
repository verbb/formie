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

    /**
     * @inheritdoc
     */
    public function actionSections()
    {
        $this->requireAcceptsJson();

        $sections = Craft::$app->getSections()->getAllSections();

        return $this->asJson(['success' => true, 'sections' => $sections]);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function actionCategoryGroups()
    {
        $this->requireAcceptsJson();

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        return $this->asJson(['success' => true, 'categoryGroups' => $categoryGroups]);
    }

    /**
     * @inheritdoc
     */
    public function actionTagGroups()
    {
        $this->requireAcceptsJson();

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        return $this->asJson(['success' => true, 'tagGroups' => $tagGroups]);
    }

    /**
     * @inheritdoc
     */
    public function actionProductTypes()
    {
        $this->requireAcceptsJson();

        $productTypes = [];

        if (Formie::$plugin->getService()->isPluginInstalledAndEnabled('commerce')) {
            $productTypes = Commerce::getInstance()->getProductTypes()->getAllProductTypes();

            // Reset keys so its an array
            $productTypes = array_values($productTypes);
        }

        return $this->asJson(['success' => true, 'productTypes' => $productTypes]);
    }

}

<?php
namespace verbb\formie\controllers;

use verbb\formie\migrations\plugins\MigrateFreeform4;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use verbb\formie\migrations\plugins\MigrateSproutForms;
use verbb\formie\migrations\plugins\Line;

use Craft;
use craft\errors\MissingComponentException;
use craft\errors\ShellCommandException;
use craft\helpers\App;

use yii\base\Exception;

use Throwable;

use Solspace\Freeform\Freeform;
use barrelstrength\sproutforms\elements\Form as SproutFormsForm;

class MigrationsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionSproutForms(): void
    {
        App::maxPowerCaptain();

        // Backup!
        try {
            Craft::$app->getDb()->backup();
        } catch (Throwable $e) {}

        $formIds = $this->request->getParam('formIds');
        $forms = SproutFormsForm::find()->id($formIds)->all();

        $outputs = [];

        if (!$forms) {
            $this->setFailFlash(Craft::t('formie', 'No forms selected.'));

            return;
        }

        foreach ($forms as $form) {
            $migration = new MigrateSproutForms(['formId' => $form->id]);

            try {
                $result = $migration->run();
                $outputs[$form->id] = $result->lines;
            } catch (Throwable $e) {
                $outputs[$form->id] = [Line::error('Failed to migrate: ' . $e->getMessage())];
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        $this->setSuccessFlash(Craft::t('formie', 'Forms migrated.'));
    }

    public function actionFreeform4(): void
    {
        App::maxPowerCaptain();

        // Backup!
        try {
            Craft::$app->getDb()->backup();
        } catch (Throwable $e) {}

        $formIds = $this->request->getParam('formIds');

        // Handle picking "all"
        if ($formIds === '*') {
            $formIds = Freeform::getInstance()->forms->getAllFormIds();
        }

        $forms = array_map([Freeform::getInstance()->forms, 'getFormById'], $formIds);

        $outputs = [];

        if (!$forms) {
            $this->setFailFlash(Craft::t('formie', 'No forms selected.'));

            return;
        }

        foreach ($forms as $form) {
            $migration = new MigrateFreeform4(['formId' => $form->id]);

            try {
                $result = $migration->run();
                $outputs[$form->id] = $result->lines;
            } catch (Throwable $e) {
                $outputs[$form->id] = [Line::error('Failed to migrate: ' . $e->getMessage())];
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        $this->setSuccessFlash(Craft::t('formie', 'Forms migrated.'));
    }

    public function actionFreeform5(): void
    {
        App::maxPowerCaptain();

        // Backup!
        try {
            Craft::$app->getDb()->backup();
        } catch (Throwable $e) {}

        $formIds = $this->request->getParam('formIds');

        // Handle picking "all"
        if ($formIds === '*') {
            $formIds = Freeform::getInstance()->forms->getAllFormIds();
        }

        $forms = array_map([Freeform::getInstance()->forms, 'getFormById'], $formIds);

        $outputs = [];

        if (!$forms) {
            $this->setFailFlash(Craft::t('formie', 'No forms selected.'));

            return;
        }

        foreach ($forms as $form) {
            $migration = new MigrateFreeform5(['formId' => $form->getId()]);

            try {
                $result = $migration->run();
                $outputs[$form->getId()] = $result->lines;
            } catch (Throwable $e) {
                $outputs[$form->getId()] = [Line::error('Failed to migrate: ' . $e->getMessage())];
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        $this->setSuccessFlash(Craft::t('formie', 'Forms migrated.'));
    }
}

<?php
namespace verbb\formie\controllers;

use verbb\formie\migrations\MigrateFreeform4;
use verbb\formie\migrations\MigrateFreeform5;
use verbb\formie\migrations\MigrateSproutForms;

use Craft;
use craft\errors\MissingComponentException;
use craft\errors\ShellCommandException;
use craft\helpers\App;
use craft\web\Controller;

use yii\base\Exception;

use Throwable;

use Solspace\Freeform\Freeform;
use barrelstrength\sproutforms\elements\Form as SproutFormsForm;

class MigrationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSproutForms(): void
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

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
                ob_start();
                $migration->up();
                $output = ob_get_clean();

                $outputs[$form->id] = nl2br($output);
            } catch (Throwable $e) {
                $outputs[$form->id] = 'Failed to migrate: ' . $e->getMessage();
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
        Craft::$app->getDb()->backup();

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
                ob_start();
                $migration->up();
                $output = ob_get_clean();

                $outputs[$form->id] = nl2br($output);
            } catch (Throwable $e) {
                $outputs[$form->id] = 'Failed to migrate: ' . $e->getMessage();
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
        Craft::$app->getDb()->backup();

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
                ob_start();
                $migration->up();
                $output = ob_get_clean();

                $outputs[$form->getId()] = nl2br($output);
            } catch (Throwable $e) {
                $outputs[$form->getId()] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        $this->setSuccessFlash(Craft::t('formie', 'Forms migrated.'));
    }
}

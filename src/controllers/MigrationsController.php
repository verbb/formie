<?php
namespace verbb\formie\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\errors\ShellCommandException;
use craft\helpers\App;
use craft\web\Controller;

use Solspace\Freeform\Freeform;
use verbb\formie\migrations\MigrateFreeform;
use verbb\formie\migrations\MigrateSproutForms;
use barrelstrength\sproutforms\elements\Form as SproutFormsForm;

use yii\base\Exception;

class MigrationsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @throws MissingComponentException
     * @throws ShellCommandException
     * @throws Exception
     */
    public function actionSproutForms()
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $formIds = Craft::$app->getRequest()->getParam('formIds');
        $forms = SproutFormsForm::find()->id($formIds)->all();

        $outputs = [];

        if (!$forms) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'No forms selected.'));

            return null;
        }

        foreach ($forms as $form) {
            $migration = new MigrateSproutForms(['formId' => $form->id]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_contents();
                ob_end_clean();

                $outputs[$form->id] = nl2br($output);
            } catch (\Throwable $e) {
                $outputs[$form->id] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Forms migrated.'));

        return null;
    }

    /**
     * @throws MissingComponentException
     * @throws ShellCommandException
     * @throws Exception
     */
    public function actionFreeform()
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $formIds = Craft::$app->getRequest()->getParam('formIds');
        $forms = array_map([Freeform::getInstance()->forms, 'getFormById'], $formIds);

        $outputs = [];

        if (!$forms) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'No forms selected.'));

            return null;
        }

        foreach ($forms as $form) {
            $migration = new MigrateFreeform(['formId' => $form->id]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_contents();
                ob_end_clean();

                $outputs[$form->id] = nl2br($output);
            } catch (\Throwable $e) {
                $outputs[$form->id] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Forms migrated.'));

        return null;
    }
}

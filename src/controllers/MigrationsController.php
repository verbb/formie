<?php
namespace verbb\formie\controllers;

use verbb\formie\migrations\MigrateFreeform;
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

    /**
     * @throws MissingComponentException
     * @throws ShellCommandException
     * @throws Exception
     */
    public function actionSproutForms(): void
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $formIds = Craft::$app->getRequest()->getParam('formIds');
        $forms = SproutFormsForm::find()->id($formIds)->all();

        $outputs = [];

        if (!$forms) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'No forms selected.'));

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

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Forms migrated.'));
    }

    /**
     * @throws MissingComponentException
     * @throws ShellCommandException
     * @throws Exception
     */
    public function actionFreeform(): void
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $formIds = Craft::$app->getRequest()->getParam('formIds');

        // Handle picking "all"
        if ($formIds === '*') {
            $formIds = Freeform::getInstance()->forms->getAllFormIds();
        }

        $forms = array_map([Freeform::getInstance()->forms, 'getFormById'], $formIds);

        $outputs = [];

        if (!$forms) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'No forms selected.'));

            return;
        }

        foreach ($forms as $form) {
            $migration = new MigrateFreeform(['formId' => $form->id]);

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

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Forms migrated.'));
    }
}

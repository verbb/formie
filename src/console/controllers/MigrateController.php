<?php
namespace verbb\formie\console\controllers;

use barrelstrength\sproutforms\elements\Form;
use verbb\formie\migrations\MigrateSproutForms;

use Throwable;

use yii\console\Controller;
use yii\console\ExitCode;

class MigrateController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Migrates Sprout Forms forms, notifications and submissions.
     *
     * @return int
     * @throws Throwable
     */
    public function actionMigrateSproutForms(): int
    {
        foreach (Form::find()->all() as $form) {
            $migration = new MigrateSproutForms(['formId' => $form->id]);
            $migration->up();
        }

        return ExitCode::OK;
    }
}

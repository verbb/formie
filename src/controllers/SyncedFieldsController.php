<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;

use yii\web\Response;

class SyncedFieldsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $syncedFields = Formie::$plugin->getFields()->getSyncedFieldReport();

        return $this->renderTemplate('formie/settings/synced-fields/index', compact('syncedFields'));
    }
}

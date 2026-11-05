<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Json;

use yii\console\ExitCode;

class UploadReferencesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionStatus(): int
    {
        foreach ((new Query())->select(['formId', 'state', 'cursor', 'highWaterId'])->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->all() as $row) {
            $row['unknown'] = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN)->where(['formId' => $row['formId']])->count();
            $this->stdout(Json::encode($row) . "\n");
        }

        return ExitCode::OK;
    }

    public function actionRebuild(?int $formId = null): int
    {
        $references = Formie::$plugin->getUploadReferences();

        foreach ((new Query())->select('id')->from(Table::FORMIE_FORMS)->andFilterWhere(['id' => $formId])->column() as $id) {
            Craft::$app->getDb()->transaction(fn() => $references->refreshSchemas((int)$id));
            $references->invalidate((int)$id);

            while (!$references->rebuildBatch((int)$id)) {
                $this->stdout('.');
            }

            $this->stdout(" Indexed form #$id.\n");
        }

        return $this->actionVerify($formId);
    }

    public function actionResume(?int $formId = null): int
    {
        $references = Formie::$plugin->getUploadReferences();

        foreach ((new Query())->select('formId')->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['state' => 'pending'])->andFilterWhere(['formId' => $formId])->column() as $id) {
            while (!$references->rebuildBatch((int)$id)) {
                $this->stdout('.');
            }

            $this->stdout(" Indexed form #$id.\n");
        }

        return $this->actionVerify($formId);
    }

    public function actionVerify(?int $formId = null): int
    {
        $failed = false;

        foreach ((new Query())->select('id')->from(Table::FORMIE_FORMS)->andFilterWhere(['id' => $formId])->column() as $id) {
            $report = Formie::$plugin->getUploadReferences()->verify((int)$id);
            $this->stdout(Json::encode(['formId' => (int)$id] + $report) . "\n");
            $failed = $failed || $report['mismatched'] || $report['unknown'];
        }

        return $failed ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}

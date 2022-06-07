<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use craft\db\Query;
use craft\helpers\Db;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class FieldsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionCleanupFieldLayouts()
    {
        $layouts = (new Query())
            ->select(['id'])
            ->from(['{{%fieldlayouts}}'])
            ->where(['type' => Form::class])
            ->column();

        foreach ($layouts as $layoutId) {
            $form = (new Query())
                ->select(['id'])
                ->from(['{{%formie_forms}}'])
                ->where(['fieldLayoutId' => $layoutId])
                ->one();

            if (!$form) {
                Db::delete('{{%fieldlayouts}}', [
                    'id' => $layoutId,
                ]);

                $this->stdout("Deleted field layout #{$layoutId}" . PHP_EOL, Console::FG_GREEN);
            }
        }

        $this->stdout('Finished field layout cleanup.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}

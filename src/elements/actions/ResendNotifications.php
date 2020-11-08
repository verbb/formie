<?php
namespace verbb\formie\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class ResendNotifications extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('formie', 'Bulk Resend');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::className());

        Craft::$app->view->registerJs('new Craft.Formie.BulkResendElementAction(' .
            $type .
        ');');
    }
}

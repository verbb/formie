<?php
namespace verbb\formie\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class ResendNotifications extends ElementAction
{
    // Public Methods
    // =========================================================================

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
    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);

        Craft::$app->getView()->registerJs('new Craft.Formie.BulkResendElementAction(' . $type . ');');

        return null;
    }
}

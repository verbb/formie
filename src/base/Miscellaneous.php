<?php
namespace verbb\formie\base;

use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Miscellaneous extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_MISCELLANEOUS_PAYLOAD = 'modifyMiscellaneousPayload';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Miscellaneous');
    }


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_MISC;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_MISC;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/miscellaneous/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/miscellaneous/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_plugin-settings", $variables);
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        return $schema;
    }

    protected function generatePayloadValues(Submission $submission): array
    {
        $payload = $this->generateSubmissionPayloadValues($submission);

        // Fire a 'modifyMiscellaneousPayload' event
        $event = new ModifyMiscellaneousPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_MISCELLANEOUS_PAYLOAD, $event);

        return $event->payload;
    }
}

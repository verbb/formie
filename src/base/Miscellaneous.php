<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Miscellaneous extends Integration implements IntegrationInterface
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_MISCELLANEOUS_PAYLOAD = 'modifyMiscellaneousPayload';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Miscellaneous');
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/miscellaneous/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml($form): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/miscellaneous/edit/' . $this->id);
    }

    /**
     * Returns the front-end JS variables.
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }


    // Protected Methods
    // =========================================================================

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

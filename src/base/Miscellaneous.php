<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;
use verbb\formie\helpers\StringHelper;

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

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/miscellaneous/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/miscellaneous/{$handle}.svg", true);
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml(Form $form): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
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

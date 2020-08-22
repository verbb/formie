<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\ThirdPartyIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;

abstract class Crm extends ThirdPartyIntegration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/crm/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }
}

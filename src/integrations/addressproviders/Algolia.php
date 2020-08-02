<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAddressProviderHtmlEvent;
use verbb\formie\web\assets\addressproviders\AlgoliaPlacesAsset;

use Craft;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class Algolia extends AddressProvider
{
    // Constants
    // =========================================================================

    const ALGOLIA_INPUT_NAME = 'formie-algolia-autocomplete';
    const EVENT_MODIFY_ADDRESS_PROVIDER_HTML = 'modifyAddressProviderHtml';


    // Properties
    // =========================================================================

    public $handle = 'algolia';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Algolia Places');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/addressproviders/dist/img/algolia.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Use [Algolia Places](https://community.algolia.com/places/) to suggest addresses, for address fields.');
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/algolia/_settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml($field, $options): string
    {
        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();
        $view->registerAssetBundle(AlgoliaPlacesAsset::class);

        $uniqueId = uniqid(self::ALGOLIA_INPUT_NAME, false);

        if (!$this->hasValidSettings()) {
            return '';
        }

        $settings = Json::encode([
            'appId' => $this->settings['appId'],
            'apiKey' => $this->settings['apiKey'],
            'container' => $uniqueId,
            'reconfigurableOptions' => $this->_getOptions(),
            'fieldContainer' => 'data-address-id-' . $field->id,
            'formId' => 'formie-form-' . $options['formId'] ?? '',
        ]);

        $view->registerJs('new FormieAlgoliaPlaces(' . $settings . ');', View::POS_END);

        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        $html = Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/algolia/_input', [
            'field' => $field,
            'data' => $uniqueId,
            'options' => $options,
        ]);

        $view->setTemplatesPath($oldTemplatesPath);

        // Fire a 'modifyAddressProviderHtml' event
        $event = new ModifyAddressProviderHtmlEvent([
            'html' => Template::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_ADDRESS_PROVIDER_HTML, $event);

        return $event->html;
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        $appId = $this->settings['appId'] ?? null;
        $apiKey = $this->settings['apiKey'] ?? null;

        if ($appId && $apiKey) {
            return true;
        }

        return false;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getOptions()
    {
        $options = [];
        $optionsRaw = $this->settings['reconfigurableOptions'] ?? [];

        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

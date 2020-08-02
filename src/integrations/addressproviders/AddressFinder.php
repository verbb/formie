<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAddressProviderHtmlEvent;
use verbb\formie\web\assets\addressproviders\AddressFinderAsset;

use Craft;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class AddressFinder extends AddressProvider
{
    // Constants
    // =========================================================================

    const AF_INPUT_NAME = 'formie-af-autocomplete';
    const EVENT_MODIFY_ADDRESS_PROVIDER_HTML = 'modifyAddressProviderHtml';


    // Properties
    // =========================================================================

    public $handle = 'addressFinder';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Address Finder');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/addressproviders/dist/img/address-finder.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Use [Address Finder](https://addressfinder.com.au/) to suggest Australian and New Zealand addresses, for address fields.');
    }


    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/address-finder/_settings', [
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
        $view->registerAssetBundle(AddressFinderAsset::class);

        $uniqueId = uniqid(self::AF_INPUT_NAME, false);

        if (!$this->hasValidSettings()) {
            return '';
        }

        $settings = Json::encode([
            'apiKey' => $this->settings['apiKey'],
            'countryCode' => $this->settings['countryCode'],
            'container' => $uniqueId,
            'widgetOptions' => $this->_getOptions(),
            'fieldContainer' => 'data-address-id-' . $field->id,
            'formId' => 'formie-form-' . $options['formId'] ?? '',
        ]);

        $view->registerJs('new FormieAddressFinder(' . $settings . ');', View::POS_END);

        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        $html = Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/address-finder/_input', [
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
        $countryCode = $this->settings['countryCode'] ?? null;
        $apiKey = $this->settings['apiKey'] ?? null;

        if ($countryCode && $apiKey) {
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
        $optionsRaw = $this->settings['widgetOptions'] ?? [];

        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}
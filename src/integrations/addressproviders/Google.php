<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAddressProviderHtmlEvent;
use verbb\formie\web\assets\addressproviders\GoogleAddressAsset;

use Craft;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class Google extends AddressProvider
{
    // Constants
    // =========================================================================

    const GOOGLE_INPUT_NAME = 'formie-google-autocomplete';
    const EVENT_MODIFY_ADDRESS_PROVIDER_HTML = 'modifyAddressProviderHtml';


    // Properties
    // =========================================================================

    public $handle = 'googleAddress';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Google Places');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/addressproviders/dist/img/google-address.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Use [Google Places Autocomplete](https://developers.google.com/maps/documentation/javascript/places-autocomplete) to suggest addresses, for address fields.');
    }


    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/google/_settings', [
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
        $view->registerAssetBundle(GoogleAddressAsset::class);

        $uniqueId = uniqid(self::GOOGLE_INPUT_NAME, false);

        if (!$this->hasValidSettings()) {
            return '';
        }

        $settings = Json::encode([
            'apiKey' => $this->settings['apiKey'],
            'container' => $uniqueId,
            'options' => $this->_getOptions(),
            'fieldContainer' => 'data-address-id-' . $field->id,
            'formId' => 'formie-form-' . $options['formId'] ?? '',
        ]);

        $view->registerJs('new FormieGoogleAddress(' . $settings . ');', View::POS_END);

        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        $html = Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/google/_input', [
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
        $apiKey = $this->settings['apiKey'] ?? null;

        if ($apiKey) {
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
        $optionsRaw = $this->settings['options'] ?? [];

        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}
<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAddressProviderHtmlEvent;

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

    public $apiKey;
    public $countryCode;
    public $widgetOptions = [];
    private $uniqueId;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->uniqueId = uniqid(self::AF_INPUT_NAME, false);
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Address Finder');
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
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'countryCode'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml($field, $options): string
    {
        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();

        if (!$this->hasValidSettings()) {
            return '';
        }

        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        $html = Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/address-finder/_input', [
            'field' => $field,
            'data' => $this->uniqueId,
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
    public function getFrontEndJsVariables(Form $form, $field = null)
    {
        if (!$this->hasValidSettings()) {
            return null;
        }
        
        $settings = [
            'apiKey' => Craft::parseEnv($this->apiKey),
            'countryCode' => $this->countryCode,
            'container' => $this->uniqueId,
            'widgetOptions' => $this->_getOptions(),
            'fieldContainer' => 'data-address-id-' . $field->id,
            'formId' => 'formie-form-' . $form->id,
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/addressproviders/dist/js/address-finder.js', true);
        $onload = 'new FormieAddressFinder(' . Json::encode($settings) . ');';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        if ($this->countryCode && $this->apiKey) {
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
        $optionsRaw = $this->widgetOptions;

        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}
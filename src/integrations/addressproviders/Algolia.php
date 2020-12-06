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

class Algolia extends AddressProvider
{
    // Constants
    // =========================================================================

    const ALGOLIA_INPUT_NAME = 'formie-algolia-autocomplete';
    const EVENT_MODIFY_ADDRESS_PROVIDER_HTML = 'modifyAddressProviderHtml';


    // Properties
    // =========================================================================

    public $apiKey;
    public $appId;
    public $reconfigurableOptions = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Algolia Places');
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
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'appId'], 'required'];

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

        $html = Craft::$app->getView()->renderTemplate('formie/integrations/address-providers/algolia-places/_input', [
            'field' => $field,
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
    public function getFrontEndJsVariables($field = null)
    {
        if (!$this->hasValidSettings()) {
            return null;
        }
        
        // These are reversed on purpose!
        $settings = [
            'appId' => Craft::parseEnv($this->apiKey),
            'apiKey' => Craft::parseEnv($this->appId),
            'reconfigurableOptions' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/addressproviders/dist/js/algolia-places.js', true),
            'module' => 'FormieAlgoliaPlaces',
            'settings' => $settings,
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        if ($this->appId && $this->apiKey) {
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
        $optionsRaw = $this->reconfigurableOptions;

        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

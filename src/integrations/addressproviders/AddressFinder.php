<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;

class AddressFinder extends AddressProvider
{
    // Constants
    // =========================================================================

    public const AF_INPUT_NAME = 'formie-af-autocomplete';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Address Finder');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $countryCode = null;
    public array $widgetOptions = [];


    // Public Methods
    // =========================================================================

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
    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $settings = [
            'apiKey' => App::parseEnv($this->apiKey),
            'countryCode' => $this->countryCode,
            'widgetOptions' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/address-providers/address-finder.js', true),
            'module' => 'FormieAddressFinder',
            'settings' => $settings,
        ];
    }

    public function hasValidSettings(): bool
    {
        return $this->countryCode && $this->apiKey;
    }


    // Public Methods
    // =========================================================================

    private function _getOptions(): array
    {
        $options = [];
        $optionsRaw = $this->widgetOptions;

        foreach ($optionsRaw as $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}
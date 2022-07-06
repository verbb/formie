<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;

class Google extends AddressProvider
{
    // Constants
    // =========================================================================

    public const GOOGLE_INPUT_NAME = 'formie-google-autocomplete';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Google Places');
    }

    public static function supportsCurrentLocation(): bool
    {
        return true;
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $geocodingApiKey = null;
    public array $options = [];


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use [Google Places Autocomplete](https://developers.google.com/maps/documentation/javascript/places-autocomplete) to suggest addresses, for address fields.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

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
            'geocodingApiKey' => App::parseEnv($this->geocodingApiKey),
            'options' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/address-providers/google-address.js', true),
            'module' => 'FormieGoogleAddress',
            'settings' => $settings,
        ];
    }

    public function hasValidSettings(): bool
    {
        if ($this->apiKey) {
            return true;
        }

        return false;
    }


    // Public Methods
    // =========================================================================

    private function _getOptions(): array
    {
        $options = [];
        $optionsRaw = $this->options;

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

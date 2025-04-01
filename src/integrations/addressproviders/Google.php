<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\base\FieldInterface;

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

    public function getClassHandle(): string
    {
        return 'google-places';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use {link} to suggest addresses, for address fields.', ['link' => '[Google Places Autocomplete](https://developers.google.com/maps/documentation/javascript/places-autocomplete)']);
    }

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
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
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/address-providers/google-address.js'),
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }


    // Private Methods
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

<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\AddressProvider;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

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
        return 'Google Places';
    }

    public static function supportsCurrentLocation(): bool
    {
        return true;
    }
    

    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    #[Sensitive]
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

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new BrowserModule([
            'moduleId' => 'formie:google-address',
            'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED, BrowserModule::SURFACE_CP_EDIT],
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'options' => $this->_getOptions(),
                'geocodeEndpoint' => \craft\helpers\UrlHelper::actionUrl('formie/address/google-places-geocode'),
            ],
        ]);
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

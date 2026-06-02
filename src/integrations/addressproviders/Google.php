<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

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

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new ClientModule([
            'id' => 'google-address',
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'options' => $this->_getOptions(),
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

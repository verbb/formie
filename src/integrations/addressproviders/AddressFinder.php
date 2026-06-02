<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

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
        return Craft::t('formie', 'Use {link} to suggest Australian and New Zealand addresses, for address fields.', ['link' => '[Address Finder](https://addressfinder.com.au/)']);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new ClientModule([
            'id' => 'address-finder',
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'countryCode' => $this->countryCode,
                'widgetOptions' => $this->_getOptions(),
            ],
        ]);
    }

    public function hasValidSettings(): bool
    {
        return $this->countryCode && $this->apiKey;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'countryCode'], 'required'];

        return $rules;
    }


    // Private Methods
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
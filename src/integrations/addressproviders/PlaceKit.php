<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;

class PlaceKit extends AddressProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'PlaceKit');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public array $options = [];


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use {link} to suggest addresses for Address fields using a fast, privacy-friendly autocomplete service.', ['link' => '[PlaceKit](https://placekit.io)']);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new ClientModule([
            'id' => 'place-kit',
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'options' => $this->_getOptions(),
            ],
        ]);
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey;
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
<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;

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

    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $settings = [
            'apiKey' => App::parseEnv($this->apiKey),
            'options' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/address-providers/place-kit.js'),
            'module' => 'FormiePlaceKit',
            'settings' => $settings,
        ];
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
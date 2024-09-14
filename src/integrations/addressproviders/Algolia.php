<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\base\AddressProvider;
use verbb\formie\base\FieldInterface;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;

class Algolia extends AddressProvider
{
    // Constants
    // =========================================================================

    public const ALGOLIA_INPUT_NAME = 'formie-algolia-autocomplete';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Algolia Places');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $appId = null;
    public array $reconfigurableOptions = [];


    // Public Methods
    // =========================================================================

    public function getClassHandle(): string
    {
        return 'algolia-places';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use {link} to suggest addresses, for address fields.', ['link' => '[Algolia Places](https://community.algolia.com/places/)']);
    }

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        // These are reversed on purpose!
        $settings = [
            'appId' => App::parseEnv($this->apiKey),
            'apiKey' => App::parseEnv($this->appId),
            'reconfigurableOptions' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/address-providers/algolia-places.js'),
            'module' => 'FormieAlgoliaPlaces',
            'settings' => $settings,
        ];
    }

    public function hasValidSettings(): bool
    {
        return $this->appId && $this->apiKey;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'appId'], 'required'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getOptions(): array
    {
        $options = [];
        $optionsRaw = $this->reconfigurableOptions;

        foreach ($optionsRaw as $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\Formie;
use verbb\formie\base\AddressProvider;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;

class Loqate extends AddressProvider
{
    // Constants
    // =========================================================================

    public const LOQATE_INPUT_NAME = 'formie-loqate-autocomplete';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Loqate');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public array $reconfigurableOptions = [];


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use [Loqate](https://www.loqate.com/) to suggest addresses, for address fields.');
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
            'namespace' => $field ? Formie::$plugin->getService()->getFieldNamespaceForScript($field) : '',
            'reconfigurableOptions' => $this->_getOptions(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/address-providers/loqate.js', true),
            'module' => 'FormieLoqate',
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
        $optionsRaw = $this->reconfigurableOptions;

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

<?php
namespace verbb\formie\integrations\addressproviders;

use verbb\formie\Formie;
use verbb\formie\base\AddressProvider;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

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

    public static function displayName(): string
    {
        return 'Loqate';
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public array $reconfigurableOptions = [];


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Use {link} to suggest addresses, for address fields.', ['link' => '[Loqate](https://www.loqate.com/)']);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new ClientModule([
            'id' => 'loqate',
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'namespace' => $context->field ? Formie::$plugin->getService()->getFieldNamespaceForScript($context->field) : '',
                'reconfigurableOptions' => $this->_getOptions(),
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
        $optionsRaw = $this->reconfigurableOptions;

        foreach ($optionsRaw as $key => $value) {
            $options[$value[0]] = Json::decode($value[1]);
        }

        return $options;
    }
}

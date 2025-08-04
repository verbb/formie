<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class AddressAutoComplete extends SingleLineText implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - Auto-Complete');
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getEmailTemplatePath(): string
    {
        return 'fields/single-line-text';
    }


    // Properties
    // =========================================================================

    public ?string $integrationHandle = null;
    public bool $currentLocation = false;


    // Public Methods
    // =========================================================================

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['integrationHandle'] = Craft::t('formie', 'Auto-Complete Integration');

        return $labels;
    }

    public function defineGeneralSchema(): array
    {
        $fields = parent::defineGeneralSchema();

        $addressProviderOptions = $this->_getAddressProviderOptions();

        array_unshift($fields, SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Auto-Complete Integration'),
            'help' => Craft::t('formie', 'Select which address provider this field should use.'),
            'name' => 'integrationHandle',
            'validation' => 'required',
            'required' => true,
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                $addressProviderOptions
            ),
        ]));

        $fields[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Show Current Location Button'),
            'help' => Craft::t('formie', 'Whether this field should show a "Use my location" button.'),
            'name' => 'currentLocation',
            'if' => '$get(integrationHandle).value == googlePlaces',
        ]);

        return $fields;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Add back when we can figure out how to enforce it with enabled state better
        // $rules[] = [['integrationHandle'], 'required'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getAddressProviderOptions(): array
    {
        $addressProviderOptions = [];
        $addressProviders = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ADDRESS_PROVIDER);

        foreach ($addressProviders as $addressProvider) {
            if ($addressProvider->getEnabled()) {
                $addressProviderOptions[] = [
                    'label' => $addressProvider->getName(),
                    'value' => $addressProvider->getHandle(),
                    'data-type' => get_class($addressProvider),
                ];
            }
        }

        return $addressProviderOptions;
    }
}

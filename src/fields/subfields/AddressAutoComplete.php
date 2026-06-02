<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\web\twig\Extension;

use Craft;
use verbb\formie\elements\Form;

class AddressAutoComplete extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - Auto-Complete');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/single-line-text';
    }


    // Properties
    // =========================================================================

    public ?string $integrationHandle = null;
    public bool $currentLocation = false;


    // Public Methods
    // =========================================================================

    public function defineFormBuilderGeneralSchema(): array
    {
        $fields = parent::defineFormBuilderGeneralSchema();

        $addressProviderOptions = $this->_getAddressProviderOptions();

        array_unshift($fields, SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Auto-Complete Integration'),
            'instructions' => Craft::t('formie', 'Select which address provider this field should use.'),
            'name' => 'integrationHandle',
            'required' => true,
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                $addressProviderOptions
            ),
        ]));

        $fields[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Show Current Location Button'),
            'instructions' => Craft::t('formie', 'Whether this field should show a "Use my location" button.'),
            'name' => 'currentLocation',
            'if' => 'integrationHandle == "googlePlaces"',
        ]);

        return $fields;
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $config = parent::getInputTemplateVariables($form, $value);

        $parent = $this->getParentField();

        if ($parent instanceof Address && $parent->hasCurrentLocation() && $this->handle === 'autoComplete') {
            $slotContext = RenderContext::from([
                'form' => $form,
                'field' => $this,
            ], []);
            $htmlTag = $this->renderSlotTag('locationLink', $slotContext);

            if ($htmlTag) {
                $twigContext = [
                    'form' => $form,
                    'field' => $this,
                    'value' => $value,
                ];
                $config['fieldLabelSuffix'] = Extension::formatSlotTagHtml('locationLink', $htmlTag, $twigContext);
            }
        }

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'locationLink') {
            $parent = $this->getParentField();

            if ($parent instanceof Address && $parent->hasCurrentLocation() && $this->handle === 'autoComplete') {
                return $parent->createLocationLinkSlotTag($context);
            }

            return null;
        }

        $tag = parent::defineFieldSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'autocomplete',
                'data-autocomplete' => true,
                'type' => 'search',
                'aria-autocomplete' => 'list',
                'data-formie-address-autocomplete-input' => true,
            ]);
        }

        return $tag;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'inputType' => 'search',
        ]);
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

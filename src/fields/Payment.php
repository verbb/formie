<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\fields\values\PaymentFieldValue;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\options\Currencies;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use Twig\Markup;

class Payment extends Field
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Payment');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/payment/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?string $paymentIntegration = null;
    public ?string $paymentIntegrationType = null;
    public ?array $providerSettings = [];
    private static ?array $_paymentIntegrationsCache = null;
    private static ?array $_paymentProviderOptionsCache = null;


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_PAYMENT;
    }

    public function init(): void
    {
        parent::init();
    }

    public function modifyFieldSettings(array $settings): array
    {
        if ($integration = $this->getPaymentIntegration()) {
            return $integration->modifyFieldSettings($settings);
        }

        return $settings;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if ($value instanceof PaymentFieldValue) {
            return $value;
        }

        if (!is_array($value)) {
            $value = [];
        }

        $data = new PaymentFieldValue($value);
        $data->setElement($element);

        return $data;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $element);

        if (!$value instanceof PaymentFieldValue) {
            return [];
        }

        // Keep persisted payload canonical as primitive array data.
        return $value->getAttributes();
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewPayment(),
        ];
    }

    public function getPaymentHtml(): Markup
    {
        $integration = $this->getPaymentIntegration();

        if (!$integration) {
            return Template::raw('');
        }

        return Template::raw($integration->renderFieldHtml($this));
    }

    public function getPaymentSubFields(): array
    {
        $integration = $this->getPaymentIntegration();

        return $integration?->getPaymentSubFields($this) ?? [];
    }

    public function getPaymentIntegration(): ?IntegrationInterface
    {
        if (!$this->paymentIntegration) {
            return null;
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($this->paymentIntegration);

        // Keep payment integrations field-aware in all contexts (CP edit, summary rendering, workflow).
        if ($integration instanceof PaymentIntegration) {
            $integration->setField($this);
        }

        return $integration;
    }

    public function beforeSave(bool $isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        if ($this->paymentIntegration) {
            $this->_ensureProviderSettingsDefaultsForHandle($this->paymentIntegration);
        }

        if ($integration = $this->getPaymentIntegration()) {
            $this->paymentIntegrationType = get_class($integration);
        }
        
        return true;
    }

    public function getProviderSettingsSchemaForHandle(string $handle, string $schemaGroup): array
    {
        $integration = $this->_getPaymentIntegrationByHandle($handle);

        if (!$integration || !method_exists($integration, $schemaGroup)) {
            return [];
        }

        return $integration->$schemaGroup();
    }

    public function getProviderSettingsDefaultsForHandle(string $handle): array
    {
        $integration = $this->_getPaymentIntegrationByHandle($handle);

        if (!($integration instanceof PaymentIntegration)) {
            return [];
        }

        return $integration->getPaymentFieldSettingsDefaults();
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'paymentIntegration' => [
                'name' => 'paymentIntegration',
                'type' => Type::string(),
            ],
            'paymentIntegrationType' => [
                'name' => 'paymentIntegrationType',
                'type' => Type::string(),
            ],
            'providerSettings' => [
                'name' => 'providerSettings',
                'type' => Type::string(),
                'resolve' => function($source, $arguments) {
                    return Json::encode($source->providerSettings);
                },
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Payment Provider'),
                'instructions' => Craft::t('formie', 'Select which payment provider this field should use.'),
                'name' => 'paymentIntegration',
                'validation' => 'required',
                'required' => true,
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                    $this->_getPaymentOptions()
                ),
            ]),
            SchemaHelper::paymentProviderSettingsField([
                'name' => 'providerSettings',
                'schemaGroup' => 'defineFormBuilderGeneralSchema',
                'fieldType' => static::class,
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'instructions' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'instructions' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => 'required',
            ]),
            SchemaHelper::paymentProviderSettingsField([
                'name' => 'providerSettings',
                'schemaGroup' => 'defineFormBuilderSettingsSchema',
                'fieldType' => static::class,
            ]),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::paymentProviderSettingsField([
                'name' => 'providerSettings',
                'schemaGroup' => 'defineFormBuilderAppearanceSchema',
                'fieldType' => static::class,
            ]),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($integration = $this->getPaymentIntegration()) {
            return $integration->renderSlotTag($key, $context) ?? parent::defineFieldSlotTag($key, $context);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/payment/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof PaymentFieldValue) {
            return Json::encode($value->getAttributes());
        }

        if (is_array($value) || is_object($value)) {
            return Json::encode($value);
        }

        return (string)$value;
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        if ($value instanceof PaymentFieldValue) {
            return $value->getAttributes();
        }

        return parent::defineValueAsArray($value, $element);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        // Payment fields can't really be previewed without real payment data
        return [];
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'integration' => $this->paymentIntegration,
            'providerSettings' => $this->providerSettings,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = function(ClientModuleContext $context) {
            $integration = $this->getPaymentIntegration();

            if (!$integration) {
                return null;
            }

            $clientModule = $integration->getClientModule(new ClientModuleContext([
                'form' => $context->form,
                'field' => $this,
                'integration' => $integration,
                'renderTarget' => $context->renderTarget,
            ]));

            if (!$clientModule?->id) {
                return null;
            }

            if (!$clientModule->type) {
                $clientModule->type = $integration->getType();
            }

            if (!$clientModule->targets) {
                $clientModule->targets = $context->getTargets();
            }

            if (!$clientModule->renderTargets) {
                $clientModule->renderTargets = [ClientModule::RENDER_TARGET_FRONTEND];
            }

            return $clientModule;
        };

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return PaymentFieldValue::class;
    }


    // Private Methods
    // =========================================================================

    private function _getPaymentOptions(): array
    {
        if (self::$_paymentProviderOptionsCache !== null) {
            return self::$_paymentProviderOptionsCache;
        }

        $paymentProviderOptions = [];

        foreach ($this->_getPaymentIntegrations() as $paymentProvider) {
            if (!$paymentProvider->getEnabled()) {
                continue;
            }

            $paymentProviderOptions[] = [
                'label' => $paymentProvider->getName(),
                'value' => $paymentProvider->getHandle(),
            ];
        }

        self::$_paymentProviderOptionsCache = $paymentProviderOptions;

        return self::$_paymentProviderOptionsCache;
    }

    private function _getPaymentIntegrations(): array
    {
        if (self::$_paymentIntegrationsCache !== null) {
            return self::$_paymentIntegrationsCache;
        }

        self::$_paymentIntegrationsCache = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_PAYMENT);

        return self::$_paymentIntegrationsCache;
    }

    private function _getPaymentIntegrationByHandle(string $handle): ?IntegrationInterface
    {
        if ($handle === '') {
            return null;
        }

        foreach ($this->_getPaymentIntegrations() as $integration) {
            if ($integration->getHandle() === $handle) {
                return $integration;
            }
        }

        return null;
    }

    private function _ensureProviderSettingsDefaultsForHandle(string $handle): void
    {
        if ($handle === '') {
            return;
        }

        $defaults = $this->getProviderSettingsDefaultsForHandle($handle);

        if (!$defaults) {
            return;
        }

        $providerSettings = is_array($this->providerSettings) ? $this->providerSettings : [];
        $existing = is_array($providerSettings[$handle] ?? null) ? $providerSettings[$handle] : [];
        $providerSettings[$handle] = array_merge($defaults, $existing);
        $this->providerSettings = $providerSettings;
    }

}

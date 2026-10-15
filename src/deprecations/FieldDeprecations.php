<?php
namespace verbb\formie\deprecations;

use verbb\formie\Formie;
use verbb\formie\compatibility\fields\FieldCompatibility;
use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

trait FieldDeprecations
{
    // Static Methods
    // =========================================================================

    /** @deprecated Use getInputTemplatePath(). */
    public static function getFrontEndInputTemplatePath(): string
    {
        Craft::$app->getDeprecator()->log(static::class . '::getFrontEndInputTemplatePath', 'Use getInputTemplatePath().');
        return FieldCompatibility::withLegacyCall(static::class, 'getFrontEndInputTemplatePath', fn() => static::getInputTemplatePath());
    }

    /** @deprecated Declare the normalized runtime type with defineValueType(). */
    public static function phpType(): string
    {
        return 'mixed';
    }

    public static function getEmailTemplatePath(): string
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(static::class . '::getEmailTemplatePath', 'Field `getEmailTemplatePath()` has been deprecated. Use `getReferenceBlockTemplatePath()` instead.');

        return FieldCompatibility::withLegacyCall(static::class, 'getEmailTemplatePath', fn() => static::getReferenceBlockTemplatePath());
    }

    protected static function normalizeConfig(array &$config = []): void
    {
        FieldConfigNormalizer::normalize($config, static::class);
    }


    // Public Methods
    // =========================================================================

    /** @deprecated Declare browser modules with defineBrowserModules(). */
    public function getFrontEndJsModules(): ?array
    {
        return null;
    }

    /** @deprecated Legacy module JSON is only for Formie 3 templates. Use registered browser modules. */
    public function getConfigJson(): ?string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Use registered browser modules and Formie rendering instead of legacy module JSON.');
        $modules = $this->getFrontEndJsModules();
        $config = [];

        foreach ($modules ? (array_is_list($modules) ? $modules : [$modules]) : [] as $module) {
            $settings = array_filter([...($module['settings'] ?? []), 'module' => $module['module'] ?? '']);

            if ($settings) {
                $config[] = $settings;
            }
        }

        return $config ? Json::encode($config) : null;
    }

    /** @deprecated Use getBrowserConditionsJson(). */
    public function getConditionsJson(): ?string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Use getBrowserConditionsJson().');
        return FieldCompatibility::withLegacyCall($this, 'getConditionsJson', fn() => $this->getBrowserConditionsJson());
    }

    public function getValueAsJson(mixed $value, ?ElementInterface $element = null): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getValueAsJson()` has been deprecated. Use `getValueAsData()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'getValueAsJson', fn() => $this->getValueAsData($value, $element));
    }

    public function getFieldKey(): string
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getFieldKey()` has been deprecated. Use `valueKey()` instead.');

        return $this->valueKey();
    }

    public function getErrorKey(): string
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getErrorKey()` has been deprecated. Use `errorKey()` instead.');

        return $this->errorKey();
    }

    public function getFullHandle()
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getFullHandle()` has been deprecated. Use `handlePath()` instead.');

        return $this->handlePath();
    }

    public function getFullNamespace()
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getFullNamespace()` has been deprecated. Use `namespacePath()` instead.');

        return $this->namespacePath();
    }

    public function getReservedHandles(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getReservedHandles()` has been deprecated. Use `Fields::getReservedHandles()` instead.');

        return Formie::$plugin->getFields()->getReservedHandles();
    }

    public function hasEmailLabel(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `hasEmailLabel()` has been deprecated. Use `hasReferenceBlockLabel()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'hasEmailLabel', fn() => $this->hasReferenceBlockLabel());
    }

    public function hasEmailPlaceholder(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `hasEmailPlaceholder()` has been deprecated. Use `hasReferenceBlockPlaceholder()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'hasEmailPlaceholder', fn() => $this->hasReferenceBlockPlaceholder());
    }

    public function getValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getValueForVariable()` has been deprecated. Use `getValueForReference()` instead.');

        return FieldCompatibility::withReferenceNotification($this, $notification, fn() => FieldCompatibility::withLegacyCall($this, 'getValueForVariable', fn() => $this->getValueForReference($value, $submission)));
    }

    public function getValueForVariableRaw(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getValueForVariableRaw()` has been deprecated. Use `getValueForReference()` instead.');

        return FieldCompatibility::withReferenceNotification($this, $notification, fn() => FieldCompatibility::withLegacyCall($this, 'getValueForVariableRaw', fn() => $this->getValueForReference($value, $submission)));
    }

    public function getValueForEmail(mixed $value, Notification $notification, ?ElementInterface $element = null): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getValueForEmail()` has been deprecated. Use `getValueForReferenceBlock()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'getValueForEmail', fn() => $this->getValueForReferenceBlock($value, $notification, $element));
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getEmailHtml()` has been deprecated. Use `getReferenceBlockHtml()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'getEmailHtml', fn() => $this->getReferenceBlockHtml($submission, $notification, $value, $renderOptions));
    }

    public function getEmailOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `getEmailOptions()` has been deprecated. Use `getReferenceBlockOptions()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'getEmailOptions', fn() => $this->getReferenceBlockOptions($submission, $notification, $value, $renderOptions));
    }

    public function defineGeneralSchema(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineGeneralSchema()` has been deprecated. Use `defineFormBuilderGeneralSchema()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineGeneralSchema', fn() => $this->defineFormBuilderGeneralSchema());
    }

    public function defineSettingsSchema(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineSettingsSchema()` has been deprecated. Use `defineFormBuilderSettingsSchema()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineSettingsSchema', fn() => $this->defineFormBuilderSettingsSchema());
    }

    public function defineAppearanceSchema(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineAppearanceSchema()` has been deprecated. Use `defineFormBuilderAppearanceSchema()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineAppearanceSchema', fn() => $this->defineFormBuilderAppearanceSchema());
    }

    public function defineAdvancedSchema(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineAdvancedSchema()` has been deprecated. Use `defineFormBuilderAdvancedSchema()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineAdvancedSchema', fn() => $this->defineFormBuilderAdvancedSchema());
    }

    public function defineConditionsSchema(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineConditionsSchema()` has been deprecated. Use `defineFormBuilderConditionsSchema()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineConditionsSchema', fn() => $this->defineFormBuilderConditionsSchema());
    }


    // Protected Methods
    // =========================================================================

    /** @deprecated Declare defineValueType() instead. */
    protected function defineValueClass(): ?string
    {
        return null;
    }

    protected function triggerDeprecatedHtmlTagEvent(ModifyFieldSlotTagEvent $event): void
    {
        if (!$this->hasEventHandlers(static::EVENT_MODIFY_HTML_TAG)) {
            return;
        }

        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(static::class . '::EVENT_MODIFY_HTML_TAG', 'Field `EVENT_MODIFY_HTML_TAG` has been deprecated. Use `EVENT_MODIFY_SLOT_TAG` instead.');

        $legacyEvent = new ModifyFieldHtmlTagEvent([
            'field' => $event->field,
            'tag' => $event->tag,
            'key' => $event->key,
            'context' => $event->context,
        ]);

        $this->trigger(static::EVENT_MODIFY_HTML_TAG, $legacyEvent);
        $event->tag = $legacyEvent->tag;
        $event->context = $legacyEvent->context;
    }

    protected function triggerDeprecatedEmailValueEvent(ModifyFieldEmailValueEvent $event): void
    {
        if (!$this->hasEventHandlers(static::EVENT_MODIFY_VALUE_FOR_EMAIL)) {
            return;
        }

        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(static::class . '::EVENT_MODIFY_VALUE_FOR_EMAIL', 'Field `EVENT_MODIFY_VALUE_FOR_EMAIL` has been deprecated. Use `EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK` instead.');

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_EMAIL, $event);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Implement defineValueAsData() instead.');
        return FieldCompatibility::withLegacyCall($this, 'defineValueAsJson', fn() => $this->defineValueAsData($value, $element));
    }

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineValueForVariable()` has been deprecated. Use `defineValueForReference()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineValueForVariable', fn() => $this->defineValueForReference($value, $submission));
    }

    protected function defineValueForVariableRaw(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineValueForVariableRaw()` has been deprecated. Use `defineValueForReference()` instead.');

        return FieldCompatibility::withLegacyCall($this, 'defineValueForVariableRaw', fn() => $this->defineValueForReference($value, $submission));
    }

    protected function defineValueForEmail(mixed $value, Notification $notification, ElementInterface $element = null): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Field `defineValueForEmail()` has been deprecated. Use `defineValueForReferenceBlock()` instead.');

        // Let reference-block templates (or the field) define what value should
        // be used for legacy email-named rendering hooks.
        return FieldCompatibility::withLegacyCall($this, 'defineValueForEmail', fn() => $this->defineValueForReferenceBlock($value, $notification, $element));
    }
}

<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldClientRenderedDefinition;
use verbb\formie\fields\definitions\FieldConditions;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldReferences;
use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use Craft;

trait FieldDefinitionTrait
{
    // Public Methods
    // =========================================================================

    /** @deprecated Declare browserModules() using registered module IDs. */
    public function getFrontEndJsModules(): ?array
    {
        return null;
    }

    // Field kind is a lightweight client/config hint, not the normalized PHP value type.
    public function fieldKind(): string
    {
        return self::KIND_CUSTOM;
    }

    // The declared type describes normalization; each projection is owned by the field.
    public function valueType(): FieldValueType
    {
        return $this->getIsCosmetic() ? FieldValueType::none() : $this->legacyValueType();
    }

    // Client children describe how managed clients should model nested parts or rows.
    public function clientRenderedChildren(): FieldClientRenderedChildren
    {
        return $this->defineClientRenderedChildren();
    }

    // Base type/input metadata for REST, GraphQL, and other client-rendered consumers.
    public function clientRenderedDefinition(): FieldClientRenderedDefinition
    {
        return FieldClientRenderedDefinition::make(type: $this->defineClientRenderedType())
            ->withInputDefinition($this->defineClientRenderedInput());
    }

    // Lazy browser modules the field needs when Formie manages client behavior.
    public function browserModules(BrowserModuleContext $context): array
    {
        return array_map(static function(array|BrowserModule $module): BrowserModule {
            return $module instanceof BrowserModule ? $module : new BrowserModule($module);
        }, array_merge(
            $this->defineBrowserModules(),
            $this->defineContextualBrowserModules($context),
            \verbb\formie\compatibility\fields\LegacyBrowserModules::fromField($this),
        ));
    }

    // Reference selectors feed token UIs and server-side variable resolution.
    public function references(): FieldReferences
    {
        return $this->referenceDefinition();
    }

    // Variable sources are usually derived from reference values rather than authored separately.
    public function variableSources(): array
    {
        return $this->variableSourceDefinitions();
    }

    // Conditions are normalized once here so browser payloads and rendered fields stay aligned.
    public function conditions(): FieldConditions
    {
        return $this->conditionDefinition();
    }

    public function getConditions(): array
    {
        return $this->conditions ?? [];
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineClientRenderedChildren(): FieldClientRenderedChildren
    {
        return FieldClientRenderedChildren::make();
    }

    protected function defineClientRenderedType(): string
    {
        return static::kebabClassName();
    }

    protected function defineBrowserModuleConfig(): array
    {
        return [];
    }

    // Build the reference/selector contract once, then let UIs and token resolution consume it.
    protected function referenceDefinition(): FieldReferences
    {
        $values = $this->referenceValueDefinitions();
        $defaultValue = $this->_findDefaultReferenceValue($values);
        $selectors = [];

        foreach ($values as $value) {
            $selector = $value->toReferenceSelectorDefinition();

            if ($selector !== null) {
                $selectors[] = $selector;
            }
        }

        return new FieldReferences([
            'allowPrimary' => $this->defineAllowPrimaryReference(),
            'primaryCondition' => $defaultValue?->condition,
            'primaryTokenSuffix' => $defaultValue?->handle ?: null,
            'allowNested' => $this->defineAllowNestedReference(),
            'nestedMode' => $this->defineNestedReferenceMode(),
            'selectors' => $selectors,
        ]);
    }

    // Variable pickers and reference helpers both originate from the same value definitions.
    protected function variableSourceDefinitions(): array
    {
        $sources = [];
        $allowPrimary = $this->defineAllowPrimaryReference();

        foreach ($this->referenceValueDefinitions() as $value) {
            if ($allowPrimary && $value->default) {
                $source = $value->toDefaultVariableSourceDefinition();

                if ($source !== null) {
                    $sources[] = $source;
                }
            }

            $source = $value->toSelectorVariableSourceDefinition();

            if ($source !== null) {
                $sources[] = $source;
            }
        }

        $deduped = [];

        foreach ($sources as $source) {
            $key = $source->selector === '' ? '__primary' : $source->selector;
            $deduped[$key] = $source;
        }

        return array_values($deduped);
    }

    // Normalize and dedupe author-defined values so downstream consumers see one stable shape.
    protected function referenceValueDefinitions(): array
    {
        $values = [];
        $defaultValueKey = null;

        foreach ($this->defineReferenceValues() as $value) {
            $value = $value instanceof FieldReferenceValue ? $value : FieldReferenceValue::fromArray($value);
            $key = $value->handle === '' ? '__default' : $value->handle;
            $values[$key] = $value;

            if ($value->default) {
                $defaultValueKey = $key;
            }
        }

        if ($defaultValueKey !== null) {
            foreach ($values as $key => $value) {
                $values[$key] = FieldReferenceValue::fromArray([...get_object_vars($value), 'default' => $key === $defaultValueKey]);
            }
        }

        return array_values($values);
    }

    protected function _findDefaultReferenceValue(array $values): ?FieldReferenceValue
    {
        foreach ($values as $value) {
            if ($value->default) {
                return $value;
            }
        }

        return null;
    }

    // Conditions remain field-authored config until we have a form context to normalize against.
    protected function conditionDefinition(): FieldConditions
    {
        if (!$this->enableConditions) {
            return FieldConditions::make();
        }

        $conditions = $this->getConditions();

        if (!$conditions) {
            return FieldConditions::make();
        }

        if ($form = $this->getForm()) {
            $conditions = ConditionsHelper::normalizeClientConditions($conditions, $form);
        }

        $conditions['clearOnHide'] = true;
        $conditions['isNested'] = (bool)$this->getParentField();

        return FieldConditions::make($conditions);
    }

    protected function defineClientRenderedInput(): array
    {
        $input = [];

        if (property_exists($this, 'placeholder')) {
            $input['placeholder'] = $this->placeholder ?: null;
        }

        return $input;
    }

    protected function defineBrowserModules(): array
    {
        return [];
    }

    protected function defineContextualBrowserModules(BrowserModuleContext $context): array
    {
        return [];
    }

    public function collectBrowserModules(): array
    {
        return $this->defineBrowserModules();
    }

    protected function defineAllowPrimaryReference(): bool
    {
        return true;
    }

    protected function defineAllowNestedReference(): bool
    {
        return false;
    }

    protected function defineNestedReferenceMode(): string
    {
        return 'none';
    }

    // Declare the values a field exposes to references and variable pickers.
    // Use FieldReferenceValue::default() for the top-level field value and
    // FieldReferenceValue::property() for named sub-values like "__toString" or "firstName".
    // variableTypes describes how the value appears to variable-picker consumers.
    // Add an "if" expression when a value only exists for certain field settings.
    protected function defineReferenceValues(): array
    {
        return [];
    }

    protected function defineValueClass(): ?string
    {
        return null;
    }

}

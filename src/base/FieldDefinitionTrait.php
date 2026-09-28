<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\fields\definitions\FieldClientRenderedDefinition;
use verbb\formie\fields\definitions\FieldConditions;
use verbb\formie\fields\definitions\FieldReferenceValue;
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

    /**
     * One declaration drives runtime resolution, field selection, and variable pickers.
     *
     * @return FieldReferenceValue[]
     */
    public function referenceValues(): array
    {
        $values = [];
        $hasPrimary = false;

        foreach ($this->defineReferenceValues() as $value) {
            if (!$value instanceof FieldReferenceValue) {
                throw new \UnexpectedValueException(sprintf('%s::defineReferenceValues() must return FieldReferenceValue objects.', static::class));
            }

            $key = $value->isPrimary() ? '__primary' : $value->selector;
            $values[$key] = $value;
            $hasPrimary = $hasPrimary || $value->isPrimary();
        }

        if (!$hasPrimary && $this->defineAllowPrimaryReference()) {
            $values = ['__primary' => FieldReferenceValue::primary(), ...$values];
        }

        return array_values($values);
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

    /**
     * Declare the primary value and any explicitly supported selectors.
     *
     * @return FieldReferenceValue[]
     */
    protected function defineReferenceValues(): array
    {
        return [];
    }

    protected function defineValueClass(): ?string
    {
        return null;
    }

}

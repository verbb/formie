<?php
namespace verbb\formie\fields\traits;

use verbb\formie\base\Field as FormieField;
use verbb\formie\base\FieldInterface;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use craft\helpers\Localization;

use ReflectionClass;
use ReflectionProperty;

trait DisplayTypeFieldTrait
{
    // Traits
    // =========================================================================

    use PresentationFieldConfigTrait;


    // Properties
    // =========================================================================

    // Delegated presentation settings persisted on wrapper fields (Survey, Quiz, etc.).
    public ?string $toggleCheckbox = null;
    public ?string $toggleCheckboxLabel = null;
    public bool $limitOptions = false;
    public int|float|null $min = null;
    public int|float|null $max = null;
    public bool $useSearchable = false;
    public bool $limit = false;
    public ?string $minType = 'characters';
    public ?string $maxType = 'characters';
    public bool $useRichText = false;
    public ?array $richTextButtons = ['bold', 'italic'];
    public bool $plainTextPaste = false;


    // Public Methods
    // =========================================================================

    public function getDisplayTypeField(): ?FieldInterface
    {
        return $this->resolvePresentationField(
            $this->getPresentationDisplayType(),
            $this->getDisplayTypeFieldConfig(),
        );
    }

    public function getDisplayTypeFieldConfig(): array
    {
        $config = [];

        if (method_exists($this, 'getFieldOptions')) {
            $config['options'] = $this->getFieldOptions();
        }

        if (property_exists($this, 'multi')) {
            $config['multi'] = $this->multi;
        }

        if (property_exists($this, 'layout')) {
            $config['layout'] = $this->layout;
        }

        if (property_exists($this, 'hasMultiNamespace')) {
            $config['hasMultiNamespace'] = $this->hasMultiNamespace;
        }

        if (property_exists($this, 'useSearchable')) {
            $config['useSearchable'] = $this->useSearchable;
        }

        if (property_exists($this, 'optgroups')) {
            $config['optgroups'] = $this->optgroups;
        }

        if ($this->getParentField()) {
            $config['parentField'] = $this->getParentField();
            $config['namespace'] = $this->getNamespace();
        } else {
            $config['namespace'] = $this->getNamespace();
        }

        $class = new ReflectionClass($this);

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->class === FormieField::class) {
                $config[$property->getName()] = $this->{$property->getName()};
            }
        }

        foreach ($this->defineDisplayTypePassthroughSettings() as $property) {
            $config[$property] = $this->{$property};
        }

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function definePresentationFieldClassMap(): array
    {
        $config = $this->defaultPresentationFieldClassMap();
        $config['singleLineText'] = SingleLineText::class;
        $config['multiLineText'] = MultiLineText::class;

        return $config;
    }

    protected function defineDisplayTypePassthroughSettings(): array
    {
        return [
            'toggleCheckbox',
            'toggleCheckboxLabel',
            'limitOptions',
            'min',
            'max',
            'useSearchable',
            'limit',
            'minType',
            'maxType',
            'useRichText',
            'richTextButtons',
            'plainTextPaste',
        ];
    }

    protected function defineDisplayTypeSettingsAttributes(): array
    {
        return $this->defineDisplayTypePassthroughSettings();
    }

    protected function initDisplayTypeFieldConfig(array &$config): void
    {
        foreach (['min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }
    }

    protected function definePresentationFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        // Choice modules and labels need the presentation field's outer layout too.
        if (!in_array($key, ['fieldLayout', 'fieldLabel'], true)) {
            return null;
        }

        $field = $this->getDisplayTypeField();

        return $field instanceof FormieField ? $field->defineSlotTag($key, $context) : null;
    }

    protected function definePresentationFieldBrowserModules(): array
    {
        $field = $this->getDisplayTypeField();

        if ($field instanceof FormieField) {
            return $field->collectBrowserModules();
        }

        return [];
    }
}

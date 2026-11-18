<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\variables\Formie as FormieVariable;

use Throwable;

trait FieldClientRenderedDefinitionTrait
{
    // Public Methods
    // =========================================================================

    public function getClientRenderedDefinition(): array
    {
        $clientRenderedChildren = $this->clientRenderedChildren();
        $valueType = $this->_clientValueType($this->valueType()->toArray());
        $definition = [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'key' => 'field-' . $this->handle,
            'handle' => (string)$this->handle,
            'label' => $this->label,
            'instructions' => $this->getInstructionsHtml()->__toString() ?: null,
            'type' => $this->defineClientRenderedType(),
            'required' => (bool)$this->required,
            'condition' => ConditionsHelper::toBrowserConditionDefinition($this->getBrowserConditions()),
            'validation' => $this->browserValidationRules(),
            'input' => $this->getClientRenderedInput(),
            'client' => [
                'children' => $clientRenderedChildren->toArray(),
                'valueType' => $valueType,
            ],
            // The form-level manifest projection replaces this placeholder with
            // the exact entry keys produced for this field and surface.
            'moduleRefs' => [],
            'meta' => [
                'fieldType' => static::kebabClassName(),
                'hidden' => $this->getIsHidden(),
                'disabled' => $this->getIsDisabled(),
            ],
        ];

        return $definition;
    }

    // Build the client input contract, including serialized default values and nested child schema.
    public function getClientRenderedInput(): array
    {
        $contract = array_merge([
            'fieldKind' => $this->fieldKind(),
            'fieldType' => static::kebabClassName(),
        ], $this->defineClientRenderedInput());
        $isFileField = $this->fieldKind() === Field::KIND_FILE;
        $initialValue = $isFileField
            ? []
            : $this->serializeValueForClientInput($this->getInitialValue());

        if ($initialValue !== null || !array_key_exists('defaultValue', $contract)) {
            $contract['defaultValue'] = $initialValue;
        }

        $contract = $this->_applyClientChildrenDefinition($contract);

        // Client-rendered forms have no template, so send the label position and autocomplete hint it would use.
        if (!array_key_exists('labelPosition', $contract) && ($labelPosition = $this->_clientLabelPosition()) !== null) {
            $contract['labelPosition'] = $labelPosition;
        }

        if (!isset($contract['autocomplete']) && ($autocomplete = $this->_clientAutocomplete()) !== null) {
            $contract['autocomplete'] = $autocomplete;
        }

        if ($isFileField) {
            $contract['defaultValue'] = $contract['defaultValue'] ?? [];
            $contract['multiple'] = $contract['multiple'] ?? true;
        }

        return $contract;
    }


    // Private Methods
    // =========================================================================

    private function _clientLabelPosition(): ?string
    {
        $form = $this->getForm();

        if (!$form) {
            return null;
        }

        $position = (new FormieVariable())->getLabelPosition($this, $form);

        return match (true) {
            $position instanceof HiddenPosition => 'hidden',
            $position instanceof BelowInput => 'below',
            $position instanceof LeftInput => 'left',
            $position instanceof RightInput => 'right',
            $position instanceof AboveInput => 'above',
            default => null,
        };
    }

    private function _clientAutocomplete(): ?string
    {
        // An autocomplete set in the field's Input Attributes wins over the field's own, as in server-rendered forms.
        $inputAttributes = $this->getInputAttributes();

        if (is_string($inputAttributes['autocomplete'] ?? null) && trim($inputAttributes['autocomplete']) !== '') {
            return trim($inputAttributes['autocomplete']);
        }

        // Only fields rendered as a single input, select or textarea have an autocomplete hint.
        if (!in_array($this->fieldKind(), [Field::KIND_TEXT, Field::KIND_TEXTAREA, Field::KIND_PHONE, Field::KIND_SELECT, Field::KIND_NAME], true)) {
            return null;
        }

        try {
            $tag = $this->defineSlotTag('fieldInput', RenderContext::from(['form' => $this->getForm(), 'field' => $this]));
        } catch (Throwable) {
            // Some inputs need a full render context, and an autocomplete hint isn't worth failing the definition.
            return null;
        }

        $autocomplete = $tag?->coreAttributes['autocomplete'] ?? null;

        return is_string($autocomplete) && trim($autocomplete) !== '' ? trim($autocomplete) : null;
    }

    private function _clientValueType(array $type): array
    {
        unset($type['class']);

        foreach ($type as $key => $value) {
            if (is_array($value)) {
                $type[$key] = $this->_clientValueType($value);
            }
        }

        return $type;
    }

    private function _applyClientChildrenDefinition(array $contract): array
    {
        $children = $this->clientRenderedChildren();

        if ($children->mode === FieldClientRenderedChildren::MODE_PARTS) {
            $childFields = $children->resolvePartFields();
            $contract['parts'] = $contract['parts'] ?? array_values(array_filter(array_map(function(FieldInterface $field) {
                return $field->getClientRenderedDefinition();
            }, $childFields)));
        }

        if ($children->mode === FieldClientRenderedChildren::MODE_ROWS) {
            $contract['defaultValue'] = $contract['defaultValue'] ?? [];
            $contract['rowSchema'] = $contract['rowSchema'] ?? [
                'id' => (string)$this->id . '-row',
                'key' => 'row-' . $this->handle,
                'rows' => array_values(array_map(static function($row) {
                    return $row->getClientRenderedDefinition();
                }, $children->resolveRows())),
            ];
        }

        return $contract;
    }
}

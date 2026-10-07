<?php
namespace verbb\formie\base;

use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\helpers\ConditionsHelper;

trait FieldClientRenderedDefinitionTrait
{
    // Public Methods
    // =========================================================================

    public function getClientRenderedDefinition(): array
    {
        $clientRenderedDefinition = $this->clientRenderedDefinition();
        $clientRenderedChildren = $this->clientRenderedChildren();
        $valueType = $this->_clientValueType($this->valueType()->toArray());
        $definition = [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'key' => 'field-' . $this->handle,
            'handle' => (string)$this->handle,
            'label' => $this->label,
            'instructions' => $this->getInstructionsHtml()->__toString() ?: null,
            'type' => $clientRenderedDefinition->type,
            'required' => (bool)$this->required,
            'condition' => ConditionsHelper::toComponentConditionDefinition($this->getBrowserConditions()),
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
        ], $this->clientRenderedDefinition()->input);
        $isFileField = $this->fieldKind() === Field::KIND_FILE;
        $initialValue = $isFileField
            ? []
            : $this->serializeValueForClientInput($this->getInitialValue());

        if ($initialValue !== null || !array_key_exists('defaultValue', $contract)) {
            $contract['defaultValue'] = $initialValue;
        }

        $contract = $this->_applyClientChildrenDefinition($contract);

        if ($isFileField) {
            $contract['defaultValue'] = $contract['defaultValue'] ?? [];
            $contract['multiple'] = $contract['multiple'] ?? true;
        }

        return $contract;
    }


    // Private Methods
    // =========================================================================

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

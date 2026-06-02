<?php
namespace verbb\formie\base;

use verbb\formie\base\Field;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\models\ClientModuleContext;

use Craft;

trait FieldClientDefinitionTrait
{
    // Public Methods
    // =========================================================================

    // Thin config used by CP submission editing, not the full REST/GQL/browser payload.
    public function getClientConfig(): array
    {
        return [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'handle' => $this->handle,
            'type' => $this->clientDefinition()->type,
            'label' => $this->label,
            'required' => (bool)$this->required,
            'validation' => $this->validationRules(),
            'settings' => $this->getSettings(),
        ];
    }

    public function getClientPayload(): array
    {
        $clientDefinition = $this->clientDefinition();
        $clientChildren = $this->clientChildren();
        $definition = [
            'id' => (string)$this->id,
            'key' => 'field-' . $this->handle,
            'handle' => (string)$this->handle,
            'label' => $this->label,
            'instructions' => $this->instructions,
            'type' => $clientDefinition->type,
            'required' => (bool)$this->required,
            'condition' => ConditionsHelper::toComponentConditionDefinition($this->conditions()->toArray()),
            'validation' => $this->validationRules(),
            'input' => $this->getClientInputDefinition(),
            'client' => [
                'children' => $clientChildren->toArray(),
                'valueClass' => $this->valueClass()->toArray(),
            ],
            'moduleRefs' => $this->getClientModuleIds(),
            'meta' => [
                'fieldType' => static::kebabClassName(),
                'hidden' => $this->getIsHidden(),
                'disabled' => $this->getIsDisabled(),
            ],
        ];

        return $definition;
    }

    // Build the client input contract, including serialized default values and nested child schema.
    public function getClientInputDefinition(): array
    {
        $contract = array_merge([
            'fieldKind' => $this->fieldKind(),
            'fieldType' => static::kebabClassName(),
        ], $this->clientDefinition()->input);
        $isFileField = $this->fieldKind() === Field::KIND_FILE;
        $initialValue = $isFileField
            ? []
            : $this->valueClass()->serializeClientValue($this->getInitialValue());

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

    private function getClientModuleIds(): array
    {
        return array_values(array_filter(array_unique($this->clientModules()->getModuleIds(new ClientModuleContext([
            'field' => $this,
        ])))));
    }

    private function _applyClientChildrenDefinition(array $contract): array
    {
        $children = $this->clientChildren();

        if ($children->mode === FieldClientChildren::MODE_PARTS) {
            $childFields = $children->resolvePartFields();
            $contract['parts'] = $contract['parts'] ?? array_values(array_filter(array_map(function(FieldInterface $field) {
                return $field->getClientPayload();
            }, $childFields)));
        }

        if ($children->mode === FieldClientChildren::MODE_ROWS) {
            $contract['defaultValue'] = $contract['defaultValue'] ?? [];
            $contract['rowSchema'] = $contract['rowSchema'] ?? [
                'id' => (string)$this->id . '-row',
                'key' => 'row-' . $this->handle,
                'rows' => array_values(array_map(static function($row) {
                    return $row->getClientPayload();
                }, $children->resolveRows())),
            ];
        }

        return $contract;
    }
}

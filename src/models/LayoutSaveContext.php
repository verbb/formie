<?php
namespace verbb\formie\models;

use verbb\formie\base\Field;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use craft\db\Query;
use craft\helpers\Json;

use yii\base\InvalidConfigException;

/** One save owns its identity scope and shared-definition write set, including nested saves. */
class LayoutSaveContext
{
    // Static Methods
    // =========================================================================

    public static function forForm(Form $form, string $operation = 'save'): self
    {
        $layoutId = $form->id ? (new Query())->select('layoutId')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar() : null;
        $context = new self($operation);

        if ($layoutId) {
            $context->includeLayout((int)$layoutId);
        }

        return $context;
    }


    // Properties
    // =========================================================================

    public array $savedDefinitions = [];
    public array $remaps = [];
    public array $fields = [];
    public array $layouts = [];
    public bool $updateDefinitions = true;
    public bool $trusted = true;

    private array $_definitionUsageCounts = [];
    private array $_affectedDefinitionIds = [];
    private array $_savedFields = [];
    private int $_saveDepth = 0;
    private bool $_validationPrepared = false;
    private bool $_graphValidated = false;
    private array $_validationFieldState = [];
    private array $_validationScopeLayoutIds = [];
    private array $_validationScopeSignatures = [];
    private array $_validationChildScopes = [];
    private array $_authoritativeValidationScopes = [];
    private array $_proposedHandles = [];
    private array $_proposedReferences = [];
    private array $_existingFieldIdsByLayout = [];
    private array $_existingHandlesByLayout = [];
    private array $_existingReferences = [];
    private array $_loadedReferences = [];


    // Public Methods
    // =========================================================================

    public function __construct(public readonly string $operation = 'save')
    {
    }

    public function includeLayout(int $id): void
    {
        if (isset($this->layouts[$id])) {
            return;
        }
        $this->layouts[$id] = true;
        $records = (new Query())->select(['ff.id', 'ff.fieldId', 'ff.layoutId', 'ff.reference', 'f.handle', 'f.settings'])->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')->where(['ff.layoutId' => $id])->all();

        foreach ($records as $record) {
            $fieldId = (int)$record['id'];
            $this->fields[$fieldId] = (int)$record['fieldId'];
            $this->_existingFieldIdsByLayout[$id][$fieldId] = true;
            $this->_existingHandlesByLayout[$id][strtolower((string)$record['handle'])][$fieldId] = true;
            $settings = Json::decodeIfJson($record['settings']) ?: [];

            if ($nestedId = $settings['nestedLayoutId'] ?? null) {
                $this->includeLayout((int)$nestedId);
            }
        }
    }

    /** Prepares one authoritative identity snapshot for a complete layout graph. */
    public function prepareValidationGraph(FieldLayout $layout): void
    {
        $this->_resetValidationState();
        $this->_indexValidationLayout($layout, true);
        $this->_loadReferenceIdentities(array_keys($this->_proposedReferences));
        $this->_validationPrepared = true;
    }

    public function ensureValidationGraph(FieldLayout $layout): void
    {
        if (!$this->_validationPrepared) {
            $this->prepareValidationGraph($layout);

            return;
        }

        $this->_indexValidationLayout($layout, true);
        $this->_loadReferenceIdentities(array_keys($this->_proposedReferences));
    }

    public function prepareFieldValidation(Field $field): void
    {
        if (!$this->_validationPrepared) {
            $this->_resetValidationState();
            $this->_validationPrepared = true;
        }

        if (!isset($this->_validationFieldState[spl_object_id($field)])) {
            $scope = $field->layoutId ? 'layout:' . $field->layoutId : 'field:' . spl_object_id($field);
            $this->_validationScopeLayoutIds[$scope] = $field->layoutId ? (int)$field->layoutId : null;
            $this->_indexValidationField($field, $scope);
        }

        $this->_loadReferenceIdentities(array_keys($this->_proposedReferences));
    }

    public function markGraphValidated(): void
    {
        $this->_graphValidated = true;
    }

    public function getIsGraphValidated(): bool
    {
        return $this->_graphValidated;
    }

    /** Refreshes identity indexes after a field's beforeSave() normalization. */
    public function refreshValidationField(Field $field): void
    {
        $objectId = spl_object_id($field);
        $state = $this->_validationFieldState[$objectId] ?? null;

        if (!$state) {
            $this->prepareFieldValidation($field);

            return;
        }

        unset($this->_proposedHandles[$state['scope']][$state['handle']][$objectId]);

        if ($state['reference'] !== null) {
            unset($this->_proposedReferences[$state['reference']][$objectId]);
        }

        $this->_indexValidationField($field, $state['scope']);
        $reference = $this->_validationFieldState[$objectId]['reference'];

        if ($reference !== null) {
            $this->_loadReferenceIdentities([$reference]);
        }
    }

    /** Returns null when validation is occurring outside this save context. */
    public function getIsHandleUnique(Field $field): ?bool
    {
        $objectId = spl_object_id($field);
        $state = $this->_validationFieldState[$objectId] ?? null;

        if (!$state) {
            return null;
        }

        if (count($this->_proposedHandles[$state['scope']][$state['handle']] ?? []) > 1) {
            return false;
        }

        $layoutId = $this->_validationScopeLayoutIds[$state['scope']] ?? null;

        if (!$layoutId) {
            return true;
        }

        foreach (array_keys($this->_existingHandlesByLayout[$layoutId][$state['handle']] ?? []) as $fieldId) {
            if ($field->id && $fieldId === (int)$field->id) {
                continue;
            }

            if (isset($this->_authoritativeValidationScopes[$state['scope']], $this->_existingFieldIdsByLayout[$layoutId][$fieldId])) {
                continue;
            }

            return false;
        }

        return true;
    }

    /** Returns null when validation is occurring outside this save context. */
    public function getIsReferenceUnique(Field $field): ?bool
    {
        $objectId = spl_object_id($field);
        $state = $this->_validationFieldState[$objectId] ?? null;
        $reference = $state['reference'] ?? null;

        if (!$state || $reference === null) {
            return $state ? true : null;
        }

        if (count($this->_proposedReferences[$reference] ?? []) > 1) {
            return false;
        }

        foreach (array_keys($this->_existingReferences[$reference] ?? []) as $fieldId) {
            if ($field->id && $fieldId === (int)$field->id) {
                continue;
            }

            if ($this->_isFieldReplacedByValidationGraph($fieldId)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /** Returns whether the caller owns the root persistence boundary. */
    public function beginSave(array $fields = []): bool
    {
        $isRoot = $this->_saveDepth === 0;
        $this->_saveDepth++;

        if ($isRoot) {
            $this->savedDefinitions = [];
            $this->_definitionUsageCounts = [];
            $this->_affectedDefinitionIds = [];
            $this->_savedFields = [];
            $definitionIds = array_values($this->fields);

            foreach ($fields as $field) {
                if ($field instanceof Field && $field->definitionId) {
                    $definitionIds[] = $field->definitionId;
                }
            }

            $this->_loadDefinitionUsage($definitionIds);
        }

        return $isRoot;
    }

    public function endSave(): void
    {
        $this->_saveDepth = max(0, $this->_saveDepth - 1);
    }

    public function getDefinitionUsageCount(?int $definitionId): int
    {
        if (!$definitionId) {
            return 0;
        }

        if (!array_key_exists($definitionId, $this->_definitionUsageCounts)) {
            // Custom parent fields can introduce a definition after the root graph was inspected.
            // Keep that extension path correct without returning to repeated counts for known fields.
            $this->_loadDefinitionUsage([$definitionId]);
        }

        return $this->_definitionUsageCounts[$definitionId] ?? 0;
    }

    public function registerNewDefinition(int $definitionId): void
    {
        $this->_definitionUsageCounts[$definitionId] = 0;
        $this->_affectedDefinitionIds[$definitionId] = true;
    }

    public function recordSavedField(Field $field, ?int $previousDefinitionId): void
    {
        $definitionId = $field->definitionId;

        if ($previousDefinitionId && $previousDefinitionId !== $definitionId) {
            $this->_definitionUsageCounts[$previousDefinitionId] = max(0, $this->getDefinitionUsageCount($previousDefinitionId) - 1);
            $this->_affectedDefinitionIds[$previousDefinitionId] = true;
        }

        if ($definitionId && (!$previousDefinitionId || $previousDefinitionId !== $definitionId)) {
            $this->_definitionUsageCounts[$definitionId] = $this->getDefinitionUsageCount($definitionId) + 1;
        }

        if ($definitionId) {
            $this->_affectedDefinitionIds[$definitionId] = true;
        }

        if ($field->id && $definitionId) {
            $this->fields[$field->id] = $definitionId;
        }

        $this->_savedFields[spl_object_id($field)] = $field;
    }

    /** Refreshes saved models from one authoritative post-cleanup usage query. */
    public function refreshSavedFieldUsage(): void
    {
        $definitionIds = array_keys($this->_affectedDefinitionIds);

        if ($definitionIds !== []) {
            $this->_loadDefinitionUsage($definitionIds, true);
        }

        foreach ($this->_savedFields as $field) {
            $field->usageCount = $this->_definitionUsageCounts[$field->definitionId] ?? 0;
            $field->isSynced = $field->usageCount > 1;
        }
    }

    public function assertLayout(?int $id): void
    {
        if ($id && !isset($this->layouts[$id])) {
            throw new InvalidConfigException('The field layout does not belong to this form.');
        }
    }

    public function assertField(Field $field): void
    {
        if (!$field->id) {
            return;
        }

        if (!isset($this->fields[$field->id])) {
            throw new InvalidConfigException('The field instance does not belong to this form.');
        }

        // Detaching a synced instance creates an independent definition; switching to a different one is forbidden.
        if ($field->definitionId && $this->fields[$field->id] !== $field->definitionId) {
            throw new InvalidConfigException('The field definition link cannot be replaced.');
        }
    }


    // Private Methods
    // =========================================================================

    private function _loadDefinitionUsage(array $definitionIds, bool $replace = false): void
    {
        $definitionIds = array_values(array_unique(array_filter(array_map('intval', $definitionIds))));

        if (!$replace) {
            $definitionIds = array_values(array_filter($definitionIds, fn(int $id) => !array_key_exists($id, $this->_definitionUsageCounts)));
        }

        if ($definitionIds === []) {
            return;
        }

        $counts = (new Query())
            ->select(['fieldId', 'usageCount' => 'COUNT(*)'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->where(['fieldId' => $definitionIds])
            ->groupBy(['fieldId'])
            ->pairs();

        foreach ($definitionIds as $definitionId) {
            $this->_definitionUsageCounts[$definitionId] = (int)($counts[$definitionId] ?? 0);
        }
    }

    private function _resetValidationState(): void
    {
        $this->_validationPrepared = false;
        $this->_graphValidated = false;
        $this->_validationFieldState = [];
        $this->_validationScopeLayoutIds = [];
        $this->_validationScopeSignatures = [];
        $this->_validationChildScopes = [];
        $this->_authoritativeValidationScopes = [];
        $this->_proposedHandles = [];
        $this->_proposedReferences = [];
        $this->_existingReferences = [];
        $this->_loadedReferences = [];
    }

    private function _indexValidationLayout(FieldLayout $layout, bool $authoritative): void
    {
        $scope = 'object:' . spl_object_id($layout);
        $wasGraphValidated = $this->_graphValidated;
        $previousSignature = $this->_validationScopeSignatures[$scope] ?? null;
        $previousChildScopes = $this->_validationChildScopes[$scope] ?? [];
        $this->_removeValidationScope($scope, false);
        $this->_validationScopeLayoutIds[$scope] = $layout->id ? (int)$layout->id : null;

        if ($authoritative) {
            $this->_authoritativeValidationScopes[$scope] = true;
        }

        $signature = [];
        $childScopes = [];

        foreach ($layout->getFields() as $field) {
            if (!$field instanceof Field) {
                continue;
            }

            $this->_indexValidationField($field, $scope);
            $state = $this->_validationFieldState[spl_object_id($field)];
            // Compare validation-relevant state rather than object identity: fixed
            // parent fields may rebuild equivalent child objects during beforeSave().
            $signature[] = [
                $field::class,
                $field->label,
                $state['handle'],
                $state['reference'],
                Json::encode($field->getSettings()),
            ];

            if ($field instanceof ParentFieldInterface && $field->hasFieldLayout()) {
                $nestedLayout = $field->getFieldLayout();
                $childScope = 'object:' . spl_object_id($nestedLayout);
                $childScopes[$childScope] = true;
                $this->_indexValidationLayout($nestedLayout, $authoritative);
            }
        }

        $this->_validationScopeSignatures[$scope] = $signature;
        $this->_validationChildScopes[$scope] = $childScopes;

        foreach (array_diff_key($previousChildScopes, $childScopes) as $removedScope => $unused) {
            $this->_removeValidationScope($removedScope, true);
        }

        if (($previousSignature === null && $wasGraphValidated) || ($previousSignature !== null && $previousSignature !== $signature)) {
            $this->_graphValidated = false;
        }
    }

    private function _indexValidationField(Field $field, string $scope): void
    {
        $objectId = spl_object_id($field);
        $handle = strtolower((string)$field->handle);
        // References are part of validation (conditions resolve through them), so
        // new fields need their durable identity before Craft validates the form.
        $field->reference = is_string($field->reference) && $field->reference !== '' ? $field->reference : StringHelper::UUID();
        $reference = $field->reference;
        $this->_validationFieldState[$objectId] = compact('scope', 'handle', 'reference');
        $this->_proposedHandles[$scope][$handle][$objectId] = true;

        if ($reference !== null) {
            $this->_proposedReferences[$reference][$objectId] = true;
        }

        $field->layoutSaveContext = $this;
    }

    private function _loadReferenceIdentities(array $references): void
    {
        $references = array_values(array_filter(array_unique($references), fn(string $reference) => !isset($this->_loadedReferences[$reference])));

        if ($references === []) {
            return;
        }

        foreach ($references as $reference) {
            $this->_loadedReferences[$reference] = true;
        }

        $records = (new Query())
            ->select(['id', 'reference'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->where(['reference' => $references])
            ->all();

        foreach ($records as $record) {
            $this->_existingReferences[(string)$record['reference']][(int)$record['id']] = true;
        }
    }

    /** Removes proposed identities for a replaced layout graph. */
    private function _removeValidationScope(string $scope, bool $recursive): void
    {
        if ($recursive) {
            foreach (array_keys($this->_validationChildScopes[$scope] ?? []) as $childScope) {
                $this->_removeValidationScope($childScope, true);
            }
        }

        foreach ($this->_validationFieldState as $objectId => $state) {
            if ($state['scope'] !== $scope) {
                continue;
            }

            unset($this->_proposedHandles[$scope][$state['handle']][$objectId]);

            if ($state['reference'] !== null) {
                unset($this->_proposedReferences[$state['reference']][$objectId]);
            }

            unset($this->_validationFieldState[$objectId]);
        }

        unset(
            $this->_validationScopeLayoutIds[$scope],
            $this->_validationScopeSignatures[$scope],
            $this->_validationChildScopes[$scope],
            $this->_authoritativeValidationScopes[$scope],
        );
    }

    private function _isFieldReplacedByValidationGraph(int $fieldId): bool
    {
        foreach ($this->_authoritativeValidationScopes as $scope => $authoritative) {
            $layoutId = $this->_validationScopeLayoutIds[$scope] ?? null;

            if ($layoutId && isset($this->_existingFieldIdsByLayout[$layoutId][$fieldId])) {
                return true;
            }
        }

        return false;
    }
}

<?php
namespace verbb\formie\models;

use verbb\formie\base\Field;
use verbb\formie\elements\Form;
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
        $records = (new Query())->select(['ff.id', 'ff.fieldId', 'f.settings'])->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')->where(['ff.layoutId' => $id])->all();
        foreach ($records as $record) {
            $this->fields[(int)$record['id']] = (int)$record['fieldId'];
            $settings = Json::decodeIfJson($record['settings']) ?: [];
            if ($nestedId = $settings['nestedLayoutId'] ?? null) {
                $this->includeLayout((int)$nestedId);
            }
        }
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

    public function assertField(\verbb\formie\base\Field $field): void
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
}

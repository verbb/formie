<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use Craft;
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
}

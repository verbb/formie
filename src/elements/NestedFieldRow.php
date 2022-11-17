<?php
namespace verbb\formie\elements;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\records\NestedFieldRow as NestedFieldRowRecord;

use Craft;
use craft\base\Element;
use craft\base\BlockElementInterface;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;

use yii\base\InvalidConfigException;

use Exception;

class NestedFieldRow extends Element implements BlockElementInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Nested Field Row');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('formie', 'Nested field row');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('formie', 'Nested Field Rows');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('formie', 'Nested field rows');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'nestedfieldrow';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return NestedFieldRowQuery
     */
    public static function find(): NestedFieldRowQuery
    {
        return new NestedFieldRowQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        /* @var NestedFieldInterface $nestedField */
        $nestedField = ArrayHelper::firstValue($sourceElements)->fieldId;

        // Set the field context
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = $nestedField->getFormFieldContext();

        $map = parent::eagerLoadingMap($sourceElements, $handle);

        $contentService->fieldContext = $originalFieldContext;

        return $map;
    }


    // Properties
    // =========================================================================

    public ?int $fieldId = null;
    public ?int $ownerId = null;
    public ?int $sortOrder = null;
    public bool $dirty = false;
    public bool $collapsed = false;
    public bool $deletedWithOwner = false;

    private ElementInterface|false|null $_owner = null;
    private ?array $_fields = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'owner';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        // Only support the site the submission is being made on
        $siteId = $this->siteId ?: Craft::$app->getSites()->getPrimarySite()->id;

        return [$siteId];
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->_getField()->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getOwner(): ?ElementInterface
    {
        if ($this->_owner === null) {
            if ($this->ownerId === null) {
                throw new InvalidConfigException('Nested field row is missing its owner ID');
            }

            if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId, ['isIncomplete' => null])) === null) {
                throw new InvalidConfigException('Invalid owner ID: ' . $this->ownerId);
            }
        }

        return $this->_owner;
    }

    public function setOwner(ElementInterface $owner = null): void
    {
        $this->_owner = $owner;
    }

    /**
     * @inheritdoc
     */
    public function getContentTable(): string
    {
        return $this->_getField()->contentTable;
    }

    /**
     * @inheritdoc
     */
    public function getFieldColumnPrefix(): string
    {
        return 'field_';
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext(): string
    {
        return $this->_getField()->getFormFieldContext();
    }

    /**
     * Returns the row’s fields.
     *
     * @return FormFieldInterface[] The row’s fields.
     */
    public function getCustomFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $fieldLayout = $this->getFieldLayout();

        if (!$fieldLayout) {
            return [];
        }

        return $this->_fields = $fieldLayout->getCustomFields();
    }

    /**
     * Returns a field by its handle.
     */
    public function getFieldByHandle(string $handle): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getCustomFields(), 'handle', $handle);
    }

    /**
     * Returns a field by its id.
     *
     * @param int $id
     * @return FormFieldInterface|null
     */
    public function getFieldById(int $id): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getCustomFields(), 'id', $id);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // Get the block record
            if (!$isNew) {
                $record = NestedFieldRowRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid nested feild row ID: ' . $this->id);
                }
            } else {
                $record = new NestedFieldRowRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = (int)$this->fieldId;
            $record->ownerId = (int)$this->ownerId;
            $record->sortOrder = (int)$this->sortOrder ?: null;
            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the row record
        Craft::$app->getDb()->createCommand()
            ->update('{{%formie_nestedfieldrows}}', [
                'deletedWithOwner' => $this->deletedWithOwner,
            ], ['id' => $this->id], [], false)
            ->execute();

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fieldId', 'ownerId', 'sortOrder'], 'number', 'integerOnly' => true];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns the nested field.
     */
    private function _getField(): NestedFieldInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}

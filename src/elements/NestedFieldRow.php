<?php

namespace verbb\formie\elements;

use Exception;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\records\NestedFieldRow as NestedFieldRowRecord;

use Craft;
use craft\base\Element;
use craft\base\BlockElementInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use yii\base\InvalidConfigException;

class NestedFieldRow extends Element implements BlockElementInterface
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Field ID
     */
    public $fieldId;

    /**
     * @var int|null Owner ID
     */
    public $ownerId;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * @var bool Whether the block has changed.
     * @internal
     */
    public $dirty = false;

    /**
     * @var bool Collapsed
     */
    public $collapsed = false;

    /**
     * @var bool Whether the block was deleted along with its owner
     * @see beforeDelete()
     */
    public $deletedWithOwner = false;

    /**
     * @var ElementInterface|false|null The owner element, or false if [[ownerId]] is invalid
     */
    private $_owner;

    /**
     * @var ElementInterface[]|null
     */
    private $_eagerLoadedBlockTypeElements;

    private $_fields;


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
    public static function refHandle()
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
    public static function find(): ElementQueryInterface
    {
        return new NestedFieldRowQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
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


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'owner';

        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fieldId', 'ownerId', 'sortOrder'], 'number', 'integerOnly' => true];
        return $rules;
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
    public function getFieldLayout()
    {
        return $this->_getField()->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getOwner(): ElementInterface
    {
        if ($this->_owner === null) {
            if ($this->ownerId === null) {
                throw new InvalidConfigException('Nested field row is missing its owner ID');
            }

            if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId, [ 'isIncomplete' => null ])) === null) {
                throw new InvalidConfigException('Invalid owner ID: ' . $this->ownerId);
            }
        }

        return $this->_owner;
    }

    /**
     * @inheritdoc
     */
    public function setOwner(ElementInterface $owner = null)
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
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $fieldLayout = $this->getFieldLayout();

        if (!$fieldLayout) {
            return [];
        }

        return $this->_fields = $fieldLayout->getFields();
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle
     * @return FormFieldInterface|null
     */
    public function getFieldByHandle(string $handle)
    {
        return ArrayHelper::firstWhere($this->getFields(), 'handle', $handle);
    }

    /**
     * Returns a field by its id.
     *
     * @param string $id
     * @return FormFieldInterface|null
     */
    public function getFieldById($id)
    {
        return ArrayHelper::firstWhere($this->getFields(), 'id', $id);
    }


    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
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


    // Private Methods
    // =========================================================================

    /**
     * Returns the nested field.
     *
     * @return NestedFieldInterface|NestedFieldTrait
     */
    private function _getField(): NestedFieldInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}

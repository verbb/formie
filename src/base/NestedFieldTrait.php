<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\services\Elements;
use craft\validators\ArrayValidator;


trait NestedFieldTrait
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $contentTable;

    /**
     * @var FieldLayout
     */
    private $_fieldLayout;

    /**
     * @var array
     */
    private $_rows;

    /**
     * @var array
     */
    private $_uniqueFieldHandles = [];


    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return NestedFieldRowQuery::class;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element)
    {
        $fields = $this->getFields();
        $value = $element->getFieldValue($this->handle);

        $rows = [];

        foreach ($value->all() as $row) {
            $values = [];

            foreach ($fields as $field) {
                $values[$field->handle] = $field->getValue($row);
            }

            $rows[] = $values;
        }

        return $rows;
    }

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        $this->_uniqueFieldHandles = [];

        $validates = true;

        // Can't validate multiple new rows at once so we'll need to give these temporary context to avoid false unique
        // handle validation errors, and just validate those manually. Also apply the future fieldColumnPrefix so that
        // field handle validation takes its length into account.
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;

        $contentService->fieldContext = StringHelper::randomString(10);
        $contentService->fieldColumnPrefix = 'field_';

        foreach ($this->getFields() as $field) {
            $field->validate();

            // Make sure the block type handle + field handle combo is unique for the whole field. This prevents us from
            // worrying about content column conflicts like "a" + "b_c" == "a_b" + "c".

            /* @var FormField $field */
            if ($this->handle && $field->handle) {
                $fieldHandle = $this->handle . '_' . $field->handle;

                if (in_array($fieldHandle, $this->_uniqueFieldHandles, true)) {
                    // This error *might* not be entirely accurate, but it's such an edge case that it's probably better
                    // for the error to be worded for the common problem (two duplicate handles within the same block
                    // type).
                    $error = Craft::t('formie', '{attribute} "{value}" has already been taken.', [
                        'attribute' => Craft::t('formie', 'Handle'),
                        'value' => $field->handle
                    ]);

                    $field->addError('handle', $error);
                } else {
                    $this->_uniqueFieldHandles[] = $fieldHandle;
                }
            }

            if ($field->hasErrors()) {
                $validates = false;
            }
        }

        $contentService->fieldContext = $originalFieldContext;
        $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

        return $validates;
    }

    /**
     * @inheritDoc
     */
    public function hasErrors($attribute = null)
    {
        if (parent::hasErrors($attribute)) {
            return true;
        }

        foreach ($this->getFields() as $field) {
            if ($field->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRows(): array
    {
        if ($fieldLayout = $this->getFieldLayout()) {
            foreach ($fieldLayout->getPages() as $page) {
                $rows = $page->getRows();
                return Formie::$plugin->getFields()->getRowConfig($rows);
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setRows(array $rows, $duplicate = false)
    {
        $fieldLayout = Formie::$plugin->getForms()->buildFieldLayout([
            [
                'label' => 'Fields',
                'rows' => $rows,
            ]
        ], static::class, $duplicate);

        if ($oldFieldLayout = $this->getFieldLayout()) {
            $fieldLayout->id = $oldFieldLayout->id;
        }

        $this->setFieldLayout($fieldLayout);
    }

    /**
     * @return FieldInterface[]
     * @throws InvalidConfigException
     */
    public function getNestedRows()
    {
        /* @var FormFieldInterface[] $pageFields */
        $pageFields = $this->getFields();
        return Formie::$plugin->getFields()->groupIntoRows($pageFields);
    }

    /**
     * Gets all nested fields.
     *
     * @return FieldInterface[]|null
     */
    public function getFields()
    {
        if ($fieldLayout = $this->getFieldLayout()) {
            return $fieldLayout->getFields();
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFieldLayout()
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        return $this->_fieldLayout = Formie::$plugin->getNestedFields()->getFieldLayout($this);
    }

    /**
     * @inheritDoc
     */
    public function setFieldLayout(FieldLayout $fieldLayout)
    {
        $this->_fieldLayout = $fieldLayout;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldContext(): string
    {
        return "formieField:{$this->uid}";
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsModules()
    {
        $modules = [];

        // Check for any nested fields
        foreach ($this->getFields() as $field) {
            if ($js = $field->getFrontEndJsModules()) {
                // Handle multiple registrations
                if (isset($js[0])) {
                    $modules = array_merge($modules, $js);
                } else {
                    $modules[] = $js;
                }
            }
        }

        return $modules;
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        // Make sure it's got a UUID
        if ($isNew) {
            if (empty($this->uid)) {
                $this->uid = StringHelper::UUID();
            }
        } else if (!$this->uid) {
            $this->uid = Db::uidById('{{%formie_nested}}', $this->id);
        }

        $fieldsService = Craft::$app->getFields();

        // Prep the fields for save
        if ($fieldLayout = $this->getFieldLayout()) {
            foreach ($fieldLayout->getFields() as $field) {
                $field->context = $this->getFormFieldContext();
                $fieldsService->prepFieldForSave($field);
            }
        }

        $this->contentTable = Formie::$plugin->getNestedFields()->defineContentTableName($this);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew)
    {
        Formie::$plugin->getNestedFields()->saveField($this);

        parent::afterSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        /** @var Element $element */
        if ($element->duplicateOf !== null) {
            Formie::$plugin->getNestedFields()->duplicateBlocks($this, $element->duplicateOf, $element, true);
        } else if ($element->isFieldDirty($this->handle)) {
            Formie::$plugin->getNestedFields()->saveElements($this, $element);
        }

        // Reset the field value if this is a new element
        if ($element->duplicateOf || $isNew) {
            $element->setFieldValue($this->handle, null);
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritDoc
     */
    public function beforeApplyDelete()
    {
        Formie::$plugin->getNestedFields()->deleteNestedField($this);

        parent::beforeApplyDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (!parent::beforeElementDelete($element)) {
            return false;
        }

        $elementsService = Craft::$app->getElements();

        // Delete any nested field rows that belong to this element(s)
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $nestedFieldRowQuery = NestedFieldRow::find();
            $nestedFieldRowQuery->anyStatus();
            $nestedFieldRowQuery->siteId($siteId);
            $nestedFieldRowQuery->ownerId($element->id);

            /** @var NestedFieldRow[] $nestedFieldRows */
            $nestedFieldRows = $nestedFieldRowQuery->all();

            foreach ($nestedFieldRows as $nestedFieldBlock) {
                $nestedFieldBlock->deletedWithOwner = true;
                $elementsService->deleteElement($nestedFieldBlock, $element->hardDelete);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element)
    {
        // Also restore any nested field rows for this element
        $elementsService = Craft::$app->getElements();

        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            $blocks = NestedFieldRow::find()
                ->anyStatus()
                ->siteId($siteInfo['siteId'])
                ->ownerId($element->id)
                ->trashed()
                ->andWhere(['formie_nestedfieldrows.deletedWithOwner' => true])
                ->all();

            foreach ($blocks as $block) {
                $elementsService->restoreElement($block);
            }
        }

        parent::afterElementRestore($element);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var Element|null $element */
        $query = NestedFieldRow::find();

        // Existing element?
        if ($element && $element->id) {
            $query->ownerId = $element->id;

            // Clear out id=false if this query was populated previously
            if ($query->id === false) {
                $query->id = null;
            }
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($element->siteId ?? null);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } else if ($element && is_array($value)) {
            $query->setCachedResult($this->_createRowsFromSerializedData($value, $element));
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var NestedFieldRowQuery $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $row) {
            $rowId = $row->id ?? 'new' . ++$new;

            $serialized[$rowId] = [
                'fields' => $row->getSerializedFieldValues(),
            ];
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':notempty:' || $value === ':empty:') {
            $alias = 'nestedfieldrows_' . $this->handle;
            $operator = ($value === ':notempty:' ? '!=' : '=');

            $query->subQuery->andWhere(
                "(select count([[{$alias}.id]]) from {{%formie_nestedfieldrows}} {{{$alias}}} where [[{$alias}.ownerId]] = [[elements.id]] and [[{$alias}.fieldId]] = :fieldId) {$operator} 0",
                [':fieldId' => $this->id]
            );
        } else if ($value !== null) {
            return false;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        /** @var NestedFieldRowQuery $value */
        return $value->count() === 0;
    }

    /**
     * Validates an owner element’s nested field rows.
     *
     * @param ElementInterface $element
     */
    public function validateRows(ElementInterface $element)
    {
        /** @var Element $element */
        /** @var NestedFieldRowQuery $value */
        $value = $element->getFieldValue($this->handle);
        $blocks = $value->all();
        $allRowsValidate = true;

        foreach ($blocks as $i => $row) {
            /** @var NestedFieldRow $row */
            if ($element->getScenario() === Element::SCENARIO_LIVE) {
                $row->setScenario(Element::SCENARIO_LIVE);
            }

            if (!$row->validate()) {
                $element->addModelErrors($row, "{$this->handle}[{$i}]");
                $allRowsValidate = false;
            }
        }

        if (!$allRowsValidate) {
            // Just in case the rows weren't already cached
            $value->setCachedResult($blocks);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        /** @var NestedFieldRowQuery $value */
        /** @var NestedFieldRow $row */
        $keywords = [];
        $contentService = Craft::$app->getContent();

        foreach ($value->all() as $row) {
            $originalContentTable = $contentService->contentTable;
            $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
            $originalFieldContext = $contentService->fieldContext;

            $contentService->contentTable = $row->getContentTable();
            $contentService->fieldColumnPrefix = $row->getFieldColumnPrefix();
            $contentService->fieldContext = $row->getFieldContext();

            foreach (Craft::$app->getFields()->getAllFields() as $field) {
                /** @var Field $field */
                if ($field->searchable) {
                    $fieldValue = $row->getFieldValue($field->handle);
                    $keywords[] = $field->getSearchKeywords($fieldValue, $element);
                }
            }

            $contentService->contentTable = $originalContentTable;
            $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
            $contentService->fieldContext = $originalFieldContext;
        }

        return parent::getSearchKeywords($keywords, $element);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['ownerId as source', 'id as target'])
            ->from(['{{%formie_nestedfieldrows}}'])
            ->where([
                'fieldId' => $this->id,
                'ownerId' => $sourceElementIds,
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return [
            'elementType' => NestedFieldRow::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
            ]
        ];
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineValueAsString($value, ElementInterface $element = null)
    {
        $values = [];

        foreach ($value->all() as $rowId => $row) {
            foreach ($row->getFieldLayout()->getFields() as $field) {
                $subValue = $row->getFieldValue($field->handle);
                $valueAsString = $field->getValueAsString($subValue, $row);

                if ($valueAsString) {
                    $values[] = $valueAsString;
                }
            }
        }

        return implode(', ', $values);
    }

    /**
     * @inheritDoc
     */
    protected function defineValueForExport($value, ElementInterface $element = null)
    {
        $values = [];

        foreach ($value->all() as $rowId => $row) {
            foreach ($row->getFieldLayout()->getFields() as $field) {
                $subValue = $row->getFieldValue($field->handle);
                $valueForExport = $field->getValueForExport($subValue, $row);

                $values[$this->handle . '_row' . ($rowId + 1) . '_' . $field->handle] = $valueForExport;
            }
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    protected function defineValueForSummary($value, ElementInterface $element = null)
    {
        $values = '';

        foreach ($value->all() as $rowId => $row) {
            foreach ($row->getFieldLayout()->getFields() as $field) {
                $v = $row->getFieldValue($field->handle);
                $html = $field->getValueForSummary($v, $row);

                $values .= '<strong>' . $field->handle . '</strong> ' . $html . '<br>';
            }
        }

        return Template::raw($values);
    }


    // Public Methods
    // =========================================================================

    /**
     * Creates an array of rows based on the given serialized data.
     *
     * @param array $value The raw field value
     * @param ElementInterface $element The element the field is associated with
     *
     * @return NestedFieldRow[]
     */
    private function _createRowsFromSerializedData(array $value, ElementInterface $element): array
    {
        /** @var Element $element */

        // Get the old rows
        if ($element->id) {
            $oldRowsById = NestedFieldRow::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->anyStatus()
                ->siteId($element->siteId)
                ->indexBy('id')
                ->all();
        } else {
            $oldRowsById = [];
        }

        $rows = [];
        $prevRow = null;

        $fieldNamespace = $this->getNamespace();
        $baseRowFieldNamespace = $fieldNamespace ? "{$fieldNamespace}.{$this->handle}" : null;

        // Because we have to have our row template as HTML due to Vue3 support (not in a `script` tag)
        // it unfortunately gets submitted as content for the field. We need to filter out - its invalid.
        if (isset($value['rows']) && is_array($value['rows'])) {
            foreach ($value['rows'] as $k => $v) {
                if ($k === '__ROW__') {
                    unset($value['rows'][$k]);
                }
            }
        }

        if (isset($value['sortOrder']) && is_array($value['sortOrder'])) {
            foreach ($value['sortOrder'] as $k => $v) {
                if ($v === '__ROW__') {
                    unset($value['sortOrder'][$k]);
                }
            }
        }

        // Was the value posted in the new (delta) format?
        if (isset($value['rows']) || isset($value['sortOrder'])) {
            $newRowData = $value['rows'] ?? [];
            $newSortOrder = $value['sortOrder'] ?? array_keys($oldRowsById);
            if ($baseRowFieldNamespace) {
                $baseRowFieldNamespace .= '.rows';
            }
        } else {
            $newRowData = $value;
            $newSortOrder = array_keys($value);
        }

        foreach ($newSortOrder as $rowId) {
            if (isset($newRowData[$rowId])) {
                $rowData = $newRowData[$rowId];
            } else if (
                isset(Elements::$duplicatedElementSourceIds[$rowId]) &&
                isset($newRowData[Elements::$duplicatedElementSourceIds[$rowId]])
            ) {
                // $rowId is a duplicated row's ID, but the data was sent with the original row ID
                $rowData = $newRowData[Elements::$duplicatedElementSourceIds[$rowId]];
            } else {
                $rowData = [];
            }

            // If this is a preexisting row but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                strpos($rowId, 'new') !== 0 &&
                !isset($oldRowsById[$rowId]) &&
                isset(Elements::$duplicatedElementIds[$rowId]) &&
                isset($oldRowsById[Elements::$duplicatedElementIds[$rowId]])
            ) {
                $rowId = Elements::$duplicatedElementIds[$rowId];
            }

            // Existing row?
            if (isset($oldRowsById[$rowId])) {
                $row = $oldRowsById[$rowId];
                $row->dirty = !empty($rowData);
            } else {
                $row = new NestedFieldRow();
                $row->fieldId = $this->id;
                $row->ownerId = $element->id;
                $row->siteId = $element->siteId;
            }

            $row->setOwner($element);

            // Set the content post location on the row if we can
            if ($baseRowFieldNamespace) {
                $row->setFieldParamNamespace("{$baseRowFieldNamespace}.{$rowId}.fields");
            }

            if (isset($rowData['fields'])) {
                $row->setFieldValues($rowData['fields']);
            }

            // Set the prev/next rows
            if ($prevRow) {
                /** @var ElementInterface $prevRow */
                $prevRow->setNext($row);
                /** @var ElementInterface $row */
                $row->setPrev($prevRow);
            }

            $prevRow = $row;
            $rows[] = $row;
        }

        return $rows;
    }
}

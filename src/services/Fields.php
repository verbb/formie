<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyExistingFieldsEvent;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldRowConfigEvent;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\feedme\elementfields as FeedMeElementField;
use verbb\formie\integrations\feedme\fields as FeedMeField;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\records\FieldLayout as FieldLayoutRecord;
use verbb\formie\records\FieldLayoutPage as FieldLayoutPageRecord;
use verbb\formie\records\FieldLayoutRow as FieldLayoutRowRecord;
use verbb\formie\records\Field as FieldRecord;

use Craft;
use craft\base\Component;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\fields\BaseRelationField;
use craft\fields\PlainText;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\validators\HandleValidator;

use Exception;
use ReflectionClass;
use ReflectionException;
use Throwable;

use yii\base\InvalidConfigException;

class Fields extends Component
{
    // Static Methods
    // =========================================================================

    public static function getFieldHandles(): array
    {
        // Just in case this fires too early in another plugin migration
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_FIELDS)) {
            return [];
        }

        // Maintain a cache of all Formie field handles, because we can't rely on Craft's customFields behaviour.
        return Craft::$app->getCache()->getOrSet('formie:fieldHandles', function() {
            return (new Query())->select(['handle', 'uid'])->from(Table::FORMIE_FIELDS)->indexBy('uid')->column();
        });
    }

    public static function resetFieldHandles(): void
    {
        Craft::$app->getCache()->delete('formie:fieldHandles');
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_EXISTING_FIELDS = 'modifyExistingFields';
    public const EVENT_BEFORE_SAVE_FIELD_ROW = 'beforeSaveFieldRow';
    public const EVENT_AFTER_SAVE_FIELD_ROW = 'afterSaveFieldRow';
    public const EVENT_BEFORE_SAVE_FIELD_PAGE = 'beforeSaveFieldPage';
    public const EVENT_AFTER_SAVE_FIELD_PAGE = 'afterSaveFieldPage';

    public const EVENT_REGISTER_FIELDS = 'registerFields';
    public const EVENT_REGISTER_LABEL_POSITIONS = 'registerLabelPositions';
    public const EVENT_REGISTER_INSTRUCTIONS_POSITIONS = 'registerInstructionsPositions';


    // Properties
    // =========================================================================

    private array $_layouts = [];
    private array $_fields = [];
    private array $_fieldsForForm = [];
    private array $_registeredFields = [];
    private array $_registeredFieldTypes = [];
    private array $_existingFields = [];


    // Public Methods
    // =========================================================================

    public function getRegisteredFieldTypes(bool $excludeDisabled = true): array
    {
        if (count($this->_registeredFieldTypes)) {
            return $this->_registeredFieldTypes;
        }

        $fieldTypes = [
            formiefields\Address::class,
            formiefields\Agree::class,
            formiefields\Calculations::class,
            formiefields\Categories::class,
            formiefields\Checkboxes::class,
            formiefields\Date::class,
            formiefields\Dropdown::class,
            formiefields\Email::class,
            formiefields\Entries::class,
            formiefields\FileUpload::class,
            formiefields\Group::class,
            formiefields\Heading::class,
            formiefields\Hidden::class,
            formiefields\Html::class,
            formiefields\MissingField::class,
            formiefields\MultiLineText::class,
            formiefields\Name::class,
            formiefields\Number::class,
            formiefields\Payment::class,
            formiefields\Password::class,
            formiefields\Phone::class,
            formiefields\Radio::class,
            formiefields\Recipients::class,
            formiefields\Repeater::class,
            formiefields\Section::class,
            formiefields\Signature::class,
            formiefields\SingleLineText::class,
            formiefields\Summary::class,
            formiefields\Table::class,
            formiefields\Tags::class,

            // Include sub-fields, despite them not being able to be added at top-level
            formiefields\subfields\AddressAutoComplete::class,
            formiefields\subfields\Address1::class,
            formiefields\subfields\Address2::class,
            formiefields\subfields\Address3::class,
            formiefields\subfields\AddressCity::class,
            formiefields\subfields\AddressZip::class,
            formiefields\subfields\AddressState::class,
            formiefields\subfields\AddressCountry::class,
            formiefields\subfields\DateDate::class,
            formiefields\subfields\DateTime::class,
            formiefields\subfields\DateYearDropdown::class,
            formiefields\subfields\DateMonthDropdown::class,
            formiefields\subfields\DateDayDropdown::class,
            formiefields\subfields\DateHourDropdown::class,
            formiefields\subfields\DateMinuteDropdown::class,
            formiefields\subfields\DateSecondDropdown::class,
            formiefields\subfields\DateAmPmDropdown::class,
            formiefields\subfields\DateYearNumber::class,
            formiefields\subfields\DateMonthNumber::class,
            formiefields\subfields\DateDayNumber::class,
            formiefields\subfields\DateHourNumber::class,
            formiefields\subfields\DateMinuteNumber::class,
            formiefields\subfields\DateSecondNumber::class,
            formiefields\subfields\DateAmPmNumber::class,
            formiefields\subfields\NamePrefix::class,
            formiefields\subfields\NameFirst::class,
            formiefields\subfields\NameMiddle::class,
            formiefields\subfields\NameLast::class,
        ];

        if (Craft::$app->getEdition() !== Craft::Solo) {
            $fieldTypes = array_merge($fieldTypes, [
                formiefields\Users::class,
            ]);
        }

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $fieldTypes = array_merge($fieldTypes, [
                formiefields\Products::class,
                formiefields\Variants::class,
            ]);
        }

        return $this->_registeredFieldTypes = $fieldTypes;
    }

    public function getRegisteredFields(bool $excludeDisabled = true): array
    {
        if (count($this->_registeredFields)) {
            return $this->_registeredFields;
        }

        $settings = Formie::$plugin->getSettings();
        $disabledFields = $settings->disabledFields;

        $fieldTypes = $this->getRegisteredFieldTypes($excludeDisabled);

        $event = new RegisterFieldsEvent([
            'fields' => $fieldTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_FIELDS, $event);

        // Missing Field cannot be removed
        $event->fields[] = formiefields\MissingField::class;
        $event->fields = array_unique($event->fields);

        foreach ($event->fields as $class) {
            // Check against plugin settings whether to exclude or not
            if ($excludeDisabled && in_array($class, $disabledFields)) {
                continue;
            }

            $this->_registeredFields[$class] = new $class;
        }

        return $this->_registeredFields;
    }

    public function getFieldsByType(string $typeClass): array
    {
        $fields = $this->getRegisteredFieldTypes();

        return array_values(array_filter($fields, function(string $fieldClass) use ($typeClass) {
            return is_subclass_of($fieldClass, $typeClass);
        }));
    }

    public function getFormBuilderFieldTypes(): array
    {
        $registeredFields = $this->getRegisteredFields();

        $internalFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\MissingField::class),
        ]);

        $basicFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\SingleLineText::class),
            ArrayHelper::remove($registeredFields, formiefields\MultiLineText::class),
            ArrayHelper::remove($registeredFields, formiefields\Name::class),
            ArrayHelper::remove($registeredFields, formiefields\Email::class),
            ArrayHelper::remove($registeredFields, formiefields\Phone::class),
            ArrayHelper::remove($registeredFields, formiefields\Number::class),
        ]);

        $optionFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\Radio::class),
            ArrayHelper::remove($registeredFields, formiefields\Checkboxes::class),
            ArrayHelper::remove($registeredFields, formiefields\Dropdown::class),
            ArrayHelper::remove($registeredFields, formiefields\Agree::class),
        ]);

        $advancedFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\Date::class),
            ArrayHelper::remove($registeredFields, formiefields\Address::class),
            ArrayHelper::remove($registeredFields, formiefields\FileUpload::class),
            ArrayHelper::remove($registeredFields, formiefields\Password::class),
            ArrayHelper::remove($registeredFields, formiefields\Hidden::class),
            ArrayHelper::remove($registeredFields, formiefields\Recipients::class),
            ArrayHelper::remove($registeredFields, formiefields\Signature::class),
            ArrayHelper::remove($registeredFields, formiefields\Calculations::class),
            ArrayHelper::remove($registeredFields, formiefields\Payment::class),
        ]);

        $dynamicFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\Repeater::class),
            ArrayHelper::remove($registeredFields, formiefields\Group::class),
            ArrayHelper::remove($registeredFields, formiefields\Table::class),
        ]);

        $cosmeticFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\Heading::class),
            ArrayHelper::remove($registeredFields, formiefields\Section::class),
            ArrayHelper::remove($registeredFields, formiefields\Html::class),
            ArrayHelper::remove($registeredFields, formiefields\Summary::class),
        ]);

        $elementFields = array_filter([
            ArrayHelper::remove($registeredFields, formiefields\Entries::class),
            ArrayHelper::remove($registeredFields, formiefields\Categories::class),
            ArrayHelper::remove($registeredFields, formiefields\Tags::class),
        ]);

        if (Craft::$app->getEdition() === Craft::Pro) {
            $elementFields = array_merge($elementFields, array_filter([
                ArrayHelper::remove($registeredFields, formiefields\Users::class),
            ]));
        }

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $elementFields = array_merge($elementFields, array_filter([
                ArrayHelper::remove($registeredFields, formiefields\Products::class),
                ArrayHelper::remove($registeredFields, formiefields\Variants::class),
            ]));
        }

        $groupedFields = [];

        if ($internalFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Internal'),
                'handle' => 'internal',
                'fields' => array_values($internalFields),
            ];
        }

        if ($basicFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Basic Fields'),
                'handle' => 'basic',
                'fields' => array_values($basicFields),
            ];
        }

        if ($optionFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Option Fields'),
                'handle' => 'option',
                'fields' => array_values($optionFields),
            ];
        }

        if ($advancedFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Advanced Fields'),
                'handle' => 'advanced',
                'fields' => array_values($advancedFields),
            ];
        }

        if ($dynamicFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Dynamic Fields'),
                'handle' => 'dynamic',
                'fields' => array_values($dynamicFields),
            ];
        }

        if ($cosmeticFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Cosmetic Fields'),
                'handle' => 'cosmetic',
                'fields' => array_values($cosmeticFields),
            ];
        }

        if ($elementFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Element Fields'),
                'handle' => 'element',
                'fields' => array_values($elementFields),
            ];
        }

        // Any custom fields
        if ($registeredFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Custom Fields'),
                'handle' => 'custom',
                'fields' => array_values($registeredFields),
            ];
        }

        foreach ($groupedFields as $groupKey => $group) {
            foreach ($group['fields'] as $fieldKey => $class) {
                $groupedFields[$groupKey]['fields'][$fieldKey] = $class->getFieldTypeConfig();
            }
        }

        return $groupedFields;
    }

    public function getRegisteredFormieFields(): array
    {
        $fields = [];

        $fields[] = FeedMeField\Address::class;
        $fields[] = FeedMeField\Agree::class;
        $fields[] = FeedMeField\Categories::class;
        $fields[] = FeedMeField\Checkboxes::class;
        $fields[] = FeedMeField\Date::class;
        $fields[] = FeedMeField\Dropdown::class;
        $fields[] = FeedMeField\Email::class;
        $fields[] = FeedMeField\Entries::class;
        $fields[] = FeedMeField\FileUpload::class;
        $fields[] = FeedMeField\Group::class;
        $fields[] = FeedMeField\Hidden::class;
        $fields[] = FeedMeField\MultiLineText::class;
        $fields[] = FeedMeField\Name::class;
        $fields[] = FeedMeField\Number::class;
        $fields[] = FeedMeField\Password::class;
        $fields[] = FeedMeField\Phone::class;
        $fields[] = FeedMeField\Radio::class;
        $fields[] = FeedMeField\Repeater::class;
        $fields[] = FeedMeField\SingleLineText::class;
        $fields[] = FeedMeField\Table::class;
        $fields[] = FeedMeField\Tags::class;

        if (Craft::$app->getEdition() !== Craft::Solo) {
            $fields[] = FeedMeField\Users::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $fields[] = FeedMeField\Products::class;
            $fields[] = FeedMeField\Variants::class;
        }

        // Include Formie's element fields
        $fields[] = FeedMeElementField\Forms::class;

        return $fields;
    }

    public function getExistingFields(Form $excludeForm = null): array
    {
        if ($this->_existingFields) {
            return $this->_existingFields;
        }

        $query = Form::find()->orderBy('title ASC');

        // Exclude the current form.
        if ($excludeForm) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();

        $allFields = [];
        $existingFields = [];

        foreach ($forms as $form) {
            $formPages = [];

            foreach ($form->getPages() as $page) {
                $pageFields = [];

                $fields = $page->getFields();
                ArrayHelper::multisort($fields, 'label', SORT_ASC, SORT_STRING);

                foreach ($fields as $field) {
                    // Only include one instance of a synced field.
                    if ($field->isSynced && ArrayHelper::contains($allFields, 'id', $field->id)) {
                        continue;
                    }

                    $pageFields[] = $allFields[] = $field->getFormBuilderConfig();
                }

                $formPages[] = [
                    'label' => $page->label,
                    'fields' => $pageFields,
                ];
            }

            $existingFields[] = [
                'key' => $form->handle,
                'label' => $form->title,
                'pages' => $formPages,
            ];
        }

        ArrayHelper::multisort($allFields, 'label', SORT_ASC, SORT_STRING);

        array_unshift($existingFields, [
            'key' => '*',
            'label' => Craft::t('formie', 'All forms'),
            'pages' => [
                [
                    'label' => Craft::t('formie', 'All fields'),
                    'fields' => $allFields,
                ],
            ],
        ]);

        // Fire a 'modifyExistingFields' event
        $event = new ModifyExistingFieldsEvent([
            'fields' => $existingFields,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXISTING_FIELDS, $event);

        return $this->_existingFields = $event->fields;
    }

    public function createField(array $config = []): FieldInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        // If already a `MissingField` (typically serialized in stencil), convert back
        if ($config['type'] === formiefields\MissingField::class) {
            $config = [
                'type' => $config['settings']['expectedType'],
                'settings' => $config['settings']['settings'] ?? [],
            ];
        }

        try {
            $field = ComponentHelper::createComponent($config, FieldInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $field = new formiefields\MissingField($config);
        }

        $field->afterCreateField($config);

        return $field;
    }

    public function getAllLayouts(): array
    {
        if ($this->_layouts) {
            return $this->_layouts;
        }

        $layouts = [];

        $layoutIds = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_FIELD_LAYOUTS)
            ->column();

        foreach ($layoutIds as $layoutId) {
            $layouts[] = $this->getLayoutById($layoutId);
        }

        return $this->_layouts = $layouts;
    }

    public function getAllFields(): array
    {
        if ($this->_fields) {
            return $this->_fields;
        }

        $fields = [];

        $fieldRecords = (new Query())->from(Table::FORMIE_FIELDS)->all();

        foreach ($fieldRecords as $fieldRecord) {
            $fields[] = Formie::$plugin->getFields()->createField($fieldRecord);
        }

        return $this->_fields = $fields;
    }

    public function getAllFieldsForForm(int $formId): array
    {
        if (isset($this->_fieldsForForm[$formId])) {
            return $this->_fieldsForForm[$formId];
        }

        $fields = [];

        $fieldRecords = (new Query())
            ->select(['f.*'])
            ->from(['f' => Table::FORMIE_FIELDS])
            ->innerJoin(['ff' => Table::FORMIE_FORMS], '[[f.layoutId]] = [[ff.layoutId]]')
            ->where(['ff.id' => $formId])
            ->all();

        foreach ($fieldRecords as $fieldRecord) {
            $fields[] = Formie::$plugin->getFields()->createField($fieldRecord);
        }

        return $this->_fieldsForForm[$formId] = $fields;
    }

    public function getLayoutById(int $id): ?FieldLayout
    {
        return $this->_getLayout(['l.id' => $id]);
    }

    public function getPageById(int $id): ?FieldLayoutPage
    {
        return $this->_getPage(['p.id' => $id]);
    }

    public function getRowById(int $id): ?FieldLayoutRow
    {
        return $this->_getRow(['r.id' => $id]);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        $fieldRecord = FieldRecord::findOne(['id' => $id])?->attributes ?? [];

        return $fieldRecord ? $this->createField($fieldRecord) : null;
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $fieldRecord = FieldRecord::findOne(['handle' => $handle])?->attributes ?? [];

        return $fieldRecord ? $this->createField($fieldRecord) : null;
    }

    public function saveLayout(FieldLayout $layout): bool
    {
        $isNewLayout = !$layout->id;

        if (!$layout->beforeSave($isNewLayout)) {
            return false;
        }

        if (!$layout->validate()) {
            return false;
        }

        if (!$isNewLayout) {
            $layoutRecord = FieldLayoutRecord::find()
                ->andWhere(['id' => $layout->id])
                ->one();

            if (!$layoutRecord) {
                throw new Exception('Invalid field layout ID: ' . $layout->id);
            }
        } else {
            $layoutRecord = new FieldLayoutRecord();
        }

        $layoutRecord->id = $layout->id;

        $layoutRecord->save(false);

        $layout->id = $layoutRecord->id;

        $layout->afterSave($isNewLayout);

        // Use a transaction to ensure we don't have any records unless the entire layout succeeds
        $transaction = Craft::$app->getDb()->beginTransaction();

        // Use `unserialize/serialize` instead of `clone()` to deeply clone objects.
        $previousPages = unserialize(serialize($layout->getPages()));

        foreach ($layout->getPages() as $pageKey => $page) {
            $page->layoutId = $layout->id;
            $page->sortOrder = $pageKey;
            
            if (!$this->savePage($page)) {
                $transaction->rollBack();

                // We also need to reset attributes on modules back to what they were. For example, one row and field
                // might validate and save correctly, but another fails. The transaction will prevent the records from saving
                // being all-or-nothing, but the IDs on the models will be updated. They will not reflect saved records anymore.
                // We then need to pluck any errors set on each model and insert back into the previous models
                foreach ($previousPages as $pageKey => $page) {
                    $currentPage = $layout->getPages()[$pageKey] ?? null;
                    $currentPageErrors = $currentPage->errors ?? [];

                    if ($currentPageErrors) {
                        $page->addErrors($currentPageErrors);
                    }

                    foreach ($page->getRows() as $rowKey => $row) {
                        $currentRow = $currentPage->getRows()[$rowKey] ?? null;
                        $currentRowErrors = $currentRow->errors ?? [];

                        if ($currentPageErrors) {
                            $row->addErrors($currentRowErrors);

                            // Bubble-up the validation errors
                            $page->addError('rows', $currentRowErrors);
                        }

                        foreach ($row->getFields() as $fieldKey => $field) {
                            $currentField = $currentRow->getFields()[$fieldKey] ?? null;
                            $currentFieldErrors = $currentField->errors ?? [];

                            if ($currentFieldErrors) {
                                $field->addErrors($currentFieldErrors);

                                // Bubble-up the validation errors
                                $row->addError('fields', $currentFieldErrors);
                                $page->addError('rows', $currentFieldErrors);
                            }
                        }
                    }
                }

                $layout->setPages($previousPages);

                return false;
            }

            $newPageIds[] = $page->id;
        }

        $transaction->commit();

        // Cleanup any deleted pages/rows/fields. Done here as we need to wait until everything is processed.
        if ($deletedItems = $layout->getDeletedItems()) {
            foreach (($deletedItems['fields'] ?? []) as $id) {
                $this->deleteFieldById($id);
            }

            foreach (($deletedItems['rows'] ?? []) as $id) {
                $this->deleteRowById($id);
            }

            foreach (($deletedItems['pages'] ?? []) as $id) {
                $this->deletePageById($id);
            }
        }

        return true;
    }
    
    public function deleteLayoutById(int $id): bool
    {
        $layout = $this->getLayoutById($id);

        if (!$layout) {
            return false;
        }

        return $this->deleteLayout($layout);
    }
    
    public function deleteLayout(FieldLayout $layout): bool
    {
        if (!$layout->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUTS, ['id' => $layout->id]);

        $layout->afterDelete();

        return true;
    }

    public function savePage(FieldLayoutPage $page): bool
    {
        $isNewPage = !$page->id;

        if (!$page->beforeSave($isNewPage)) {
            return false;
        }

        if (!$page->validate()) {
            return false;
        }

        if (!$isNewPage) {
            $pageRecord = FieldLayoutPageRecord::find()
                ->andWhere(['id' => $page->id])
                ->one();

            if (!$pageRecord) {
                throw new Exception('Invalid field page ID: ' . $page->id);
            }
        } else {
            $pageRecord = new FieldLayoutPageRecord();
        }

        $pageRecord->id = $page->id;
        $pageRecord->layoutId = $page->layoutId;
        $pageRecord->label = $page->label;
        $pageRecord->sortOrder = $page->sortOrder;
        $pageRecord->settings = $page->getSettings();

        $pageRecord->save(false);

        $page->id = $pageRecord->id;

        $page->afterSave($isNewPage);

        foreach ($page->getRows() as $rowKey => $row) {
            $row->layoutId = $page->layoutId;
            $row->pageId = $page->id;
            $row->sortOrder = $rowKey;

            if (!$this->saveRow($row)) {
                return false;
            }
        }

        return true;
    }
    
    public function deletePageById(int $id): bool
    {
        $page = $this->getPageById($id);

        if (!$page) {
            return false;
        }

        return $this->deletePage($page);
    }
    
    public function deletePage(FieldLayoutPage $page): bool
    {
        if (!$page->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUT_PAGES, ['id' => $page->id]);

        $page->afterDelete();

        return true;
    }

    public function saveRow(FieldLayoutRow $row): bool
    {
        $isNewRow = !$row->id;

        if (!$row->beforeSave($isNewRow)) {
            return false;
        }

        if (!$row->validate()) {
            return false;
        }

        if (!$isNewRow) {
            $rowRecord = FieldLayoutRowRecord::find()
                ->andWhere(['id' => $row->id])
                ->one();

            if (!$rowRecord) {
                throw new Exception('Invalid field row ID: ' . $row->id);
            }
        } else {
            $rowRecord = new FieldLayoutRowRecord();
        }

        $rowRecord->id = $row->id;
        $rowRecord->layoutId = $row->layoutId;
        $rowRecord->pageId = $row->pageId;
        $rowRecord->sortOrder = $row->sortOrder;

        $rowRecord->save(false);

        $row->id = $rowRecord->id;

        $row->afterSave($isNewRow);

        foreach ($row->getFields() as $fieldKey => $field) {
            $field->layoutId = $row->layoutId;
            $field->pageId = $row->pageId;
            $field->rowId = $row->id;
            $field->sortOrder = $fieldKey;

            if (!$this->saveField($field)) {
                return false;
            }
        }

        return true;
    }

    public function deleteRowById(int $id): bool
    {
        $row = $this->getRowById($id);

        if (!$row) {
            return false;
        }

        return $this->deleteRow($row);
    }
    
    public function deleteRow(FieldLayoutRow $row): bool
    {
        if (!$row->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUT_ROWS, ['id' => $row->id]);

        $row->afterDelete();

        return true;
    }

    public function saveField(Field $field, bool $updateSyncedFields = true): bool
    {
        $isNewField = !$field->id;

        if (!$field->beforeSave($isNewField)) {
            return false;
        }

        if (!$field->validate()) {
            return false;
        }

        if (!$isNewField) {
            $fieldRecord = FieldRecord::find()
                ->andWhere(['id' => $field->id])
                ->one();

            if (!$fieldRecord) {
                throw new Exception('Invalid field ID: ' . $field->id);
            }
        } else {
            $fieldRecord = new FieldRecord();
        }

        $fieldRecord->id = $field->id;
        $fieldRecord->layoutId = $field->layoutId;
        $fieldRecord->pageId = $field->pageId;
        $fieldRecord->rowId = $field->rowId;
        $fieldRecord->syncId = $field->syncId;
        $fieldRecord->label = $field->label;
        $fieldRecord->handle = $field->handle;
        $fieldRecord->type = $field->type;
        $fieldRecord->sortOrder = $field->sortOrder;
        $fieldRecord->settings = $field->settings;

        // Check if this is a missing field, and swap back its type. 
        // This can commonly happen during a migration, not really from normal use.
        if ($field instanceof formiefields\MissingField) {
            $fieldRecord->type = $field->expectedType;
        }

        $fieldRecord->save(false);

        $field->id = $fieldRecord->id;
        $field->uid = $fieldRecord->uid;

        $field->afterSave($isNewField);

        // For any synced fields, we should update them all. Add behind a flag to ensure when we call `saveField()`
        // again for the synced fields, we don't end up in a loop.
        if ($updateSyncedFields && $field->getIsSynced()) {
            $this->updateSyncedFields($field);
        }

        Fields::resetFieldHandles();

        return true;
    }
    
    public function deleteFieldById(int $id): bool
    {
        $field = $this->getFieldById($id);

        if (!$field) {
            return false;
        }

        return $this->deleteField($field);
    }
    
    public function deleteField(Field $field): bool
    {
        if (!$field->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELDS, ['id' => $field->id]);

        $field->afterDelete();

        return true;
    }

    public function updateSyncedFields(Field $field): bool
    {
        // Do a direct update on the source field for this sync, to ensure it's synced too
        Db::update(Table::FORMIE_FIELDS, ['syncId' => $field->syncId], ['id' => $field->syncId]);

        // Get all instances of the syncs, whether the source or destination
        $fieldIds = (new Query())
            ->select('id')
            ->from(Table::FORMIE_FIELDS)
            ->where(['syncId' => $field->syncId])
            ->column();

        // Exclude _this_ field, as we've just saved it already
        unset($fieldIds[array_search($field->id, $fieldIds)]);

        foreach ($fieldIds as $fieldId) {
            // Update the settings from this field to be synced
            $syncedField = $this->getFieldById($fieldId);

            if ($syncedField) {
                $settings = [
                    'label' => $field->label,
                    'handle' => $field->handle,
                    ...$field->getSettings(),
                ];

                // Remove some attributes that shouldn't be synced
                unset($settings['required']);

                $syncedField->setAttributes($settings, false);

                // Saved the synced field, but be careful not to case a loop
                $this->saveField($syncedField, false);
            }
        }

        return true;
    }

    public function checkRequiredPlugin(FieldInterface $field): bool
    {
        if (!method_exists($field, 'getRequiredPlugins')) {
            throw new MissingComponentException();
        }

        foreach ($field::getRequiredPlugins() as $requiredPlugin) {
            $version = $requiredPlugin['version'] ?? 0;
            $handle = $requiredPlugin['handle'] ?? '';

            if ($handle) {
                if (!Plugin::isPluginInstalledAndEnabled($handle)) {
                    throw new MissingComponentException();
                }

                $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                if (version_compare($plugin->getVersion(), $version, '<')) {
                    throw new MissingComponentException();
                }
            }
        }

        return true;
    }

    public function getFieldOptions(FieldInterface $field, array $options = null): array
    {
        if (empty($options)) {
            return [];
        }

        /* @var Field $field */
        $allFieldOptions = $options['fields'] ?? [];
        $fieldOptions = $allFieldOptions[$field->handle] ?? [];

        if (isset($allFieldOptions['*'])) {
            $fieldOptions = ArrayHelper::merge($allFieldOptions['*'], $fieldOptions);
        }

        return $fieldOptions;
    }

    public function getLabelPositions(FieldInterface $field = null): array
    {
        $labelPositions = [
            AboveInput::class,
            BelowInput::class,
            LeftInput::class,
            RightInput::class,
            HiddenPosition::class,
        ];

        $event = new RegisterFieldOptionsEvent([
            'field' => $field,
            'options' => $labelPositions,
        ]);
        $this->trigger(self::EVENT_REGISTER_LABEL_POSITIONS, $event);

        if ($field) {
            $supportedPositions = [];

            foreach ($event->options as $class) {
                if ($class::supports($field)) {
                    $supportedPositions[] = $class;
                }
            }

            return $supportedPositions;
        }

        return $event->options;
    }

    public function getLabelPositionsOptions(FieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getLabelPositions($field));
    }

    public function getInstructionsPositions(FieldInterface $field = null): array
    {
        $instructionsPositions = [
            AboveInput::class,
            BelowInput::class,
        ];

        $event = new RegisterFieldOptionsEvent([
            'field' => $field,
            'options' => $instructionsPositions,
        ]);
        $this->trigger(self::EVENT_REGISTER_INSTRUCTIONS_POSITIONS, $event);

        if ($field) {
            $supportedPositions = [];

            foreach ($event->options as $class) {
                if ($class::supports($field)) {
                    $supportedPositions[] = $class;
                }
            }

            return $supportedPositions;
        }

        return $event->options;
    }

    public function getInstructionsPositionsOptions(FieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getInstructionsPositions($field));
    }

    public function getReservedHandles(): array
    {
        return (new formiefields\SingleLineText())->getReservedHandles();
    }


    // Private Methods
    // =========================================================================

    private function _getLayoutQuerySelect(): array
    {
        return [
            'l.id as layoutId',
            'l.dateCreated as layoutDateCreated',
            'l.dateUpdated as layoutDateUpdated',
            'l.uid as layoutUid',
        ];
    }

    private function _getPageQuerySelect(): array
    {
        return [
            'p.id as pageId',
            'p.layoutId as pageLayoutId',
            'p.label as pageLabel',
            'p.sortOrder as pageSortOrder',
            'p.settings as pageSettings',
            'p.dateCreated as pageDateCreated',
            'p.dateUpdated as pageDateUpdated',
            'p.uid as pageUid',
        ];
    }

    private function _getRowQuerySelect(): array
    {
        return [
            'r.id as rowId',
            'r.layoutId as rowLayoutId',
            'r.pageId as rowPageId',
            'r.sortOrder as rowSortOrder',
            'r.dateCreated as rowDateCreated',
            'r.dateUpdated as rowDateUpdated',
            'r.uid as rowUid',
        ];
    }

    private function _getFieldQuerySelect(): array
    {
        return [
            'f.id as fieldId',
            'f.layoutId as fieldLayoutId',
            'f.pageId as fieldPageId',
            'f.rowId as fieldRowId',
            'f.syncId as fieldSyncId',
            'f.label as fieldLabel',
            'f.handle as fieldHandle',
            'f.type as fieldType',
            'f.sortOrder as fieldSortOrder',
            'f.settings as fieldSettings',
            'f.dateCreated as fieldDateCreated',
            'f.dateUpdated as fieldDateUpdated',
            'f.uid as fieldUid',
        ];
    }

    private function _getPopulatedPage(array $item): array
    {
        return [
            'id' => $item['pageId'],
            'layoutId' => $item['pageLayoutId'],
            'label' => $item['pageLabel'],
            'settings' => $item['pageSettings'],
            'sortOrder' => $item['pageSortOrder'],
            'dateCreated' => $item['pageDateCreated'],
            'dateUpdated' => $item['pageDateUpdated'],
            'uid' => $item['pageUid'],
            'rows' => [],
        ];
    }

    private function _getPopulatedRow(array $item): array
    {
        return [
            'id' => $item['rowId'],
            'layoutId' => $item['rowLayoutId'],
            'pageId' => $item['rowPageId'],
            'sortOrder' => $item['rowSortOrder'],
            'dateCreated' => $item['rowDateCreated'],
            'dateUpdated' => $item['rowDateUpdated'],
            'uid' => $item['rowUid'],
            'fields' => [],
        ];
    }

    private function _getPopulatedField(array $item): array
    {
        return [
            'id' => $item['fieldId'],
            'layoutId' => $item['fieldLayoutId'],
            'pageId' => $item['fieldPageId'],
            'rowId' => $item['fieldRowId'],
            'syncId' => $item['fieldSyncId'],
            'label' => $item['fieldLabel'],
            'handle' => $item['fieldHandle'],
            'type' => $item['fieldType'],
            'settings' => $item['fieldSettings'],
            'sortOrder' => $item['fieldSortOrder'],
            'dateCreated' => $item['fieldDateCreated'],
            'dateUpdated' => $item['fieldDateUpdated'],
            'uid' => $item['fieldUid'],
        ];
    }

    private function _getLayout(array $params): ?FieldLayout
    {
        $layoutData = [];

        // Do a single query here for everything, for performance, then cleanup due to lack of
        // MySQL being able to prefix tables nicely and get a nested structure.
        $dataItems = (new Query())
            ->select([
                ...$this->_getLayoutQuerySelect(),
                ...$this->_getPageQuerySelect(),
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['l' => Table::FORMIE_FIELD_LAYOUTS])
            ->leftJoin(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES], '[[p.layoutId]] = [[l.id]]')
            ->leftJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.pageId]] = [[p.id]]')
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.rowId]] = [[r.id]]')
            ->where($params)
            ->orderBy([
                'p.sortOrder' => SORT_ASC,
                'r.sortOrder' => SORT_ASC,
                'f.sortOrder' => SORT_ASC,
            ])
            ->all();

        $pages = [];
        $rows = [];
        $fields = [];

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutData['id'] = $item['layoutId'];
                $layoutData['dateCreated'] = $item['layoutDateCreated'];
                $layoutData['dateUpdated'] = $item['layoutDateUpdated'];
                $layoutData['uid'] = $item['layoutUid'];

                $pageId = $item['pageId'];
                $rowId = $item['rowId'];
                $fieldId = $item['fieldId'];

                if ($pageId && !isset($pages[$pageId])) {
                    $pages[$pageId] = $this->_getPopulatedPage($item);
                }

                if ($rowId && !isset($rows[$rowId])) {
                    $rows[$rowId] = $this->_getPopulatedRow($item);
                }

                if ($fieldId && !isset($fields[$fieldId])) {
                    $fields[$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        // Stitch pages/rows/fields together into a single nested array
        foreach ($fields as $field) {
            $rowId = $field['rowId'];

            if (isset($rows[$rowId])) {
                $rows[$rowId]['fields'][] = $field;
            }
        }

        foreach ($rows as $row) {
            $pageId = $row['pageId'];

            if (isset($pages[$pageId])) {
                $pages[$pageId]['rows'][] = $row;
            }
        }

        $layoutData['pages'] = array_values($pages);

        return $layoutData ? new FieldLayout($layoutData) : null;
    }

    private function _getPage(array $params): ?FieldLayoutPage
    {
        $layoutData = [];

        // Do a single query here for everything, for performance, then cleanup due to lack of
        // MySQL being able to prefix tables nicely and get a nested structure.
        $dataItems = (new Query())
            ->select([
                ...$this->_getPageQuerySelect(),
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES])
            ->leftJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.pageId]] = [[p.id]]')
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.rowId]] = [[r.id]]')
            ->where($params)
            ->orderBy([
                'p.sortOrder' => SORT_ASC,
                'r.sortOrder' => SORT_ASC,
                'f.sortOrder' => SORT_ASC,
            ])
            ->all();

        $rows = [];
        $fields = [];

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutData = $this->_getPopulatedPage($item);

                $rowId = $item['rowId'];
                $fieldId = $item['fieldId'];

                if ($rowId && !isset($rows[$rowId])) {
                    $rows[$rowId] = $this->_getPopulatedRow($item);
                }

                if ($fieldId && !isset($fields[$fieldId])) {
                    $fields[$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        // Stitch pages/rows/fields together into a single nested array
        foreach ($fields as $field) {
            $rowId = $field['rowId'];

            if (isset($rows[$rowId])) {
                $rows[$rowId]['fields'][] = $field;
            }
        }

        $layoutData['rows'] = array_values($rows);

        return $layoutData ? new FieldLayoutPage($layoutData) : null;
    }

    private function _getRow(array $params): ?FieldLayoutRow
    {
        $layoutData = [];

        // Do a single query here for everything, for performance, then cleanup due to lack of
        // MySQL being able to prefix tables nicely and get a nested structure.
        $dataItems = (new Query())
            ->select([
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS])
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.rowId]] = [[r.id]]')
            ->where($params)
            ->orderBy([
                'r.sortOrder' => SORT_ASC,
                'f.sortOrder' => SORT_ASC,
            ])
            ->all();

        $fields = [];

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutData = $this->_getPopulatedRow($item);

                $fieldId = $item['fieldId'];

                if ($fieldId && !isset($fields[$fieldId])) {
                    $fields[$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        $layoutData['fields'] = array_values($fields);

        return $layoutData ? new FieldLayoutRow($layoutData) : null;
    }
}

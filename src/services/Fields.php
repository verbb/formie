<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\FieldPageEvent;
use verbb\formie\events\FieldRowEvent;
use verbb\formie\events\ModifyExistingFieldsEvent;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldRowConfigEvent;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\fields\formfields;
use verbb\formie\integrations\feedme\fields as FeedMeField;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\FieldsetEnd;
use verbb\formie\positions\FieldsetStart;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\records\PageSettings;
use verbb\formie\records\Row;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\errors\MissingComponentException;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\validators\HandleValidator;

use ReflectionClass;
use ReflectionException;

use yii\base\InvalidConfigException;

class Fields extends Component
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_EXISTING_FIELDS = 'modifyExistingFields';
    const EVENT_MODIFY_FIELD_CONFIG = 'modifyFieldConfig';
    const EVENT_MODIFY_FIELD_ROW_CONFIG = 'modifyFieldRowConfig';
    const EVENT_BEFORE_SAVE_FIELD_ROW = 'beforeSaveFieldRow';
    const EVENT_AFTER_SAVE_FIELD_ROW = 'afterSaveFieldRow';
    const EVENT_BEFORE_SAVE_FIELD_PAGE = 'beforeSaveFieldPage';
    const EVENT_AFTER_SAVE_FIELD_PAGE = 'afterSaveFieldPage';
    /**
     * @event RegisterFieldsEvent The event that is triggered when registering fields.
     */
    const EVENT_REGISTER_FIELDS = 'registerFields';

    /**
     * @event RegisterFieldOptionsEvent The event that is triggered when registering label positions.
     */
    const EVENT_REGISTER_LABEL_POSITIONS = 'registerLabelPositions';

    /**
     * @event RegisterFieldOptionsEvent The event that is triggered when registering instructions positions.
     */
    const EVENT_REGISTER_INSTRUCTIONS_POSITIONS = 'registerInstructionsPositions';


    // Properties
    // =========================================================================

    private $_fields = [];
    private $_layoutsById = [];
    private $_existingFields = [];


    // Public Methods
    // =========================================================================

    /**
     * Returns the registered field groups.
     *
     * @return array[]
     */
    public function getRegisteredFieldGroups()
    {
        $registeredFields = $this->getRegisteredFields();

        $internalFields = array_filter([
            ArrayHelper::remove($registeredFields, formfields\MissingField::class),
        ]);

        $commonFields = array_filter([
            ArrayHelper::remove($registeredFields, formfields\SingleLineText::class),
            ArrayHelper::remove($registeredFields, formfields\MultiLineText::class),
            ArrayHelper::remove($registeredFields, formfields\Radio::class),
            ArrayHelper::remove($registeredFields, formfields\Checkboxes::class),
            ArrayHelper::remove($registeredFields, formfields\Dropdown::class),
            ArrayHelper::remove($registeredFields, formfields\Number::class),
            ArrayHelper::remove($registeredFields, formfields\Name::class),
            ArrayHelper::remove($registeredFields, formfields\Email::class),
            ArrayHelper::remove($registeredFields, formfields\Phone::class),
            ArrayHelper::remove($registeredFields, formfields\Agree::class),
        ]);

        $advancedFields = array_filter([
            ArrayHelper::remove($registeredFields, formfields\Date::class),
            ArrayHelper::remove($registeredFields, formfields\Address::class),
            ArrayHelper::remove($registeredFields, formfields\FileUpload::class),
            ArrayHelper::remove($registeredFields, formfields\Recipients::class),
            ArrayHelper::remove($registeredFields, formfields\Hidden::class),
            ArrayHelper::remove($registeredFields, formfields\Repeater::class),
            ArrayHelper::remove($registeredFields, formfields\Table::class),
            ArrayHelper::remove($registeredFields, formfields\Group::class),
            ArrayHelper::remove($registeredFields, formfields\Heading::class),
            ArrayHelper::remove($registeredFields, formfields\Section::class),
            ArrayHelper::remove($registeredFields, formfields\Html::class),
            ArrayHelper::remove($registeredFields, formfields\Summary::class),
            ArrayHelper::remove($registeredFields, formfields\Password::class),
            ArrayHelper::remove($registeredFields, formfields\Signature::class),
            ArrayHelper::remove($registeredFields, formfields\Calculations::class),
        ]);

        $elementFields = array_filter([
            ArrayHelper::remove($registeredFields, formfields\Entries::class),
            ArrayHelper::remove($registeredFields, formfields\Categories::class),
            ArrayHelper::remove($registeredFields, formfields\Tags::class),
        ]);

        if (Craft::$app->getEdition() === Craft::Pro) {
            $elementFields = array_merge($elementFields, array_filter([
                ArrayHelper::remove($registeredFields, formfields\Users::class),
            ]));
        }

        if (Formie::$plugin->getService()->isPluginInstalledAndEnabled('commerce')) {
            $elementFields = array_merge($elementFields, array_filter([
                ArrayHelper::remove($registeredFields, formfields\Products::class),
                ArrayHelper::remove($registeredFields, formfields\Variants::class),
            ]));
        }

        $groupedFields = [];

        if ($internalFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Internal'),
                'fields' => $internalFields,
            ];
        }

        if ($commonFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Common Fields'),
                'fields' => $commonFields,
            ];
        }

        if ($advancedFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Advanced Fields'),
                'fields' => $advancedFields,
            ];
        }

        if ($elementFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Element Fields'),
                'fields' => $elementFields,
            ];
        }

        // Any custom fields
        if ($registeredFields) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Custom Fields'),
                'fields' => $registeredFields,
            ];
        }

        foreach ($groupedFields as $groupKey => $group) {
            foreach ($group['fields'] as $fieldKey => $class) {
                $groupedFields[$groupKey]['fields'][$fieldKey] = $class->getBaseFieldConfig();
            }
        }

        return $groupedFields;
    }

    /**
     * Returns a registered field by its class.
     *
     * @param $class
     * @return FormFieldInterface|null
     */
    public function getRegisteredField($class)
    {
        $fields = $this->getRegisteredFields();

        foreach ($fields as $field) {
            if (get_class($field) === $class) {
                /* @var FormFieldInterface $instance */
                $instance = $this->createField($class);
                return $instance;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getRegisteredFields($excludeDisabled = true): array
    {
        if (count($this->_fields)) {
            return $this->_fields;
        }

        $settings = Formie::$plugin->getSettings();
        $disabledFields = $settings->disabledFields;

        $fields = [
            formfields\Address::class,
            formfields\Agree::class,
            formfields\Calculations::class,
            formfields\Categories::class,
            formfields\Checkboxes::class,
            formfields\Date::class,
            formfields\Dropdown::class,
            formfields\Email::class,
            formfields\Entries::class,
            formfields\FileUpload::class,
            formfields\Group::class,
            formfields\Heading::class,
            formfields\Hidden::class,
            formfields\Html::class,
            formfields\MissingField::class,
            formfields\MultiLineText::class,
            formfields\Name::class,
            formfields\Number::class,
            formfields\Password::class,
            formfields\Phone::class,
            formfields\Radio::class,
            formfields\Recipients::class,
            formfields\Repeater::class,
            formfields\Section::class,
            formfields\Signature::class,
            formfields\SingleLineText::class,
            formfields\Summary::class,
            formfields\Table::class,
            formfields\Tags::class,
        ];

        if (Craft::$app->getEdition() === Craft::Pro) {
            $fields = array_merge($fields, [
                formfields\Users::class,
            ]);
        }

        if (Formie::$plugin->getService()->isPluginInstalledAndEnabled('commerce')) {
            $fields = array_merge($fields, [
                formfields\Products::class,
                formfields\Variants::class,
            ]);
        }

        $event = new RegisterFieldsEvent([
            'fields' => $fields,
        ]);

        $this->trigger(self::EVENT_REGISTER_FIELDS, $event);

        // Missing Field cannot be removed
        $event->fields[] = formfields\MissingField::class;
        $event->fields = array_unique($event->fields);

        foreach ($event->fields as $class) {
            // Check against plugin settings whether to exclude or not
            if ($excludeDisabled && is_array($disabledFields) && in_array($class, $disabledFields)) {
                continue;
            }

            $this->_fields[$class] = new $class;
        }

        return $this->_fields;
    }

    /**
     * Returns the registered fields for Feed Me.
     *
     * @return array[]
     */
    public function getRegisteredFormieFields()
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

        if (Craft::$app->getEdition() === Craft::Pro) {
            $fields[] = FeedMeField\Users::class;
        }

        if (Formie::$plugin->getService()->isPluginInstalledAndEnabled('commerce')) {
            $fields[] = FeedMeField\Products::class;
            $fields[] = FeedMeField\Variants::class;
        }

        return $fields;
    }

    /**
     * Returns an array of existing form fields grouped into pages.
     *
     * @param Form|null $excludeForm
     * @return array
     * @throws InvalidConfigException
     */
    public function getExistingFields($excludeForm = null): array
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

        $fields = [];
        $syncs = [];

        foreach (Craft::$app->getFields()->getAllFields(false) as $field) {
            preg_match('/formie:(?P<uid>.+)/', $field->context, $matches);
            if (!$matches) {
                // Not a formie field.
                continue;
            }

            // Is this a non-Formie field, or a MissingField?
            if (!($field instanceof FormFieldInterface)) {
                continue;
            }

            // Get the UI
            $uid = $matches['uid'];

            if (ArrayHelper::contains($forms, 'uid', $uid)) {
                if ($sync = Formie::$plugin->getSyncs()->getFieldSync($field)) {
                    if (in_array($sync->id, $syncs, false)) {
                        // Only include one instance of a synced field.
                        continue;
                    }

                    $syncs[] = $sync->id;
                }

                $fields[] = $field;
            }
        }

        ArrayHelper::multisort($fields, 'name', SORT_ASC, SORT_STRING);

        foreach ($fields as $field) {
            if (!($field instanceof NestedFieldInterface)) {
                /* @var FormFieldInterface $field */
                $allFields[] = $this->getSavedFieldConfig($field);
            }
        }

        $existingFields[] = [
            'key' => '*',
            'label' => Craft::t('formie', 'All forms'),
            'pages' => [
                [
                    'label' => Craft::t('formie', 'All fields'),
                    'fields' => $allFields,
                ],
            ],
        ];

        foreach ($forms as $form) {
            $formPages = [];

            foreach ($form->getPages() as $page) {
                $pageFields = [];

                $fields = $page->getFields();
                ArrayHelper::multisort($fields, 'name', SORT_ASC, SORT_STRING);

                foreach ($fields as $field) {
                    if (!($field instanceof NestedFieldInterface)) {
                        $pageFields[] = $this->getSavedFieldConfig($field);
                    }
                }

                $formPages[] = [
                    'label' => $page->name,
                    'fields' => $pageFields,
                ];
            }

            $existingFields[] = [
                'key' => $form->handle,
                'label' => $form->title,
                'pages' => $formPages,
            ];
        }

        // Fire a 'modifyExistingFields' event
        $event = new ModifyExistingFieldsEvent([
            'fields' => $existingFields,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXISTING_FIELDS, $event);

        return $this->_existingFields = $event->fields;
    }

    /**
     * Returns all formie fields.
     *
     * @return FormFieldInterface[]
     */
    public function getAllFields()
    {
        $allFields = [];
        $fields = Craft::$app->getFields()->getAllFields(false);

        foreach ($fields as $field) {
            if (strpos($field->context, 'formie:') === 0) {
                $allFields[] = $field;
            }
        }

        return $allFields;
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
                if (!Formie::$plugin->getService()->isPluginInstalledAndEnabled($handle)) {
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

    /**
     * Returns all fields on a provided element, for a given type. Includes drilling into nested fields.
     *
     * @return FormFieldInterface[]
     */
    public function getElementFieldsForType($element, $type)
    {
        $fields = [];

        foreach ($element->getFieldLayout()->getFields() as $field) {
            if (get_class($field) === $type) {
                $fields[] = $field;
            }

            if ($field instanceof NestedFieldInterface) {
                $fields = array_merge($fields, $this->getElementFieldsForType($field, $type));
            }
        }

        return $fields;
    }

    /**
     * Deletes any fields that aren't attached to a form anymore.
     */
    public function deleteOrphanedFields($consoleInstance = null)
    {
        $allFieldIds = [];
        $forms = Form::find()->trashed(null)->all();

        /* @var Form $form */
        foreach ($forms as $form) {
            /* @var FormField $field */
            foreach ($form->getFields() as $field) {
                $allFieldIds[] = $field->id;
            }
        }

        foreach ($this->getAllFields() as $field) {
            if (!in_array($field->id, $allFieldIds)) {
                // Just a sanity check to protect against any non-Formie contexted fields
                if (!strstr($field->context, 'formie:')) {
                    continue;
                }

                // Be careful when deleting a field. `getFields()->deleteField()` will try and cleanup the content
                // column on the global scope. We can't change the contentTable because the form where this is
                // stored is gone (because this is an orphaned field, we know that). So we must to a direct delete
                // which is mostly okay, as Formie fields aren't stored in project config.
                Db::delete(CraftTable::FIELDS, [
                    'id' => $field->id,
                ]);
            }
        }
    }

    /**
     * Gets a field's config for rendering form builder.
     *
     * @param FormFieldInterface $field
     * @return array
     */
    public function getSavedFieldConfig(FormFieldInterface $field)
    {
        /* @var FormField $field */
        $config = $field->getSavedFieldConfig();

        $config['label'] = $field->name;
        $config['icon'] = $field->getSvgIcon();
        $config['type'] = get_class($field);
        $config['errors'] = $field->getErrors();
        $config['hasLabel'] = $field->hasLabel();
        $config['hasError'] = (bool)$field->getErrors();
        $config['settings'] = $field->getSavedSettings();
        $config['isTextInput'] = $field->getIsTextInput();
        $config['isCosmetic'] = $field->getIsCosmetic();

        // Indicates whether the field is currently synced to another field.
        $config['isSynced'] = Formie::$plugin->getSyncs()->isSynced($field);

        // Indicates whether the field contains conditions.
        $config['hasConditions'] = $field->hasConditions();

        // Copy some attributes into `settings` - required for Formulate for the moment
        // as it doesn't support nested data, and it really has trouble dealing with top-level
        // attributes like `label` and `settings[attribute]` together in one go.
        $config['settings']['label'] = $field->name;
        $config['settings']['handle'] = $field->handle;

        // These really belong in settings anyway...
        $config['settings']['required'] = (bool)$field->required;
        $config['settings']['instructions'] = $field->instructions;

        // Nested fields have rows of their own.
        if ($config['supportsNested'] = $field instanceof NestedFieldInterface) {
            $config['isElementField'] = true;

            /* @var NestedFieldInterface|NestedFieldTrait $field */
            $config['rows'] = $field->getRows();
        }

        // Allow fields to provide sub-field options for mapping
        if ($field instanceof SubFieldInterface) {
            $config['subfieldOptions'] = $field->getSubfieldOptions();
            $config['hasSubfields'] = $field->hasSubfields();
        }

        // Whether this field is nested inside another one
        $config['isNested'] = $field->isNested;

        // Whether this is an element field
        if ($field instanceof BaseRelationField) {
            $config['isElementField'] = true;
        }

        // Fire a 'modifyFieldConfig' event
        $event = new ModifyFieldConfigEvent([
            'config' => $config,
        ]);
        $this->trigger(self::EVENT_MODIFY_FIELD_CONFIG, $event);

        return $event->config;
    }

    /**
     * Returns a field's render options from the main options array.
     *
     * @param FormFieldInterface $field
     * @param array|null $options
     * @return array
     */
    public function getFieldOptions($field, array $options = null): array
    {
        if (empty($options)) {
            return [];
        }

        /* @var FormField $field */
        $allFieldOptions = $options['fields'] ?? [];
        $fieldOptions = $allFieldOptions[$field->handle] ?? [];

        if (isset($allFieldOptions['*'])) {
            $fieldOptions = ArrayHelper::merge($allFieldOptions['*'], $fieldOptions);
        }

        return $fieldOptions;
    }

    /**
     * Returns a list of available field label positions.
     *
     * @param FormFieldInterface|null $field
     * @return array
     * @noinspection DuplicatedCode
     */
    public function getLabelPositions($field = null): array
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

    /**
     * Returns label positions for use in form builder.
     *
     * @param FormFieldInterface|null $field
     * @return array
     */
    public function getLabelPositionsArray($field = null): array
    {
        $labelPositions = [];

        foreach ($this->getLabelPositions($field) as $class) {
            $labelPositions[] = [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }

        return $labelPositions;
    }

    /**
     * Returns a list of available field instructions positions.
     *
     * @param FormFieldInterface|null $field
     * @return array
     * @noinspection DuplicatedCode
     */
    public function getInstructionsPositions($field = null): array
    {
        $instructionsPositions = [
            AboveInput::class,
            BelowInput::class,
            FieldsetStart::class,
            FieldsetEnd::class,
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

    /**
     * Returns instructions positions for use in form builder.
     *
     * @param FormFieldInterface|null $field
     * @return array
     */
    public function getInstructionsPositionsArray($field = null): array
    {
        $instructionsPositions = [];

        foreach ($this->getInstructionsPositions($field) as $class) {
            $instructionsPositions[] = [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }

        return $instructionsPositions;
    }


    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns a field layout by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId)
    {
        if ($this->_layoutsById !== null && array_key_exists($layoutId, $this->_layoutsById)) {
            return $this->_layoutsById[$layoutId];
        }

        $result = $this->_createLayoutQuery()
            ->andWhere(['id' => $layoutId])
            ->one();

        return $this->_layoutsById[$layoutId] = $result ? new FieldLayout($result) : null;
    }

    /**
     * Returns a layout's pages by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayoutPage[] The field layout’s pages
     */
    public function getLayoutPagesById(int $layoutId): array
    {
        $tabs = $this->_createLayoutPageQuery()
            ->addSelect([
                'ps.settings',
            ])
            ->leftJoin('{{%formie_pagesettings}} ps', '[[ps.fieldLayoutTabId]] = [[fieldlayouttabs.id]]')
            ->where(['layoutId' => $layoutId])
            ->all();

        $isMysql = Craft::$app->getDb()->getIsMysql();

        foreach ($tabs as $key => $value) {
            if ($isMysql) {
                $value['name'] = html_entity_decode($value['name'], ENT_QUOTES | ENT_HTML5);
            }

            $tabs[$key] = new FieldLayoutPage($value);
        }

        return $tabs;
    }

    /**
     * Returns the field IDs for a given layout ID.
     *
     * @param int $layoutId The field layout ID
     * @return int[]
     */
    public function getFieldIdsByLayoutId(int $layoutId): array
    {
        return Craft::$app->getFields()->getFieldIdsByLayoutId($layoutId);
    }

    /**
     * Returns the fields in a field layout, identified by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldInterface[] The fields
     */
    public function getFieldsByLayoutId(int $layoutId): array
    {
        $fields = [];

        $results = $this->_createFieldQuery()
            ->addSelect([
                'flf.layoutId',
                'flf.tabId',
                'flf.required',
                'flf.sortOrder',
                'forms.id as formId',
                'rows.id as rowId',
                'rows.uid as rowUid',
                'rows.row as rowIndex',
            ])
            ->innerJoin('{{%fieldlayoutfields}} flf', '[[flf.fieldId]] = [[fields.id]]')
            ->innerJoin('{{%fieldlayouttabs}} flt', '[[flt.id]] = [[flf.tabId]]')
            ->leftJoin('{{%formie_forms}} forms', '[[forms.fieldLayoutId]] = [[flf.layoutId]]')
            ->leftJoin('{{%formie_rows}} rows', '[[rows.fieldLayoutFieldId]] = [[flf.id]]')
            ->where(['flf.layoutId' => $layoutId])
            ->orderBy([
                'flt.sortOrder' => SORT_ASC,
                'rows.row' => SORT_ASC,
                'flf.sortOrder' => SORT_ASC,
            ])
            ->all();

        foreach ($results as $result) {
            $field = Formie::$plugin->getFields()->createField($result);

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Saves a Formie's custom field layout data.
     *
     * @param FieldLayout $fieldLayout
     *
     * @see \craft\services\Fields::saveLayout()
     * @see Formie::_registerFieldsEvents()
     */
    public function onSaveFieldLayout(FieldLayout $fieldLayout)
    {
        $this->saveRows($fieldLayout);
        $this->savePages($fieldLayout);
    }

    /**
     * Creates a field with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return FieldInterface The field
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function createField($config): FieldInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (!empty($config['id']) && empty($config['uid']) && is_numeric($config['id'])) {
            $uid = Db::uidById(CraftTable::FIELDS, $config['id']);
            $config['uid'] = $uid;
        }

        try {
            $field = ComponentHelper::createComponent($config, FormFieldInterface::class);
        } catch (InvalidConfigException | MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $field = new formfields\MissingField($config);
        }

        return $field;
    }


    // Rows
    // -------------------------------------------------------------------------

    /**
     * Returns an array of fields grouped into rows.
     *
     * @param FormFieldInterface[] $fields
     * @return array
     */
    public function groupIntoRows($fields): array
    {
        $rows = [];

        foreach ($fields as $field) {
            /* @var FormField $field */
            $rows[$field->rowIndex]['id'] = $field->rowId;
            $rows[$field->rowIndex]['uid'] = $field->rowUid;

            if (!isset($rows[$field->rowIndex]['fields'])) {
                $rows[$field->rowIndex]['fields'] = [];
            }

            // @var FormField $field
            $rows[$field->rowIndex]['fields'][] = $field;
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * Returns row config for a page's fields.
     *
     * @param array $rows
     * @return array
     */
    public function getRowConfig($rows)
    {
        $rowConfig = [];

        foreach ($rows as $rowIndex => $row) {
            $fields = [];

            foreach ($row['fields'] as $fieldIndex => $field) {
                // Set a flag on any field inside a nested field
                if ($field instanceof NestedFieldInterface) {
                    foreach ($field->getFields() as $key => $nestedField) {
                        $nestedField->isNested = true;
                    }
                }

                $fields[$fieldIndex] = Formie::$plugin->getFields()->getSavedFieldConfig($field);
            }

            $rowConfig[$rowIndex] = [
                'id' => $row['id'],
                'fields' => $fields,
            ];
        }

        // Fire a 'modifyFieldRowConfig' event
        $event = new ModifyFieldRowConfigEvent([
            'config' => $rowConfig,
        ]);
        $this->trigger(self::EVENT_MODIFY_FIELD_ROW_CONFIG, $event);

        return $event->config;
    }

    /**
     * Saves all a field layouts row data.
     *
     * @param FieldLayout $fieldLayout
     */
    public function saveRows(FieldLayout $fieldLayout)
    {
        foreach ($fieldLayout->getFields() as $field) {
            $record = new Row();
            $isNew = $record->getIsNewRecord();

            $record->row = $field->rowIndex;
            $record->fieldLayoutId = $fieldLayout->id;
            $record->fieldLayoutFieldId = (new Query())->select(['id'])
                ->from(CraftTable::FIELDLAYOUTFIELDS)
                ->where([
                    'layoutId' => $fieldLayout->id,
                    'fieldId' => $field->id,
                ])
                ->scalar();

            // This can happen in very specific circumstances
            if (!$record->fieldLayoutFieldId) {
                Formie::error(Craft::t('app', 'Preparing row error: layoutId:{layoutId} - fieldId:{fieldId}', [
                    'layoutId' => $fieldLayout->id,
                    'fieldId' => $field->id,
                ]));
            }

            // Fire a 'beforeSaveFieldRow' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD_ROW)) {
                $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_ROW, new FieldRowEvent([
                    'row' => $record,
                    'isNew' => $isNew,
                ]));
            }

            $record->save();

            // Fire a 'afterSaveFieldRow' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_ROW)) {
                $this->trigger(self::EVENT_AFTER_SAVE_FIELD_ROW, new FieldRowEvent([
                    'row' => $record,
                    'isNew' => $isNew,
                ]));
            }
        }
    }


    // Pages
    // -------------------------------------------------------------------------

    /**
     * Saves all a field layouts page data.
     *
     * @param FieldLayout $fieldLayout
     */
    public function savePages(FieldLayout $fieldLayout)
    {
        foreach ($fieldLayout->getPages() as $page) {
            // Try to find the page settings first
            $record = PageSettings::find()->where(['fieldLayoutId' => $fieldLayout->id, 'fieldLayoutTabId' => $page->id])->one();

            if (!$record) {
                $record = new PageSettings();
            }

            $isNew = $record->getIsNewRecord();

            $record->settings = $page->settings;
            $record->fieldLayoutId = $fieldLayout->id;
            $record->fieldLayoutTabId = $page->id;

            // Fire a 'beforeSaveFieldRow' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD_PAGE)) {
                $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_PAGE, new FieldPageEvent([
                    'page' => $record,
                    'isNew' => $isNew,
                ]));
            }

            $record->save();

            // Fire a 'afterSaveFieldRow' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_PAGE)) {
                $this->trigger(self::EVENT_AFTER_SAVE_FIELD_PAGE, new FieldPageEvent([
                    'page' => $record,
                    'isNew' => $isNew,
                ]));
            }
        }
    }

    /**
     * Returns a list of reserved field handles.
     *
     * @return string[]
     */
    public function getReservedHandles()
    {
        try {
            $class = new ReflectionClass(Field::class);
            $method = $class->getMethod('defineRules');
            $method->setAccessible(true);
            $rule = ArrayHelper::firstWhere($method->invoke(new formfields\SingleLineText()), function($rule) {
                return $rule[1];
            }, HandleValidator::class);

            $reservedWords = $rule['reservedWords'];
        } catch (ReflectionException $e) {
            $reservedWords = [];
        }

        return array_merge(
            $reservedWords,
            HandleValidator::$baseReservedWords
        );
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving fields.
     *
     * @return Query
     */
    private function _createFieldQuery(): Query
    {
        $query = (new Query())
            ->select([
                'fields.id',
                'fields.dateCreated',
                'fields.dateUpdated',
                'fields.groupId',
                'fields.name',
                'fields.handle',
                'fields.context',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid',
            ])
            ->from(['fields' => CraftTable::FIELDS])
            ->orderBy(['fields.name' => SORT_ASC, 'fields.handle' => SORT_ASC]);

        // TODO: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();

        if (version_compare($schemaVersion, '3.7.0', '>=')) {
            $query->addSelect(['fields.columnSuffix']);
        }

        return $query;
    }

    /**
     * Returns a Query object prepped for retrieving layouts.
     *
     * @return Query
     */
    private function _createLayoutQuery(): Query
    {
        return (new Query)
            ->select([
                'id',
                'type',
                'uid',
            ])
            ->from([CraftTable::FIELDLAYOUTS])
            ->where(['dateDeleted' => null]);
    }

    /**
     * Returns a Query object prepped for retrieving layout pages.
     *
     * @return Query
     */
    private function _createLayoutPageQuery(): Query
    {
        return (new Query())
            ->select([
                'fieldlayouttabs.id',
                'fieldlayouttabs.layoutId',
                'fieldlayouttabs.name',
                'fieldlayouttabs.sortOrder',
                'fieldlayouttabs.uid',
            ])
            ->from([CraftTable::FIELDLAYOUTTABS . ' fieldlayouttabs'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }
}

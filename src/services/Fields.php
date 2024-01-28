<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyExistingFieldsEvent;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldRowConfigEvent;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\fields\formfields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\integrations\feedme\elementfields as FeedMeElementField;
use verbb\formie\integrations\feedme\fields as FeedMeField;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\errors\MissingComponentException;
use craft\fields\BaseRelationField;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\validators\HandleValidator;

use ReflectionClass;
use ReflectionException;

use yii\base\InvalidConfigException;

class Fields extends Component
{
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

    private array $_fields = [];
    private array $_existingFields = [];


    // Public Methods
    // =========================================================================

    public function getFormBuilderFieldTypes(): array
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
            ArrayHelper::remove($registeredFields, formfields\Payment::class),
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

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
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
                $groupedFields[$groupKey]['fields'][$fieldKey] = $class->getFieldTypeConfig();
            }
        }

        return $groupedFields;
    }

    public function getRegisteredFields(bool $excludeDisabled = true): array
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
            formfields\Payment::class,
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

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
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
            if ($excludeDisabled && in_array($class, $disabledFields)) {
                continue;
            }

            $this->_fields[$class] = new $class;
        }

        return $this->_fields;
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

        if (Craft::$app->getEdition() === Craft::Pro) {
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

        foreach ($forms as $form) {
            $formPages = [];

            foreach ($form->getPages() as $page) {
                $pageFields = [];

                $fields = $page->getFields();
                ArrayHelper::multisort($fields, 'name', SORT_ASC, SORT_STRING);

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

        ArrayHelper::multisort($allFields, 'name', SORT_ASC, SORT_STRING);

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

    public function updateIsSynced(FieldInterface $field): void
    {
        $foundFields = 0;

        // Find any references for the field, to check if we still need to sync it.
        foreach (Form::find()->all() as $form) {
            // Skip the form for this field, as we're deleting the field, and it shouldn't be counted
            if ($field->getForm()?->uid === $form->uid) {
                continue;
            }

            if ($layout = $form->getFormFieldLayout()) {
                $allFieldUids = ArrayHelper::getColumn($layout->getFields(), 'uid');

                if (in_array($field->uid, $allFieldUids)) {
                    $foundFields++;
                }
            }
        }

        // There's only one reference to to field now, so it's no longer synced to anything
        if ($foundFields < 2) {
            $field->isSynced = false;

            Craft::$app->getFields()->saveField($field);
        }
    }

    public function checkRequiredPlugin(FormFieldInterface $field): bool
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

    public function getFieldOptions(FormFieldInterface $field, array $options = null): array
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

    public function getLabelPositions(FormFieldInterface $field = null): array
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

    public function getLabelPositionsOptions(FormFieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getLabelPositions($field));
    }

    public function getInstructionsPositions(FormFieldInterface $field = null): array
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

    public function getInstructionsPositionsOptions(FormFieldInterface $field = null): array
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

        return array_merge($reservedWords, HandleValidator::$baseReservedWords);
    }

    public function deleteOrphanedFields($consoleInstance = null): void
    {
        // Because forms support soft-deletion, and we can't mark fields as soft-deleted, we can't really delete them
        // until a form is hard-deleted. This is most check on a routine basis.
        $formUids = (new Query())
            ->select(['uid'])
            ->from(['{{%elements}}'])
            ->where(['type' => Form::class])
            ->column();

        $fields = (new Query())
            ->select(['id', 'uid', 'context'])
            ->from(['{{%fields}}'])
            ->indexBy('uid')
            ->all();

        foreach ($fields as $field) {
            if (str_starts_with($field['context'], 'formie:')) {
                $uid = str_replace('formie:', '', $field['context']);

                if (!in_array($uid, $formUids)) {
                    // Do a direct database delete, so as not to trigger any field-deletion events, and performance gains
                    Db::delete(CraftTable::FIELDS, [
                        'id' => $field['id'],
                    ]);
                }
            }

            // Check for Group/Repeater fields
            if (str_starts_with($field['context'], 'formieField:')) {
                $uid = str_replace('formieField:', '', $field['context']);

                if (!isset($fields[$uid])) {
                    Db::delete(CraftTable::FIELDS, [
                        'id' => $field['id'],
                    ]);
                }
            }
        }
    }
}

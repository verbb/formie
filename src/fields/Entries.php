<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ElementField;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\elements\Entry;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\fields\Entries as CraftEntries;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\records\EntryType as EntryTypeRecord;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;

class Entries extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Entries');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/entries/icon.svg';
    }

    public static function elementType(): string
    {
        return Entry::class;
    }


    // Public Methods
    // =========================================================================

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['placeholder'] = Craft::t('formie', 'Select an entry');

        return $settings;
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 0 ? Craft::t('formie', 'No sections available. View [section settings]({link}).', ['link' => UrlHelper::cpUrl('settings/sections')]) : false,
        ];
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/entries/preview', [
            'field' => $this,
        ]);
    }

    public function defineLabelSourceOptions(): array
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
            ['value' => 'slug', 'label' => Craft::t('app', 'Slug')],
            ['value' => 'uri', 'label' => Craft::t('app', 'URI')],
            ['value' => 'postDate', 'label' => Craft::t('app', 'Post Date')],
            ['value' => 'expiryDate', 'label' => Craft::t('app', 'Expiry Date')],
        ];

        $extraOptions = [];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $sectionId = $source['criteria']['sectionId'] ?? null;

                if ($sectionId && !is_array($sectionId)) {
                    $entryTypes = Craft::$app->getEntries()->getEntryTypesBySectionId($sectionId);

                    foreach ($entryTypes as $entryType) {
                        $fields = $this->getStringCustomFieldOptions($entryType->getCustomFields());

                        $extraOptions[] = $fields;
                    }
                }
            }
        }

        return array_merge($options, ...$extraOptions);
    }

    public function getSourceOptions(): array
    {
        $options = [];
        $optionNames = [];

        foreach ($this->availableSources() as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];

                $sectionId = $source['criteria']['sectionId'] ?? null;

                if ($sectionId && !is_array($sectionId)) {
                    $entryTypes = Craft::$app->getEntries()->getEntryTypesBySectionId($sectionId);

                    foreach ($entryTypes as $entryType) {
                        $options[] = [
                            'label' => $source['label'] . ': ' . $entryType['name'],
                            'value' => 'type:' . $entryType['uid'],
                        ];
                    }
                }
            }
        }

        ArrayHelper::multisort($options, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'defaultEntry' => [
                'name' => 'defaultEntry',
                'type' => EntryInterface::getType(),
                'args' => EntryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getDefaultValueQuery() ? $class->getDefaultValueQuery()->one() : null;
                },
            ],
            'entries' => [
                'name' => 'entries',
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => EntryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getElementsQuery()->all();
                },
            ],
        ]);
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EntryInterface::getType())),
            'args' => EntryArguments::getArguments(),
            'resolve' => EntryResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    public function defineGeneralSchema(): array
    {
        $options = $this->getSourceOptions();

        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                'name' => 'placeholder',
                'validation' => 'required',
                'required' => true,
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source Type'),
                'help' => Craft::t('formie', 'Select what source type to use for this field.'),
                'name' => 'sourceType',
                'options' => [
                    ['value' => 'groups', 'label' => Craft::t('formie', 'Sections')],
                    ['value' => 'elements', 'label' => Craft::t('formie', 'Specific Elements')],
                ],
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select entries from?'),
                'name' => 'sources',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'if' => '$get(sourceType).value == groups',
                'showAllOption' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No sections available. View [section settings]({link}).', ['link' => UrlHelper::cpUrl('settings/sections')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select entries from?'),
                'name' => 'sourceElements',
                'validation' => 'required',
                'required' => true,
                'if' => '$get(sourceType).value == elements',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                    'limit' => 999,
                ],
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default entry to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::emailNotificationValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'help' => Craft::t('formie', 'Limit the number of available entries.'),
                'name' => 'limitOptions',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each entry.'),
                'name' => 'labelSource',
                'options' => $this->getLabelSourceOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'help' => Craft::t('formie', 'Select what order to show entries by.'),
                'name' => 'orderBy',
                'options' => array_merge([
                    ['value' => 'lft ASC', 'label' => 'Structure Ascending'],
                    ['value' => 'lft DESC', 'label' => 'Structure Descending'],
                ], $this->getOrderByOptions()),
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'help' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multi',
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'help' => Craft::t('formie', 'Select which layout to use for these fields.'),
                'name' => 'layout',
                'if' => '$get(displayType).value != dropdown',
                'options' => [
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                ],
            ]),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}

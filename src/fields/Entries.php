<?php
namespace verbb\formie\fields;

use verbb\formie\base\ElementField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentTypeDefinitionFromConfig(
            $config,
            EntryInterface::getType(),
            EntryArguments::getArguments(),
            EntryResolver::class,
        );
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentMutationArgumentTypeDefinitionFromConfig($config);
    }

    protected static function defineOptionSource(): ?array
    {
        return [
            'handle' => 'entries',
            'label' => static::displayName(),
        ];
    }


    // Properties
    // =========================================================================

    public ?string $sourceSiteUid = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['placeholder'] = $config['placeholder'] ?? Craft::t('formie', 'Select an entry');

        parent::__construct($config);
    }

    public function settingsAttributes(): array
    {
        return [...parent::settingsAttributes(), 'sourceSiteUid'];
    }

    public function getOptionSourceParams(): array
    {
        $params = parent::getOptionSourceParams();

        if ($this->sourceSiteUid) {
            $params['sourceSiteUid'] = $this->sourceSiteUid;
        }

        return $params;
    }

    public function getOptionSourceFieldConfig(array $params = []): array
    {
        $config = parent::getOptionSourceFieldConfig($params);

        if (!empty($params['sourceSiteUid'])) {
            $config['sourceSiteUid'] = $params['sourceSiteUid'];
        }

        return $config;
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 0 ? Craft::t('formie', 'No sections available. View [section settings]({link}).', ['link' => UrlHelper::cpUrl('settings/sections')]) : false,
        ];
    }

    public function getOptionSourceWarning(array $sourceOptions): ?string
    {
        return $sourceOptions === []
            ? Craft::t('formie', 'No sections available. View [section settings]({link}).', ['link' => UrlHelper::cpUrl('settings/sections')])
            : null;
    }

    public function getOptionSourceOrderByOptions(): array
    {
        return array_merge([
            ['value' => 'lft ASC', 'label' => Craft::t('formie', 'Structure Ascending')],
            ['value' => 'lft DESC', 'label' => Craft::t('formie', 'Structure Descending')],
        ], parent::getOptionSourceOrderByOptions());
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewElementField(),
        ];
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
                    $entryTypes = Craft::$app->getEntries()->getSectionById($sectionId)?->getEntryTypes() ?? [];

                    foreach ($entryTypes as $entryType) {
                        $fields = $this->getStringCustomFieldOptions($entryType->getCustomFields());

                        $extraOptions[] = $fields;
                    }
                }
            }
        }

        return array_merge($options, ...$extraOptions);
    }

    protected function defineElementAttributes(): array
    {
        return [
            ...parent::defineElementAttributes(),
            'slug' => ['label' => Craft::t('app', 'Slug'), 'value' => static fn(ElementInterface $entry): ?string => $entry->slug],
            'section' => ['label' => Craft::t('app', 'Section'), 'value' => static fn(Entry $entry): ?string => $entry->getSection()?->name],
            'entryType' => ['label' => Craft::t('app', 'Entry Type'), 'value' => static fn(Entry $entry): string => $entry->getType()->name],
            'postDate' => ['label' => Craft::t('app', 'Post Date'), 'value' => static fn(Entry $entry): ?string => $entry->postDate ? Craft::$app->getFormatter()->asDate($entry->postDate, 'short') : null],
            'status' => ['label' => Craft::t('app', 'Status'), 'value' => static fn(Entry $entry): ?string => Entry::statuses()[$entry->getStatus()] ?? $entry->getStatus()],
            'author' => ['label' => Craft::t('app', 'Author'), 'value' => static fn(Entry $entry): ?string => $entry->getAuthor()?->getName()],
        ];
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
                    $entryTypes = Craft::$app->getEntries()->getSectionById($sectionId)?->getEntryTypes() ?? [];

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
            'sourceSiteUid' => ['name' => 'sourceSiteUid', 'type' => Type::string()],
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

    public function defineFormBuilderGeneralSchema(): array
    {
        $options = $this->getSourceOptions();

        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                'name' => 'placeholder',
                'validation' => 'required',
                'required' => true,
                'if' => 'displayType == "dropdown"',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'instructions' => Craft::t('formie', 'Which sources do you want to select entries from?'),
                'name' => 'sources',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'showAllOption' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No sections available. View [section settings]({link}).', ['link' => UrlHelper::cpUrl('settings/sections')]) : false,
            ]),
            ...$this->_sourceSiteSchema(),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Select a default entry to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prefillQueryParam(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'instructions' => Craft::t('formie', 'Limit the number of available entries.'),
                'name' => 'optionLimit',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'instructions' => Craft::t('formie', 'Select what to use as the label for each entry.'),
                'name' => 'labelSource',
                'options' => $this->getLabelSourceOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'instructions' => Craft::t('formie', 'Select what order to show entries by.'),
                'name' => 'orderBy',
                'options' => array_merge([
                    ['value' => 'lft ASC', 'label' => Craft::t('formie', 'Structure Ascending')],
                    ['value' => 'lft DESC', 'label' => Craft::t('formie', 'Structure Descending')],
                ], $this->getOrderByOptions()),
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'instructions' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            ...$this->defineElementFieldMultiSelectAppearanceSchema(),
            ...$this->defineElementFieldSearchableDropdownAppearanceSchema(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function createElementQuery(?ElementInterface $element = null): ElementQueryInterface
    {
        $query = parent::createElementQuery($element);

        if ($this->sourceSiteUid) {
            $siteId = $this->_sourceSiteId();

            // A custom source can set its own site criterion. Keep both restrictions.
            $query->siteId($siteId)->andWhere(['elements_sites.siteId' => $siteId]);
        }

        return $query;
    }

    protected function cpInputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::cpInputTemplateVariables($value, $element);

        if ($this->sourceSiteUid) {
            $siteId = $this->_sourceSiteId();
            $variables['criteria']['siteId'] = $siteId;
            $variables['siteIds'] = [$siteId];
            $variables['showSiteMenu'] = false;
            $variables['modalSettings']['defaultSiteId'] = $siteId;
        }

        return $variables;
    }


    // Private Methods
    // =========================================================================

    private function _sourceSiteSchema(): array
    {
        if (!Craft::$app->getIsMultiSite()) {
            return [];
        }

        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source Site'),
                'name' => 'sourceSiteUid',
                'options' => [
                    ['label' => Craft::t('formie', 'Current Site'), 'value' => ''],
                    ...array_map(static fn(Site $site) => ['label' => $site->getUiLabel(), 'value' => $site->uid], Craft::$app->getSites()->getAllSites(false)),
                ],
            ]),
        ];
    }

    private function _sourceSiteId(): int
    {
        try {
            return Craft::$app->getSites()->getSiteByUid($this->sourceSiteUid, false)->id;
        } catch (SiteNotFoundException) {
            // A removed or disabled source must not fall back to the current site's entries.
            return -1;
        }
    }
}

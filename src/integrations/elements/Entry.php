<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\base\Element as CraftElement;
use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\Json;

use Throwable;

class Entry extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Entry');
    }


    // Properties
    // =========================================================================

    public ?string $entryTypeSection = null;
    public mixed $defaultAuthorId = null;
    public ?bool $createDraft = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        unset($config['entryTypeId']);
        unset($config['entryTypeUid']);

        parent::__construct($config);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create {name} elements.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $customFields = [];

        $sections = Craft::$app->getEntries()->getAllSections();

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            foreach ($section->getEntryTypes() as $entryType) {
                $fields = $this->getFieldLayoutFields($entryType->getFieldLayout());

                $customFields[$section->name][] = new IntegrationCollection([
                    'id' => $section->uid . ':' . $entryType->uid,
                    'name' => $entryType->name,
                    'fields' => $fields,
                ]);
            }
        }

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    public function getElementAttributes(): array
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Site ID'),
                'handle' => 'siteId',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Author'),
                'handle' => 'author',
                'type' => IntegrationField::TYPE_ARRAY,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Post Date'),
                'handle' => 'postDate',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Expiry Date'),
                'handle' => 'expiryDate',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
                'type' => IntegrationField::TYPE_BOOLEAN,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Created'),
                'handle' => 'dateCreated',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Updated'),
                'handle' => 'dateUpdated',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
        ];
    }

    public function getUpdateAttributes(): array
    {
        $attributes = [];

        $sections = Craft::$app->getEntries()->getAllSections();

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            foreach ($section->getEntryTypes() as $entryType) {
                $key = $section->uid . ':' . $entryType->uid;

                $attributes[$key] = [
                    new IntegrationField([
                        'name' => Craft::t('app', 'ID'),
                        'handle' => 'id',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Title'),
                        'handle' => 'title',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Slug'),
                        'handle' => 'slug',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Site'),
                        'handle' => 'site',
                    ]),
                ];

                foreach ($entryType->getFieldLayout()->getCustomFields() as $field) {
                    if (!$this->fieldCanBeUniqueId($field)) {
                        continue;
                    }

                    $attributes[$key][] = new IntegrationField([
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $this->getFieldTypeForField(get_class($field)),
                        'sourceType' => get_class($field),
                    ]);
                }
            }
        }

        return $attributes;
    }

    public function sendPayload(Submission $submission): IntegrationResponse|bool
    {
        $entriesService = Craft::$app->getEntries();
        
        if (!$this->entryTypeSection || !str_contains($this->entryTypeSection, ':')) {
            Integration::error($this, Craft::t('formie', 'Unable to save element integration. No `entryTypeId`.'), true);

            return false;
        }

        try {
            [$sectionIdentifier, $entryTypeIdentifier] = explode(':', $this->entryTypeSection);

            // Detect UID format (has dashes, standard UID pattern)
            if (str_contains($sectionIdentifier, '-')) {
                $section = $entriesService->getSectionByUid($sectionIdentifier);
                $entryType = $entriesService->getEntryTypeByUid($entryTypeIdentifier);
            } else {
                $section = $entriesService->getSectionById((int)$sectionIdentifier);
                $entryType = $entriesService->getEntryTypeById((int)$entryTypeIdentifier);
            }

            if (!$section || !$entryType) {
                Integration::error($this, Craft::t('formie', 'Unable to save element integration. Missing `section` or `entryType`.'), true);

                return false;
            }

            $entry = $this->getElementForPayload(EntryElement::class, $this->entryTypeSection, $submission, [
                'typeId' => $entryType->id,
                'sectionId' => $section->id,
            ]);

            $entry->siteId = $submission->siteId;
            $entry->typeId = $entryType->id;
            $entry->sectionId = $section->id;

            if ($this->defaultAuthorId) {
                $entry->authorId = $this->defaultAuthorId;
            }

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());

            // Filter null values
            if (!$this->overwriteValues) {
                $attributeValues = ArrayHelper::filterNull($attributeValues);
            }

            foreach ($attributeValues as $entryFieldHandle => $fieldValue) {
                if ($entryFieldHandle === 'author') {
                    $fieldValue = $this->_normalizeIds($fieldValue);

                    $entry->setAuthorIds($fieldValue);
                } else {
                    $entry->{$entryFieldHandle} = $fieldValue;
                }
            }

            $fields = $this->_getEntryTypeSettings()->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            // Filter null values
            if (!$this->overwriteValues) {
                $fieldValues = ArrayHelper::filterNull($fieldValues);
            }

            $entry->setFieldValues($fieldValues);
            $entry->updateTitle();

            // If we're not mapping to the status, ensure it's inherited from the section's default
            $statusAttributeMapping = $this->attributeMapping['enabled'] ?? '';

            if ($statusAttributeMapping === '') {
                $siteSettings = ArrayHelper::firstWhere($section->getSiteSettings(), 'siteId', $entry->siteId);
                $enabled = $siteSettings->enabledByDefault;

                if (Craft::$app->getIsMultiSite() && count($entry->getSupportedSites()) > 1) {
                    $entry->enabled = true;
                    $entry->setEnabledForSite($enabled);
                } else {
                    $entry->enabled = $enabled;
                    $entry->setEnabledForSite(true);
                }
            }

            // Although empty, because we pass via reference, we need variables
            $endpoint = '';
            $method = '';

            // Allow events to cancel sending - return as success            
            if (!$this->beforeSendPayload($submission, $endpoint, $entry, $method)) {
                return true;
            }

            // Check if we need to create a new draft
            if ($this->createDraft) {
                $authorId = $entry->authorId ?? Craft::$app->getUser()->getId();

                // Is this a brand-new entry?
                if (!$entry->id) {
                    $entry->setScenario(CraftElement::SCENARIO_ESSENTIALS);

                    if (!Craft::$app->getDrafts()->saveElementAsDraft($entry, $authorId)) {
                        Integration::error($this, Craft::t('formie', 'Unable to save “{type}” draft element integration. Error: {error}.', [
                            'type' => $this->handle,
                            'error' => Json::encode($entry->getErrors()),
                        ]), true);

                        return false;
                    }

                    $this->afterSendPayload($submission, '', $entry, '', []);
                } else {
                    // Otherwise, create a new draft on the entry
                    $draft = Craft::$app->getDrafts()->createDraft($entry, $authorId);

                    $this->afterSendPayload($submission, '', $entry, '', ['draft' => $draft]);
                }

                return true;
            }

            if (!$entry->validate()) {
                Integration::error($this, Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]), true);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($entry, true, true, $this->updateSearchIndexes)) {
                Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]), true);

                return false;
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, '', $entry, '', [])) {
                return true;
            }
        } catch (Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}. Trace: “{trace}”.', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, [$error]);
        }

        return true;
    }

    public function getAuthor($form): array
    {
        $defaultAuthorId = $form->settings->integrations[$this->handle]['defaultAuthorId'] ?? '';

        if (!$defaultAuthorId) {
            $defaultAuthorId = $this->defaultAuthorId;
        }

        if ($defaultAuthorId) {
            return User::find()->id($defaultAuthorId)->all();
        }

        return [Craft::$app->getUser()->getIdentity()];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['entryTypeSection', 'defaultAuthorId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        // Find the field for the entry type - a little trickier due to nested in sections
        $fields = $this->_getEntryTypeSettings()->fields ?? [];

        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getEntryTypeSettings()
    {
        $entryTypes = $this->getFormSettingValue('elements');

        foreach ($entryTypes as $key => $entryType) {
            if ($collection = ArrayHelper::firstWhere($entryType, 'id', $this->entryTypeSection)) {
                return $collection;
            }
        }

        return [];
    }

    private function _normalizeIds($content): array
    {
        if ($content === null || $content === '') {
            return [];
        }

        // If it's already an array, recurse
        if (is_array($content)) {
            $ids = [];

            foreach ($content as $value) {
                $ids = array_merge($ids, $this->_normalizeIds($value));
            }

            return array_values(array_unique($ids));
        }

        // If it's a string, try JSON decode first
        if (is_string($content)) {
            if (Json::isJsonObject($content) || str_starts_with(trim($content), '[')) {
                $decoded = Json::decodeIfJson($content);

                if ($decoded !== null) {
                    return $this->_normalizeIds($decoded);
                }
            }

            // Fallback: comma-separated values in a string
            if (preg_match('/^\[(.*)\]$/', trim($content), $matches)) {
                return array_values(array_unique(
                    array_map('intval', array_map('trim', explode(',', $matches[1])))
                ));
            }

            // Otherwise single value
            return [(int)$content];
        }

        // Numeric scalars
        if (is_numeric($content)) {
            return [(int)$content];
        }

        return [];
    }
}
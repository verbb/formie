<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class Entry extends Element
{
    // Properties
    // =========================================================================

    public $handle = 'entry';
    public $entryTypeId;
    public $defaultAuthorId;
    public $attributeMapping;
    public $fieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Entry');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/elements/dist/img/entry.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create Entry elements.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['entryTypeId', 'defaultAuthorId'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/elements/entry/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/elements/entry/_form-settings', [
            'integration' => $this,
            'form' => $form,
            'sectionOptions' => $this->getSectionOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAuthor($form)
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

    /**
     * @inheritDoc
     */
    public function getElementFields($entryTypeId = null)
    {
        if (!$entryTypeId) {
            $entryTypeId = $this->getEntryTypes()[0]->id ?? '';

            if (!$entryTypeId) {
                return [];
            }
        }

        $entryType = Craft::$app->getSections()->getEntryTypeById($entryTypeId);
        $options = [];

        foreach ($entryType->getFields() as $field) {
            $options[] = [
                'name' => $field->name,
                'handle' => $field->handle,
            ];
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getElementFieldsFromRequest($request)
    {
        $entryTypeId = $request->getParam('entryTypeId');

        if (!$entryTypeId) {
            return ['error' => Craft::t('formie', 'No “{entryTypeId}” provided.')];
        }

        return $this->getElementFields($entryTypeId);
    }

    /**
     * @inheritDoc
     */
    public function getElementAttributes()
    {
        return [
            [
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ],
            [
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ],
            [
                'name' => Craft::t('app', 'Author'),
                'handle' => 'author',
            ],
            [
                'name' => Craft::t('app', 'Post Date'),
                'handle' => 'postDate',
            ],
            [
                'name' => Craft::t('app', 'Expiry Date'),
                'handle' => 'expiryDate',
            ],
            [
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
            ],
            [
                'name' => Craft::t('app', 'Date Created'),
                'handle' => 'dateCreated',
            ],
            [
                'name' => Craft::t('app', 'Date Updated'),
                'handle' => 'dateUpdated',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function saveElement(Submission $submission)
    {
        if (!$this->entryTypeId) {
            Formie::error('Unable to save element integration. No `entryTypeId`.');

            return false;
        }

        // try {
            $entryType = Craft::$app->getSections()->getEntryTypeById($this->entryTypeId);

            $entry = new EntryElement();
            $entry->typeId = $entryType->id;
            $entry->sectionId = $entryType->sectionId;

            $view = Craft::$app->getView();

            foreach ($this->attributeMapping as $entryFieldHandle => $formFieldHandle) {
                if ($formFieldHandle) {
                    $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                    $fieldValue = $submission->{$formFieldHandle};

                    if ($entryFieldHandle === 'author') {
                        $entry->authorId = $fieldValue->one()->id ?? '';
                    } else {
                        $entry->{$entryFieldHandle} = $fieldValue;
                    }
                }
            }

            foreach ($this->fieldMapping as $entryFieldHandle => $formFieldHandle) {
                if ($formFieldHandle) {
                    $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                    $fieldValue = $submission->{$formFieldHandle};

                    $entry->setFieldValue($entryFieldHandle, $fieldValue);
                }
            }

            if (!$entry->validate()) {
                Formie::error('Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($entry)) {
                Formie::error('Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]);
                
                return false;
            }
        // } catch (\Throwable $e) {
        //     $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}', [
        //         'error' => $e->getMessage(),
        //         'file' => $e->getFile(),
        //         'line' => $e->getLine(),
        //         'submission' => $submission->id,
        //     ]);

        //     Formie::error($error);

        //     return false;
        // }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function getEntryTypes(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        $entryTypes = [];

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            $entryTypes = array_merge($entryTypes, $section->getEntryTypes());
        }

        return $entryTypes;
    }

    private function getSectionOptions(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ];

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            $entryTypes = $section->getEntryTypes();

            $options[] = ['optgroup' => $section->name];

            foreach ($entryTypes as $entryType) {
                $options[] = [
                    'label' => $entryType->name,
                    'value' => $entryType->id,
                ];
            }
        }

        return $options;
    }
}
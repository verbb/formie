<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\fields\Assets as CraftAssets;
use craft\helpers\Assets;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\models\Volume;
use craft\web\UploadedFile;

use GraphQL\Type\Definition\Type;

class FileUpload extends CraftAssets implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait, RelationFieldTrait {
        getFrontEndInputOptions as traitGetFrontendInputOptions;
        getSettingGqlTypes as traitGetSettingGqlTypes;
        FormFieldTrait::getIsFieldset insteadof RelationFieldTrait;
        RelationFieldTrait::defineValueAsString insteadof FormFieldTrait;
        RelationFieldTrait::defineValueAsJson insteadof FormFieldTrait;
        RelationFieldTrait::defineValueForIntegration insteadof FormFieldTrait;
        RelationFieldTrait::defineValueForIntegration as traitDefineValueForIntegration;
        RelationFieldTrait::populateValue insteadof FormFieldTrait;
        RelationFieldTrait::renderLabel insteadof FormFieldTrait;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'File Upload');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/file-upload/icon.svg';
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $searchable = true;
    public ?string $sizeLimit = null;
    public ?string $sizeMinLimit = null;
    public ?string $limitFiles = null;
    public bool $restrictFiles = false;
    public ?array $allowedKinds = null;
    public ?string $uploadLocationSource = null;
    public ?string $uploadLocationSubpath = null;
    public bool $restrictLocation = true;

    protected string $inputTemplate = 'formie/_includes/element-select-input';

    private array $_assetsToDelete = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        // For Assets field compatibility - we always use a single upload location
        $this->restrictedLocationSource = $this->uploadLocationSource;
        $this->restrictedLocationSubpath = $this->uploadLocationSubpath ?? '';
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // For Assets field compatibility - we always use a single upload location
        $this->restrictedLocationSource = $this->uploadLocationSource;
        $this->restrictedLocationSubpath = $this->uploadLocationSubpath ?? '';

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element): mixed
    {
        $values = [];
        foreach ($element->getFieldValue($this->handle)->all() as $asset) {
            /* @var Asset $asset */
            $values[] = $asset->filename;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        return [
            'volumes' => $this->getVolumeOptions(),
            'fileKindOptions' => $this->getFileKindOptions(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        $settings = Formie::$plugin->getSettings();

        $volume = $settings->defaultFileUploadVolume;
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!$volume && !empty($volumes)) {
            $volume = 'folder:' . $volumes[0]->uid;
        }

        return [
            'uploadLocationSource' => $volume,
            'uploadLocationSubpath' => '',
            'restrictFiles' => true,
            'allowedKinds' => [
                'image',
                'pdf',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->limitFiles) {
            $rules[] = 'validateFileLimit';
        }

        if ($this->sizeMinLimit) {
            $rules[] = 'validateMinFileSize';
        }

        if ($this->sizeLimit) {
            $rules[] = 'validateMaxFileSize';
        }

        return $rules;
    }

    /**
     * Validates number of files selected.
     *
     * @param ElementInterface $element
     */
    public function validateFileLimit(ElementInterface $element): void
    {
        $fileLimit = (int)($this->limitFiles ?? 1);

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        if (count($uploadedFiles) > $fileLimit) {
            $element->addError($this->handle, Craft::t('formie', 'Choose up to {files} files.', [
                'files' => $fileLimit,
            ]));
        }
    }

    /**
     * Validates the files to make sure they are over the allowed min file size.
     *
     * @param ElementInterface $element
     */
    public function validateMinFileSize(ElementInterface $element): void
    {
        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        $sizeMinLimit = $this->sizeMinLimit * 1000000;

        foreach ($uploadedFiles as $file) {
            if (file_exists($file['location']) && (filesize($file['location']) < $sizeMinLimit)) {
                $filenames[] = $file['filename'];
            }
        }

        if ($filenames) {
            $element->addError($this->handle, Craft::t('formie', 'File must be larger than {size} MB.', [
                'size' => $this->sizeMinLimit,
            ]));
        }
    }

    /**
     * Validates the files to make sure they are under the allowed max file size.
     *
     * @param ElementInterface $element
     */
    public function validateMaxFileSize(ElementInterface $element): void
    {
        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        $sizeLimit = $this->sizeLimit * 1000000;

        foreach ($uploadedFiles as $file) {
            if (file_exists($file['location']) && (filesize($file['location']) > $sizeLimit)) {
                $filenames[] = $file['filename'];
            }
        }

        if ($filenames) {
            $element->addError($this->handle, Craft::t('formie', 'File must be smaller than {size} MB.', [
                'size' => $this->sizeLimit,
            ]));
        }
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/file-upload/preview', [
            'field' => $this,
        ]);
    }

    /**
     * Returns a comma separated list of allowed file extensions
     * that are allowed to be uploaded.
     *
     * @return string|null
     */
    public function getAccept(): ?string
    {
        if (!$this->restrictFiles) {
            return null;
        }

        $extensions = [];
        $allKinds = Assets::getAllowedFileKinds();

        foreach ($this->allowedKinds as $allowedKind) {
            $kind = $allKinds[$allowedKind];

            foreach ($kind['extensions'] as $extension) {
                $extensions[] = ".$extension";
            }
        }

        return implode(', ', $extensions);
    }

    public function getVolumeOptions(): array
    {
        $volumes = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $volumes[] = [
                'label' => $volume->name,
                'value' => 'folder:' . $volume->uid,
            ];
        }

        return $volumes;
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/file-upload.js', true),
            'module' => 'FormieFileUpload',
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            [
                'label' => Craft::t('formie', 'Upload Location'),
                'help' => Craft::t('formie', 'Note that the subfolder path can contain variables like {myFieldHandle}.'),
                'type' => 'fieldWrap',
                'children' => [
                    [
                        'component' => 'div',
                        'class' => 'flex',
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'uploadLocationSource',
                                'options' => $this->getVolumeOptions(),
                            ]),
                            SchemaHelper::textField([
                                'name' => 'uploadLocationSubpath',
                                'class' => 'text flex-grow',
                                'placeholder' => 'path/to/subfolder',
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        $configLimit = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;
        $phpLimit = (max((int)ini_get('post_max_size'), (int)ini_get('upload_max_filesize'))) * 1048576;
        $maxUpload = $this->humanFilesize(max($phpLimit, $configLimit));

        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Limit Number of Files'),
                'help' => Craft::t('formie', 'Limit the number of files a user can upload.'),
                'name' => 'limitFiles',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Min File Size'),
                'help' => Craft::t('formie', 'Set the minimum size of the files a user can upload.'),
                'name' => 'sizeMinLimit',
                'size' => '3',
                'class' => 'text',
                'type' => 'textWithSuffix',
                'suffix' => Craft::t('formie', 'MB'),
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Max File Size'),
                'help' => Craft::t('formie', 'Set the maximum size of the files a user can upload.'),
                'name' => 'sizeLimit',
                'size' => '3',
                'class' => 'text',
                'type' => 'textWithSuffix',
                'suffix' => Craft::t('formie', 'MB'),
                'validation' => 'optional|number|min:0',
                'warning' => Craft::t('formie', 'Maximum allowed upload size is {size}.', ['size' => $maxUpload]),
            ]),
            SchemaHelper::checkboxField([
                'label' => Craft::t('formie', 'Restrict allowed file types?'),
                'name' => 'restrictFiles',
            ]),
            SchemaHelper::toggleContainer('settings.restrictFiles', [
                SchemaHelper::checkboxField([
                    'name' => 'allowedKinds',
                    'options' => $this->getFileKindOptions(),
                ]),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritdoc
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        // For Assets field compatibility - we always use a single upload location
        $this->restrictedLocationSource = $this->uploadLocationSource;
        $this->restrictedLocationSubpath = $this->uploadLocationSubpath ?? '';

        if (!parent::beforeElementSave($element, $isNew)) {
            return false;
        }

        // If we're going back to a previous page and replacing any assets already uploaded
        // we need to delete them. BUT - we need to check for the existing assets here
        // but wait until `afterElementSave` to delete them, because we must wait for validation
        // to succeed or fail, which happens after this event.

        // First, check if there are any new uploaded files. We're not going to delete anything
        // unless we're replacing things.
        $uploadedFiles = $this->_getUploadedFiles($element);

        if ($uploadedFiles) {
            // Get any already saved assets to delete later
            $value = $element->getFieldValue($this->handle);

            $this->_assetsToDelete = $value->ids();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        parent::afterElementSave($element, $isNew);

        // Were any assets marked as to be deleted?
        if ($this->_assetsToDelete) {
            $assets = Asset::find()->id($this->_assetsToDelete)->all();

            $elementService = Craft::$app->getElements();

            foreach ($assets as $asset) {
                $elementService->deleteElement($asset, true);
            }
        }
    }


    // Protected Methods
    // =========================================================================

    public function getSettingGqlTypes(): array
    {
        return array_merge($this->traitGetSettingGqlTypes(), [
            'allowedKinds' => [
                'name' => 'allowedKinds',
                'type' => Type::listOf(Type::string()),
            ],
            'volumeHandle' => [
                'name' => 'volumeHandle',
                'type' => Type::string(),
                'resolve' => function($class) {
                    return $class->getVolume()->handle ?? '';
                },
            ],
        ]);
    }

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        $value = $this->_all($value, $element)->all();

        return implode(', ', array_map(function($item) {
            // Handle when volumes don't have a public URL
            return $item->url ?? $item->filename;
        }, $value));
    }

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            // For any element integrations, always return IDs (default behaviour)
            if ($integration instanceof Element) {
                return $value->ids();
            }

            $value = $this->getValueAsJson($value, $element);

            return array_map(function($item) {
                // Handle when volumes don't have a public URL
                return $item['url'] ?? $item['filename'];
            }, $value);
        }

        // Fetch the default handling
        return $this->traitDefineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        $html = '';
        $value = $this->_all($value, $element)->all();

        foreach ($value as $asset) {
            if ($asset->url) {
                $html .= Html::tag('a', $asset->filename, ['href' => $asset->url]);
            } else {
                $html .= Html::tag('p', $asset->filename);
            }
        }

        return Template::raw($html);
    }


    // Private Methods
    // =========================================================================

    private function getVolume(): ?Volume
    {
        $sourceKey = $this->uploadLocationSource;

        if ($sourceKey && str_starts_with($sourceKey, 'folder:')) {
            $parts = explode(':', $sourceKey);

            return Craft::$app->getVolumes()->getVolumeByUid($parts[1]);
        }

        return null;
    }

    private function humanFilesize($size, $precision = 2): string
    {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }
        return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }

    /**
     * Returns any files that were uploaded to the field.
     *
     * @param ElementInterface $element
     * @return array
     */
    private function _getUploadedFiles(ElementInterface $element): array
    {
        $uploadedFiles = [];

        // See if we have uploaded file(s).
        $paramName = $this->requestParamName($element);

        if ($paramName !== null) {
            $files = UploadedFile::getInstancesByName($paramName);

            foreach ($files as $file) {
                $uploadedFiles[] = [
                    'filename' => $file->name,
                    'location' => $file->tempName,
                    'type' => 'upload',
                ];
            }
        }

        return $uploadedFiles;
    }
}

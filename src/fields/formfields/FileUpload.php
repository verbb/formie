<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\input\FileUploadInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Settings;

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
        defineHtmlTag as traitDefineHtmlTag;
        RelationFieldTrait::defineValueAsString insteadof FormFieldTrait;
        RelationFieldTrait::defineValueAsJson insteadof FormFieldTrait;
        RelationFieldTrait::defineValueForIntegration insteadof FormFieldTrait;
        RelationFieldTrait::defineValueForIntegration as traitDefineValueForIntegration;
        RelationFieldTrait::populateValue insteadof FormFieldTrait;
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
    public mixed $filenameFormat = null;

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
            'volumes' => $this->getSourceOptions(),
            'fileKindOptions' => $this->getFileKindOptions(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $volume = $settings->defaultFileUploadVolume;
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!$volume && !empty($volumes)) {
            $volume = 'volume:' . $volumes[0]->uid;
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

        $sizeMinLimit = $this->sizeMinLimit * 1024 * 1024;

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

        $sizeLimit = $this->sizeLimit * 1024 * 1024;

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

        $allowedFileExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

        foreach ($this->allowedKinds as $allowedKind) {
            $kind = $allKinds[$allowedKind];

            foreach ($kind['extensions'] as $extension) {
                if (in_array($extension, $allowedFileExtensions)) {
                    $extensions[] = ".$extension";
                }
            }
        }

        return implode(', ', $extensions);
    }

    /**
     * @inheritdoc
     */
    public function getVolumeOptions()
    {
        $volumes = [];

        /* @var Volume $volume */
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
                '$formkit' => 'fieldWrap',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex flex-nowrap',
                        ],
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'uploadLocationSource',
                                'options' => $this->getSourceOptions(),
                            ]),
                            SchemaHelper::textField([
                                'name' => 'uploadLocationSubpath',
                                'class' => 'text flex-grow fullwidth',
                                'outerClass' => 'flex-grow',
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Number of Files'),
                'help' => Craft::t('formie', 'Limit the number of files a user can upload.'),
                'name' => 'limitFiles',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Min File Size'),
                'help' => Craft::t('formie', 'Set the minimum size of the files a user can upload.'),
                'name' => 'sizeMinLimit',
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'MB'),
                    ],
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Max File Size'),
                'help' => Craft::t('formie', 'Set the maximum size of the files a user can upload.'),
                'name' => 'sizeLimit',
                'warning' => Craft::t('formie', 'Maximum allowed upload size is {size}.', ['size' => $maxUpload]),
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'MB'),
                    ],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Filename Format'),
                'help' => Craft::t('formie', 'Enter the format for uploaded files to be renamed as. Do not include the extension.'),
                'name' => 'filenameFormat',
                'variables' => 'plainTextVariables',
            ]),
            SchemaHelper::checkboxField([
                'label' => Craft::t('formie', 'Restrict allowed file types?'),
                'name' => 'restrictFiles',
            ]),
            SchemaHelper::checkboxField([
                'name' => 'allowedKinds',
                'options' => $this->getFileKindOptions(),
                'if' => '$get(restrictFiles).value',
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

        // Check if there are any invalid assets, likely done by bots. This is where the POST
        // data has come in as ['JrFVNoLBCicUTAOn'] instead of a empty value (for new assets) or an ID.
        // This is only usually done by malicious actors manipulating POST data.
        // Note that this is set on the AssetQuery itself.
        $assetIds = $element->getFieldValue($this->handle)->id ?? false;

        if ($assetIds && is_array($assetIds)) {
            foreach ($assetIds as $assetId) {
                if (!(int)$assetId) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        parent::afterElementSave($element, $isNew);

        $elementService = Craft::$app->getElements();

        // Were any assets marked as to be deleted?
        if ($this->_assetsToDelete) {
            $assets = Asset::find()->id($this->_assetsToDelete)->all();

            foreach ($assets as $asset) {
                $elementService->deleteElement($asset, true);
            }
        }

        $submission = $element;

        // Watch out for Group/Repeater
        if ($element instanceof NestedFieldRow) {
            $submission = $element->getOwner();
        }

        // Rename files, if enabled
        $filenameFormat = Variables::getParsedValue($this->filenameFormat, $submission);

        if ($filenameFormat) {
            $assets = $element->getFieldValue($this->handle)->all();

            foreach ($assets as $key => $asset) {
                $suffix = ($key > 0) ? '_' . $key : '';

                $filename = $filenameFormat . $suffix;
                $asset->newFilename = Assets::prepareAssetName($filename . '.' . $asset->getExtension());
                $asset->title = Assets::filename2Title($filename);

                $elementService->saveElement($asset);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType(): array|Type
    {
        return FileUploadInputType::getType($this);
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        $sizeMaxLimit = $this->sizeLimit ?? 0;
        $sizeMinLimit = $this->sizeMinLimit ?? 0;
        $limitFiles = $this->limitFiles ?? 0;

        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'file',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName('[]'),
                'multiple' => $limitFiles != 1,
                'accept' => $this->accept,
                'data' => [
                    'fui-id' => $dataId,
                    'size-min-limit' => $sizeMinLimit,
                    'size-max-limit' => $sizeMaxLimit,
                    'file-limit' => $limitFiles,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        if ($key === 'fieldSummary') {
            return new HtmlTag('div', [
                'class' => 'fui-file-summary',
            ]);
        }

        if ($key === 'fieldSummaryContainer') {
            return new HtmlTag('ul');
        }

        if ($key === 'fieldSummaryItem') {
            return new HtmlTag('li');
        }

        return $this->traitDefineHtmlTag($key, $context);
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

        if ($sourceKey && str_starts_with($sourceKey, 'volume:')) {
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

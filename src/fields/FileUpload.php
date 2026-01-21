<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ElementField;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Repeater;
use verbb\formie\gql\types\input\FileUploadInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Settings;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\ElementCollection;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\FsObjectNotFoundException;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\events\LocateUploadedFilesEvent;
use craft\fields\Assets as CraftAssets;
use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\services\Gql as GqlService;
use craft\web\UploadedFile;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\base\InvalidConfigException;

use Twig\Error\RuntimeError;

class FileUpload extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'File Upload');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/file-upload/icon.svg';
    }

    public static function elementType(): string
    {
        return Asset::class;
    }

    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', AssetQuery::class, ElementCollection::class, Asset::class);
    }


    // Properties
    // =========================================================================

    public bool $allowMultipleSources = false;
    public ?string $sizeLimit = null;
    public ?string $sizeMinLimit = null;
    public ?string $limitFiles = null;
    public bool $restrictFiles = true;
    public ?array $allowedKinds = ['image', 'pdf'];
    public ?string $uploadLocationSource = null;
    public ?string $uploadLocationSubpath = null;
    public mixed $filenameFormat = null;

    protected ?string $cpInputJsClass = 'Craft.AssetSelectInput';
    protected string $cpInputTemplate = '_components/fieldtypes/Assets/input';

    private array $_assetsToDelete = [];
    private array $_uploadedDataFiles = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        if (array_key_exists('restrictLocation', $config)) {
            unset($config['restrictLocation']);
        }

        // Setup defaults from the plugin-level
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!isset($config['uploadLocationSource'])) {
            $config['uploadLocationSource'] = $settings->defaultFileUploadVolume ?? null;
        }

        parent::__construct($config);
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 1 ? Craft::t('formie', 'No asset volumes available. View [asset volume settings]({link}).', ['link' => UrlHelper::cpUrl('settings/assets/volumes')]) : false,
        ];
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/file-upload/preview', [
            'field' => $this,
        ]);
    }

    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        return array_merge([['label' => Craft::t('formie', 'Select an option'), 'value' => '']], $options);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // For GQL mutations, we need a little extra handling here, because the Assets field doesn't support multiple data-encoded items
        // and there's issues when using Repeater > File fields (https://github.com/verbb/formie/issues/1419) we handle things ourselves.
        if (is_array($value) && isset($value['mutationData'])) {
            if ($paramName = $this->requestParamName($element)) {
                // Save for later, in the format `fields.repeater.rows.new2.fields.file`.
                $this->_uploadedDataFiles[$paramName] = $value['mutationData'];
            }

            unset($value['mutationData']);
        }

        return parent::normalizeValue($value, $element);
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return parent::isValueEmpty($value, $element) && empty($this->_getUploadedFiles($element));
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = 'validateFileType';

        if ($this->restrictFiles) {
            $rules[] = 'validateFileType';
        }

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

    public function validateFileType(ElementInterface $element): void
    {
        $filenames = [];

        // Get all the value's assets' filenames
        $value = $element->getFieldValue($this->fieldKey);

        foreach ($value->all() as $asset) {
            $filenames[] = $asset->getFilename();
        }

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        foreach ($uploadedFiles as $file) {
            $filenames[] = $file['filename'];
        }

        // Now make sure that they all check out
        $allowedExtensions = $this->_getAllowedExtensions();

        foreach ($filenames as $filename) {
            if (!in_array(mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $allowedExtensions, true)) {
                $element->addError($this->fieldKey, Craft::t('app', '“{filename}” is not allowed in this field.', [
                    'filename' => $filename,
                ]));
            }
        }
    }

    public function validateFileLimit(ElementInterface $element): void
    {
        $fileLimit = (int)($this->limitFiles ?? 1);

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        if (count($uploadedFiles) > $fileLimit) {
            $element->addError($this->fieldKey, Craft::t('formie', 'Choose up to {files} files.', [
                'files' => $fileLimit,
            ]));
        }
    }

    public function validateMinFileSize(ElementInterface $element): void
    {
        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        $sizeMinLimit = $this->sizeMinLimit * 1000 * 1000;

        foreach ($uploadedFiles as $file) {
            // Watch for data (GQL), which doesn't support this validation yet
            if (isset($file['path'])) {
                if (file_exists($file['path']) && (filesize($file['path']) < $sizeMinLimit)) {
                    $filenames[] = $file['filename'];
                }
            }
        }

        if ($filenames) {
            $element->addError($this->fieldKey, Craft::t('formie', 'File must be larger than {filesize} MB.', [
                'filesize' => $this->sizeMinLimit,
            ]));
        }
    }

    public function validateMaxFileSize(ElementInterface $element): void
    {
        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        $sizeLimit = $this->sizeLimit * 1000 * 1000;

        foreach ($uploadedFiles as $file) {
            // Watch for data (GQL), which doesn't support this validation yet
            if (isset($file['path'])) {
                if (file_exists($file['path']) && (filesize($file['path']) > $sizeLimit)) {
                    $filenames[] = $file['filename'];
                }
            }
        }

        if ($filenames) {
            $element->addError($this->fieldKey, Craft::t('formie', 'File must be smaller than {filesize} MB.', [
                'filesize' => $this->sizeLimit,
            ]));
        }
    }

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

    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        return [$this->uploadLocationSource];
    }

    public function getFileKindOptions(): array
    {
        $fileKindOptions = [];

        foreach (Assets::getAllowedFileKinds() as $value => $kind) {
            $fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $fileKindOptions;
    }

    public function resolveDynamicPathToFolderId(?ElementInterface $element = null): int
    {
        return $this->_uploadFolder($element)->id;
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/file-upload.js'),
            'module' => 'FormieFileUpload',
        ];
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            [
                'label' => Craft::t('formie', 'Upload Location'),
                'help' => Craft::t('formie', 'Note that the subfolder path can contain variables like {myFieldHandle}.'),
                '$formkit' => 'fieldWrap',
                'required' => true,
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

    public function defineSettingsSchema(): array
    {
        $configLimit = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;
        $phpLimit = (max((int)ini_get('post_max_size'), (int)ini_get('upload_max_filesize'))) * 1048576;
        $maxUpload = $this->_humanFilesize(max($phpLimit, $configLimit));

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
            SchemaHelper::includeInEmailField(),
            SchemaHelper::emailNotificationValue(),
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

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
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

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
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
            $value = $element->getFieldValue($this->fieldKey);

            $this->_assetsToDelete = $value->ids();
        }

        // Check if there are any invalid assets, likely done by bots. This is where the POST
        // data has come in as ['JrFVNoLBCicUTAOn'] instead of a empty value (for new assets) or an ID.
        // This is only usually done by malicious actors manipulating POST data.
        // Note that this is set on the AssetQuery itself.
        $assetIds = $element->getFieldValue($this->fieldKey)->id ?? false;

        if ($assetIds && is_array($assetIds)) {
            foreach ($assetIds as $assetId) {
                if (!(int)$assetId) {
                    return false;
                }
            }
        }

        return true;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Process any uploads and turn into assets
        $this->_processAssets($element);

        $elementService = Craft::$app->getElements();

        // Were any assets marked as to be deleted?
        if ($this->_assetsToDelete) {
            $assets = Asset::find()->id($this->_assetsToDelete)->all();

            foreach ($assets as $asset) {
                $elementService->deleteElement($asset, true);
            }
        }

        // Rename files, if enabled
        if ($this->filenameFormat) {
            if ($filenameFormat = Variables::getParsedValue($this->filenameFormat, $element)) {
                $assets = $element->getFieldValue($this->fieldKey)->all();

                foreach ($assets as $key => $asset) {
                    $suffix = ($key > 0) ? '_' . $key : '';

                    // Introduce an additional suffix for repeaters
                    // if ($element instanceof NestedFieldRow) {
                    //     if ($element->getField() instanceof Repeater) {
                    //         $suffix = '_' . $element->sortOrder . $suffix;
                    //     }
                    // }

                    $filename = $filenameFormat . $suffix;
                    $asset->newFilename = Assets::prepareAssetName($filename . '.' . $asset->getExtension());
                    $asset->title = Assets::filename2Title($filename);

                    $elementService->saveElement($asset);
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return FileUploadInputType::getType($this);
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(AssetInterface::getType())),
            'args' => AssetArguments::getArguments(),
            'resolve' => AssetResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
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
            return new HtmlTag('input', [
                'type' => 'file',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName('[]'),
                'multiple' => $limitFiles != 1,
                'accept' => $this->getAccept(),
                'data' => [
                    'fui-id' => $dataId,
                    'size-min-limit' => $sizeMinLimit,
                    'size-max-limit' => $sizeMaxLimit,
                    'file-limit' => $limitFiles,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
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

        return parent::defineHtmlTag($key, $context);
    }

    public function getSettingGqlTypes(): array
    {
        $types = parent::getSettingGqlTypes();

        // Remove some inherited types that don't make sense here
        unset($types['limitOptions'], $types['multi']);

        return array_merge($types, [
            'sizeLimit' => [
                'name' => 'sizeLimit',
                'type' => Type::string(),
            ],
            'sizeMinLimit' => [
                'name' => 'sizeMinLimit',
                'type' => Type::string(),
            ],
            'limitFiles' => [
                'name' => 'limitFiles',
                'type' => Type::string(),
            ],
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


    // Protected Methods
    // =========================================================================

    protected function availableSources(): array
    {
        // Index by key so it'll be easier to potentially remove
        $sources = ArrayHelper::index(parent::availableSources(), 'key');

        $settings = Formie::$plugin->getSettings();

        // Ensure that only return volumes that are allowed
        if (!$settings->allowPublicVolumes) {
            $volumesByKey = [];

            foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
                if ($volume->fs->hasUrls) {
                    unset($sources["volume:{$volume->uid}"]);
                }
            }
        }

        return array_values($sources);
    }

    protected function cpInputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::cpInputTemplateVariables($value, $element);

        $uploadVolume = $this->_getVolume();
        $uploadFs = $uploadVolume?->getFs();

        $variables['fsType'] = $uploadFs ? get_class($uploadFs) : null;
        $variables['showFolders'] = true;
        $variables['defaultFieldLayoutId'] = $uploadVolume->fieldLayoutId ?? null;
        $variables['limit'] = $this->limitFiles;
        $variables['showSourcePath'] = true;

        // The outer "Upload" button only supports a true Assets field. Uploads within the element select are fine.
        $variables['canUpload'] = false;

        return $variables;
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return implode(', ', array_map(function($item) {
            // Handle when volumes don't have a public URL
            return $item->url ?? $item->filename;
        }, $value->all()));
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
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
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        $html = '';

        foreach ($value->all() as $asset) {
            if ($asset->url) {
                $html .= Html::tag('a', $asset->filename, ['href' => $asset->url]);
            } else {
                $html .= Html::tag('p', $asset->filename);
            }
        }

        return Template::raw($html);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return Asset::find()->limit(1);
    }


    // Private Methods
    // =========================================================================

    private function _processAssets(ElementInterface $element): void
    {
        $query = $element->getFieldValue($this->fieldKey);
        $assetsService = Craft::$app->getAssets();

        $getUploadFolderId = function() use ($element, &$_targetFolderId): int {
            return $_targetFolderId ?? ($_targetFolderId = $this->_uploadFolder($element)->id);
        };

        // Were there any uploaded files?
        $uploadedFiles = $this->_getUploadedFiles($element);

        if (!empty($uploadedFiles)) {
            $uploadFolderId = $getUploadFolderId();

            // Convert them to assets
            $assetIds = [];

            foreach ($uploadedFiles as $file) {
                $tempPath = Assets::tempFilePath($file['filename']);

                switch ($file['type']) {
                    case 'data':
                        FileHelper::writeToFile($tempPath, $file['data']);
                        break;
                    case 'file':
                        rename($file['path'], $tempPath);
                        break;
                    case 'upload':
                        move_uploaded_file($file['path'], $tempPath);
                        break;
                }

                $uploadFolder = $assetsService->getFolderById($uploadFolderId);
                $asset = new Asset();
                $asset->tempFilePath = $tempPath;
                $asset->setFilename($file['filename']);
                $asset->newFolderId = $uploadFolderId;
                $asset->setVolumeId($uploadFolder->volumeId);
                $asset->uploaderId = Craft::$app->getUser()->getId();
                $asset->avoidFilenameConflicts = true;
                $asset->setScenario(Asset::SCENARIO_CREATE);

                if (Craft::$app->getElements()->saveElement($asset)) {
                    $assetIds[] = $asset->id;
                } else {
                    Formie::info('Couldn’t save uploaded asset due to validation errors: ' . implode(', ', $asset->getFirstErrors()));
                }
            }

            if (!empty($assetIds)) {
                // Add the newly uploaded IDs to the mix.
                if (is_array($query->id)) {
                    $query = $this->normalizeValue(array_merge($query->id, $assetIds), $element);
                } else {
                    $query = $this->normalizeValue($assetIds, $element);
                }

                $element->setFieldValue($this->fieldKey, $query);

                // Unset the GQL data, but only for this field. If in a repeater, there's more to process
                if ($paramName = $this->requestParamName($element)) {
                    unset($this->_uploadedDataFiles[$paramName]);
                }

                // Save against the submission, so we can populate on the front-end.
                $element->getForm()->addSubmitData([
                    'event' => 'FormieFileUpload',
                    'data' => [
                        $this->fieldKey => $assetIds,
                    ],
                ]);
            }
        }

        // Are there any related assets?
        $assets = $query->all();

        if (!empty($assets)) {
            $rootRestrictedFolderId = $this->_uploadFolder($element)->id;

            $assetsToMove = array_filter($assets, function(Asset $asset) use ($rootRestrictedFolderId, $assetsService) {
                if ($asset->folderId === $rootRestrictedFolderId) {
                    return false;
                }

                $rootRestrictedFolder = $assetsService->getFolderById($rootRestrictedFolderId);

                return (
                    $asset->volumeId !== $rootRestrictedFolder->volumeId ||
                    !str_starts_with($asset->folderPath, $rootRestrictedFolder->path)
                );
            });

            if (!empty($assetsToMove)) {
                $uploadFolder = $assetsService->getFolderById($getUploadFolderId());

                // Resolve all conflicts by keeping both
                foreach ($assetsToMove as $asset) {
                    $asset->avoidFilenameConflicts = true;

                    try {
                        $assetsService->moveAsset($asset, $uploadFolder);
                    } catch (FsObjectNotFoundException $e) {
                        // Don't freak out about that.
                        Formie::info('Couldn’t move asset because the file doesn’t exist: ' . $e->getMessage());
                    }
                }
            }

            // We now need to update the submission with the IDs of asset for this field, so do a direct record update
            // because this is triggered after the element has been saved, and we don't want to end up in a loop.
            // Using direct queries is also too risky with JSON columns and database engines.
            if ($record = SubmissionRecord::findOne($element->id)) {
                // Re-serializing submission values will include IDs now
                $record->content = $element->serializeFieldValues();

                $record->save(false);
            }
        }
    }

    private function _getVolume(): ?Volume
    {
        $sourceKey = $this->uploadLocationSource;

        if ($sourceKey && (str_starts_with($sourceKey, 'volume:') || str_starts_with($sourceKey, 'folder:'))) {
            $parts = explode(':', $sourceKey);

            return Craft::$app->getVolumes()->getVolumeByUid($parts[1]);
        }

        return null;
    }

    private function _humanFilesize(mixed $size, int $precision = 2): string
    {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }
        return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }

    private function _getUploadedFiles(ElementInterface $element): array
    {
        $files = [];

        // Grab data strings
        if (isset($this->_uploadedDataFiles['data']) && is_array($this->_uploadedDataFiles['data'])) {
            foreach ($this->_uploadedDataFiles['data'] as $index => $dataString) {
                if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9\+\-\.]+);base64,(?<data>.+)/i', $dataString, $matches)) {
                    $type = $matches['type'];
                    $data = base64_decode($matches['data']);

                    if (!$data) {
                        continue;
                    }

                    if (!empty($this->_uploadedDataFiles['filename'][$index])) {
                        $filename = $this->_uploadedDataFiles['filename'][$index];
                    } else {
                        $extensions = FileHelper::getExtensionsByMimeType($type);

                        if (empty($extensions)) {
                            continue;
                        }

                        $filename = 'Uploaded_file.' . reset($extensions);
                    }

                    $files[] = [
                        'filename' => $filename,
                        'data' => $data,
                        'type' => 'data',
                    ];
                }
            }
        }

        // See if we have uploaded file(s).
        $paramName = $this->requestParamName($element);

        if ($paramName !== null) {
            $uploadedFiles = UploadedFile::getInstancesByName($paramName);

            // Handle GraphQL
            if (isset($this->_uploadedDataFiles[$paramName])) {
                $files = $this->_uploadedDataFiles[$paramName];
            }

            foreach ($uploadedFiles as $uploadedFile) {
                $files[] = [
                    'filename' => $uploadedFile->name,
                    'path' => $uploadedFile->tempName,
                    'type' => 'upload',
                ];
            }
        }

        return $files;
    }

    private function _findFolder(?ElementInterface $element): VolumeFolder
    {
        $subpath = $this->uploadLocationSubpath;

        // Make sure the volume and root folder actually exist
        $volume = $this->_getVolume();

        if (!$volume) {
            throw new InvalidFsException("Invalid volume: $this->uploadLocationSource");
        }

        $assetsService = Craft::$app->getAssets();
        $rootFolder = $assetsService->getRootFolderByVolumeId($volume->id);

        // Are we looking for the root folder?
        $subpath = trim($subpath ?? '', '/');

        if ($subpath === '') {
            return $rootFolder;
        }

        $isDynamic = preg_match('/\{|\}/', $subpath);

        if ($isDynamic) {
            // Prepare the path by parsing tokens and normalizing slashes.
            try {
                $renderedSubpath = Craft::$app->getView()->renderObjectTemplate($subpath, $element);
            } catch (InvalidConfigException|RuntimeError $e) {
                throw new InvalidSubpathException($subpath, null, 0, $e);
            }

            // Did any of the tokens return null?
            if ($renderedSubpath === '' || trim($renderedSubpath, '/') != $renderedSubpath || str_contains($renderedSubpath, '//')) {
                throw new InvalidSubpathException($subpath);
            }

            // Sanitize the subpath
            $segments = array_filter(explode('/', $renderedSubpath), function(string $segment): bool {
                return $segment !== ':ignore:';
            });

            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $segments = array_map(function(string $segment) use ($generalConfig): string {
                return FileHelper::sanitizeFilename($segment, [
                    'asciiOnly' => $generalConfig->convertFilenamesToAscii,
                ]);
            }, $segments);

            $subpath = implode('/', $segments);
        }

        $folder = $assetsService->findFolder([
            'volumeId' => $volume->id,
            'path' => $subpath . '/',
        ]);

        // Ensure that the folder exists
        if (!$folder) {
            $folder = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
        }

        return $folder;
    }

    private function _uploadFolder(?ElementInterface $element = null): VolumeFolder
    {
        try {
            if (!$this->uploadLocationSource) {
                throw new InvalidFsException();
            }

            return $this->_findFolder($element);
        } catch (InvalidFsException $e) {
            throw new InvalidFsException(Craft::t('app', 'The {field} field is set to an invalid volume.', [
                'field' => $this->label,
            ]), 0, $e);
        } catch (InvalidSubpathException $e) {
            // If this is a new/disabled element, the subpath probably just contained a token that returned null, like {id}
            // so use the user’s upload folder instead
            if ($element === null || !$element->id || !$element->enabled) {
                return Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            }

            // Existing element, so this is just a bad subpath
            throw new InvalidSubpathException($e->subpath, Craft::t('app', 'The {field} field has an invalid subpath (“{subpath}”).', [
                'field' => $this->label,
                'subpath' => $e->subpath,
            ]), 0, $e);
        }
    }

    private function _getAllowedExtensions(): array
    {
        if (!is_array($this->allowedKinds)) {
            return [];
        }

        $extensions = [];
        $allKinds = Assets::getFileKinds();

        foreach ($this->allowedKinds as $allowedKind) {
            foreach ($allKinds[$allowedKind]['extensions'] as $ext) {
                $extensions[] = $ext;
            }
        }

        return $extensions;
    }
}

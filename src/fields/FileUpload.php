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
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\values\FileUploadFieldValue;
use verbb\formie\gql\types\input\FileUploadInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Settings;
use verbb\formie\records\Submission as SubmissionRecord;

use verbb\formie\theme\context\RenderContext;

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
use craft\helpers\UrlHelper;
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return self::gqlElementContentTypeDefinitionFromConfig(
            $config,
            AssetInterface::getType(),
            AssetArguments::getArguments(),
            AssetResolver::class,
        );
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return FileUploadInputType::getType(null);
    }


    // Constants
    // =========================================================================

    private const ACTIVE_CONTENT_EXTENSIONS = ['svg', 'svgz', 'html', 'htm', 'xhtml', 'xml'];
    

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

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_FILE;
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 1 ? Craft::t('formie', 'No asset volumes available. View [asset volume settings]({link}).', ['link' => UrlHelper::cpUrl('settings/assets/volumes')]) : false,
        ];
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewFileInput(),
        ];
    }

    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        return array_merge([['label' => Craft::t('formie', 'Select an option'), 'value' => '']], $options);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (is_array($value) && !isset($value['mutationData']) && array_is_list($value)) {
            $hasCanonicalUploadPayload = false;
            $assetIds = [];

            foreach ($value as $item) {
                if (!is_array($item)) {
                    continue;
                }

                if (isset($item['assetId'])) {
                    $assetId = (int)$item['assetId'];

                    if ($assetId) {
                        $assetIds[] = $assetId;
                        $hasCanonicalUploadPayload = true;
                    }

                    continue;
                }

                if (!empty($item['fileData'])) {
                    $hasCanonicalUploadPayload = true;
                }
            }

            if ($hasCanonicalUploadPayload) {
                $value = $assetIds && count($assetIds) === count($value)
                    ? $assetIds
                    : ['mutationData' => $value];
            }
        }

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

        $rules[] = [$this->handle, 'validateFileType'];

        if ($this->restrictFiles) {
            $rules[] = [$this->handle, 'validateFileType'];
        }

        if ($this->limitFiles) {
            $rules[] = [$this->handle, 'validateFileLimit'];
        }

        if ($this->sizeMinLimit) {
            $rules[] = [$this->handle, 'validateMinFileSize'];
        }

        if ($this->sizeLimit) {
            $rules[] = [$this->handle, 'validateMaxFileSize'];
        }

        return $rules;
    }

    public function validateFileType(ElementInterface $element): void
    {
        // Get all the value's assets' filenames
        $value = $element->getFieldValue($this->valueKey());

        foreach ($value->all() as $asset) {
            foreach ($this->getUploadTypeValidationErrors($asset->getFilename()) as $message) {
                $element->addError($this->valueKey(), $message);
            }
        }

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        foreach ($uploadedFiles as $file) {
            foreach ($this->getUploadTypeValidationErrors(
                (string)($file['filename'] ?? ''),
                $file['path'] ?? null,
                $file['mimeType'] ?? null,
            ) as $message) {
                $element->addError($this->valueKey(), $message);
            }
        }
    }

    public function validateFileLimit(ElementInterface $element): void
    {
        $fileLimit = (int)($this->limitFiles ?? 1);

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);

        if (count($uploadedFiles) > $fileLimit) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_FILES, [
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
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_FILE_SIZE, [
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
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_FILE_SIZE, [
                'filesize' => $this->sizeLimit,
            ]));
        }
    }

    public function getAccept(): ?string
    {
        if (!$this->restrictFiles) {
            return null;
        }

        $extensions = array_map(static fn(string $extension) => ".$extension", $this->_getPermittedExtensions());

        return implode(', ', $extensions);
    }

    public function sanitizeUploadedFilename(string $filename): string
    {
        $filename = basename(trim($filename));
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return FileHelper::sanitizeFilename($filename, [
            'asciiOnly' => $generalConfig->convertFilenamesToAscii,
        ]);
    }

    public function getAllowedExtensions(): array
    {
        return $this->_getAllowedExtensions();
    }

    public function exceedsMaxUploadSize(int $size): bool
    {
        if (!$this->sizeLimit) {
            return false;
        }

        return $size > ((float)$this->sizeLimit * 1000 * 1000);
    }

    public function getUploadTypeValidationErrors(string $filename, ?string $path = null, ?string $mimeType = null): array
    {
        $filename = $this->sanitizeUploadedFilename($filename);

        if ($filename === '') {
            return [Craft::t('formie', 'Invalid upload filename.')];
        }

        $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $errors = [];

        if ($extension === '' || in_array($extension, self::ACTIVE_CONTENT_EXTENSIONS, true)) {
            $errors[] = Craft::t('app', '“{filename}” is not allowed in this field.', [
                'filename' => $filename,
            ]);
        }

        if ($extension !== '' && !in_array($extension, Craft::$app->getConfig()->getGeneral()->allowedFileExtensions, true)) {
            $errors[] = Craft::t('app', '“{filename}” is not allowed in this field.', [
                'filename' => $filename,
            ]);
        }

        if ($this->restrictFiles && !in_array($extension, $this->_getAllowedExtensions(), true)) {
            $errors[] = Craft::t('app', '“{filename}” is not allowed in this field.', [
                'filename' => $filename,
            ]);
        }

        $declaredKind = Assets::getFileKindByExtension($filename);
        $detectedKind = $this->_resolveDetectedFileKind($path, $mimeType);

        if ($declaredKind !== Asset::KIND_UNKNOWN && $detectedKind !== null && $detectedKind !== Asset::KIND_UNKNOWN && $declaredKind !== $detectedKind) {
            $errors[] = Craft::t('formie', '“{filename}” does not match its detected file type.', [
                'filename' => $filename,
            ]);
        }

        return array_values(array_unique($errors));
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

    public function getFileKindOptions(): array
    {
        $fileKindOptions = [];

        foreach (Assets::getAllowedFileKinds() as $value => $kind) {
            $fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $fileKindOptions;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Upload Location'),
                'instructions' => Craft::t('formie', 'Note that the subfolder path can contain variables like {myFieldHandle}.'),
                'required' => true,
                'children' => [
                    SchemaHelper::selectField([
                        'name' => 'uploadLocationSource',
                        'options' => $this->getSourceOptions(),
                        'validation' => 'required',
                        'required' => true,
                    ]),
                    SchemaHelper::textField([
                        'name' => 'uploadLocationSubpath',
                        'class' => 'text flex-grow fullwidth',
                        'outerClass' => 'flex-grow',
                        'placeholder' => 'path/to/subfolder',
                    ]),
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        $allowPublicVolumes = FieldBuilderPolicy::allowPublicVolumes();

        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue([
                'options' => array_values(array_filter([
                    $allowPublicVolumes ? ['label' => Craft::t('formie', 'Public URL'), 'value' => 'publicUrl'] : null,
                    ['label' => Craft::t('formie', 'Control Panel URL'), 'value' => 'cpUrl'],
                ])),
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Filename Format'),
                'instructions' => Craft::t('formie', 'Enter the format for uploaded files to be renamed as. Do not include the extension.'),
                'name' => 'filenameFormat',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Restrict File Types'),
                'instructions' => Craft::t('formie', 'Whether to restrict the allowed types of files a user can upload.'),
                'name' => 'restrictFiles',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Allowed File Types'),
                'instructions' => Craft::t('formie', 'Select the allowed file types.'),
                'name' => 'allowedKinds',
                'options' => $this->getFileKindOptions(),
                'if' => 'restrictFiles',
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        $configLimit = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;
        $phpLimit = (max((int)ini_get('post_max_size'), (int)ini_get('upload_max_filesize'))) * 1048576;
        $maxUpload = $this->_humanFilesize(max($phpLimit, $configLimit));

        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Number of Files'),
                'instructions' => Craft::t('formie', 'Limit the number of files a user can upload.'),
                'name' => 'limitFiles',
            ]),
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_MAX_FILES,
                'name' => 'validationMessages.maxFiles',
                'if' => 'limitFiles',
                'tokens' => ['files'],
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Min File Size'),
                'instructions' => Craft::t('formie', 'Set the minimum size of the files a user can upload.'),
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'sizeMinLimit',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'MB'),
                    ],
                ],
            ]),
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_MIN_FILE_SIZE,
                'name' => 'validationMessages.minFileSize',
                'if' => 'sizeMinLimit',
                'tokens' => ['filesize'],
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Max File Size'),
                'instructions' => Craft::t('formie', 'Set the maximum size of the files a user can upload.'),
                'warning' => Craft::t('formie', 'Maximum allowed upload size is {size}.', ['size' => $maxUpload]),
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'sizeLimit',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'MB'),
                    ],
                ],
            ]),
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_MAX_FILE_SIZE,
                'name' => 'validationMessages.maxFileSize',
                'if' => 'sizeLimit',
                'tokens' => ['filesize'],
            ]),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
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
            $value = $element->getFieldValue($this->valueKey());

            $this->_assetsToDelete = $value->ids();
        }

        // Check if there are any invalid assets, likely done by bots. This is where the POST
        // data has come in as ['JrFVNoLBCicUTAOn'] instead of a empty value (for new assets) or an ID.
        // This is only usually done by malicious actors manipulating POST data.
        // Note that this is set on the AssetQuery itself.
        $assetIds = $element->getFieldValue($this->valueKey())->id ?? false;

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
            if ($filenameFormat = References::parseContent($this->filenameFormat, $element)) {
                $assets = $element->getFieldValue($this->valueKey())->all();

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

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        $sizeMaxLimit = $this->sizeLimit ?? 0;
        $sizeMinLimit = $this->sizeMinLimit ?? 0;
        $limitFiles = $this->limitFiles ?? 0;

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'file',
                    'id' => $id,
                    'name' => $this->getHtmlName('[]'),
                    'multiple' => $limitFiles != 1,
                    'accept' => $this->getAccept(),
                    'data-formie-input' => true,
                    'data-formie-file-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'file',
                    'data-formie-file-upload-key' => $id,
                    'data-formie-input-error-state' => $errors ? true : false,
                    'data-formie-size-min-limit' => $sizeMinLimit,
                    'data-formie-size-max-limit' => $sizeMaxLimit,
                    'data-formie-file-limit' => $limitFiles,
                    'data-formie-file-upload-hydrate-endpoint' => UrlHelper::actionUrl('formie/file-upload/hydrate'),
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::fileUploadValidationClientAttributes(
                    $this,
                    (int)$limitFiles,
                    (float)$sizeMinLimit,
                    (float)$sizeMaxLimit,
                )))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-file-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldSummary') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-file-summary' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-note',
                        'formie-file-summary',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryContainer') {
            return SlotTag::make('ul')
                ->core([
                    'data-formie-file-summary-container' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-file-summary-container',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryItem') {
            return SlotTag::make('li')
                ->core([
                    'data-formie-file-summary-item' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-file-summary-item',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
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
        $variables['showSourcePath'] = false;

        // The outer "Upload" button only supports a true Assets field. Uploads within the element select are fine.
        $variables['canUpload'] = false;

        return $variables;
    }

    protected function supportedDefaults(): array
    {
        return ['uploadLocationSource'];
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

            $value = $this->getValueAsArray($value, $element);

            return array_map(function($item) {
                // Handle when volumes don't have a public URL
                return $item['url'] ?? $item['filename'];
            }, $value);
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        $html = '';

        foreach ($value->all() as $asset) {
            $url = $this->getSafeElementUrl($asset);

            if ($url) {
                $html .= Html::tag('a', $asset->filename, ['href' => $url]);
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

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'multiple' => (int)($this->limitFiles ?? 0) !== 1,
            'limitFiles' => $this->limitFiles !== null ? (int)$this->limitFiles : null,
            'allowedKinds' => array_values($this->allowedKinds ?? []),
            'restrictFiles' => (bool)$this->restrictFiles,
            'sizeMinLimit' => $this->sizeMinLimit !== null ? (float)$this->sizeMinLimit : null,
            'sizeLimit' => $this->sizeLimit !== null ? (float)$this->sizeLimit : null,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = new ClientModule([
            'id' => 'file-upload',
            'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
        ]);

        return $modules;
    }

    protected function defineInputTemplatePath(): string
    {
        return 'fields/' . static::kebabClassName();
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [
                    Variables::TYPE_TEXT,
                    Variables::TYPE_URL,
                    Variables::TYPE_BOOLEAN,
                ],
            ]),
            FieldReferenceValue::property([
                'handle' => 'url',
                'label' => Craft::t('formie', 'URL'),
                'variableTypes' => [
                    Variables::TYPE_TEXT,
                    Variables::TYPE_URL,
                ],
            ]),
            FieldReferenceValue::property([
                'handle' => 'filename',
                'label' => Craft::t('formie', 'Filename'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'extension',
                'label' => Craft::t('formie', 'Extension'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'size',
                'label' => Craft::t('formie', 'Size'),
                'variableTypes' => [
                    Variables::TYPE_NUMBER,
                    Variables::TYPE_TEXT,
                ],
            ]),
        ];
    }

    protected function defineValueClass(): ?string
    {
        return FileUploadFieldValue::class;
    }


    // Private Methods
    // =========================================================================

    private function _processAssets(ElementInterface $element): void
    {
        $query = $element->getFieldValue($this->valueKey());
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
                    Formie::$plugin->getFileUploads()->trackFromFieldAsset($asset, $this, $element);
                } else {
                    Formie::info('Couldn’t save uploaded asset due to validation errors: ' . implode(', ', $asset->getFirstErrors()));

                    foreach ($asset->getFirstErrors() as $message) {
                        $element->addError($this->valueKey(), (string)$message);
                    }

                    if (!$asset->getFirstErrors()) {
                        $element->addError($this->valueKey(), Craft::t('formie', 'Couldn’t save uploaded file “{filename}”.', [
                            'filename' => $file['filename'],
                        ]));
                    }
                }
            }

            if (!empty($assetIds)) {
                // Add the newly uploaded IDs to the mix.
                if (is_array($query->id)) {
                    $query = $this->normalizeValue(array_merge($query->id, $assetIds), $element);
                } else {
                    $query = $this->normalizeValue($assetIds, $element);
                }

                $element->setFieldValue($this->valueKey(), $query);

                // Unset the GQL data, but only for this field. If in a repeater, there's more to process
                if ($paramName = $this->requestParamName($element)) {
                    unset($this->_uploadedDataFiles[$paramName]);
                }

                // Tell the ajax frontend which persisted asset ids now belong to
                // this field so it can replace pending file names with real assets.
                $form = $element->getForm();
                $inputName = $this->getHtmlName('[]');
                $inputKey = $this->getHtmlId($form);

                $form->addSubmitData([
                    'event' => 'formie:file-upload:uploaded',
                    'data' => [
                        'fieldHandle' => $this->handle,
                        'inputKey' => $inputKey,
                        'inputName' => $inputName,
                        'assetIds' => $assetIds,
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['uploadLocationSource'],
            'required',
            'when' => fn() => empty(Formie::$plugin->getFormDefaults()->resolveFieldTypeDefaults(self::class)['uploadLocationSource'] ?? null),
            'message' => Craft::t('formie', 'Upload Location must be selected.'),
        ];

        return $rules;
    }

    private function _getEffectiveUploadLocationSource(): ?string
    {
        if ($this->uploadLocationSource) {
            return $this->uploadLocationSource;
        }

        $default = Formie::$plugin->getFormDefaults()->resolveFieldTypeDefaults(self::class)['uploadLocationSource'] ?? null;

        return $default !== '' && $default !== null ? $default : null;
    }

    private function _getVolume(): ?Volume
    {
        $sourceKey = $this->_getEffectiveUploadLocationSource();

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
            throw new InvalidFsException('Invalid volume: ' . ($this->_getEffectiveUploadLocationSource() ?? ''));
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
            if (!$this->_getEffectiveUploadLocationSource()) {
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
        return $this->_getPermittedExtensions();
    }

    private function _getPermittedExtensions(): array
    {
        if (!is_array($this->allowedKinds)) {
            return [];
        }

        $extensions = [];
        $allKinds = Assets::getAllowedFileKinds();
        $allowedFileExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

        foreach ($this->allowedKinds as $allowedKind) {
            if (!isset($allKinds[$allowedKind])) {
                continue;
            }

            foreach ($allKinds[$allowedKind]['extensions'] as $extension) {
                if (in_array($extension, $allowedFileExtensions, true) && !in_array($extension, self::ACTIVE_CONTENT_EXTENSIONS, true)) {
                    $extensions[] = $extension;
                }
            }
        }

        return array_values(array_unique($extensions));
    }

    private function _resolveDetectedFileKind(?string $path = null, ?string $mimeType = null): ?string
    {
        $mimeType = $this->_resolveDetectedMimeType($path, $mimeType);

        if (!$mimeType) {
            return null;
        }

        foreach ((array)FileHelper::getExtensionsByMimeType($mimeType) as $extension) {
            $extension = trim((string)$extension);

            if ($extension === '') {
                continue;
            }

            $kind = Assets::getFileKindByExtension("upload.{$extension}");

            if ($kind !== Asset::KIND_UNKNOWN) {
                return $kind;
            }
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') => Asset::KIND_IMAGE,
            str_starts_with($mimeType, 'audio/') => Asset::KIND_AUDIO,
            str_starts_with($mimeType, 'video/') => Asset::KIND_VIDEO,
            str_starts_with($mimeType, 'text/') => Asset::KIND_TEXT,
            $mimeType === 'application/pdf' => Asset::KIND_PDF,
            $mimeType === 'application/json' => Asset::KIND_JSON,
            $mimeType === 'application/xml',
            $mimeType === 'text/xml' => Asset::KIND_XML,
            $mimeType === 'text/html',
            $mimeType === 'application/xhtml+xml' => Asset::KIND_HTML,
            $mimeType === 'application/javascript',
            $mimeType === 'text/javascript' => Asset::KIND_JAVASCRIPT,
            default => null,
        };
    }

    private function _resolveDetectedMimeType(?string $path = null, ?string $mimeType = null): ?string
    {
        if ($path && is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $resolvedMimeType = finfo_file($finfo, $path);
                finfo_close($finfo);

                if (is_string($resolvedMimeType)) {
                    $resolvedMimeType = mb_strtolower(trim($resolvedMimeType));

                    if ($resolvedMimeType !== '') {
                        return $resolvedMimeType;
                    }
                }
            }
        }

        if (!is_string($mimeType)) {
            return null;
        }

        $mimeType = mb_strtolower(trim($mimeType));

        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            return null;
        }

        return $mimeType;
    }

}

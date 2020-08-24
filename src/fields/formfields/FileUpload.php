<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\Volume;
use craft\elements\Asset;
use craft\fields\Assets as CraftAssets;
use craft\helpers\Assets;
use craft\helpers\Json;

class FileUpload extends CraftAssets implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getFrontEndInputOptions as traitGetFrontendInputOptions;
    }
    use RelationFieldTrait;


    // Properties
    // =========================================================================

    protected $inputTemplate = 'formie/_includes/elementSelect';


    // Public Properties
    // =========================================================================

    public $sizeLimit;
    public $limitFiles;
    public $restrictFiles;
    public $allowedKinds;
    public $uploadLocationSource;
    public $uploadLocationSubpath;
    public $useSingleFolder = true;


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


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        // For Assets field compatibility - we always use a single upload location
        $this->singleUploadLocationSource = $this->uploadLocationSource;
        $this->singleUploadLocationSubpath = $this->uploadLocationSubpath ?? '';
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->singleUploadLocationSource = $this->uploadLocationSource;
        $this->singleUploadLocationSubpath = $this->uploadLocationSubpath ?? '';

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element)
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
        $volume = null;
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!empty($volumes)) {
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
        $rules[] = 'validateFileLimit';

        return $rules;
    }

    /**
     * Validates number of files selected.
     *
     * @param ElementInterface $element
     */
    public function validateFileLimit(ElementInterface $element)
    {
        $fileLimit = intval($this->limitFiles ?? 1);

        $value = $element->getFieldValue($this->handle);
        $count = $value->count();

        // TODO: fix, doesn't work.
        if ($count > $fileLimit) {
            $element->addError(
                $this->handle,
                Craft::t('formie', 'Choose up to {files} files.', [
                    'files' => $fileLimit
                ])
            );
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
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, $value, array $options = null)
    {
        return false;
    }

    /**
     * Returns a comma separated list of allowed file extensions
     * that are allowed to be uploaded.
     *
     * @return string|null
     */
    public function getAccept()
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

    /**
     * @inheritdoc
     */
    public function getFrontEndJsVariables(Form $form)
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/file-upload.js', true);
        $onload = 'new FormieFileUpload(' . Json::encode(['formId' => $form->id]) . ');';

        return [
            'src' => $src,
            'onload' => $onload,
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
                'help' => Craft::t('formie', 'Note that the subfolder path can contain variables like {slug} or {author.username}.'),
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
                'label' => Craft::t('formie', 'Limit File Size'),
                'help' => Craft::t('formie', 'Limit the size of the files a user can upload.'),
                'name' => 'sizeLimit',
                'size' => '3',
                'class' => 'text',
                'type' => 'textWithSuffix',
                'suffix' => Craft::t('formie', 'MB'),
                'validation' => 'optional|number|min:0',
                'warning' => Craft::t('formie', 'Maxiumum allowed upload size is {size}.', ['size' => $maxUpload]),
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


    // Private Methods
    // =========================================================================

    private function humanFilesize($size, $precision = 2) {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {}
        return round($size, $precision).['B','kB','MB','GB','TB','PB','EB','ZB','YB'][$i];
    }
}

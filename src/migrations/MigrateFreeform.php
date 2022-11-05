<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\events\ModifyMigrationFormEvent;
use verbb\formie\events\ModifyMigrationNotificationEvent;
use verbb\formie\events\ModifyMigrationSubmissionEvent;
use verbb\formie\fields\formfields;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\db\Migration;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\Json;

use Throwable;

use yii\console\Controller;
use yii\helpers\Markdown;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Models\FormModel;
use Solspace\Freeform\Elements\Submission as FreeformSubmission;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;
use Solspace\Freeform\Fields as freeformfields;
use Solspace\Freeform\Fields\SubmitField;
use yii\base\InvalidConfigException;
use verbb\formie\models\Settings;

/**
 * Migrates Freeform forms, notifications and submissions.
 */
class MigrateFreeform extends Migration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FIELD = 'modifyField';
    public const EVENT_MODIFY_FORM = 'modifyForm';
    public const EVENT_MODIFY_NOTIFICATION = 'modifyNotification';
    public const EVENT_MODIFY_SUBMISSION = 'modifySubmission';


    // Properties
    // =========================================================================

    public ?int $formId = null;

    private ?FormModel $_freeformForm = null;
    private ?Form $_form = null;
    private ?array $_reservedHandles = null;
    private ?Controller $_consoleRequest = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->_reservedHandles = Formie::$plugin->getFields()->getReservedHandles();

        if ($this->_freeformForm = Freeform::getInstance()->forms->getFormById($this->formId)) {
            if ($this->_form = $this->_migrateForm()) {
                $this->_migrateSubmissions();
                $this->_migrateNotifications();
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return false;
    }

    public function setConsoleRequest($value)
    {
        $this->_consoleRequest = $value;
    }

    private function _migrateForm(): ?Form
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $transaction = Craft::$app->getDb()->beginTransaction();
        $freeformForm = $this->_freeformForm;

        $this->stdout("Form: Preparing to migrate form “{$freeformForm->handle}”.");

        try {
            $form = new Form();
            $form->title = $freeformForm->name;
            $form->handle = $this->_getHandle($freeformForm);
            $form->settings->submissionTitleFormat = $freeformForm->submissionTitleFormat != '{{ dateCreated|date("Y-m-d H:i:s") }}' ? $freeformForm->submissionTitleFormat : '';
            $form->settings->submitMethod = $freeformForm->getForm()->isAjaxEnabled() ? 'ajax' : 'page-reload';
            $form->settings->submitActionUrl = $freeformForm->returnUrl;
            $form->settings->submitAction = 'url';

            // Set default template
            if ($templateId = $settings->getDefaultFormTemplateId()) {
                $form->templateId = $templateId;
            }

            // Fire a 'modifyForm' event
            $event = new ModifyMigrationFormEvent([
                'form' => $freeformForm,
                'newForm' => $form,
            ]);
            $this->trigger(self::EVENT_MODIFY_FORM, $event);

            $form = $this->_form = $event->newForm;

            if ($fieldLayout = $this->_buildFieldLayout($freeformForm)) {
                $form->setFormFieldLayout($fieldLayout);
            }

            if (!$event->isValid) {
                $this->stdout("    > Skipped form due to event cancellation.", Console::FG_YELLOW);
                return $form;
            }

            if (!Formie::$plugin->getForms()->saveForm($form)) {
                $this->stdout("    > Failed to save form “{$form->handle}”.", Console::FG_RED);

                foreach ($form->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->stdout("    > $attr: $error", Console::FG_RED);
                    }
                }

                foreach ($form->getPages() as $page) {
                    foreach ($page->getErrors() as $attr => $errors) {
                        foreach ($errors as $error) {
                            $this->stdout("    > $attr: $error", Console::FG_RED);
                        }
                    }

                    foreach ($page->getRows() as $row) {
                        foreach ($row['fields'] as $field) {
                            foreach ($field->getErrors() as $attr => $errors) {
                                foreach ($errors as $error) {
                                    $this->stdout("    > $attr: $error", Console::FG_RED);
                                }
                            }
                        }
                    }
                }
            } else {
                $this->stdout("    > Form “{$form->handle}” migrated.", Console::FG_GREEN);
            }
        } catch (Throwable $e) {
            $this->stdout("    > Failed to migrate “{$freeformForm->handle}”.", Console::FG_RED);
            $this->stdout("    > `{$this->getExceptionTraceAsString($e)}`", Console::FG_RED);

            $transaction->rollBack();

            throw $e;
        }

        return $form;
    }

    private function _migrateSubmissions(): void
    {
        $status = Formie::$plugin->getStatuses()->getAllStatuses()[0];

        $entries = FreeformSubmission::find()->form($this->_freeformForm->handle)->all();
        $total = count($entries);

        $this->stdout("Entries: Preparing to migrate $total entries to submissions.");

        if (!$total) {
            $this->stdout('    > No entries to migrate.', Console::FG_YELLOW);

            return;
        }

        foreach ($entries as $entry) {
            /* @var FreeformSubmission $entry */
            $submission = new Submission();
            $submission->title = $entry->title;
            $submission->setForm($this->_form);
            $submission->setStatus($status);
            $submission->dateCreated = $entry->dateCreated;
            $submission->dateUpdated = $entry->dateUpdated;

            foreach ($entry as $field) {
                // Parse the handle for a few things just in case
                $handle = $this->_getFieldHandle($field->getHandle(), false);

                $field = $entry->$handle;

                try {
                    switch (get_class($field)) {
                        case freeformfields\Pro\OpinionScaleField::class:
                            // Not implemented
                            break;

                        case freeformfields\Pro\RatingField::class:
                            // Not implemented
                            break;

                        case freeformfields\Pro\RichTextField::class:
                            // Not implemented
                            break;

                        case freeformfields\Pro\SignatureField::class:
                            // Not implemented
                            break;

                        case freeformfields\DynamicRecipientField::class:
                            // Not implemented
                            break;

                        case freeformfields\HtmlField::class:
                            // Not implemented
                            break;

                        case freeformfields\MailingListField::class:
                            // Not implemented
                            break;

                        case freeformfields\RecaptchaField::class:
                            // Not implemented
                            break;

                        case SubmitField::class:
                            // Not implemented
                            break;

                        case freeformfields\CheckboxField::class:
                            $submission->setFieldValue($handle, $field->isChecked());
                            break;

                        case freeformfields\FileUploadField::class:
                            $value = $field->getValue();
                            if (!empty($value)) {
                                $assets = Asset::find()->id($value)->ids();
                                $submission->setFieldValue($handle, $assets);
                            }
                            break;

                        case freeformfields\EmailField::class:
                            $value = $field->getValue();
                            if (!empty($value)) {
                                $submission->setFieldValue($handle, $value[0]);
                            }
                            break;

                        default:
                            $submission->setFieldValue($handle, $field->getValue());
                            break;
                    }
                } catch (Throwable $e) {
                    $this->stdout("    > Failed to migrate “{$handle}”.", Console::FG_RED);
                    $this->stdout("    > `{$this->getExceptionTraceAsString($e)}`", Console::FG_RED);

                    continue;
                }
            }

            // Fire a 'modifySubmission' event
            $event = new ModifyMigrationSubmissionEvent([
                'form' => $this->_form,
                'submission' => $submission,
            ]);
            $this->trigger(self::EVENT_MODIFY_SUBMISSION, $event);

            if (!$event->isValid) {
                $this->stdout("    > Skipped submission due to event cancellation.", Console::FG_YELLOW);
                continue;
            }

            if (!Craft::$app->getElements()->saveElement($event->submission)) {
                $this->stdout("    > Failed to save Formie submission for Freeform submission “{$entry->id}”.", Console::FG_RED);

                foreach ($submission->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->stdout("    > $attr: $error", Console::FG_RED);
                    }
                }
            } else {
                $this->stdout("    > Migrated Freeform submission “{$entry->id}” to Formie submission “{$event->submission->id}”.", Console::FG_GREEN);
            }
        }

        $this->stdout("    > All entries completed.", Console::FG_GREEN);
    }

    private function _migrateNotifications(): void
    {
        $settings = Formie::$plugin->getSettings();

        $props = $this->_freeformForm->getForm()->getAdminNotificationProperties();
        if ($props && $notificationId = $props->getNotificationId()) {
            $notification = Freeform::getInstance()->notifications->getNotificationById($notificationId);
            $this->stdout("Notifications: Preparing to migrate notification.");

            try {
                $newNotification = new Notification();
                $newNotification->formId = $this->_form->id;
                $newNotification->name = $notification->name;
                $newNotification->subject = $notification->getSubject();
                $newNotification->recipients = 'email';
                $newNotification->to = str_replace(PHP_EOL, ',', $props->getRecipients());
                $newNotification->cc = $notification->getCc();
                $newNotification->bcc = $notification->getBcc();
                $newNotification->from = $notification->getFromEmail();
                $newNotification->fromName = $notification->getFromName();
                $newNotification->replyTo = $notification->getReplyToEmail();
                $newNotification->attachFiles = $notification->isIncludeAttachmentsEnabled();
                $newNotification->enabled = true;

                // Set default template
                if ($templateId = $settings->getDefaultEmailTemplateId()) {
                    $newNotification->templateId = $templateId;
                }

                $body = $this->_tokenizeNotificationBody($notification->getBodyText());
                $newNotification->content = Json::encode($body);

                // Fire a 'modifyNotification' event
                $event = new ModifyMigrationNotificationEvent([
                    'form' => $this->_form,
                    'notification' => $notification,
                    'newNotification' => $newNotification,
                ]);
                $this->trigger(self::EVENT_MODIFY_NOTIFICATION, $event);

                if (!$event->isValid) {
                    $this->stdout("    > Skipped notification due to event cancellation.", Console::FG_YELLOW);
                    return;
                }

                if (Formie::$plugin->getNotifications()->saveNotification($event->newNotification)) {
                    $this->stdout("    > Migrated notification “{$notification->name}”. You may need to check the notification body.", Console::FG_GREEN);
                } else {
                    $this->stdout("    > Failed to save notification “{$notification->name}”.", Console::FG_RED);

                    foreach ($notification->getErrors() as $attr => $errors) {
                        foreach ($errors as $error) {
                            $this->stdout("    > $attr: $error", Console::FG_RED);
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->stdout("    > Failed to migrate “{$notification->name}”.", Console::FG_RED);
                $this->stdout("    > `{$this->getExceptionTraceAsString($e)}`", Console::FG_RED);

                return;
            }
        } else {
            $this->stdout("    > No notifications to migrate.", Console::FG_YELLOW);
        }

        $this->stdout("    > All notifications completed.", Console::FG_GREEN);
    }

    private function _getHandle(FormModel $form): string
    {
        $increment = 1;
        $handle = $form->handle;

        while (true) {
            if (!Form::find()->handle($handle)->exists()) {
                return $handle;
            }

            $newHandle = $form->handle . $increment;

            $this->stdout("    > Handle “{$handle}” is taken, will try “{$newHandle}” instead.", Console::FG_YELLOW);

            $handle = $newHandle;

            $increment++;
        }
    }

    /**
     * @param FormModel $form
     * @return FieldLayout
     * @noinspection PhpDocMissingThrowsInspection
     */
    private function _buildFieldLayout(FormModel $form): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Form::class]);
        $fieldLayout->type = Form::class;

        $pages = [];
        $fields = [];
        $layout = $form->getLayout();

        foreach ($layout->getPages() as $pageIndex => $page) {
            $newPage = new FieldLayoutPage();
            $newPage->name = $page->getLabel();
            $newPage->sortOrder = '' . $pageIndex;

            $pageFields = [];
            $fieldHashes = [];

            foreach ($page->getRows() as $rowIndex => $row) {
                foreach ($row as $fieldIndex => $field) {
                    $newField = $this->_mapField($field);

                    // Fire a 'modifyField' event
                    $event = new ModifyMigrationFieldEvent([
                        'form' => $this->_form,
                        'originForm' => $form,
                        'field' => $field,
                        'newField' => $newField,
                    ]);
                    $this->trigger(self::EVENT_MODIFY_FIELD, $event);

                    if (!$event->isValid) {
                        $this->stdout("    > Skipped field “{$newField->handle}” due to event cancellation.", Console::FG_YELLOW);
                        continue;
                    }

                    // Allow events to modify the `newField`
                    $newField = $event->newField;

                    if ($newField) {
                        $newField->validate();

                        if ($newField->hasErrors()) {
                            $this->stdout("    > Failed to save field “{$newField->handle}”.", Console::FG_RED);

                            foreach ($newField->getErrors() as $attr => $errors) {
                                foreach ($errors as $error) {
                                    $this->stdout("    > $attr: $error", Console::FG_RED);
                                }
                            }
                        } else {
                            $newField->sortOrder = $fieldIndex;
                            $newField->rowIndex = $rowIndex;
                            $pageFields[] = $newField;
                            $fields[] = $newField;
                            $fieldHashes[] = $field->getHash();
                        }
                    } else if (get_class($field) === SubmitField::class) {
                        $newPage->settings->buttonsPosition = $field->getPosition();
                        $newPage->settings->submitButtonLabel = $field->getLabelNext();
                        $newPage->settings->backButtonLabel = $field->getLabelPrev();
                        $newPage->settings->showBackButton = !$field->isDisablePrev();

                        if ($newPage->settings->buttonsPosition === 'spread') {
                            $newPage->settings->buttonsPosition = 'left-right';
                        }
                    } else {
                        $this->stdout("    > Failed to migrate field “{$field->getHandle()}” on form “{$form->handle}”. Unsupported field.", Console::FG_RED);
                    }
                }
            }

            // Migrate any hidden fields excluded from the layout.
            foreach ($this->_freeformForm->getLayout()->getCustomFields() as $field) {
                if ($field->getPageIndex() != $pageIndex) {
                    continue;
                }

                if (in_array($field->getHash(), $fieldHashes)) {
                    continue;
                }

                if ($newField = $this->_mapField($field)) {
                    // Fire a 'modifyField' event
                    $event = new ModifyMigrationFieldEvent([
                        'form' => $this->_form,
                        'originForm' => $form,
                        'field' => $field,
                        'newField' => $newField,
                    ]);
                    $this->trigger(self::EVENT_MODIFY_FIELD, $event);

                    $newField = $event->newField;

                    if (!$event->isValid) {
                        $this->stdout("    > Skipped field “{$newField->handle}” due to event cancellation.", Console::FG_YELLOW);
                        continue;
                    }

                    $newField->validate();

                    if ($newField->hasErrors()) {
                        $this->stdout("    > Failed to save field “{$newField->handle}”.", Console::FG_RED);

                        foreach ($newField->getErrors() as $attr => $errors) {
                            foreach ($errors as $error) {
                                $this->stdout("    > $attr: $error", Console::FG_RED);
                            }
                        }
                    } else {
                        $newField->sortOrder = 0;
                        $newField->rowIndex = count($pageFields);
                        $pageFields[] = $newField;
                        $fields[] = $newField;
                        $fieldHashes[] = $field->getHash();
                    }
                }
            }

            $newPage->setLayout($fieldLayout);
            $newPage->setCustomFields($pageFields);
            $pages[] = $newPage;
        }

        $fieldLayout->setPages($pages);
        $fieldLayout->setCustomFields($fields);

        return $fieldLayout;
    }

    /**
     * @param FieldInterface $field
     * @return FormFieldInterface|null
     * @throws InvalidConfigException
     */
    private function _mapField(FieldInterface $field): ?FormFieldInterface
    {
        switch (get_class($field)) {
            case freeformfields\CheckboxField::class:
                /* @var freeformfields\CheckboxField $field */
                $newField = new formfields\Agree();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->isChecked();
                $newField->description = $field->getLabel();
                $newField->checkedValue = $field->getValue();
                $newField->uncheckedValue = Craft::t('app', 'No');
                break;

            case freeformfields\Pro\ConfirmationField::class:
                // We want to ensure *this* field is the same as the target field, so grab that type    
                $targetField = $this->_freeformForm->getLayout()->getFieldByHash($field->getTargetFieldHash());
                $targetFormieField = $this->_mapField($targetField);

                if ($targetFormieField) {
                    $fieldClass = get_class($targetFormieField);

                    $newField = new $fieldClass();
                    $newField->matchField = '{' . $targetFormieField->handle . '}';

                    $this->_applyFieldDefaults($newField);
                }

                break;

            case freeformfields\CheckboxGroupField::class:
                /* @var freeformfields\CheckboxGroupField $field */
                $newField = new formfields\Checkboxes();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\DynamicRecipientField::class:
                // Not implemented
                return null;

            case freeformfields\Pro\DatetimeField::class:
                /* @var freeformfields\CheckboxGroupField $field */
                $newField = new formfields\Date();
                $this->_applyFieldDefaults($newField);

                if ($field->getDateTimeType() === 'both') {
                    $newField->includeTime = true;
                }

                switch ($field->getDateOrder()) {
                    case 'mdy':
                        $newField->dateFormat = 'm-d-Y';

                        break;

                    case 'dmy':
                        $newField->dateFormat = 'd-m-Y';

                        break;

                    case 'ymd':
                        $newField->dateFormat = 'Y-m-d';

                        break;
                }

                break;

            case freeformfields\EmailField::class:
                /* @var freeformfields\EmailField $field */
                $newField = new formfields\Email();
                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\FileUploadField::class:
                /* @var freeformfields\FileUploadField $field */
                $newField = new formfields\FileUpload();
                $this->_applyFieldDefaults($newField);

                $source = $field->getAssetSourceId();
                if ($source = Craft::$app->getAssets()->getRootFolderByVolumeId($source)) {
                    $newField->uploadLocationSource = "folder:{$source->getVolume()->uid}";
                } else if ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                    $newField->uploadLocationSource = "folder:{$volumes[0]->uid}";
                }

                $newField->uploadLocationSubpath = $field->getDefaultUploadLocation();
                $newField->restrictFiles = !empty($field->getFileKinds());
                $newField->allowedKinds = $field->getFileKinds() ?? [];
                break;

            case freeformfields\HiddenField::class:
                /* @var freeformfields\HiddenField $field */
                $newField = new formfields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->getValue();
                break;

            case freeformfields\HtmlField::class:
                /* @var freeformfields\HtmlField $field */
                $newField = new formfields\Html();
                $this->_applyFieldDefaults($newField);

                $newField->name = $field->getLabel();
                $newField->handle = $field->getHash();
                $newField->htmlContent = $field->getValue();
                $newField->labelPosition = HiddenPosition::class;
                break;

            case freeformfields\Pro\InvisibleField::class:
                /* @var freeformfields\HiddenField $field */
                $newField = new formfields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->getValue();
                break;

            case freeformfields\MailingListField::class:
                // Not implemented
                return null;

            case freeformfields\MultipleSelectField::class:
                /* @var freeformfields\MultipleSelectField $field */
                $newField = new formfields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->setMultiple(true);
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\NumberField::class:
                /* @var freeformfields\NumberField $field */
                $newField = new formfields\Number();
                $this->_applyFieldDefaults($newField);

                $newField->min = $field->getMinValue();
                $newField->max = $field->getMaxValue();
                $newField->decimals = $field->getDecimalCount();
                break;

            case freeformfields\Pro\PhoneField::class:
                /* @var freeformfields\TextField $field */
                $newField = new formfields\Phone();

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\RadioGroupField::class:
                /* @var freeformfields\RadioGroupField $field */
                $newField = new formfields\Radio();
                $this->_applyFieldDefaults($newField);

                $newField->layout = $field->isOneLine() ? 'horizontal' : 'vertical';
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\RecaptchaField::class:
                // Not implemented
                return null;

            case freeformfields\SelectField::class:
                /* @var freeformfields\SelectField $field */
                $newField = new formfields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case SubmitField::class:
                // Not implemented
                return null;

            case freeformfields\Pro\TableField::class:
                /* @var freeformfields\TableField $field */
                $newField = new formfields\Table();
                $newField->addRowLabel = $field->getAddButtonLabel();

                foreach ($field->getTableLayout() as $key => $row) {
                    $newField->columns[$key] = [
                        'id' => 'col' . ($key + 1),
                        'heading' => $row['label'] ?? '',
                        'handle' => $row['value'] ?? '',
                        'type' => $row['type'] ?? 'singleline',
                    ];
                }

                break;

            case freeformfields\TextareaField::class:
                /* @var freeformfields\TextareaField $field */
                $newField = new formfields\MultiLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\TextField::class:
                /* @var freeformfields\TextField $field */
                $newField = new formfields\SingleLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\Pro\WebsiteField::class:
                /* @var freeformfields\TextField $field */
                $newField = new formfields\SingleLineText();

                $this->_applyFieldDefaults($newField);
                break;

            default:
                return null;
        }

        if (!$newField->name) {
            $newField->name = $field->getLabel();
        }

        if (!$newField->handle) {
            $newField->handle = $field->getHandle();
        }

        // Parse the handle for a few things just in case
        $newField->handle = $this->_getFieldHandle($newField->handle);

        $newField->instructions = $field->getInstructions();

        if (method_exists($field, 'getPlaceholder')) {
            $newField->placeholder = $field->getPlaceholder();
        }

        if (method_exists($field, 'getValue')) {
            $newField->defaultValue = $field->getValue();

            // Just use non-arrays for default values
            if (is_array($newField->defaultValue)) {
                $newField->defaultValue = null;
            }
        }

        if (!$newField instanceof formfields\Address and !$newField instanceof formfields\Name) {
            $newField->required = (bool)($field->isRequired() ?? false);
        }

        return $newField;
    }

    private function _getFieldHandle($currentHandle, $showLog = true): array|string
    {
        $newHandle = $currentHandle;

        // Special-handling for reserved handles. We should prefix
        if (in_array(strtolower($currentHandle), $this->_reservedHandles)) {
            $newHandle = 'field_' . $currentHandle;

            if ($showLog) {
                $this->stdout("    > Handle “{$currentHandle}” is a reserved word, will use “{$newHandle}” instead.", Console::FG_YELLOW);
            }
        }

        // Remove any dashes (maybe open up to other characters?)
        if (str_contains($newHandle, '-')) {
            $newHandle = str_replace('-', '_', $newHandle);

            if ($showLog) {
                $this->stdout("    > Handle “{$currentHandle}” contains an invalid character, will use “{$newHandle}” instead.", Console::FG_YELLOW);
            }
        }

        return $newHandle;
    }

    private function _applyFieldDefaults(FormFieldInterface $field): void
    {
        $defaults = $field->getAllFieldDefaults();
        Craft::configure($field, $defaults);
    }

    /**
     * @param Option[] $options
     * @return array
     */
    private function _mapOptions(array $options): array
    {
        if (!$options) {
            return [];
        }

        return array_values(array_map(function($option) {
            return [
                'isDefault' => $option->isChecked(),
                'label' => $option->getLabel(),
                'value' => $option->getValue(),
            ];
        }, $options));
    }

    private function _tokenizeNotificationBody($body): array
    {
        $variables = Variables::getVariables();

        $tokens = preg_split('/(?<!{)({[a-zA-Z0-9 ]+?})(?!})/', $body, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $content = [];

        foreach ($tokens as $token) {
            if (preg_match('/^{(?P<handle>.+?)}$/', $token, $matches)) {
                $attrs = ArrayHelper::firstWhere($variables, 'value', $token);
                if (!$attrs && $field = $this->_form->getFieldByHandle(trim($matches['handle']))) {
                    $attrs = [
                        'label' => $field->name,
                        'value' => $token,
                    ];
                }

                if ($attrs) {
                    $content[] = [
                        'type' => 'variableTag',
                        'attrs' => $attrs,
                    ];
                } else {
                    $content[] = [
                        'type' => 'text',
                        'text' => $token,
                    ];
                }
            } else {
                $content[] = [
                    'type' => 'text',
                    'text' => $token,
                ];
            }
        }

        return [
            [
                'type' => 'paragraph',
                'content' => $content,
            ],
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'variableTag',
                        'attrs' => [
                            'label' => Craft::t('formie', 'All Form Fields'),
                            'value' => '{allFields}',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function stdout($string, $color = ''): void
    {
        if ($this->_consoleRequest) {
            $this->_consoleRequest->stdout($string . PHP_EOL, $color);
        } else {
            $class = '';

            if ($color) {
                $class = 'color-' . $color;
            }

            echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
        }
    }

    private function getExceptionTraceAsString($exception): string
    {
        $rtn = "";
        $count = 0;

        foreach ($exception->getTrace() as $frame) {
            $args = "";

            if (isset($frame['args'])) {
                $args = [];

                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } else if (is_array($arg)) {
                        $args[] = "Array";
                    } else if (is_null($arg)) {
                        $args[] = 'NULL';
                    } else if (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } else if (is_object($arg)) {
                        $args[] = get_class($arg);
                    } else if (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }

                $args = implode(", ", $args);
            }

            $rtn .= sprintf("#%s %s(%s): %s(%s)\n",
                $count,
                $frame['file'] ?? '[internal function]',
                $frame['line'] ?? '',
                (isset($frame['class'])) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'],
                $args);

            $count++;
        }

        return $rtn;
    }
}

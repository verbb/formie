<?php
namespace verbb\formie\migrations\plugins;

use verbb\formie\Formie;
use verbb\formie\base\Field as FormieField;
use verbb\formie\base\FieldInterface as FormieFieldInterface;
use verbb\formie\base\ParentFieldInterface as FormieParentFieldInterface;
use verbb\formie\elements\Form as FormieForm;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\events\ModifyMigrationFormEvent;
use verbb\formie\events\ModifyMigrationNotificationEvent;
use verbb\formie\events\ModifyMigrationSubmissionEvent;
use verbb\formie\fields as formiefields;
use verbb\formie\models\RichText;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\models\RichText;
use verbb\formie\validators\HandleValidator;

use Craft;
use craft\elements\Asset;

use DateTime;
use DateTimeZone;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\Fields\FieldInterface as FreeformFieldInterfae;
use Solspace\Freeform\Fields\Implementations as freeformfields;
use Solspace\Freeform\Form\Form as FreeformForm;
use Solspace\Freeform\Elements\Submission as FreeformSubmission;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;
use Solspace\Freeform\Notifications\Types\Admin\Admin;

class MigrateFreeform5 extends BasePluginMigrator
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

    private ?FreeformForm $_freeformForm = null;
    private ?FormieForm $_form = null;
    private ?array $_reservedHandles = null;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_reservedHandles = Formie::$plugin->getFields()->getReservedHandles();

        if (!$this->_freeformForm = Freeform::getInstance()->forms->getFormById($this->formId)) {
            return true;
        }

        if ($this->submissionsOnly) {
            $this->_form = FormieForm::find()->handle($this->_freeformForm->handle)->one();

            if (!$this->_form) {
                $this->error("Form: No Formie form found with handle “{$this->_freeformForm->handle}”. Migrate the form first or run without --submissions-only.");

                return false;
            }

            if (!$this->skipSubmissions) {
                $this->_migrateSubmissions();
            }

            return true;
        }

        if ($this->_form = $this->_migrateForm()) {
            if (!$this->skipSubmissions) {
                $this->_migrateSubmissions();
            }

            $this->_migrateNotifications();
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }

    private function _migrateForm(): ?FormieForm
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $transaction = Craft::$app->getDb()->beginTransaction();
        $freeformForm = $this->_freeformForm;

        $this->info("Form: Preparing to migrate form “{$freeformForm->handle}”.");

        try {
            $form = new FormieForm();
            $form->title = $freeformForm->getName();
            $form->handle = $this->_getHandle($freeformForm);
            $form->settings->submissionTitleFormat = $freeformForm->submissionTitle != '{{ dateCreated|date("Y-m-d H:i:s") }}' ? $freeformForm->submissionTitle : '';
            $form->settings->submitMethod = $freeformForm->isAjaxEnabled() ? 'ajax' : 'page-reload';

            $behaviorSettings = $freeformForm->getSettings()->getBehavior();

            if ($behaviorSettings->successBehavior === 'reload') {
                $form->settings->submitActionMessage = RichText::fromHtml('<p>' . $behaviorSettings->successMessage . '</p>');
                $form->settings->submitAction = 'message';
            } else if ($behaviorSettings->successBehavior === 'redirect-return-url') {
                $form->settings->submitActionUrl = $behaviorSettings->returnUrl;
                $form->settings->submitAction = 'url';
            } else if ($behaviorSettings->successBehavior === 'load-success-template') {
                $form->settings->submitActionMessage = RichText::fromHtml('<p>' . $behaviorSettings->successMessage . '</p>');
                $form->settings->submitAction = 'message';
            }

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
                $form->setFormLayout($fieldLayout);
            }

            if (!$event->isValid) {
                $this->warning("    > Skipped form due to event cancellation.");
                return $form;
            }

            if (!Craft::$app->getElements()->saveElement($form)) {
                $this->error("    > Failed to save form “{$form->handle}”.");

                foreach ($form->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->error("    > $attr: $error");
                    }
                }

                foreach ($form->getPages() as $page) {
                    foreach ($page->getErrors() as $attr => $errors) {
                        foreach ($errors as $error) {
                            $this->error("    > $attr: $error");
                        }
                    }

                    foreach ($page->getRows() as $row) {
                        foreach ($row['fields'] as $field) {
                            foreach ($field->getErrors() as $attr => $errors) {
                                foreach ($errors as $error) {
                                    $this->error("    > {$field->handle}.{$attr}: $error");
                                }
                            }
                        }
                    }
                }
            } else {
                $this->success("    > Form “{$form->handle}” (#{$form->id}) migrated.");

                $transaction->commit();
            }
        } catch (Throwable $e) {
            $this->error("    > Failed to migrate “{$freeformForm->handle}”.");

            $transaction->rollBack();

            throw $e;
        }

        return $form;
    }

    private function _migrateSubmissions(): void
    {
        $statusesService = Formie::$plugin->getSubmissionStatuses();
        $statuses = $statusesService->getAllStatuses();
        $fallbackStatus = $statusesService->getDefaultStatus() ?? (reset($statuses) ?: null);
        $formHandle = $this->_freeformForm->handle;

        $this->migrateSubmissionBatches(
            fn() => FreeformSubmission::find()->form($formHandle),
            function ($entry) use ($statusesService, $fallbackStatus) {
                /* @var FreeformSubmission $entry */
                $submission = new Submission();
                $submission->title = $entry->title;
                $submission->setForm($this->_form);
                $submission->dateCreated = $entry->dateCreated;
                $submission->dateUpdated = $entry->dateUpdated;

                $entryStatusHandle = (string)($entry->status ?? '');
                $entryStatus = $entryStatusHandle ? $statusesService->getStatusByHandle($entryStatusHandle) : null;

                if ($entryStatus) {
                    $submission->setStatus($entryStatus);
                } else if ($fallbackStatus) {
                    $submission->setStatus($fallbackStatus);
                }

                foreach ($entry->getFieldCollection() as $field) {
                    // Parse the handle for a few things just in case
                    $handle = $this->_getFieldHandle($field->getHandle(), false);

                    // Freeform can expose nested/group child fields in this flat collection.
                    // Skip values for handles that don't exist on the migrated form.
                    if (!$this->_form?->getFieldByHandle($handle)) {
                        continue;
                    }

                    try {
                        switch (get_class($field)) {
                            case freeformfields\CheckboxField::class:
                                $submission->setFieldValue($handle, $field->isChecked());
                                break;

                            case freeformfields\EmailField::class:
                                $value = $field->getValue();

                                // Handle older Freeform installs storing emails as array
                                if (is_array($value)) {
                                    $submission->setFieldValue($handle, $value[0]);
                                } else {
                                    $submission->setFieldValue($handle, $value);
                                }

                                break;

                            case freeformfields\FileUploadField::class:
                                $value = $field->getValue();

                                if (!empty($value)) {
                                    $assets = Asset::find()->id($value)->ids();
                                    $submission->setFieldValue($handle, $assets);
                                }

                                break;

                            case freeformfields\Pro\GroupField::class:
                                $values = [];

                                foreach ($field->getLayout()->getAllRows() as $row) {
                                    foreach ($row->getAllFields() as $innerField) {
                                        $values[$innerField->getHandle()] = $entry->getFormFieldValue($innerField);
                                    }
                                }

                                $submission->setFieldValue($handle, $values);

                                break;

                            case freeformfields\DropdownField::class:
                            case freeformfields\RadiosField::class:
                                $submission->setFieldValue($handle, $this->_normalizeSingleOptionValue($field->getValue()));
                                break;

                            case freeformfields\CheckboxesField::class:
                            case freeformfields\MultipleSelectField::class:
                                $submission->setFieldValue($handle, $this->_normalizeMultiOptionValue($field->getValue()));
                                break;

                            case freeformfields\HtmlField::class:
                            case freeformfields\Pro\ConfirmationField::class:
                                // Not implemented
                                break;

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

                            default:
                                $submission->setFieldValue($handle, $field->getValue());
                                break;
                        }
                    } catch (Throwable $e) {
                        $this->error("    > Failed to migrate “{$handle}”.");
                        $this->error("    > `{$this->getExceptionTraceAsString($e)}`");

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
                    $this->warning("    > Skipped submission due to event cancellation.");
                    return;
                }

                if (!Craft::$app->getElements()->saveElement($event->submission)) {
                    $this->error("    > Failed to save Formie submission for Freeform submission “{$entry->id}”.");

                    foreach ($submission->getErrors() as $attr => $errors) {
                        foreach ($errors as $error) {
                            $this->error("    > $attr: $error");
                        }
                    }
                } else {
                    $this->logSubmissionMigrated($entry->id, $event->submission->id);
                }
            }
        );
    }

    private function _migrateNotifications(): void
    {
        $settings = Formie::$plugin->getSettings();

        $notificationsProvider = Craft::$container->get(NotificationsProvider::class);
        $notifications = $notificationsProvider->getByFormAndClass($this->_freeformForm, Admin::class);

        if ($notifications) {
            $this->info("Notifications: Preparing to migrate notification.");

            foreach ($notifications as $notification) {
                if (!$notification->getTemplate()) {
                    $this->warning("    > Skipped notification “{$notification->getName()}” because no template was defined.");
                    continue;
                }

                try {
                    $newNotification = new Notification();
                    $newNotification->formId = $this->_form->id;
                    $newNotification->name = $notification->getName();
                    $newNotification->handle = $this->getNotificationHandle($notification->getTemplate()->getHandle());
                    $newNotification->subject = $notification->getTemplate()->getSubject();
                    $newNotification->recipients = 'email';
                    $newNotification->to = implode(',', $notification->getRecipients()->emailsToArray());
                    $newNotification->cc = $notification->getTemplate()->getCc();
                    $newNotification->bcc = $notification->getTemplate()->getBcc();
                    $newNotification->from = $notification->getTemplate()->getFromEmail();
                    $newNotification->fromName = $notification->getTemplate()->getFromName();
                    $newNotification->replyTo = $notification->getTemplate()->getReplyToEmail();
                    $newNotification->attachFiles = $notification->getTemplate()->isIncludeAttachments();
                    $newNotification->enabled = true;

                    // Set default template
                    if ($templateId = $settings->getDefaultEmailTemplateId()) {
                        $newNotification->templateId = $templateId;
                    }

                    $body = $this->_tokenizeNotificationBody($notification->getTemplate()->getBody());
                    $newNotification->content = $this->toRichText($body);

                    // Fire a 'modifyNotification' event
                    $event = new ModifyMigrationNotificationEvent([
                        'form' => $this->_form,
                        'notification' => $notification,
                        'newNotification' => $newNotification,
                    ]);
                    $this->trigger(self::EVENT_MODIFY_NOTIFICATION, $event);

                    if (!$event->isValid) {
                        $this->warning("    > Skipped notification due to event cancellation.");
                        continue;
                    }

                    if (Formie::$plugin->getNotifications()->saveNotification($event->newNotification)) {
                        $this->success("    > Migrated notification “{$notification->getName()}”. You may need to check the notification body.");
                    } else {
                        $this->error("    > Failed to save notification “{$notification->getName()}”.");

                        foreach ($event->newNotification->getErrors() as $attr => $errors) {
                            foreach ($errors as $error) {
                                $this->error("    > $attr: $error");
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $this->error("    > Failed to migrate “{$notification->getName()}”.");
                    $this->error("    > `{$e->getMessage()}`");
                    $this->error("    > `{$this->getExceptionTraceAsString($e)}`");

                    continue;
                }
            }
        } else {
            $this->warning("    > No notifications to migrate.");
        }

        $this->success("    > All notifications completed.");
    }

    private function _getHandle(FreeformForm $form): string
    {
        $increment = 1;
        $handle = $form->handle;

        // Check for invalid handles from Freeform, and convert it automatically
        if (str_contains($handle, '-')) {
            $newHandle = str_replace('-', '_', $handle);

            $this->warning("    > Handle “{$handle}” is invalid, using “{$newHandle}” instead.");

            $handle = $newHandle;
        }

        while (true) {
            if (!FormieForm::find()->handle($handle)->exists()) {
                return $handle;
            }

            $newHandle = $form->handle . $increment;

            $this->warning("    > Handle “{$handle}” is taken, will try “{$newHandle}” instead.");

            $handle = $newHandle;

            $increment++;
        }
    }

    private function getNotificationHandle($currentHandle): string
    {
        $newHandle = $currentHandle;

        // Check for invalid handles from Freeform, and convert it automatically
        if (str_contains($currentHandle, '-')) {
            $newHandle = str_replace('-', '_', $currentHandle);

            $this->warning("    > Handle “{$currentHandle}” is invalid, using “{$newHandle}” instead.");
        }

        return $newHandle;
    }

    private function _buildFieldLayout(FreeformForm $form): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => FormieForm::class]);
        $fieldLayout->type = FormieForm::class;

        $pages = [];
        $fields = [];

        foreach ($form->getPages() as $pageIndex => $page) {
            $newPage = [];
            $newPage['label'] = $page->getLabel();
            $newPage['sortOrder'] = '' . $pageIndex;

            foreach ($page->getLayout()->getAllRows() as $rowIndex => $row) {
                $newRow = [];

                foreach ($row->getAllFields() as $fieldIndex => $field) {
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
                        $this->warning("    > Skipped field “{$newField->handle}” due to event cancellation.");
                        continue;
                    }

                    // Allow events to modify the `newField`
                    $newField = $event->newField;

                    if ($newField) {
                        $newField->validate();

                        if ($newField->hasErrors()) {
                            $this->error("    > Failed to save field “{$newField->handle}”.");

                            foreach ($newField->getErrors() as $attr => $errors) {
                                foreach ($errors as $error) {
                                    $this->error("    > $attr: $error");
                                }
                            }

                            if ($newField instanceof FormieParentFieldInterface) {
                                foreach ($newField->getCustomFields() as $nestedField) {
                                    foreach ($nestedField->getErrors() as $attr => $errors) {
                                        foreach ($errors as $error) {
                                            $this->error("    > $attr: $error");
                                        }
                                    }
                                }
                            }
                        } else {
                            $newField->sortOrder = $fieldIndex;

                            $newRow['fields'][] = $newField;
                        }
                    } else {
                        $this->error("    > Failed to migrate field “{$field->getHandle()}” on form “{$form->handle}”. Unsupported field.");
                    }
                }

                if ($newRow) {
                    $newPage['rows'][] = $newRow;
                }
            }

            if ($page->getButtons()->isBack()) {
                $newPage['settings']['showBackButton'] = true;
            }

            $newPage['settings']['submitButtonLabel'] = $page->getButtons()->getSubmitLabel();
            $newPage['settings']['backButtonLabel'] = $page->getButtons()->getBackLabel();

            $pages[] = $newPage;
        }

        $fieldLayout->setPages($pages);

        return $fieldLayout;
    }

    private function _mapField(FreeformFieldInterfae $field): ?FormieFieldInterface
    {
        switch (get_class($field)) {
            case freeformfields\CheckboxField::class:
                /* @var freeformfields\CheckboxField $field */
                $newField = new formiefields\Agree();
                $this->_applyFieldDefaults($newField);

                $newField->description = RichText::fromHtml('<p>' . $field->getLabel() . '</p>');
                $newField->defaultValue = $field->isChecked();
                $newField->checkedValue = $field->getValue();
                $newField->uncheckedValue = Craft::t('app', 'No');
                break;

            case freeformfields\Pro\ConfirmationField::class:
                // We want to ensure *this* field is the same as the target field, so grab that type    
                $targetField = $field->getTargetField();
                $targetFormieField = $targetField ? $this->_mapField($targetField) : null;

                if ($targetFormieField) {
                    $fieldClass = get_class($targetFormieField);

                    $newField = new $fieldClass();
                    $newField->matchField = '{' . $targetFormieField->handle . '}';
                } else {
                    // Freeform confirmation fields can occasionally have no resolvable target.
                    // Fallback to email to keep migration resilient.
                    $newField = new formiefields\Email();
                }

                $this->_applyFieldDefaults($newField);

                break;

            case freeformfields\CheckboxesField::class:
                /* @var freeformfields\CheckboxesField $field */
                $newField = new formiefields\Checkboxes();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\Pro\DatetimeField::class:
                /* @var freeformfields\DatetimeField $field */
                $newField = new formiefields\Date();
                $this->_applyFieldDefaults($newField);

                // Formie Date is now a fixed parent field. Drive date/time behavior via child sub-fields.
                $hasTime = in_array($field->getDateTimeType(), ['both', 'time'], true);
                $hasDate = in_array($field->getDateTimeType(), ['both', 'date'], true);

                if ($dateSubField = $newField->getFieldByHandle('date')) {
                    $dateSubField->enabled = $hasDate;
                }

                if ($timeSubField = $newField->getFieldByHandle('time')) {
                    $timeSubField->enabled = $hasTime;
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

            case freeformfields\DropdownField::class:
                /* @var freeformfields\DropdownField $field */
                $newField = new formiefields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\EmailField::class:
                /* @var freeformfields\EmailField $field */
                $newField = new formiefields\Email();
                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\FileUploadField::class:
                /* @var freeformfields\FileUploadField $field */
                $newField = new formiefields\FileUpload();
                $this->_applyFieldDefaults($newField);

                $sourceId = $field->getAssetSourceId();

                if ($sourceId && $source = Craft::$app->getAssets()->getRootFolderByVolumeId($sourceId)) {
                    $newField->uploadLocationSource = "folder:{$source->getVolume()->uid}";
                } else if ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                    $newField->uploadLocationSource = "folder:{$volumes[0]->uid}";
                }

                $newField->uploadLocationSubpath = $field->getDefaultUploadLocation();
                $newField->restrictFiles = !empty($field->getFileKinds());
                $newField->allowedKinds = $field->getFileKinds() ?? [];
                break;

            case freeformfields\Pro\GroupField::class:
                /* @var freeformfields\HiddenField $field */
                $newField = new formiefields\Group();

                $newRows = [];

                foreach ($field->getLayout()->getAllRows() as $rowKey => $row) {
                    $newInnerFields = [];

                    foreach ($row->getAllFields() as $fieldKey => $innerField) {
                        $newInnerFields[] = $this->_mapField($innerField);
                    }

                    $newRows[] = [
                        'fields' => $newInnerFields,
                    ];
                }

                $newField->setRows($newRows);

                break;

            case freeformfields\HiddenField::class:
                /* @var freeformfields\HiddenField $field */
                $newField = new formiefields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultOption = 'custom';
                $newField->defaultValue = $field->getDefaultValue();
                break;

            case freeformfields\HtmlField::class:
                /* @var freeformfields\HtmlField $field */
                $newField = new formiefields\Html();
                $this->_applyFieldDefaults($newField);

                $newField->label = $field->getLabel();
                $newField->handle = $field->getHandle();
                $newField->htmlContent = $field->getContent();
                $newField->allowTwig = $field->isTwig();
                $newField->labelPosition = HiddenPosition::class;

                break;

            case freeformfields\Pro\InvisibleField::class:
                /* @var freeformfields\Pro\InvisibleField $field */
                $newField = new formiefields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->getValue();
                break;

            case freeformfields\MultipleSelectField::class:
                /* @var freeformfields\MultipleSelectField $field */
                $newField = new formiefields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->multi = true;
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\NumberField::class:
                /* @var freeformfields\NumberField $field */
                $newField = new formiefields\Number();
                $this->_applyFieldDefaults($newField);

                if ($min = $field->getMinValue()) {
                    $newField->min = $min;
                }

                if ($max = $field->getMaxValue()) {
                    $newField->max = $max;
                }

                $newField->decimals = $field->getDecimalCount();
                break;

            case freeformfields\Pro\PhoneField::class:
                /* @var freeformfields\Pro\PhoneField $field */
                $newField = new formiefields\Phone();

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\RadiosField::class:
                /* @var freeformfields\RadiosField $field */
                $newField = new formiefields\Radio();
                $this->_applyFieldDefaults($newField);

                $newField->layout = $field->isOneLine() ? 'horizontal' : 'vertical';
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case freeformfields\Pro\RichTextField::class:
                /* @var freeformfields\Pro\RichTextField $field */
                $newField = new formiefields\Content();
                $this->_applyFieldDefaults($newField);

                $newField->label = $field->getLabel();
                $newField->handle = $field->getHandle();
                $newField->content = \verbb\formie\models\RichText::from($field->getContent());
                $newField->labelPosition = HiddenPosition::class;

                break;

            case freeformfields\Pro\TableField::class:
                /* @var freeformfields\TableField $field */
                $newField = new formiefields\Table();
                $newField->addRowLabel = $field->getAddButtonLabel();

                foreach ($field->getTableLayout() as $key => $row) {
                    $newField->columns[$key] = [
                        'id' => 'c' . ($key + 1),
                        'heading' => $row->label ?? '',
                        'handle' => StringHelper::toCamelCase($row->value ?? ''),
                        'type' => $row->type ?? 'singleline',
                    ];
                }

                break;

            case freeformfields\TextareaField::class:
                /* @var freeformfields\TextareaField $field */
                $newField = new formiefields\MultiLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\TextField::class:
                /* @var freeformfields\TextField $field */
                $newField = new formiefields\SingleLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case freeformfields\Pro\WebsiteField::class:
                /* @var freeformfields\Pro\WebsiteField $field */
                $newField = new formiefields\SingleLineText();

                $this->_applyFieldDefaults($newField);
                break;

            default:
                return null;
        }

        if (!$newField->label) {
            $newField->label = $field->getLabel();
        }

        if (!$newField->handle) {
            $newField->handle = $field->getHandle();
        }

        // Parse the handle for a few things just in case
        $newField->handle = $this->_getFieldHandle($newField->handle);

        $newField->instructions = RichText::from($field->getInstructions());

        if (!$newField->placeholder && method_exists($field, 'getPlaceholder')) {
            $newField->placeholder = $field->getPlaceholder();
        }

        if (!$newField->defaultValue && method_exists($field, 'getValue')) {
            $newField->defaultValue = $field->getValue();

            // Just use non-arrays for default values
            if (is_array($newField->defaultValue)) {
                $newField->defaultValue = null;
            }
        }

        if (!$newField instanceof formiefields\Address and !$newField instanceof formiefields\Name) {
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
                $this->warning("    > Handle “{$currentHandle}” is a reserved word, will use “{$newHandle}” instead.");
            }
        }

        // Remove any dashes (maybe open up to other characters?)
        if (str_contains($newHandle, '-')) {
            $newHandle = str_replace('-', '_', $newHandle);

            if ($showLog) {
                $this->warning("    > Handle “{$currentHandle}” contains an invalid character, will use “{$newHandle}” instead.");
            }
        }

        // If the handle is longer than 64 characters cut the remainder off
        if (strlen($newHandle) > 64) {
            $currentHandle = $newHandle;
            $newHandle = substr($newHandle, 0, 64);

            if ($showLog) {
                $this->warning("    > Handle “{$currentHandle}” is longer than the allowed 64 characters, will use “{$newHandle}” instead.");
            }
        }

        // Validate the handle on it's correctness
        try {
            $reflection = new ReflectionClass(Submission::class);
            
            $reserved =  array_map(function($prop) {
                return $prop->name;
            }, $reflection->getProperties(ReflectionProperty::IS_PUBLIC));
        } catch (Throwable $e) {
            $reserved = [];
        }

        $validator = new HandleValidator([
            'reservedWords' => $reserved,
        ]);

        if (!$validator->validate($newHandle)) {
            $newHandle = 'formie_' . StringHelper::randomString(10);

            if ($showLog) {
                $this->warning("    > Handle “{$currentHandle}” is invalid, will use the generated “{$newHandle}” instead.");
            }
        }

        return $newHandle;
    }

    private function _applyFieldDefaults(FormieFieldInterface $field): void
    {

    }

    private function _mapOptions(OptionCollection $collection): array
    {
        if (!$collection) {
            return [];
        }

        return array_values(array_map(function($option) {
            return [
                'label' => $option->getLabel(),
                'value' => $option->getValue(),
            ];
        }, $collection->getOptions()));
    }

    private function _tokenizeNotificationBody($body): array
    {
        $variables = Variables::getVariables();
        $body = preg_replace('/\{\{\s*([a-zA-Z0-9_]+(?::[a-zA-Z0-9_]+)?)\s*\}\}/', '{$1}', (string)$body) ?? (string)$body;
        $tokens = preg_split('/(?<!\{)(\{[^{}]+\})(?!\})/', $body, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $content = [];

        foreach ($tokens as $token) {
            if (preg_match('/^\{(?P<legacy>.+?)\}$/', $token, $matches)) {
                $attrs = ArrayHelper::firstWhere($variables, 'value', $token);

                // Convert legacy Freeform-style tokens ({handle} / {handle:selector}) to
                // Formie reference tokens ({field:reference} / {field:reference:selector}).
                if (!$attrs && preg_match('/^(?P<handle>[a-zA-Z0-9_]+)(?::(?P<selector>[a-zA-Z0-9_]+))?$/', trim($matches['legacy']), $legacyMatches)) {
                    $handle = trim($legacyMatches['handle']);
                    $selector = trim($legacyMatches['selector'] ?? '');

                    if ($field = $this->_form->getFieldByHandle($handle)) {
                        $reference = $field->reference ?? null;

                        if ($reference) {
                            $referenceToken = References::field($reference, $selector ?: null);
                            $attrs = ArrayHelper::firstWhere($variables, 'value', $referenceToken) ?? [
                                'label' => $selector ? (($field->name ?? $field->label ?? $field->handle) . ': ' . $selector) : ($field->name ?? $field->label ?? $field->handle),
                                'value' => $referenceToken,
                            ];
                        }
                    }
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
        ];
    }

    private function _normalizeSingleOptionValue(mixed $value): mixed
    {
        if ($value instanceof Option) {
            return $value->getValue();
        }

        if (is_object($value) && method_exists($value, 'getValue')) {
            $normalized = $value->getValue();
            return is_scalar($normalized) || $normalized === null ? $normalized : null;
        }

        if (is_array($value)) {
            if ($value === []) {
                return null;
            }

            return $this->_normalizeSingleOptionValue(reset($value));
        }

        return is_scalar($value) || $value === null ? $value : null;
    }

    private function _normalizeMultiOptionValue(mixed $value): array
    {
        if (!is_array($value)) {
            $normalized = $this->_normalizeSingleOptionValue($value);
            return $normalized === null ? [] : [$normalized];
        }

        $values = [];

        foreach ($value as $item) {
            $normalized = $this->_normalizeSingleOptionValue($item);

            if ($normalized !== null) {
                $values[] = $normalized;
            }
        }

        return array_values($values);
    }

}

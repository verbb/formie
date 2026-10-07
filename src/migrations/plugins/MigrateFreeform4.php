<?php
namespace verbb\formie\migrations\plugins;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface as FormieFieldInterface;
use verbb\formie\elements\Form as FormieForm;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\events\ModifyMigrationFormEvent;
use verbb\formie\events\ModifyMigrationNotificationEvent;
use verbb\formie\events\ModifyMigrationSubmissionEvent;
use verbb\formie\fields\Address;
use verbb\formie\fields\Agree;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Email;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Html;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\Phone;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Table;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\models\Settings;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\elements\Asset;

use DateTime;
use DateTimeZone;
use Throwable;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\Elements\Submission as FreeformSubmission;
use Solspace\Freeform\Fields\FieldInterface as FreeformFieldInterfae;
use Solspace\Freeform\Fields\Implementations as freeformfields;
use Solspace\Freeform\Fields\Implementations\CheckboxesField;
use Solspace\Freeform\Fields\Implementations\CheckboxField;
use Solspace\Freeform\Fields\Implementations\DropdownField;
use Solspace\Freeform\Fields\Implementations\EmailField;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\HiddenField;
use Solspace\Freeform\Fields\Implementations\HtmlField;
use Solspace\Freeform\Fields\Implementations\MultipleSelectField;
use Solspace\Freeform\Fields\Implementations\NumberField;
use Solspace\Freeform\Fields\Implementations\Pro\ConfirmationField;
use Solspace\Freeform\Fields\Implementations\Pro\DatetimeField;
use Solspace\Freeform\Fields\Implementations\Pro\InvisibleField;
use Solspace\Freeform\Fields\Implementations\Pro\OpinionScaleField;
use Solspace\Freeform\Fields\Implementations\Pro\PhoneField;
use Solspace\Freeform\Fields\Implementations\Pro\RatingField;
use Solspace\Freeform\Fields\Implementations\Pro\RichTextField;
use Solspace\Freeform\Fields\Implementations\Pro\SignatureField;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\Pro\WebsiteField;
use Solspace\Freeform\Fields\Implementations\RadiosField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Form\Form as FreeformForm;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Notifications\Types\Admin\Admin;

class MigrateFreeform4 extends BasePluginMigrator
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
            $this->_form = FormieForm::find()->withoutCpIndexScope()->handle($this->_freeformForm->handle)->site('*')->unique()->status(null)->one();

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


    // Private Methods
    // =========================================================================

    private function _migrateForm(): ?FormieForm
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $transaction = Craft::$app->getDb()->beginTransaction();
        $freeformForm = $this->_freeformForm;

        $this->info("Form: Preparing to migrate form “{$freeformForm->handle}”.");

        try {
            $form = new FormieForm();
            $form->title = $freeformForm->name;
            $form->handle = $this->_getHandle($freeformForm);
            $form->settings->submissionTitleFormat = $freeformForm->submissionTitle != '{{ dateCreated|date("Y-m-d H:i:s") }}' ? $freeformForm->submissionTitle : '';
            $form->settings->submitMethod = $freeformForm->isAjaxEnabled() ? 'ajax' : 'page-reload';
            $form->settings->redirectUrl = $freeformForm->returnUrl;
            $form->settings->completionBehavior = 'redirect';
            $form->settings->completionRedirectSource = 'url';

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

            $this->_buildFieldLayout($freeformForm);

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
                                    $this->error("    > $attr: $error");
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
        $statuses = Formie::$plugin->getSubmissionStatuses()->getAllStatuses();
        $status = reset($statuses) ?: null;
        $formHandle = $this->_freeformForm->handle;

        $this->migrateSubmissionBatches(
            fn() => FreeformSubmission::find()->form($formHandle),
            function($entry) use ($status) {
                $now = new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));

                /* @var FreeformSubmission $entry */
                $submission = new Submission();
                $submission->title = $now->format('D, d M Y H:i:s');
                $submission->setForm($this->_form);
                $submission->dateCreated = $entry->dateCreated;
                $submission->dateUpdated = $entry->dateUpdated;

                if ($status) {
                    $submission->setStatus($status);
                }

                foreach ($entry->getFieldCollection() as $field) {
                    // Parse the handle for a few things just in case
                    $handle = $this->_getFieldHandle($field->getHandle(), false);

                    $field = $entry->$handle;

                    try {
                        switch (get_class($field)) {
                            case OpinionScaleField::class:
                                // Not implemented
                                break;

                            case RatingField::class:
                                // Not implemented
                                break;

                            case RichTextField::class:
                                // Not implemented
                                break;

                            case SignatureField::class:
                                // Not implemented
                                break;

                            case HtmlField::class:
                                // Not implemented
                                break;

                            case CheckboxField::class:
                                $submission->setFieldValue($handle, $field->isChecked());
                                break;

                            case FileUploadField::class:
                                $value = $field->getValue();

                                if (!empty($value)) {
                                    $assets = Asset::find()->id($value)->ids();
                                    $submission->setFieldValue($handle, $assets);
                                }
                                break;

                            case EmailField::class:
                                $value = $field->getValue();

                                // Handle older Freeform installs storing emails as array
                                if (is_array($value)) {
                                    $submission->setFieldValue($handle, $value[0]);
                                } else {
                                    $submission->setFieldValue($handle, $value);
                                }

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
                try {
                    $newNotification = new Notification();
                    $newNotification->formId = $this->_form->id;
                    $newNotification->name = $notification->getName();
                    $newNotification->handle = Formie::$plugin->getNotifications()->getUniqueNotificationHandle($newNotification);
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
            if (!FormieForm::find()->withoutCpIndexScope()->handle($handle)->site('*')->unique()->status(null)->exists()) {
                return $handle;
            }

            $newHandle = $form->handle . $increment;

            $this->warning("    > Handle “{$handle}” is taken, will try “{$newHandle}” instead.");

            $handle = $newHandle;

            $increment++;
        }
    }

    private function _buildFieldLayout(FreeformForm $form): void
    {
        $pages = [];
        $fields = [];
        $layout = $form->getLayout();

        foreach ($layout->getPages() as $pageIndex => $page) {
            $newPage = [];
            $newPage['label'] = $page->getLabel();

            // $pageFields = [];
            $fieldHashes = [];

            foreach ($page->getRows() as $rowIndex => $row) {
                $newRow = [];

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
                        } else {
                            $fieldHashes[] = $field->getHandle();

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

        $this->_form->getFormLayout()->setPages($pages);
    }

    private function _mapField(FreeformFieldInterfae $field): ?FormieFieldInterface
    {
        switch (get_class($field)) {
            case CheckboxField::class:
                /* @var freeformfields\CheckboxField $field */
                $newField = new Agree();
                $this->_applyFieldDefaults($newField);

                $newField->description = RichText::fromHtml('<p>' . $field->getLabel() . '</p>');
                $newField->defaultValue = $field->isChecked();
                $newField->checkedValue = $field->getValue();
                $newField->uncheckedValue = Craft::t('app', 'No');
                break;

            case ConfirmationField::class:
                // We want to ensure *this* field is the same as the target field, so grab that type
                $targetField = $field->getTargetField();
                $targetFormieField = $this->_mapField($targetField);

                if ($targetFormieField) {
                    $fieldClass = get_class($targetFormieField);

                    $newField = new $fieldClass();
                    $newField->matchField = '{' . $targetFormieField->handle . '}';

                    $this->_applyFieldDefaults($newField);
                }

                break;

            case CheckboxesField::class:
                /* @var freeformfields\CheckboxesField $field */
                $newField = new Checkboxes();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case DatetimeField::class:
                /* @var freeformfields\DatetimeField $field */
                $newField = new Date();
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

            case EmailField::class:
                /* @var freeformfields\EmailField $field */
                $newField = new Email();
                $this->_applyFieldDefaults($newField);
                break;

            case FileUploadField::class:
                /* @var freeformfields\FileUploadField $field */
                $newField = new FileUpload();
                $this->_applyFieldDefaults($newField);

                $source = $field->getAssetSourceId();

                if ($source = Craft::$app->getAssets()->getRootFolderByVolumeId($source)) {
                    $newField->uploadLocationSource = "folder:{$source->getVolume()->uid}";
                } elseif ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                    $newField->uploadLocationSource = "folder:{$volumes[0]->uid}";
                }

                $newField->uploadLocationSubpath = $field->getDefaultUploadLocation();
                $newField->restrictFiles = !empty($field->getFileKinds());
                $newField->allowedKinds = $field->getFileKinds() ?? [];
                break;

            case HiddenField::class:
                /* @var freeformfields\HiddenField $field */
                $newField = new Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->getValue();
                break;

            case HtmlField::class:
                /* @var freeformfields\HtmlField $field */
                $newField = new Html();
                $this->_applyFieldDefaults($newField);

                $newField->label = $field->getLabel();
                $newField->handle = $field->getHash();
                $newField->htmlContent = $field->getValue();
                $newField->labelPosition = HiddenPosition::class;
                break;

            case InvisibleField::class:
                /* @var freeformfields\Pro\InvisibleField $field */
                $newField = new Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->getValue();
                break;

            case MultipleSelectField::class:
                /* @var freeformfields\MultipleSelectField $field */
                $newField = new Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->setMultiple(true);
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case NumberField::class:
                /* @var freeformfields\NumberField $field */
                $newField = new Number();
                $this->_applyFieldDefaults($newField);

                if ($min = $field->getMinValue()) {
                    $newField->min = $min;
                }

                if ($max = $field->getMaxValue()) {
                    $newField->max = $max;
                }

                $newField->decimals = $field->getDecimalCount();
                break;

            case PhoneField::class:
                /* @var freeformfields\Pro\PhoneField $field */
                $newField = new Phone();

                $this->_applyFieldDefaults($newField);
                break;

            case RadiosField::class:
                /* @var freeformfields\RadiosField $field */
                $newField = new Radio();
                $this->_applyFieldDefaults($newField);

                $newField->layout = $field->isOneLine() ? 'horizontal' : 'vertical';
                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case DropdownField::class:
                /* @var freeformfields\DropdownField $field */
                $newField = new Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->getOptions());

                // Setup the default value properly in options
                $newField->defaultValue = null;
                break;

            case TableField::class:
                /* @var freeformfields\TableField $field */
                $newField = new Table();
                $newField->addRowLabel = $field->getAddButtonLabel();

                foreach ($field->getTableLayout() as $key => $row) {
                    $newField->columns[$key] = [
                        'id' => 'col' . ($key + 1),
                        'heading' => $row->label ?? '',
                        'handle' => StringHelper::toCamelCase($row->value ?? ''),
                        'type' => $row->type ?? 'singleline',
                    ];
                }

                break;

            case TextareaField::class:
                /* @var freeformfields\TextareaField $field */
                $newField = new MultiLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case TextField::class:
                /* @var freeformfields\TextField $field */
                $newField = new SingleLineText();

                if ($field->getMaxLength()) {
                    $newField->limit = true;
                    $newField->maxType = 'characters';
                    $newField->max = $field->getMaxLength();
                }

                $this->_applyFieldDefaults($newField);
                break;

            case WebsiteField::class:
                /* @var freeformfields\Pro\WebsiteField $field */
                $newField = new SingleLineText();

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

        if (!$newField instanceof Address and !$newField instanceof Name) {
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
}

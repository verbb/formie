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
use verbb\formie\models\Address;
use verbb\formie\models\Name;
use verbb\formie\models\Notification;
use verbb\formie\models\Phone;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\base\FieldInterface;
use craft\db\Migration;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\Json;

use yii\console\Controller;
use yii\helpers\Markdown;

use Throwable;

use barrelstrength\sproutforms\elements\Form as SproutFormsForm;
use barrelstrength\sproutforms\elements\Entry as SproutFormsEntry;
use barrelstrength\sproutforms\fields\formfields as sproutfields;
use barrelstrength\sproutbaseemail\elements\NotificationEmail;
use barrelstrength\sproutbaseemail\SproutBaseEmail;
use barrelstrength\sproutforms\SproutForms;
use verbb\formie\models\Settings;

/**
 * Migrates Sprout Forms forms, notifications and submissions.
 */
class MigrateSproutForms extends Migration
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

    private ?SproutFormsForm $_sproutForm = null;
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

        /* @var SproutFormsForm $sproutFormsForm */
        if ($this->_sproutForm = SproutFormsForm::find()->id($this->formId)->one()) {
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
        $sproutFormsForm = $this->_sproutForm;

        $this->stdout("Form: Preparing to migrate form “{$sproutFormsForm->handle}”.");

        try {
            $form = new Form();
            $form->title = $sproutFormsForm->name;
            $form->handle = $this->_getHandle($sproutFormsForm);
            $form->settings->submissionTitleFormat = $sproutFormsForm->titleFormat != "{dateCreated|date('D, d M Y H:i:s')}" ? $sproutFormsForm->titleFormat : '';
            $form->settings->displayPageTabs = $sproutFormsForm->displaySectionTitles;
            $form->settings->submitMethod = $sproutFormsForm->submissionMethod == 'sync' ? 'page-reload' : 'ajax';
            $form->settings->submitActionUrl = $sproutFormsForm->redirectUri;
            $form->settings->submitAction = 'url';
            $form->settings->submitActionMessage = $sproutFormsForm->successMessage;
            $form->settings->storeData = $sproutFormsForm->saveData ?? true;

            // Set default template
            if ($templateId = $settings->getDefaultFormTemplateId()) {
                $form->templateId = $templateId;
            }

            // Fire a 'modifyForm' event
            $event = new ModifyMigrationFormEvent([
                'form' => $sproutFormsForm,
                'newForm' => $form,
            ]);
            $this->trigger(self::EVENT_MODIFY_FORM, $event);

            $form = $this->_form = $event->newForm;

            if ($fieldLayout = $this->_buildFieldLayout($sproutFormsForm)) {
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
            $this->stdout("    > Failed to migrate “{$sproutFormsForm->handle}”.", Console::FG_RED);
            $this->stdout("    > `{$this->getExceptionTraceAsString($e)}`", Console::FG_RED);

            $transaction->rollBack();

            throw $e;
        }

        return $form;
    }

    private function _migrateSubmissions(): void
    {
        $status = Formie::$plugin->getStatuses()->getAllStatuses()[0];

        $fields = $this->_sproutForm->getFieldLayout()->getCustomFields();
        $entries = SproutFormsEntry::find()->formId($this->_sproutForm->id)->ids();
        $total = count($entries);

        $this->stdout("Entries: Preparing to migrate $total entries to submissions.");

        if (!$total) {
            $this->stdout('    > No entries to migrate.', Console::FG_YELLOW);

            return;
        }

        foreach ($entries as $entryId) {
            $entry = SproutForms::$app->entries->getEntryById($entryId);
            $submission = new Submission();
            $submission->title = $entry->title;
            $submission->setForm($this->_form);
            $submission->setStatus($status);
            $submission->dateCreated = $entry->dateCreated;
            $submission->dateUpdated = $entry->dateUpdated;

            foreach ($fields as $field) {
                // Parse the handle for a few things just in case
                $handle = $this->_getFieldHandle($field->handle, false);

                try {
                    switch (get_class($field)) {
                        case sproutfields\Address::class:
                            /* @var \barrelstrength\sproutbasefields\models\Address $value */
                            $value = $entry->getFieldValue($field->handle);

                            $address = new Address();
                            $address->address1 = $value->address1 ?? '';
                            $address->address2 = $value->address2 ?? '';
                            $address->address3 = $value->address3 ?? '';
                            $address->city = $value->locality ?? '';
                            $address->state = $value->administrativeArea ?? '';
                            $address->country = $value->countryCode ?? '';

                            $submission->setFieldValue($handle, $address);
                            break;
                        case sproutfields\Name::class:
                            /* @var \barrelstrength\sproutbasefields\models\Name $value */
                            $value = $entry->getFieldValue($field->handle);

                            $name = new Name();

                            $name->prefix = $value->prefix ?? '';
                            $name->firstName = $value->firstName ?? '';
                            $name->middleName = $value->middleName ?? '';
                            $name->lastName = $value->lastName ?? '';

                            $submission->setFieldValue($handle, $name);
                            break;
                        case sproutfields\Phone::class:
                            /* @var \barrelstrength\sproutbasefields\models\Phone $value */
                            $value = $entry->getFieldValue($field->handle);

                            /* @var formfields\Phone $newField */
                            $newField = $this->_form->getFieldByHandle($field->handle);

                            $phone = new Phone();
                            $phone->number = $value->phone ?? '';

                            $country = $value->country ?? '';
                            $countryDefaultValue = $value->countryDefaultValue ?? '';

                            $phone->hasCountryCode = (bool)$country;
                            $phone->country = $country ?: $countryDefaultValue;

                            $submission->setFieldValue($handle, $phone);
                            break;
                        default:
                            $submission->setFieldValue($handle, $entry->getFieldValue($field->handle));
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
                $this->stdout("    > Failed to save Formie submission for Sprout Forms entry “{$entry->id}”.", Console::FG_RED);

                foreach ($event->submission->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->stdout("    > $attr: $error", Console::FG_RED);
                    }
                }
            } else {
                $this->stdout("    > Migrated Sprout Forms entry “{$entry->id}” to Formie submission “{$event->submission->id}”.", Console::FG_GREEN);
            }
        }

        $this->stdout("    > All entries completed.", Console::FG_GREEN);
    }

    private function _migrateNotifications(): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        /* @var Notification[] $notifications */
        $notifications = SproutBaseEmail::$app->notifications->getAllNotificationEmails();
        $total = 0;

        foreach ($notifications as $notification) {
            $options = $notification->getOptions();
            $formIds = $options['formIds'] ?? [];

            if (in_array($this->_sproutForm->id, $formIds)) {
                $total++;
            }
        }

        $this->stdout("Notifications: Preparing to migrate $total notifications.");

        if (!$notifications) {
            $this->stdout("    > No notifications to migrate.", Console::FG_YELLOW);

            return;
        }

        foreach ($notifications as $notification) {
            try {
                $options = $notification->getOptions();
                $formIds = $options['formIds'] ?? [];

                if (!$formIds) {
                    $this->stdout("    > No form IDs found for “{$notification->title}”.", Console::FG_YELLOW);
                }

                if (in_array($this->_sproutForm->id, $formIds)) {
                    $newNotification = new Notification();
                    $newNotification->formId = $this->_form->id;
                    $newNotification->name = $notification->title;
                    $newNotification->subject = $notification->subjectLine;
                    $newNotification->recipients = 'email';
                    $newNotification->to = $notification->recipients;
                    $newNotification->cc = $notification->cc;
                    $newNotification->bcc = $notification->bcc;
                    $newNotification->from = $notification->fromEmail;
                    $newNotification->fromName = $notification->fromName;
                    $newNotification->replyTo = $notification->replyToEmail;
                    $newNotification->attachFiles = (bool)$notification->enableFileAttachments;
                    $newNotification->enabled = (bool)$notification->enabled;

                    // Set default template
                    if ($templateId = $settings->getDefaultEmailTemplateId()) {
                        $newNotification->templateId = $templateId;
                    }

                    $body = $this->_tokenizeNotificationBody($notification->defaultBody);
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
                        continue;
                    }

                    $newNotification = $event->newNotification;

                    if (Formie::$plugin->getNotifications()->saveNotification($newNotification)) {
                        $this->stdout("    > Migrated notification “{$notification->title}”.", Console::FG_GREEN);
                    } else {
                        $this->stdout("    > Failed to save notification “{$notification->title}”.", Console::FG_RED);

                        foreach ($newNotification->getErrors() as $attr => $errors) {
                            foreach ($errors as $error) {
                                $this->stdout("    > $attr: $error", Console::FG_RED);
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->stdout("    > Failed to migrate “{$notification->title}”.", Console::FG_RED);
                $this->stdout("    > `{$this->getExceptionTraceAsString($e)}`", Console::FG_RED);

                continue;
            }
        }

        $this->stdout("    > All notifications completed.", Console::FG_GREEN);
    }

    private function _getHandle(SproutFormsForm $form): string
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
     * @param SproutFormsForm $form
     * @return FieldLayout
     */
    private function _buildFieldLayout(SproutFormsForm $form): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => Form::class]);
        $fieldLayout->type = Form::class;

        if ($sproutFieldLayout = $form->getFieldLayout()) {
            $pages = [];
            $fields = [];

            foreach ($sproutFieldLayout->getTabs() as $tabIndex => $tab) {
                $newPage = new FieldLayoutPage();
                $newPage->name = $tab->name;
                $newPage->sortOrder = '' . $tabIndex;

                $pageFields = [];

                foreach ($tab->getFields() as $field) {
                    $newField = $this->_mapField($field);

                foreach ($tab->getCustomFields() as $field) {
                    if ($newField = $this->_mapField($field)) {
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

                            continue;
                        }

                        $newField->sortOrder = 0;
                        $newField->rowIndex = count($pageFields);
                        $pageFields[] = $newField;
                        $fields[] = $newField;
                    } else {
                        $this->stdout("    > Failed to migrate field “{$field->handle}” on form “{$form->handle}”. Unsupported field.", Console::FG_RED);
                    }
                }

                $newPage->setLayout($fieldLayout);
                $newPage->setCustomFields($pageFields);
                $pages[] = $newPage;
            }

            $fieldLayout->setPages($pages);
            $fieldLayout->setCustomFields($fields);
        }

        return $fieldLayout;
    }

    /**
     * @param FieldInterface $field
     * @return FormFieldInterface|null
     */
    private function _mapField(FieldInterface $field): ?FormFieldInterface
    {
        switch (get_class($field)) {
            case sproutfields\Address::class:
                /* @var sproutfields\Address $field */
                $newField = new formfields\Address();
                $this->_applyFieldDefaults($newField);

                $newField->countryEnabled = (bool)$field->showCountryDropdown;
                $newField->countryDefaultValue = $field->defaultCountry;
                $newField->address1Required = (bool)$field->required;
                $newField->address2Required = false;
                $newField->cityRequired = (bool)$field->required;
                $newField->stateRequired = (bool)$field->required;
                $newField->zipRequired = (bool)$field->required;

                if ($newField->countryEnabled) {
                    $newField->countryRequired = (bool)$field->required;
                }
                break;
            case sproutfields\Categories::class:
                /* @var formfields\Categories $field */
                $newField = new formfields\Categories();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->branchLimit = $field->branchLimit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            case sproutfields\Checkboxes::class:
                /* @var sproutfields\Checkboxes $field */
                $newField = new formfields\Checkboxes();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->options);
                break;
            case sproutfields\CustomHtml::class:
                /* @var sproutfields\CustomHtml $field */
                $newField = new formfields\Html();
                $this->_applyFieldDefaults($newField);

                $newField->htmlContent = $field->customHtml;
                $newField->labelPosition = $field->hideLabel ? HiddenPosition::class : '';
                break;
            case sproutfields\Date::class:
                /* @var sproutfields\Date $field */
                $newField = new formfields\Date();
                $this->_applyFieldDefaults($newField);

                $newField->displayType = 'calendar';
                break;
            case sproutfields\Dropdown::class:
                /* @var sproutfields\Dropdown $field */
                $newField = new formfields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $field->options;
                break;
            case sproutfields\Email::class:
                /* @var sproutfields\Email $field */
                $newField = new formfields\Email();
                $this->_applyFieldDefaults($newField);
                break;
            case sproutfields\EmailDropdown::class:
                /* @var sproutfields\Dropdown $field */
                $newField = new formfields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $field->options;
                break;
            case sproutfields\Entries::class:
                /* @var BaseRelationField $field */
                $newField = new formfields\Entries();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->limit = $field->limit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            case sproutfields\FileUpload::class:
                /* @var sproutfields\FileUpload $field */
                $newField = new formfields\FileUpload();
                $this->_applyFieldDefaults($newField);

                $newField->uploadLocationSource = str_replace('volume', 'folder', $field->defaultUploadLocationSource);
                $newField->uploadLocationSubpath = $field->defaultUploadLocationSubpath;
                $newField->restrictFiles = !empty($field->allowedKinds);
                $newField->allowedKinds = $field->allowedKinds ?? [];
                break;
            case sproutfields\Hidden::class:
                /* @var sproutfields\Hidden $field */
                $newField = new formfields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->value;
                break;
            case sproutfields\Invisible::class:
                /* @var sproutfields\Hidden $field */
                $newField = new formfields\Hidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->value;
                return null;
            case sproutfields\MultipleChoice::class:
                /* @var sproutfields\MultipleChoice $field */
                $newField = new formfields\Radio();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->options);
                break;
            case sproutfields\MultiSelect::class:
                /* @var sproutfields\MultiSelect $field */
                $newField = new formfields\Dropdown();
                $this->_applyFieldDefaults($newField);

                $newField->setMultiple(true);
                $newField->options = $this->_mapOptions($field->options);
                break;
            case sproutfields\Name::class:
                /* @var sproutfields\Name $field */
                $newField = new formfields\Name();
                $this->_applyFieldDefaults($newField);

                $newField->useMultipleFields = (bool)$field->displayMultipleFields;
                if ($newField->useMultipleFields) {
                    $newField->prefixEnabled = (bool)$field->displayPrefix;
                    $newField->firstNameEnabled = true;
                    $newField->middleNameEnabled = (bool)$field->displayMiddleName;
                    $newField->lastNameEnabled = true;

                    $newField->firstNameRequired = (bool)$field->required;
                    $newField->lastNameRequired = (bool)$field->required;

                    if ($newField->prefixEnabled) {
                        $newField->prefixRequired = (bool)$field->required;
                    }

                    if ($newField->middleNameRequired) {
                        $newField->middleNameRequired = (bool)$field->required;
                    }
                }
                break;
            case sproutfields\Number::class:
                /* @var sproutfields\Number $field */
                $newField = new formfields\Number();
                $this->_applyFieldDefaults($newField);

                $newField->min = $field->min;
                $newField->max = $field->max;
                $newField->decimals = $field->decimals;
                break;
            case sproutfields\OptIn::class:
                /* @var sproutfields\OptIn $field */
                $newField = new formfields\Agree();
                $this->_applyFieldDefaults($newField);

                $description = (new Renderer)->render('<p>' . $field->optInMessage . '</p>');

                $newField->description = $description['content'];
                $newField->checkedValue = $field->optInValueWhenTrue;
                $newField->uncheckedValue = $field->optInValueWhenFalse;
                break;
            case sproutfields\Paragraph::class:
                /* @var sproutfields\Paragraph $field */
                $newField = new formfields\MultiLineText();
                $this->_applyFieldDefaults($newField);
                break;
            case sproutfields\Phone::class:
                /* @var sproutfields\Phone $field */
                $newField = new formfields\Phone();
                $this->_applyFieldDefaults($newField);

                $newField->countryEnabled = !$field->limitToSingleCountry;
                $newField->countryDefaultValue = $field->country;
                break;
            case sproutfields\PrivateNotes::class:
                // Not implemented
                return null;
            case sproutfields\RegularExpression::class:
                // Not implemented
                return null;
            case sproutfields\SectionHeading::class:
                $newField = new formfields\Heading();
                $this->_applyFieldDefaults($newField);

                $newField->labelPosition = $field->hideLabel ? HiddenPosition::class : '';
                break;
            case sproutfields\SingleLine::class:
                $newField = new formfields\SingleLineText();
                $this->_applyFieldDefaults($newField);

                break;
            case sproutfields\Tags::class:
                /* @var BaseRelationField $field */
                $newField = new formfields\Tags();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->limit = $field->limit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
            case sproutfields\Url::class:
                break;
                $newField = new formfields\SingleLineText();
                $this->_applyFieldDefaults($newField);

                break;
            case sproutfields\Users::class:
                /* @var BaseRelationField $field */
                $newField = new formfields\Users();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->limit = $field->limit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            default:
                return null;
        }

        $newField->name = $field->name;
        $newField->handle = $field->handle;
        $newField->placeholder = $field->placeholder ?? '';
        $newField->cssClasses = $field->cssClasses ?? '';
        $newField->instructions = $field->instructions ?? '';

        // Parse the handle for a few things just in case
        $newField->handle = $this->_getFieldHandle($newField->handle);

        if (!$newField instanceof formfields\Address and !$newField instanceof formfields\Name) {
            $newField->required = (bool)$field->required;
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

    private function _mapOptions($options): array
    {
        if (!$options) {
            return [];
        }

        return array_values(array_map(function($option) {
            $option['isDefault'] = (bool)($option['default'] ?? false);
            unset($option['default']);
            return $option;
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

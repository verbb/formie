<?php
namespace verbb\formie\migrations\plugins;

use verbb\formie\Formie;
use verbb\formie\base\ElementFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\events\ModifyMigrationFormEvent;
use verbb\formie\events\ModifyMigrationNotificationEvent;
use verbb\formie\events\ModifyMigrationSubmissionEvent;
use verbb\formie\fields\values\AddressFieldValue;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;
use verbb\formie\migrations\plugins\formfields\Address as FormieAddress;
use verbb\formie\migrations\plugins\formfields\Agree;
use verbb\formie\migrations\plugins\formfields\Categories as FormieCategories;
use verbb\formie\migrations\plugins\formfields\Checkboxes as FormieCheckboxes;
use verbb\formie\migrations\plugins\formfields\Date as FormieDate;
use verbb\formie\migrations\plugins\formfields\Dropdown as FormieDropdown;
use verbb\formie\migrations\plugins\formfields\Email as FormieEmail;
use verbb\formie\migrations\plugins\formfields\Entries as FormieEntries;
use verbb\formie\migrations\plugins\formfields\FileUpload as FormieFileUpload;
use verbb\formie\migrations\plugins\formfields\Heading;
use verbb\formie\migrations\plugins\formfields\Hidden as FormieHidden;
use verbb\formie\migrations\plugins\formfields\Html;
use verbb\formie\migrations\plugins\formfields\MultiLineText;
use verbb\formie\migrations\plugins\formfields\Name as FormieName;
use verbb\formie\migrations\plugins\formfields\Number as FormieNumber;
use verbb\formie\migrations\plugins\formfields\Phone as FormiePhone;
use verbb\formie\migrations\plugins\formfields\Radio;
use verbb\formie\migrations\plugins\formfields\SingleLineText;
use verbb\formie\migrations\plugins\formfields\Tags as FormieTags;
use verbb\formie\migrations\plugins\formfields\Users as FormieUsers;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;

use Throwable;

use barrelstrength\sproutbaseemail\SproutBaseEmail;
use barrelstrength\sproutforms\elements\Entry as SproutFormsEntry;
use barrelstrength\sproutforms\elements\Form as SproutFormsForm;
use barrelstrength\sproutforms\fields as sproutfields;
use barrelstrength\sproutforms\fields\Address;
use barrelstrength\sproutforms\fields\Categories;
use barrelstrength\sproutforms\fields\Checkboxes;
use barrelstrength\sproutforms\fields\CustomHtml;
use barrelstrength\sproutforms\fields\Date;
use barrelstrength\sproutforms\fields\Dropdown;
use barrelstrength\sproutforms\fields\Email;
use barrelstrength\sproutforms\fields\EmailDropdown;
use barrelstrength\sproutforms\fields\Entries;
use barrelstrength\sproutforms\fields\FileUpload;
use barrelstrength\sproutforms\fields\Hidden;
use barrelstrength\sproutforms\fields\Invisible;
use barrelstrength\sproutforms\fields\MultipleChoice;
use barrelstrength\sproutforms\fields\MultiSelect;
use barrelstrength\sproutforms\fields\Name;
use barrelstrength\sproutforms\fields\Number;
use barrelstrength\sproutforms\fields\OptIn;
use barrelstrength\sproutforms\fields\Paragraph;
use barrelstrength\sproutforms\fields\Phone;
use barrelstrength\sproutforms\fields\PrivateNotes;
use barrelstrength\sproutforms\fields\RegularExpression;
use barrelstrength\sproutforms\fields\SectionHeading;
use barrelstrength\sproutforms\fields\SingleLine;
use barrelstrength\sproutforms\fields\Tags;
use barrelstrength\sproutforms\fields\Url;
use barrelstrength\sproutforms\fields\Users;
use barrelstrength\sproutforms\SproutForms;

class MigrateSproutForms extends BasePluginMigrator
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


    // Public Methods
    // =========================================================================

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

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateForm(): ?Form
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $transaction = Craft::$app->getDb()->beginTransaction();
        $sproutFormsForm = $this->_sproutForm;

        $this->info("Form: Preparing to migrate form “{$sproutFormsForm->handle}”.");

        try {
            $form = new Form();
            $form->title = $sproutFormsForm->name;
            $form->handle = $this->_getHandle($sproutFormsForm);
            $form->settings->submissionTitleFormat = $sproutFormsForm->titleFormat != "{dateCreated|date('D, d M Y H:i:s')}" ? $sproutFormsForm->titleFormat : '';
            $form->settings->displayPageTabs = $sproutFormsForm->displaySectionTitles;
            $form->settings->submitMethod = $sproutFormsForm->submissionMethod == 'sync' ? 'page-reload' : 'ajax';
            $form->settings->redirectUrl = $sproutFormsForm->redirectUri;
            $form->settings->completionBehavior = 'redirect';
            $form->settings->completionRedirectSource = 'url';
            $form->settings->successMessage = $this->toRichText($sproutFormsForm->successMessage);
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
                $form->setFieldLayout($fieldLayout);
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
                                    $this->error("    > $attr: $error");
                                }
                            }
                        }
                    }
                }
            } else {
                $this->success("    > Form “{$form->handle}” migrated.");
            }
        } catch (Throwable $e) {
            $this->error("    > Failed to migrate “{$sproutFormsForm->handle}”.");

            $transaction->rollBack();

            throw $e;
        }

        return $form;
    }

    private function _migrateSubmissions(): void
    {
        $statuses = Formie::$plugin->getSubmissionStatuses()->getAllStatuses();
        $status = reset($statuses) ?: null;

        $fields = $this->_sproutForm->getFieldLayout()->getCustomFields();
        $entries = SproutFormsEntry::find()->formId($this->_sproutForm->id)->ids();
        $total = count($entries);

        $this->info("Entries: Preparing to migrate $total entries to submissions.");

        if (!$total) {
            $this->warning('    > No entries to migrate.');

            return;
        }

        foreach ($entries as $entryId) {
            $entry = SproutForms::$app->entries->getEntryById($entryId);
            $submission = new Submission();
            $submission->title = $entry->title;
            $submission->setForm($this->_form);
            $submission->dateCreated = $entry->dateCreated;
            $submission->dateUpdated = $entry->dateUpdated;

            if ($status) {
                $submission->setStatus($status);
            }

            foreach ($fields as $field) {
                // Parse the handle for a few things just in case
                $handle = $this->_getFieldHandle($field->handle, false);

                try {
                    switch (get_class($field)) {
                        case Address::class:
                            /* @var \barrelstrength\sproutbasefields\models\Address $value */
                            $value = $entry->getFieldValue($field->handle);

                            $address = new AddressFieldValue([
                                'address1' => $value->address1 ?? '',
                                'address2' => $value->address2 ?? '',
                                'address3' => $value->address3 ?? '',
                                'city' => $value->locality ?? '',
                                'state' => $value->administrativeArea ?? '',
                                'country' => $value->countryCode ?? '',
                            ]);
                            $submission->setFieldValue($handle, $address);
                            break;
                        case Name::class:
                            /* @var \barrelstrength\sproutbasefields\models\Name $value */
                            $value = $entry->getFieldValue($field->handle);

                            $name = new NameFieldValue([
                                'prefix' => $value->prefix ?? '',
                                'firstName' => $value->firstName ?? '',
                                'middleName' => $value->middleName ?? '',
                                'lastName' => $value->lastName ?? '',
                            ]);
                            $submission->setFieldValue($handle, $name);
                            break;
                        case Phone::class:
                            /* @var \barrelstrength\sproutbasefields\models\Phone $value */
                            $value = $entry->getFieldValue($field->handle);

                            /* @var formfields\Phone $newField */
                            $newField = $this->_form->getFieldByHandle($field->handle);

                            $submission->setFieldValue($handle, ['number' => $value->phone ?? '', 'country' => $value->country ?: $value->countryDefaultValue]);
                            break;
                        default:
                            $submission->setFieldValue($handle, $entry->getFieldValue($field->handle));
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
                continue;
            }

            if (!Craft::$app->getElements()->saveElement($event->submission)) {
                $this->error("    > Failed to save Formie submission for Sprout Forms entry “{$entry->id}”.");

                foreach ($event->submission->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->error("    > $attr: $error");
                    }
                }
            } else {
                $this->success("    > Migrated Sprout Forms entry “{$entry->id}” to Formie submission “{$event->submission->id}”.");
            }
        }

        $this->success("    > All entries completed.");
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

        $this->info("Notifications: Preparing to migrate $total notifications.");

        if (!$notifications) {
            $this->warning("    > No notifications to migrate.");

            return;
        }

        foreach ($notifications as $notification) {
            try {
                $options = $notification->getOptions();
                $formIds = $options['formIds'] ?? [];

                if (!$formIds) {
                    $this->warning("    > No form IDs found for “{$notification->title}”.");
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

                    $newNotification = $event->newNotification;

                    if (Formie::$plugin->getNotifications()->saveNotification($newNotification)) {
                        $this->success("    > Migrated notification “{$notification->title}”.");
                    } else {
                        $this->error("    > Failed to save notification “{$notification->title}”.");

                        foreach ($newNotification->getErrors() as $attr => $errors) {
                            foreach ($errors as $error) {
                                $this->error("    > $attr: $error");
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->error("    > Failed to migrate “{$notification->title}”.");
                $this->error("    > `{$this->getExceptionTraceAsString($e)}`");

                continue;
            }
        }

        $this->success("    > All notifications completed.");
    }

    private function _getHandle(SproutFormsForm $form): string
    {
        $increment = 1;
        $handle = $form->handle;

        while (true) {
            if (!Form::find()->withoutCpIndexScope()->handle($handle)->site('*')->unique()->status(null)->exists()) {
                return $handle;
            }

            $newHandle = $form->handle . $increment;

            $this->warning("    > Handle “{$handle}” is taken, will try “{$newHandle}” instead.");

            $handle = $newHandle;

            $increment++;
        }
    }

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

                                continue;
                            }

                            $newField->sortOrder = 0;
                            $newField->rowIndex = count($pageFields);
                            $pageFields[] = $newField;
                            $fields[] = $newField;
                        } else {
                            $this->error("    > Failed to migrate field “{$field->handle}” on form “{$form->handle}”. Unsupported field.");
                        }
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

    private function _mapField(FieldInterface $field): ?FieldInterface
    {
        switch (get_class($field)) {
            case Address::class:
                /* @var sproutfields\Address $field */
                $newField = new FormieAddress();
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
            case Categories::class:
                /* @var formfields\Categories $field */
                $newField = new FormieCategories();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->branchLimit = $field->branchLimit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            case Checkboxes::class:
                /* @var sproutfields\Checkboxes $field */
                $newField = new FormieCheckboxes();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->options);
                break;
            case CustomHtml::class:
                /* @var sproutfields\CustomHtml $field */
                $newField = new Html();
                $this->_applyFieldDefaults($newField);

                $newField->htmlContent = $field->customHtml;
                $newField->labelPosition = $field->hideLabel ? HiddenPosition::class : '';
                break;
            case Date::class:
                /* @var sproutfields\Date $field */
                $newField = new FormieDate();
                $this->_applyFieldDefaults($newField);

                $newField->displayType = 'calendar';
                break;
            case Dropdown::class:
                /* @var sproutfields\Dropdown $field */
                $newField = new FormieDropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $field->options;
                break;
            case Email::class:
                /* @var sproutfields\Email $field */
                $newField = new FormieEmail();
                $this->_applyFieldDefaults($newField);
                break;
            case EmailDropdown::class:
                /* @var sproutfields\Dropdown $field */
                $newField = new FormieDropdown();
                $this->_applyFieldDefaults($newField);

                $newField->options = $field->options;
                break;
            case Entries::class:
                /* @var ElementFieldInterface $field */
                $newField = new FormieEntries();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->limit = $field->limit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            case FileUpload::class:
                /* @var sproutfields\FileUpload $field */
                $newField = new FormieFileUpload();
                $this->_applyFieldDefaults($newField);

                $newField->uploadLocationSource = str_replace('volume', 'folder', $field->defaultUploadLocationSource);
                $newField->uploadLocationSubpath = $field->defaultUploadLocationSubpath;
                $newField->restrictFiles = !empty($field->allowedKinds);
                $newField->allowedKinds = $field->allowedKinds ?? [];
                break;
            case Hidden::class:
                /* @var sproutfields\Hidden $field */
                $newField = new FormieHidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->value;
                break;
            case Invisible::class:
                /* @var sproutfields\Hidden $field */
                $newField = new FormieHidden();
                $this->_applyFieldDefaults($newField);

                $newField->defaultValue = $field->value;
                return null;
            case MultipleChoice::class:
                /* @var sproutfields\MultipleChoice $field */
                $newField = new Radio();
                $this->_applyFieldDefaults($newField);

                $newField->options = $this->_mapOptions($field->options);
                break;
            case MultiSelect::class:
                /* @var sproutfields\MultiSelect $field */
                $newField = new FormieDropdown();
                $this->_applyFieldDefaults($newField);

                $newField->setMultiple(true);
                $newField->options = $this->_mapOptions($field->options);
                break;
            case Name::class:
                /* @var sproutfields\Name $field */
                $newField = new FormieName();
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
            case Number::class:
                /* @var sproutfields\Number $field */
                $newField = new FormieNumber();
                $this->_applyFieldDefaults($newField);

                $newField->min = $field->min;
                $newField->max = $field->max;
                $newField->decimals = $field->decimals;
                break;
            case OptIn::class:
                /* @var sproutfields\OptIn $field */
                $newField = new Agree();
                $this->_applyFieldDefaults($newField);

                $newField->description = RichText::fromHtml('<p>' . $field->optInMessage . '</p>');
                $newField->checkedValue = $field->optInValueWhenTrue;
                $newField->uncheckedValue = $field->optInValueWhenFalse;
                break;
            case Paragraph::class:
                /* @var sproutfields\Paragraph $field */
                $newField = new MultiLineText();
                $this->_applyFieldDefaults($newField);
                break;
            case Phone::class:
                /* @var sproutfields\Phone $field */
                $newField = new FormiePhone();
                $this->_applyFieldDefaults($newField);

                $newField->countryEnabled = !$field->limitToSingleCountry;
                $newField->countryDefaultValue = $field->country;
                break;
            case PrivateNotes::class:
                // Not implemented
                return null;
            case RegularExpression::class:
                // Not implemented
                return null;
            case SectionHeading::class:
                $newField = new Heading();
                $this->_applyFieldDefaults($newField);

                $newField->labelPosition = $field->hideLabel ? HiddenPosition::class : '';
                break;
            case SingleLine::class:
                $newField = new SingleLineText();
                $this->_applyFieldDefaults($newField);

                break;
            case Tags::class:
                /* @var ElementFieldInterface $field */
                $newField = new FormieTags();
                $this->_applyFieldDefaults($newField);

                $newField->placeholder = $field->selectionLabel;
                $newField->groupId = $field->groupId;
                $newField->limit = $field->limit;
                $newField->source = $field->source;
                $newField->sources = $field->sources;
                break;
            case Url::class:
                $newField = new SingleLineText();
                $this->_applyFieldDefaults($newField);

                break;
            case Users::class:
                /* @var ElementFieldInterface $field */
                $newField = new FormieUsers();
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
        $newField->instructions = RichText::from($field->instructions ?? '');

        // Parse the handle for a few things just in case
        $newField->handle = $this->_getFieldHandle($newField->handle);

        if (!$newField instanceof FormieAddress and !$newField instanceof FormieName) {
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

    private function _applyFieldDefaults(FieldInterface $field): void
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
            $option['default'] = (bool)($option['default'] ?? false);
            unset($option['default']);
            return $option;
        }, $options));
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

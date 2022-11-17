<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\formfields\FileUpload;
use verbb\formie\fields\formfields\Payment;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\SiteIdValidator;
use craft\validators\StringValidator;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\NumberValidator;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

use Throwable;

use Twig\Markup;

class Submission extends Element
{
    // Constants
    // =========================================================================

    public const EVENT_DEFINE_RULES = 'defineSubmissionRules';
    public const EVENT_BEFORE_MARKED_AS_SPAM = 'beforeMarkedAsSpam';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Submission');
    }

    /**
     * @inheritDoc
     */
    public static function refHandle(): ?string
    {
        return 'submission';
    }

    /**
     * @inheritDoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function hasTitles(): bool
    {
        // We cannot have titles because the element index for All Forms doesn't seem
        // to like resolving multiple content tables. Don't use `populateElementContent`
        // because it adds n+1 queries.
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function find(): SubmissionQuery
    {
        return new SubmissionQuery(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Submission';
    }

    /**
     * @inheritdoc
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        return ['formieSubmissions.' . $context->uid];
    }

    /**
     * @inheritdoc
     */
    public static function gqlMutationNameByContext(mixed $context): string
    {
        return 'save_' . $context->handle . '_Submission';
    }

    /**
     * @inheritDoc
     */
    public static function statuses(): array
    {
        return Formie::$plugin->getStatuses()->getStatusesArray();
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;

        $submission = $sourceElements[0] ?? null;

        // Ensure we setup the correct content table before fetching the eager-loading map.
        // This is particular helps resolve element fields' content.
        if ($submission && $submission instanceof self) {
            $contentService->fieldContext = $submission->getFieldContext();
        }

        $map = parent::eagerLoadingMap($sourceElements, $handle);

        $contentService->fieldContext = $originalFieldContext;

        return $map;
    }

    /**
     * @inheritDoc
     */
    protected static function defineSources(string $context = null): array
    {
        $forms = Form::find()->all();

        $ids = self::_getAvailableFormIds();

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'criteria' => ['formId' => $ids],
                'defaultSort' => ['formie_submissions.title', 'desc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('formie', 'Forms')];

        foreach ($forms as $form) {
            if (is_array($ids)) {
                if (!in_array($form->id, $ids)) {
                    continue;
                }
            } else if ($ids === 0) {
                continue;
            }

            /* @var Form $form */
            $key = "form:{$form->id}";

            $sources[$key] = [
                'key' => $key,
                'label' => $form->title,
                'data' => [
                    'handle' => $form->handle,
                ],
                'criteria' => ['formId' => $form->id],
                'defaultSort' => ['formie_submissions.title', 'desc'],
            ];
        }

        return $sources;
    }

    /**
     * @inheritDoc
     */
    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        $actions[] = $elementsService->createAction([
            'type' => SetSubmissionStatus::class,
            'statuses' => Formie::$plugin->getStatuses()->getAllStatuses(),
        ]);

        $actions[] = $elementsService->createAction([
            'type' => SetSubmissionSpam::class,
        ]);

        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected submissions?'),
            'successMessage' => Craft::t('formie', 'Submissions deleted.'),
        ]);

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Submissions restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some submissions restored.'),
            'failMessage' => Craft::t('formie', 'Submissions not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];

        if (preg_match('/^form:(.+)$/', $source, $matches) && ($form = Formie::$plugin->getForms()->getFormById($matches[1]))) {
            if ($fieldLayout = $form->getFormFieldLayout()) {
                $fieldLayouts[] = $fieldLayout;
            }
        }

        return $fieldLayouts;
    }

    /**
     * @inheritDoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'form' => ['label' => Craft::t('formie', 'Form')],
            'spamReason' => ['label' => Craft::t('app', 'Spam Reason')],
            'ipAddress' => ['label' => Craft::t('app', 'IP Address')],
            'userId' => ['label' => Craft::t('app', 'User')],
            'sendNotification' => ['label' => Craft::t('formie', 'Send Notification')],
            'status' => ['label' => Craft::t('formie', 'Status')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'title';

        if ($source === '*') {
            $attributes[] = 'form';
        }

        $attributes[] = 'dateCreated';
        $attributes[] = 'dateUpdated';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

    /**
     * @inheritDoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Title'),
                'orderBy' => 'formie_submissions.title',
                'attribute' => 'title',
            ],
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
            ],
        ];
    }

    private static function _getAvailableFormIds(): int|array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        $submissions = Formie::$plugin->getSubmissions()->getEditableSubmissions($currentUser);
        $editableIds = ArrayHelper::getColumn($submissions, 'id');

        // Important to check if empty, there are zero editable forms, but as we use this as a criteria param
        // that would return all forms, not what we want.
        if (!$editableIds) {
            $editableIds = 0;
        }

        return $editableIds;
    }

    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $formId = null;
    public ?int $statusId = null;
    public ?int $userId = null;
    public ?string $ipAddress = null;
    public bool $isIncomplete = false;
    public bool $isSpam = false;
    public ?string $spamReason = null;
    public ?string $spamClass = null;
    public array $snapshot = [];
    public ?bool $validateCurrentPageOnly = null;

    private ?Form $_form = null;
    private ?Status $_status = null;
    private ?User $_user = null;
    private ?FieldLayout $_fieldLayout = null;
    private ?string $_fieldContext = null;
    private ?string $_contentTable = null;
    private ?array $_pagesForField = null;
    private ?array $_assetsToDelete = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->title;
    }
    
    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->getStatusModel(true)->handle ?? null;
    }

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        $form = $this->getForm();

        if ($form && $form->settings->requireUser) {
            if (!Craft::$app->getUser()->getIdentity()) {
                $this->addError('form', Craft::t('formie', 'You must be logged in to submit this form.'));
            }
        }

        if ($form && $form->settings->scheduleForm) {
            if (!$form->isScheduleActive()) {
                $this->addError('form', Craft::t('formie', 'This form is not available.'));
            }
        }

        if ($form && $form->settings->limitSubmissions) {
            if (!$form->isWithinSubmissionsLimit()) {
                $this->addError('form', Craft::t('formie', 'This form has met the number of allowed submissions.'));
            }
        }

        return $validates;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        // Only support the site the submission is being made on
        $siteId = $this->siteId ?: Craft::$app->getSites()->getPrimarySite()->id;

        return [$siteId];
    }

    /**
     * @inheritdoc
     */
    public function getIsDraft(): bool
    {
        return $this->isIncomplete;
    }

    /**
     * @inheritDoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if (!$this->_fieldLayout && $form = $this->getForm()) {
            $this->_fieldLayout = $form->getFormFieldLayout();
        }

        return $this->_fieldLayout;
    }

    /**
     * @inheritDoc
     */
    public function getFieldContext(): string
    {
        if (!$this->_fieldContext && $this->getFormRecord()) {
            $this->_fieldContext = "formie:{$this->getFormRecord()->uid}";
        }

        return $this->_fieldContext;
    }

    /**
     * @inheritDoc
     */
    public function getContentTable(): string
    {
        if (!$this->_contentTable && $this->getFormRecord()) {
            $this->_contentTable = $this->getFormRecord()->fieldContentTable;
        }

        return $this->_contentTable;
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): ?string
    {
        $form = $this->getForm();

        if (!$form) {
            return '';
        }

        $path = "formie/submissions/$form->handle";

        if ($this->id) {
            $path .= "/$this->id";
        } else {
            $path .= '/new';
        }

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    public function updateTitle($form): void
    {
        if ($customTitle = Variables::getParsedValue($form->settings->submissionTitleFormat, $this, $form)) {
            $this->title = $customTitle;

            // Rather than re-save, directly update the submission record
            Craft::$app->getDb()->createCommand()->update('{{%formie_submissions}}', ['title' => $customTitle], ['id' => $this->id])->execute();
            Craft::$app->getDb()->createCommand()->update('{{%content}}', ['title' => $customTitle], ['elementId' => $this->id])->execute();
        }
    }

    /**
     * Gets the submission's form.
     */
    public function getForm(): ?Form
    {
        if (!$this->_form && $this->formId) {
            $query = Form::find()->id($this->formId);

            $this->_form = $query->one();

            // If no form found yet, and the submission has been trashed, maybe the form has been trashed?
            if (!$this->_form && $this->trashed) {
                $query->trashed(true);

                $this->_form = $query->one();
            }
        }

        return $this->_form;
    }

    /**
     * Sets teh submission's form.
     */
    public function setForm(Form $form): void
    {
        $this->_form = $form;
        $this->formId = $form->id;

        // When setting the form, see if there's an in-session snapshot, or if there's a saved
        // snapshot from the database. This will be field settings set via templates which we want
        // to apply to fields in our form, for this submission.
        $this->snapshot = $form->getSnapshotData() ?: $this->snapshot;

        $fields = $this->snapshot['fields'] ?? [];

        foreach ($fields as $handle => $settings) {
            $form->setFieldSettings($handle, $settings, false);
        }

        // Do the same for form settings
        $formSettings = $this->snapshot['form'] ?? null;

        if ($formSettings) {
            $form->settings->setAttributes($formSettings, false);
        }
    }

    public function getFormRecord(): ?Form
    {
        if ($this->formId) {
            if ($form = Formie::$plugin->getForms()->getFormRecord($this->formId)) {
                return $form;
            }
        }

        if ($this->_form) {
            return $this->_form;
        }

        return null;
    }

    public function getFormName(): ?string
    {
        if ($form = $this->getForm()) {
            return $form->title;
        }
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle
     * @return FormFieldInterface|null
     */
    public function getFieldByHandle(string $handle): ?FormFieldInterface
    {
        if ($fieldLayout = $this->getFieldLayout()) {
            return ArrayHelper::firstWhere($fieldLayout->getCustomFields(), 'handle', $handle);
        }

        return null;
    }

    /**
     * Gets the submission's status model.
     *
     * @return Status
     */
    public function getStatusModel(): Status
    {
        if (!$this->_status && $this->statusId) {
            $this->_status = Formie::$plugin->getStatuses()->getStatusById($this->statusId);
        }

        if ($this->_status) {
            return $this->_status;
        }

        if ($form = $this->getForm()) {
            return $this->_status = $form->getDefaultStatus();
        }

        return $this->_status = Formie::$plugin->getStatuses()->getDefaultStatus();
    }

    /**
     * Sets the submission's status.
     */
    public function setStatus(Status $status): void
    {
        $this->_status = $status;
        $this->statusId = $status->id;
    }

    /**
     * Returns the user who created the submission.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        if (!$this->userId) {
            return null;
        }

        if ($this->_user) {
            return $this->_user;
        }

        return $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
    }

    /**
     * Sets the submission's user.
     */
    public function setUser(User $user): void
    {
        $this->_user = $user;
        $this->userId = $user->id;
    }

    /**
     * Returns any payments integration HTML, shown when editing a submission in the control panel.
     *
     * @return array
     */
    public function getPaymentSummaryHtml(): ?Markup
    {
        $html = '';

        if ($fieldLayout = $this->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field instanceof Payment && ($paymentIntegration = $field->getPaymentIntegration())) {
                    // Set the payment field on the integration, for ease-of-use
                    $paymentIntegration->setField($field);

                    if ($summaryHtml = $paymentIntegration->getSubmissionSummaryHtml($this, $field)) {
                        $html .= $summaryHtml;
                    }
                }
            }
        }

        if (!$html) {
            return null;
        }

        return Template::raw($html);
    }

    /**
     * Returns any payments made for the submission.
     *
     * @return array
     */
    public function getPayments(): ?array
    {
        return Formie::$plugin->getPayments()->getSubmissionPayments($this);
    }

    /**
     * Returns any subscriptions made for the submission.
     *
     * @return array
     */
    public function getSubscriptions(): ?array
    {
        return Formie::$plugin->getSubscriptions()->getSubmissionSubscriptions($this);
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        // A little extra work here to handle visibly disabled fields
        if ($form = $this->getForm()) {
            $disabledValues = $form->getPopulatedFieldValuesFromRequest();

            if ($disabledValues && is_array($disabledValues)) {
                foreach ($disabledValues as $key => $value) {
                    try {
                        // Special handling for group/repeater which would be otherwise pretty verbose to supply
                        $value = $form->getFieldByHandle($key)->parsePopulatedFieldValues($value, $this);

                        $this->setFieldValue($key, $value);
                    } catch (Throwable) {
                        continue;
                    }
                }
            }
        }

        parent::setFieldValuesFromRequest($paramNamespace);

        // Any conditionally hidden fields should have their content excluded when saving.
        // But - only for incomplete forms. Not a great idea to remove content for completed forms.
        if ($this->getFieldLayout() && $this->isIncomplete) {
            foreach ($this->getFieldLayout()->getCustomFields() as $field) {
                if ($field->isConditionallyHidden($this)) {
                    // Reset the field value - watch out for some fields
                    if ($field instanceof NestedFieldInterface) {
                        $this->setFieldValue($field->handle, []);
                    } else {
                        $this->setFieldValue($field->handle, null);
                    }
                }
            }
        }

        // If the final page, populate any visibly disabled fields with empty values with their default
        if ($this->getFieldLayout() && !$this->isIncomplete) {
            foreach ($this->getFieldLayout()->getCustomFields() as $field) {
                if ($field->visibility === 'disabled') {
                    $value = $this->getFieldValue($field->handle);

                    if ($field->isEmpty($value)) {
                        $this->setFieldValue($field->handle, $field->defaultValue);
                    }
                }
            }
        }
    }

    /**
     * Returns all field values.
     *
     * @param FieldLayoutPage|null
     * @return array
     * @throws InvalidConfigException
     */
    public function getValues($page): array
    {
        $values = [];

        $form = $this->getForm();

        if ($form) {
            $fields = $page ? $page->getCustomFields() : $form->getCustomFields();

            foreach ($fields as $field) {
                $values[$field->handle] = $field->getValue($this);
            }
        }

        return $values;
    }

    public function getValuesAsString(): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($field->getIsCosmetic()) {
                continue;
            }

            $value = $this->getFieldValue($field->handle);
            $values[$field->handle] = $field->getValueAsString($value, $this);
        }

        return $values;
    }

    public function getValuesAsJson(): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($field->getIsCosmetic()) {
                continue;
            }

            $value = $this->getFieldValue($field->handle);
            $values[$field->handle] = $field->getValueAsJson($value, $this);
        }

        return $values;
    }

    public function getValuesForExport(): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($field->getIsCosmetic()) {
                continue;
            }

            $value = $this->getFieldValue($field->handle);
            $valueForExport = $field->getValueForExport($value, $this);

            // If an array, we merge it in. This is because some fields provide content
            // for multiple "columns" in the export, expressed through `field_subhandle`.
            if (is_array($valueForExport)) {
                $values = array_merge($values, $valueForExport);
            } else {
                $values[$field->getExportLabel($this)] = $valueForExport;
            }
        }

        return $values;
    }

    public function getValuesForSummary(): array
    {
        $items = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($this)) {
                continue;
            }

            $value = $this->getFieldValue($field->handle);
            $html = $field->getValueForSummary($value, $this);

            $items[] = [
                'field' => $field,
                'value' => $value,
                'html' => Template::raw($html),
            ];
        }

        return $items;
    }

    public function getFieldPages(): array
    {
        if ($this->_pagesForField) {
            return $this->_pagesForField;
        }

        $this->_pagesForField = [];

        if ($fieldLayout = $this->getForm()->getFormFieldLayout()) {
            foreach ($fieldLayout->getPages() as $page) {
                foreach ($page->getCustomFields() as $field) {
                    $this->_pagesForField[$field->handle] = $page;
                }
            }
        }

        return $this->_pagesForField;
    }

    public function getRelations(): array
    {
        return Formie::$plugin->getRelations()->getRelations($this);
    }

    /**
     * @inheritdoc
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getForm());
    }

    /**
     * @inheritdoc
     */
    public function getSpamCaptcha(): ?Captcha
    {
        if ($this->spamClass) {
            $captchas = Formie::$plugin->getIntegrations()->getAllCaptchas();

            foreach ($captchas as $captcha) {
                if ($captcha instanceof $this->spamClass) {
                    return $captcha;
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        // Check if this is a spam submission and if we should save it
        // Only trigger this for site requests though
        if ($this->isSpam && $request->getIsSiteRequest()) {
            // Always log spam submissions
            Formie::$plugin->getSubmissions()->logSpam($this);

            // Fire an 'beforeMarkedAsSpam' event
            $event = new SubmissionMarkedAsSpamEvent([
                'submission' => $this,
                'isNew' => $isNew,
                'isValid' => false,
            ]);
            $this->trigger(self::EVENT_BEFORE_MARKED_AS_SPAM, $event);

            if (!$event->isValid) {
                // Check if we should be saving spam. We actually want to return as if
                // there's an error if we don't want to save the element
                if (!$settings->shouldSaveSpam($this)) {
                    return false;
                }
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew): void
    {
        // Get the node record
        if (!$isNew) {
            $record = SubmissionRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid notification ID: ' . $this->id);
            }
        } else {
            $record = new SubmissionRecord();
            $record->id = $this->id;
        }

        $record->title = $this->title;
        $record->formId = $this->formId;
        $record->statusId = $this->statusId;
        $record->userId = $this->userId;
        $record->isIncomplete = $this->isIncomplete;
        $record->isSpam = $this->isSpam;
        $record->spamReason = $this->spamReason;
        $record->spamClass = $this->spamClass;
        $record->snapshot = $this->snapshot;
        $record->ipAddress = $this->ipAddress;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

        $record->save(false);

        // Check to see if we need to save any relations
        Formie::$plugin->getRelations()->saveRelations($this);

        parent::afterSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete(): bool
    {
        $form = $this->getForm();

        if (!Craft::$app->getRequest()->getIsConsoleRequest() && !Craft::$app->getResponse()->isSent) {
            if ($form && ($submission = $form->getCurrentSubmission()) && $submission->id == $this->id) {
                $form->resetCurrentSubmission();
            }
        }

        // Check if we should hard-delete any file uploads - note once an asset is soft-deleted
        // it's file is hard-deleted gone, so we cannot restore a file upload. I'm aware of `keepFileOnDelete`, but there's
        // no way to remove that file on hard-delete, so that won't work.
        // See https://github.com/craftcms/cms/issues/5074
        if ($form && $form->fileUploadsAction === 'delete') {
            foreach ($form->getCustomFields() as $field) {
                if ($field instanceof FileUpload) {
                    // Store them now while we still have access to them, to delete in `afterDelete()`
                    $this->_assetsToDelete = array_merge($this->_assetsToDelete, $this->getFieldValue($field->handle)->all());
                }
            }
        }

        return parent::beforeDelete();
    }

    /**
     * @inheritDoc
     */
    public function afterDelete(): void
    {
        $elementsService = Craft::$app->getElements();

        // Check if we have any assets to delete
        if ($this->_assetsToDelete) {
            foreach ($this->_assetsToDelete as $asset) {
                if (!$elementsService->deleteElement($asset)) {
                    Formie::error("Unable to delete file ”{$asset->id}” for submission ”{$this->id}”: " . Json::encode($asset->getErrors()) . ".");
                }
            }
        }

        parent::beforeDelete();
    }


    // Protected methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Find and override the `SiteIdValidator` from the base element rules, to allow creation for disabled sites
        // This is otherwise only enabled during element propagation, which doesn't happen for submissions.
        foreach ($rules as $key => $rule) {
            [$attribute, $validator] = $rule;

            if ($validator === SiteIdValidator::class) {
                $rules[$key]['allowDisabled'] = true;
            }
        }

        $rules[] = [['title'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['formId'], 'number', 'integerOnly' => true];

        // Fire a 'defineSubmissionRules' event
        $event = new SubmissionRulesEvent([
            'rules' => $rules,
            'submission' => $this,
        ]);
        $this->trigger(self::EVENT_DEFINE_RULES, $event);

        return $event->rules;
    }


    /**
     * @inheritDoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'form':
                $form = $this->getForm();

                return $form->title ?? '';
            case 'userId':
                $user = $this->getUser();
                return $user ? Cp::elementHtml($user) : '';
            case 'status':
                $status = $this->getStatusModel(true);
                
                return Html::tag('span', Html::tag('span', '', [
                        'class' => array_filter([
                            'status',
                            $status->handle ?? null,
                            $status->color ?? null,
                        ]),
                    ]) . ($status->name ?? null), [
                    'style' => [
                        'display' => 'flex',
                        'align-items' => 'center',
                    ],
                ]);
            case 'sendNotification':
                if (($form = $this->getForm()) && $form->getNotifications()) {
                    return Html::a(Craft::t('formie', 'Send'), '#', [
                        'class' => 'btn small formsubmit js-fui-submission-modal-send-btn',
                        'data-id' => $this->id,
                        'title' => Craft::t('formie', 'Send'),
                    ]);
                }

                return '';
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}

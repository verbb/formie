<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\CosmeticField;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\FieldTrait;
use verbb\formie\base\FieldValueInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\MultiNestedFieldInterface;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\conditions\SubmissionCondition;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayout as FormLayout;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\records\Submission as SubmissionRecord;
use verbb\formie\web\assets\cp\CpAsset;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\Model;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
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

use ReflectionClass;
use Throwable;

use Twig\Markup;

class Submission extends CustomElement
{
    // Constants
    // =========================================================================

    public const EVENT_DEFINE_RULES = 'defineSubmissionRules';
    public const EVENT_BEFORE_MARKED_AS_SPAM = 'beforeMarkedAsSpam';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Submission');
    }

    public static function refHandle(): ?string
    {
        return 'submission';
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(SubmissionCondition::class, [static::class]);
    }

    public static function find(): SubmissionQuery
    {
        return new SubmissionQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Submission';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['formieSubmissions.' . $context->uid];
    }

    public static function gqlMutationNameByContext(mixed $context): string
    {
        return 'save_' . $context->handle . '_Submission';
    }

    public static function statuses(): array
    {
        return Formie::$plugin->getStatuses()->getStatusesArray();
    }

    protected static function defineSources(string $context = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $forms = Form::find()->all();

        $sources = [];

        if ($currentUser->can('formie-viewSubmissions')) {
            $sources[] = [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'defaultSort' => ['elements_sites.title', 'desc'],
            ];
        }

        $formItems = [];

        foreach ($forms as $form) {
            if (!$currentUser->can('formie-viewSubmissions') && !$currentUser->can("formie-viewSubmissions:{$form->uid}")) {
                continue;
            }

            /* @var Form $form */
            $key = "form:{$form->id}";

            $formItems[$key] = [
                'key' => $key,
                'label' => $form->title,
                'data' => [
                    'handle' => $form->handle,
                ],
                'criteria' => ['formId' => $form->id],
                'defaultSort' => ['elements_sites.title', 'desc'],
            ];
        }

        if ($formItems) {
            $sources[] = ['heading' => Craft::t('formie', 'Forms')];

            $sources += $formItems;
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        // Get the UID from the ID (for the source)
        $formId = (int)str_replace('form:', '', $source);
        $formUid = Formie::$plugin->getForms()->getFormById($formId)?->uid ?? null;

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canSaveSubmissions = $currentUser->can('formie-saveSubmissions') || $currentUser->can("formie-saveSubmissions:$formUid");
        $canDeleteSubmissions = $currentUser->can('formie-deleteSubmissions') || $currentUser->can("formie-deleteSubmissions:$formUid");

        if ($canSaveSubmissions) {
            $actions[] = $elementsService->createAction([
                'type' => SetSubmissionStatus::class,
                'statuses' => Formie::$plugin->getStatuses()->getAllStatuses(),
            ]);

            $actions[] = $elementsService->createAction([
                'type' => SetSubmissionSpam::class,
            ]);
        }

        if ($canDeleteSubmissions) {
            $actions[] = $elementsService->createAction([
                'type' => Delete::class,
                'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected submissions?'),
                'successMessage' => Craft::t('formie', 'Submissions deleted.'),
            ]);
        }

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Submissions restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some submissions restored.'),
            'failMessage' => Craft::t('formie', 'Submissions not restored.'),
        ]);

        return $actions;
    }

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
            'paymentStatus' => ['label' => Craft::t('formie', 'Payment Status')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

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

    protected static function defineSearchableElementAttributes(): array
    {
        return ['title'];
    }

    protected static function defineSearchableAttributes(): array
    {
        $cosmeticFieldTypes = Formie::$plugin->getFields()->getFieldsByType(CosmeticField::class);

        $fieldIds = (new Query())
            ->select(['id'])
            ->from(TABLE::FORMIE_FIELDS)
            ->where(['not in', 'type', $cosmeticFieldTypes])
            ->column();

        $fieldHandles = [];

        foreach ($fieldIds as $fieldId) {
            $fieldHandles[] = "field:$fieldId";
        }

        // Due to this being a static function, we don't have access to just _this_ submission's fields
        // So collect them all system-wide here, and we filter later in `searchKeywords()`.
        return array_merge(static::defineSearchableElementAttributes(), $fieldHandles);
    }

    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Title'),
                'orderBy' => 'elements_sites.title',
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
    public bool $isNewSubmission = false;

    private ?Form $_form = null;
    private ?Status $_status = null;
    private ?User $_user = null;
    private ?FormLayout $_formLayout = null;
    private ?string $_fieldContext = null;
    private ?array $_pagesForField = null;
    private ?array $_assetsToDelete = [];
    private bool $_previousIsSpam = false;
    private ?int $_previousStatusId = null;
    private array $_captchaData = [];


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->title;
    }
    
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        if ($user->can('formie-viewSubmissions')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            // Viewing without a form is fine, in case the form's been deleted
            return true;
        }

        if (!$user->can("formie-viewSubmissions:$form->uid")) {
            return false;
        }

        return true;
    }
    
    public function canSave(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        // Front-end requests don't require permissions here, they're in the controller
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            // But, if we're not editing an existing submission, disallow creation from the front-end
            if (!$this->id) {
                return false;
            }

            return true;
        }

        if ($user->can('formie-saveSubmissions')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            return false;
        }

        if (!$user->can("formie-saveSubmissions:$form->uid")) {
            return false;
        }

        return true;
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        if ($user->can('formie-deleteSubmissions')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            return false;
        }

        if (!$user->can("formie-deleteSubmissions:$form->uid")) {
            return false;
        }

        return true;
    }

    public function getActionMenuItems(): array
    {
        $actions = parent::getActionMenuItems();

        // Remove some actions Craft adds by default
        foreach ($actions as $key => $action) {
            if (str_starts_with($action['id'] ?? '', 'action-edit-')) {
                unset($actions[$key]);
            }
        }

        return array_values($actions);
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $processFields = function ($fields) use (&$processFields, &$labels) {
            foreach ($fields as $field) {
                $labels[$field->fieldKey] = $field->label;

                // Allow fields to modify the attribute labels
                $field->modifyAttributeLabels($labels);

                if ($field instanceof NestedFieldInterface) {
                    $processFields($field->getFields());
                }
            }
        };

        $processFields($this->getFields());

        return $labels;
    }

    public function getStatus(): ?string
    {
        return $this->getStatusModel()->handle ?? null;
    }

    public static function defineElementChipHtml(\craft\events\DefineElementHtmlEvent $event): void
    {
        $element = $event->element;

        if (!($element instanceof self)) {
            return;
        }

        // Remove the quick-edit ability
        $event->html = str_replace('data-editable', '', $event->html);

        $icon = null;
        $label = null;
        
        // Swap out the different icons for status/spam/etc
        if ($element->isIncomplete) {
            $icon = 'draft';
            $label = Craft::t('formie', 'Incomplete');
        } else if ($element->isSpam) {
            $icon = 'bug';
            $label = Craft::t('formie', 'Spam');
        }

        if ($icon && $label) {
            $iconStyle = [
                'width' => '10px',
                'height' => '10px',
                'margin-top' => '-12px',
                'margin-left' => '0',
                'font-size' => '12px',
                'margin-right' => '3px !important',
                'color' => 'color: #3f4d5a',
            ];

            $replacement = Html::tag('span', '', [
                'data' => ['icon' => $icon],
                'class' => 'icon',
                'role' => 'img',
                'style' => $iconStyle,
                'aria' => ['label' => Craft::t('app', 'Status:') . ' ' . $label],
            ]);

            $event->html = preg_replace(
                '#<span\b[^>]*\bclass\s*=\s*["\'][^"\']*\bstatus\b[^"\']*["\'][^>]*></span>#i',
                $replacement,
                $event->html
            );
        }
    }

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

        // Check whether the submission is either incomplete or "new" (the latter important for GQL)
        if (($this->isIncomplete || !$this->id) && $form && $form->settings->limitSubmissions) {
            if (!$form->isWithinSubmissionsLimit()) {
                $this->addError('form', Craft::t('formie', 'This form has met the number of allowed submissions.'));
            }
        }

        return $validates;
    }

    public function getSupportedSites(): array
    {
        // Only support the site the submission is being made on
        $siteId = $this->siteId ?: Craft::$app->getSites()->getPrimarySite()->id;

        return [$siteId];
    }

    public function getSidebarHtml(bool $static): string
    {
        // For when viewing a submission in a Submissions element select field
        Craft::$app->getView()->registerAssetBundle(CpAsset::class);

        return parent::getSidebarHtml($static);
    }

    public function getIsDraft(): bool
    {
        return $this->isIncomplete;
    }

    public function getFormLayout(): ?FormLayout
    {
        if (!$this->_formLayout && $form = $this->getForm()) {
            $this->_formLayout = $form->getFormLayout();
        }

        return $this->_formLayout;
    }

    public function getPages(): array
    {
        return $this->getFormLayout()?->getPages() ?? [];
    }

    public function getRows(bool $includeDisabled = true): array
    {
        return $this->getFormLayout()?->getRows($includeDisabled) ?? [];
    }

    public function getFields(): array
    {
        return $this->getFormLayout()?->getFields() ?? [];
    }

    public function getFieldLayout(): ?FieldLayout
    {
        // For compatibility with essential element services like search
        return $this->getFormLayout()?->getFieldLayout() ?? null;
    }

    public function getCustomFields(): array
    {
        // Backward compatibility
        return $this->getFields();
    }

    public function getFieldValue(string $fieldHandle): mixed
    {
        // Add support for dot-notation lookup for field values
        $fieldKey = explode('.', $fieldHandle);
        $handle = array_shift($fieldKey);
        $fieldKey = implode('.', $fieldKey);

        $fieldValue = parent::getFieldValue($handle);

        if ($fieldKey) {
            if (is_array($fieldValue) || $fieldValue instanceof Model || $fieldValue instanceof FieldValueInterface) {
                try {
                    return ArrayHelper::getValue($fieldValue, $fieldKey);
                } catch (Throwable $e) {
                    // Just in case there's an issue with getting that value
                    // (So far, only an issue with Date Dropdown + Repeater due to DateTime limitation handling)
                }
            }
        }

        return $fieldValue;
    }

    public function getFieldValuesForField(string $type): array
    {
        $fieldValues = [];

        // Return all values for a field for a given type. Includes nested fields like Group/Repeater.
        foreach ($this->getFields() as $field) {
            if ($field instanceof $type) {
                $fieldValues[$field->handle] = $this->getFieldValue($field->handle);
            }

            if ($field instanceof SingleNestedFieldInterface) {
                foreach ($field->getFields() as $nestedField) {
                    if ($nestedField instanceof $type) {
                        $fieldKey = "$field->handle.$nestedField->handle";

                        $fieldValues[$fieldKey] = $this->getFieldValue($fieldKey);
                    }
                }
            }

            if ($field instanceof MultiNestedFieldInterface) {
                $value = $this->getFieldValue($field->handle);

                foreach ($value as $rowKey => $row) {
                    foreach ($field->getFields() as $nestedField) {
                        if ($nestedField instanceof $type) {
                            $fieldKey = "$field->handle.$rowKey.$nestedField->handle";

                            $fieldValues[$fieldKey] = $this->getFieldValue($fieldKey);
                        }
                    }
                }
            }
        }

        return $fieldValues;
    }

    public function setCaptchaData(string $key, mixed $value): void
    {
        $this->_captchaData[$key] = $value;
    }

    public function getCaptchaData(string $key): mixed
    {
        return $this->_captchaData[$key] ?? null;
    }

    public function updateTitle(Form $form): void
    {
        if ($customTitle = Variables::getParsedValue($form->settings->submissionTitleFormat, $this, $form)) {
            // In case any values are encoded for HTML, we should decode them here. This is after sanitization
            $this->title = html_entity_decode($customTitle);

            // Rather than re-save, directly update the content record
            Db::update(Table::ELEMENTS_SITES, ['title' => $this->title], ['elementId' => $this->id, 'siteId' => $this->siteId]);
        }
    }

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

    public function setForm(Form $form): void
    {
        $this->_form = $form;
        $this->formId = $form->id;

        // When setting the form, see if there's an in-session snapshot, or if there's a saved
        // snapshot from the database. This will be field settings set via templates which we want
        // to apply to fields in our form, for this submission. Only do this for front-end checks
        // and if there's no already-saved snapshot data
        if (Craft::$app->getRequest()->getIsSiteRequest() && !$this->snapshot) {
            if ($snapshotData = $form->getSnapshotData()) {
                $this->snapshot = $snapshotData;
            }
        }

        $fields = $this->snapshot['fields'] ?? [];

        foreach ($fields as $handle => $settings) {
            $this->setFieldSettings($handle, $settings);
        }

        // Do the same for form settings
        $formSettings = $this->snapshot['form'] ?? null;

        if ($formSettings) {
            $this->_form->settings->setAttributes($formSettings, false);
        }
    }

    public function setFieldSettings(string $handle, array $settings): void
    {
        $field = null;
        
        // Check for nested fields so we can use `group.dropdown` or `dropdown`.
        $handles = explode('.', $handle);

        if (count($handles) > 1) {
            $parentField = $this->getFieldByHandle($handles[0]);

            if ($parentField) {
                $field = $parentField->getFieldByHandle($handles[1]);
            }
        } else {
            $field = $this->getFieldByHandle($handles[0]);
        }

        if ($field) {
            $field->setAttributes($settings, false);
        }
    }

    public function getFormName(): ?string
    {
        if ($form = $this->getForm()) {
            return $form->title;
        }

        return null;
    }

    public function getFormHandle(): ?string
    {
        if ($form = $this->getForm()) {
            return $form->handle;
        }

        return null;
    }

    public function getSiteHandle(): ?string
    {
        if ($site = $this->getSite()) {
            return $site->handle;
        }

        return null;
    }

    public function getSiteName(): ?string
    {
        if ($site = $this->getSite()) {
            return $site->name;
        }

        return null;
    }

    public function getStatusModel(): Status
    {
        if (!$this->_status && $this->statusId) {
            $this->_status = Formie::$plugin->getStatuses()->getStatusById($this->statusId);
        }

        if ($this->_status) {
            return $this->_status;
        }

        // Get the default status from the form, if defined yet
        if ($form = $this->getForm()) {
            if ($this->_status = $form->getDefaultStatus()) {
                return $this->_status;
            }
        }

        // Get the global default status
        if ($this->_status = Formie::$plugin->getStatuses()->getDefaultStatus()) {
            return $this->_status;
        }

        // Get _any_ status
        $statuses = Formie::$plugin->getStatuses()->getAllStatuses();
        $status = reset($statuses) ?: null;

        if ($status) {
            return $this->_status = $status;
        }

        // Create a dummy status
        return $this->_status = new Status([
            'name' => 'New',
            'handle' => 'new',
            'color' => 'green',
            'sortOrder' => 1,
            'isDefault' => 1,
        ]);;
    }

    public function setStatus(Status|string $status): void
    {
        if (is_string($status)) {
            if ($foundStatus = Formie::$plugin->getStatuses()->getStatusByHandle($status)) {
                $status = $foundStatus;
            }
        }
        
        $this->_status = $status;
        $this->statusId = $status->id;
    }

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

    public function setUser(User $user): void
    {
        $this->_user = $user;
        $this->userId = $user->id;
    }

    public function getPaymentSummaryHtml(): ?Markup
    {
        $html = '';

        foreach ($this->getFields() as $field) {
            if ($field instanceof Payment && ($paymentIntegration = $field->getPaymentIntegration())) {
                // Ensure that the field matches the integration details for multi-payment field forms
                if (!$paymentIntegration->field || $paymentIntegration->field->id !== $field->id) {
                    continue;
                }

                if ($summaryHtml = $paymentIntegration->getSubmissionSummaryHtml($this, $field)) {
                    $html .= $summaryHtml;
                }
            }
        }

        if (!$html) {
            return null;
        }

        return Template::raw($html);
    }

    public function getPayments(): ?array
    {
        return Formie::$plugin->getPayments()->getSubmissionPayments($this);
    }

    public function getSubscriptions(): ?array
    {
        return Formie::$plugin->getSubscriptions()->getSubmissionSubscriptions($this);
    }

    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        // A little extra work here to handle visibly disabled fields
        if ($form = $this->getForm()) {
            $disabledValues = $form->getPopulatedFieldValuesFromRequest();

            if ($disabledValues && is_array($disabledValues)) {
                foreach ($disabledValues as $key => $value) {
                    try {
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
        if ($this->isIncomplete) {
            foreach ($this->getFields() as $field) {
                if ($field->isConditionallyHidden($this)) {
                    // Reset the field value
                    $this->setFieldValue($field->handle, null);
                }
            }
        }

        // If the final page, populate any visibly disabled fields with empty values with their default
        if (!$this->isIncomplete) {
            foreach ($this->getFields() as $field) {
                if ($field->visibility === 'disabled') {
                    $value = $this->getFieldValue($field->handle);

                    if ($field->isValueEmpty($value, $this)) {
                        $this->setFieldValue($field->handle, $field->getDefaultValue());
                    }
                }
            }
        }
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Check if we only want to set the fields for the current page. This helps with large
        // forms with lots of Repeater/Group fields not on the current page being saved.
        if ($settings->setOnlyCurrentPagePayload) {
            $currentPageFields = $this->getForm()->getCurrentPage()->getFields();
            $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

            if (!in_array($fieldHandle, $currentPageFieldHandles)) {
                return;
            }
        }

        parent::setFieldValueFromRequest($fieldHandle, $value);
    }

    public function getValues($page): array
    {
        $values = [];

        $form = $this->getForm();

        if ($form) {
            $fields = $page ? $page->getFields() : $form->getFields();

            foreach ($fields as $field) {
                $values[$field->handle] = $field->getValue($this);
            }
        }

        return $values;
    }

    public function getValueAsString(string $fieldHandle): mixed
    {
        if ($field = $this->getFieldByHandle($fieldHandle)) {
            $value = $this->getFieldValue($field->fieldKey);

            return $field->getValueAsString($value, $this);
        }

        return null;
    }

    public function getValueAsJson(string $fieldHandle): mixed
    {
        if ($field = $this->getFieldByHandle($fieldHandle)) {
            $value = $this->getFieldValue($field->fieldKey);

            return $field->getValueAsJson($value, $this);
        }

        return null;
    }

    public function getValueForExport(string $fieldHandle): mixed
    {
        if ($field = $this->getFieldByHandle($fieldHandle)) {
            $value = $this->getFieldValue($field->fieldKey);

            return $field->getValueForExport($value, $this);
        }

        return null;
    }

    public function getValueForSummary(string $fieldHandle): mixed
    {
        if ($field = $this->getFieldByHandle($fieldHandle)) {
            $value = $this->getFieldValue($field->fieldKey);

            return $field->getValueForSummary($value, $this);
        }

        return null;
    }

    public function getValuesAsString(): array
    {
        $values = [];

        foreach ($this->getFields() as $field) {
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

        foreach ($this->getFields() as $field) {
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

        foreach ($this->getFields() as $field) {
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

        foreach ($this->getFields() as $field) {
            if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($this)) {
                continue;
            }

            $value = $this->getFieldValue($field->handle);
            $html = $field->getValueForSummary($value, $this);

            // Just in case some fields want to opt-out
            if ($html === false || $html === null) {
                continue;
            }

            $items[] = [
                'field' => $field,
                'value' => $value,
                'html' => Template::raw($html),
            ];
        }

        return $items;
    }

    public function getRelations(): array
    {
        return Formie::$plugin->getRelations()->getRelations($this);
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getForm());
    }

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

    public function getHtmlAttributes(string $context): array
    {
        $attributes = parent::getHtmlAttributes($context);
        $attributes['data-date-created'] = $this->dateCreated->format('Y-m-d\TH:i:s.u\Z');

        return $attributes;
    }

    public function hasStatusChanged(): bool
    {
        return $this->_previousStatusId !== $this->statusId;
    }

    public function hasSpamChanged(?bool $previousState = null, ?bool $currentState = null): bool
    {
        // We want to check if we've marked this as not-spam, when it was spam
        if ($previousState !== null && $currentState !== null) {
            return $this->_previousIsSpam === $previousState && $this->isSpam === $currentState;
        }

        // Otherwise, just if it was different
        return $this->_previousIsSpam !== $this->isSpam;
    }

    public function hasSearchIndexAttribute(string $attribute): bool
    {
        if (in_array($attribute, static::defineSearchableElementAttributes(), true)) {
            return true;
        }

        return (bool)$this->getFieldBySearchIndex($attribute);
    }

    public function beforeValidate(): bool
    {
        // Some captchas need to fire early as they act like a field to prevent submission
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($this->getForm());

        foreach ($captchas as $captcha) {
            if ($captcha->hasStrictValidation()) {
                $captcha->validateSubmission($this);
            }
        }

        return parent::beforeValidate();
    }

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

        // Save the current status and spam state before saving so we can compare
        if ($this->id) {
            $previousSettings = (new Query())
                ->select(['statusId', 'isSpam'])
                ->from([Table::FORMIE_SUBMISSIONS])
                ->where(['id' => $this->id])
                ->one();

            $this->_previousStatusId = $previousSettings['statusId'] ?? null;
            $this->_previousIsSpam = (bool)($previousSettings['isSpam'] ?? false);
        }

        return parent::beforeSave($isNew);
    }

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

        $record->content = $this->serializeFieldValues();
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

        // Reset cache as we might be acting on statuses below
        $this->_status = null;

        // Check to see if we need to save any relations
        Formie::$plugin->getRelations()->saveRelations($this);

        // If the status has changed, fire any applicable email notifications. 
        // Also check for `isNewSubmission` to see whether we're submitting something new, or just resaving.
        if (!$this->isNewSubmission && $this->hasStatusChanged()) {
            // Only send notifications that match a status-change condition
            $form = $this->getForm();
            $notifications = $form->getEnabledNotifications();

            foreach ($notifications as $notification) {
                if ($status = $notification->getStatusCondition($this)) {
                    if ($status === $this->getStatus()) {
                        Formie::$plugin->getSubmissions()->sendNotification($notification, $this);
                    }
                }
            }
        }

        parent::afterSave($isNew);
    }

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
            foreach ($this->getFieldValuesForField(FileUpload::class) as $value) {
                $this->_assetsToDelete = array_merge($this->_assetsToDelete, $value->all());
            }
        }

        return parent::beforeDelete();
    }

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

    public function afterValidate(): void
    {
        // Lift from `craft\base\Element::afterValidate()` all so we can modify the `RequiredValidator` message
        // for our custom error message. Might ask the Craft crew if there's a better way to access private methods
        if (Craft::$app->getIsInstalled() && $formLayout = $this->getFormLayout()) {
            $scenario = $this->getScenario();
            $fields = $formLayout->getVisiblePageFields($this);

            foreach ($fields as $field) {
                $attribute = 'field:' . $field->getErrorKey();

                if ($field->getIsDisabled()) {
                    continue;
                }

                if (isset($this->_attributeNames) && !isset($this->_attributeNames[$attribute])) {
                    continue;
                }

                $isEmpty = fn() => $field->isValueEmpty($this->getFieldValue($field->handle), $this);

                // Add the required validator but with our custom message
                if ($scenario === self::SCENARIO_LIVE && $field->required) {
                    (new RequiredValidator(['isEmpty' => $isEmpty, 'message' => $field->errorMessage]))
                        ->validateAttribute($this, $attribute);
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    $validator = $this->_callPrivateMethod('_normalizeFieldValidator', $attribute, $rule, $field, $isEmpty);
                    if (
                        in_array($scenario, $validator->on) ||
                        (empty($validator->on) && !in_array($scenario, $validator->except))
                    ) {
                        $validator->validateAttributes($this);
                    }
                }
            }
        }

        // Bubble up past the `Element::afterValidate()` to prevent this happening twice
        Component::afterValidate();
    }


    // Protected Methods
    // =========================================================================

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

        // Required for typecasting the JSON column
        // https://github.com/yiisoft/yii2/issues/15839
        $rules[] = [['content'], 'safe'];

        // Fire a 'defineSubmissionRules' event
        $event = new SubmissionRulesEvent([
            'rules' => $rules,
            'submission' => $this,
        ]);
        $this->trigger(self::EVENT_DEFINE_RULES, $event);

        return $event->rules;
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute == 'form') {
            $form = $this->getForm();

            return $form->title ?? '';
        } 

        if ($attribute == 'userId') {
            $user = $this->getUser();
            
            return $user ? Cp::elementChipHtml($user) : '';
        }

        if ($attribute == 'status') {
            $status = $this->getStatusModel();

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
        }

        if ($attribute == 'paymentStatus') {
            if ($payments = $this->getPayments()) {
                $lastPayment = end($payments);

                $color = $lastPayment->status;

                if ($color === 'success') {
                    $color = 'live';
                }

                return Html::tag('span', Html::tag('span', '', [
                        'class' => ['status', $color],
                    ]) . StringHelper::toTitleCase($lastPayment->status), [
                    'style' => [
                        'display' => 'flex',
                        'align-items' => 'center',
                    ],
                ]);
            }

            return '';
        }

        if ($attribute == 'sendNotification') {
            if (($form = $this->getForm()) && $form->getNotifications()) {
                return Html::a(Craft::t('formie', 'Send'), '#', [
                    'class' => 'btn small formsubmit js-fui-submission-modal-send-btn',
                    'data-id' => $this->id,
                    'title' => Craft::t('formie', 'Send'),
                ]);
            }

            return '';
        }

        return parent::attributeHtml($attribute);
    }

    protected function cpEditUrl(): ?string
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

    protected function searchKeywords(string $attribute): string
    {
        if (in_array($attribute, static::defineSearchableElementAttributes(), true)) {
            return parent::searchKeywords($attribute);
        }

        if ($field = $this->getFieldBySearchIndex($attribute)) {
            $fieldValue = $this->getFieldValue($field->handle);

            return $field->getSearchKeywords($fieldValue, $this);
        }

        return '';
    }

    protected function inlineAttributeInputHtml(string $attribute): string
    {
        $field = null;

        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            try {
                $fieldHandle = $matches[1];
                $field = $this->getFieldByHandle($fieldHandle);
            } catch (Throwable $e) {
                // Ignore any fields that don't belong to this element
            }
        }

        if ($field !== null) {
            if ($field instanceof InlineEditableFieldInterface) {
                try {
                    $value = $this->getFieldValue($field->handle);
                } catch (Throwable $e) {
                    return '';
                }

                return $field->getInlineInputHtml($value, $this);
            }

            return $this->getAttributeHtml($attribute);
        }

        return $this->attributeHtml($attribute);
    }


    // Private Methods
    // =========================================================================

    private function _callPrivateMethod(string $methodName): mixed
    {
        // Required to be able to call private methods in this class for `afterValidate()`.
        $object = $this;
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        $params = array_slice(func_get_args(), 1);
        return $reflectionMethod->invokeArgs($object, $params);
    }
}

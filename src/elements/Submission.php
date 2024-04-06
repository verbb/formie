<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\FieldTrait;
use verbb\formie\base\MultiNestedFieldInterface;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\ArrayHelper;
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
use craft\base\Model;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
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
                'defaultSort' => ['formie_submissions.title', 'desc'],
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
                'defaultSort' => ['formie_submissions.title', 'desc'],
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

    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

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

    public function getStatus(): ?string
    {
        return $this->getStatusModel(true)->handle ?? null;
    }

    public static function defineElementChipHtml(\craft\events\DefineElementHtmlEvent $event): void
    {
        $element = $event->element;

        if (!($element instanceof self)) {
            return;
        }

        $elementsService = Craft::$app->getElements();
        $user = Craft::$app->getUser()->getIdentity();
        $editable = $user && $elementsService->canView($element, $user);

        $id = sprintf('chip-%s', mt_rand());

        $attributes = array_merge($element->getHtmlAttributes($event->context), [
            'class' => ['element', 'chip', 'small'],
            'title' => $element->getUiLabel(),
            'id' => $id,
            'data' => [
                'type' => get_class($element),
                'id' => $element->id,
                'draft-id' => $element->draftId,
                'revision-id' => $element->revisionId,
                'site-id' => $element->siteId,
                'status' => $element->getStatus(),
                'label' => (string)$element,
                'url' => $element->getUrl(),
                'cp-url' => $editable ? $element->getCpEditUrl() : null,
                'level' => $element->level,
                'trashed' => $element->trashed,
                'editable' => $editable,
                'savable' => $editable && $elementsService->canSave($element),
                'duplicatable' => $editable && $elementsService->canDuplicate($element),
                'deletable' => $editable && $elementsService->canDelete($element),

                'settings' => [
                    'selectable' => false,
                    'context' => $event->context,
                    'id' => Craft::$app->getView()->namespaceInputId($id),
                    'showDraftName' => true,
                    'showLabel' => true,
                    'showStatus' => true,
                    'showThumb' => false,
                    'size' => 'small',
                    'ui' => 'chip',
                ],
            ],
        ]);

        $html = Html::beginTag('div', $attributes);
        $html .= Html::beginTag('div', ['class' => 'chip-content']);

        if ($element->isIncomplete) {
            $iconStyle = [
                'width' => '10px',
                'height' => '10px',
                'margin-top' => '-12px',
                'margin-left' => '0',
                'font-size' => '12px',
                'margin-right' => '3px !important',
                'color' => 'color: #3f4d5a',
            ];

            $html .= Html::tag('span', '', [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'style' => $iconStyle,
                'aria' => [
                    'label' => sprintf('%s %s', Craft::t('app', 'Status:'), Craft::t('formie', 'Incomplete')),
                ],
            ]);
        } else if ($element->isSpam) {
            $iconStyle = [
                'width' => '10px',
                'height' => '10px',
                'margin-top' => '-12px',
                'margin-left' => '0',
                'font-size' => '12px',
                'margin-right' => '3px !important',
                'color' => 'color: #3f4d5a',
            ];

            $html .= Html::tag('span', '', [
                'data' => ['icon' => 'bug'],
                'class' => 'icon',
                'role' => 'img',
                'style' => $iconStyle,
                'aria' => [
                    'label' => sprintf('%s %s', Craft::t('app', 'Status:'), Craft::t('formie', 'Spam')),
                ],
            ]);
        } else {
            $status = $element->getStatus();
            $statusAttributes = $element::statuses()[$status] ?? null;

            // Just to give the `statusIndicatorHtml` clean types
            if (is_string($statusAttributes)) {
                $statusAttributes = ['label' => $statusAttributes];
            }

            $html .= Html::tag('span', '', [
                'class' => array_filter([
                    'status',
                    $status,
                    $statusAttributes['color'] ?? null,
                ]),
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s', Craft::t('app', 'Status:'), $statusAttributes['label'] ?? ucfirst($status)),
                ],
            ]);
        }

        $html .= Html::beginTag('div', ['class' => 'label', 'id' => $id . '-label']);
        $html .= Html::tag('a', $element->getChipLabelHtml(), ['class' => 'label-link', 'href' => $element->getCpEditUrl()]);
        $html .= Html::endTag('div') . Html::endTag('div') . Html::endTag('div');

        $event->html = $html;
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
        // For compatibility with essential element services like search
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
            if (is_array($fieldValue) || $fieldValue instanceof Model) {
                return ArrayHelper::getValue($fieldValue, $fieldKey);
            }
        }

        return $fieldValue;
    }

    public function getFieldValuesForField(string $type): array
    {
        $fieldValues = [];

        // Return all values for a field for a given type. Includes nested fields like Group/Repeater.
        foreach ($this->getFields() as $field) {
            if (get_class($field) === $type) {
                $fieldValues[$field->handle] = $this->getFieldValue($field->handle);
            }

            if ($field instanceof SingleNestedFieldInterface) {
                foreach ($field->getFields() as $nestedField) {
                    if (get_class($nestedField) === $type) {
                        $fieldKey = "$field->handle.$nestedField->handle";

                        $fieldValues[$fieldKey] = $this->getFieldValue($fieldKey);
                    }
                }
            }

            if ($field instanceof MultiNestedFieldInterface) {
                $value = $this->getFieldValue($field->handle);

                foreach ($value as $rowKey => $row) {
                    foreach ($this->getFields() as $nestedField) {
                        if (get_class($nestedField) === $type) {
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
            $this->title = $customTitle;

            // Rather than re-save, directly update the content record
            Db::update(Table::ELEMENTS_SITES, ['title' => $customTitle], ['elementId' => $this->id, 'siteId' => $this->siteId]);
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
            $form->setFieldSettings($handle, $settings, false);
        }

        // Do the same for form settings
        $formSettings = $this->snapshot['form'] ?? null;

        if ($formSettings) {
            $form->settings->setAttributes($formSettings, false);
        }
    }

    public function getFormName(): ?string
    {
        if ($form = $this->getForm()) {
            return $form->title;
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

        if ($form = $this->getForm()) {
            return $this->_status = $form->getDefaultStatus();
        }

        return $this->_status = Formie::$plugin->getStatuses()->getDefaultStatus();
    }

    public function setStatus(Status $status): void
    {
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
                // Set the payment field on the integration, for ease-of-use
                $paymentIntegration->setField($field);

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

        // Check to see if we need to save any relations
        Formie::$plugin->getRelations()->saveRelations($this);

        // If the status has changed, fire any applicable email notifications
        if ($this->hasStatusChanged()) {
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
                $attribute = "field:$field->handle";

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

<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\content\SubmissionContentManager;
use verbb\formie\content\SubmissionContentState;
use verbb\formie\deprecations\SubmissionValueDeprecations;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\conditions\SubmissionCondition;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldAttributesHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\FieldLayout as FormLayout;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\models\ValueContext;
use verbb\formie\records\Submission as SubmissionRecord;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Table as CraftTable;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\events\DefineElementHtmlEvent;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\SiteIdValidator;

use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

use Throwable;

use Twig\Markup;

class Submission extends Element
{
    use SubmissionValueDeprecations;

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

    public static function defineElementChipHtml(DefineElementHtmlEvent $event): void
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

    protected static function defineSources(string $context = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $sitesService = Craft::$app->getSites();
        $currentSiteId = $sitesService->getCurrentSite()->id;
        $canViewAllSubmissions = $currentUser->can('formie-viewSubmissions');

        $formGroups = Formie::$plugin->getFormGroups()->getAllGroups();

        $cacheKey = implode(':', [
            (string)($currentUser->id ?? 0),
            (string)$currentSiteId,
            (string)$context,
            $canViewAllSubmissions ? '1' : '0',
            (string)count($formGroups),
        ]);

        if (isset(self::$_sourcesCache[$cacheKey])) {
            return self::$_sourcesCache[$cacheKey];
        }

        // Keep source construction lightweight by avoiding full Form element hydration.
        $formColumns = [
            'f.id',
            'f.uid',
            'f.handle',
            'es.title',
        ];

        if (Craft::$app->getDb()->columnExists(Table::FORMIE_FORMS, 'groupId')) {
            $formColumns[] = 'f.groupId';
        }

        $forms = (new Query())
            ->select($formColumns)
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => CraftTable::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->innerJoin(['es' => CraftTable::ELEMENTS_SITES], '[[es.elementId]] = [[f.id]] AND [[es.siteId]] = :siteId', [
                ':siteId' => $currentSiteId,
            ])
            ->where(['e.dateDeleted' => null])
            ->orderBy(['es.title' => SORT_ASC])
            ->all();

        $sources = [];

        if ($canViewAllSubmissions) {
            $sources[] = [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'defaultSort' => ['elements_sites.title', 'desc'],
            ];
        }

        $formItemsByGroupId = [];
        $ungroupedFormItems = [];

        foreach ($forms as $form) {
            $formUid = (string)($form['uid'] ?? '');

            if (!$canViewAllSubmissions && !$currentUser->can("formie-viewSubmissions:{$formUid}")) {
                continue;
            }

            $formId = (int)($form['id'] ?? 0);
            $formHandle = (string)($form['handle'] ?? '');
            $formTitle = (string)($form['title'] ?? $formHandle);
            $key = "form:{$formId}";

            $formItem = [
                'key' => $key,
                'label' => $formTitle,
                'data' => [
                    'handle' => $formHandle,
                ],
                'criteria' => ['formId' => $formId],
                'defaultSort' => ['elements_sites.title', 'desc'],
            ];

            $groupId = (int)($form['groupId'] ?? 0);

            if ($groupId) {
                $formItemsByGroupId[$groupId][$key] = $formItem;
            } else {
                $ungroupedFormItems[$key] = $formItem;
            }
        }

        if ($formGroups) {
            foreach ($formGroups as $group) {
                $groupFormItems = $formItemsByGroupId[$group->id] ?? [];

                if (!$groupFormItems) {
                    continue;
                }

                $sources[] = ['heading' => $group->name];
                $sources += $groupFormItems;
            }

            if ($ungroupedFormItems) {
                $sources[] = ['heading' => Craft::t('formie', 'Ungrouped')];
                $sources += $ungroupedFormItems;
            }
        } else {
            $formItems = [];

            foreach ($formItemsByGroupId as $groupFormItems) {
                $formItems += $groupFormItems;
            }

            $formItems += $ungroupedFormItems;

            if ($formItems) {
                $sources[] = ['heading' => Craft::t('formie', 'Forms')];
                $sources += $formItems;
            }
        }

        return self::$_sourcesCache[$cacheKey] = $sources;
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

    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
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
    public ?array $integrationDispatchContext = null;
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
    private ?array $_validationAttributeNames = null;
    private ?SubmissionContentManager $_contentManager = null;
    private ?SubmissionContentState $_contentState = null;
    private null|string|array $_deferredFieldContent = null;
    private bool $_hasDeferredFieldContent = false;
    private static array $_formByIdCache = [];
    private static array $_sourcesCache = [];
    private bool $_updateTitle = false;
    private bool $_snapshotSettingsApplied = false;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function __isset($name): bool
    {
        return parent::__isset($name) || $this->getFieldByHandle($name);
    }

    public function __get($name)
    {
        if ($this->getFieldByHandle($name) !== null) {
            return $this->getContentManager()->cloneValue($this, $name);
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (InvalidCallException|UnknownPropertyException $e) {
            if ($this->getFieldByHandle($name) !== null) {
                $this->setFieldValue($name, $value);
            } else {
                throw $e;
            }
        }
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        return $this->getContentManager()->getFieldByHandle($this, $handle);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return $this->getContentManager()->getFieldById($this, $id);
    }

    public function setFieldContent(null|string|array $content): void
    {
        // Defer heavy DB-content normalization until field data is actually requested.
        // This keeps submission index/table hydration fast when field values are unused.
        $this->_deferredFieldContent = $content;
        $this->_hasDeferredFieldContent = true;
        $this->_contentState = null;
    }

    public function getContentManager(): SubmissionContentManager
    {
        $manager = $this->_contentManager ??= new SubmissionContentManager();

        if ($this->_hasDeferredFieldContent) {
            // Clear deferred flags before normalization to prevent re-entrant recursion
            // via manager/accessor lookups during normalizeFromDb().
            $deferredContent = $this->_deferredFieldContent;
            $this->_hasDeferredFieldContent = false;
            $this->_deferredFieldContent = null;
            $manager->normalizeFromDb($this, $deferredContent);
        }

        return $manager;
    }

    public function getContentState(): SubmissionContentState
    {
        return $this->_contentState ??= new SubmissionContentState();
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
                $labels[$field->valueKey()] = $field->label;

                // Allow fields to modify the attribute labels
                $field->modifyAttributeLabels($labels);

                if ($field instanceof ParentFieldInterface) {
                    $processFields($field->getFields());
                }
            }
        };

        $processFields($this->getFields());

        return $labels;
    }

    public function getStatus(): ?string
    {
        return $this->getStatusModel(true)->handle ?? null;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $this->_validationAttributeNames = $attributeNames ? array_flip((array)$attributeNames) : null;

        try {
            $validates = parent::validate($attributeNames, $clearErrors);
        } finally {
            $this->_validationAttributeNames = null;
        }

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
        Formie::$plugin->registerCpSubmissionsAssets();

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

    public function setFieldValue(string $fieldKey, mixed $value): void
    {
        $this->getContentManager()->setRawValue($this, $fieldKey, $value);
    }

    public function serializeFieldValues(): array
    {
        return $this->getContentManager()->serializeForDb($this);
    }

    public function getFieldValue(string $fieldKey, mixed $context = null): mixed
    {
        return $this->getContentManager()->getFieldValue($this, $fieldKey, $context);
    }

    public function getFieldValuesForField(string $type): array
    {
        return $this->getContentManager()->getFieldValuesForField($this, $type);
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
        if ($customTitle = References::parseContent($form->settings->submissionTitleFormat, $this)) {
            // In case any values are encoded for HTML, we should decode them here. This is after sanitization
            $this->title = html_entity_decode($customTitle);

            // Rather than re-save, directly update the content record
            Db::update(Table::ELEMENTS_SITES, ['title' => $this->title], ['elementId' => $this->id, 'siteId' => $this->siteId]);
        }
    }

    public function setUpdateTitle(bool $updateTitle): void
    {
        $this->_updateTitle = $updateTitle;
    }

    public function getForm(): ?Form
    {
        if (!$this->_form && $this->formId) {
            if (array_key_exists($this->formId, self::$_formByIdCache)) {
                $this->_form = self::$_formByIdCache[$this->formId];
            } else {
                $query = Form::find()->id($this->formId);
                $this->_form = $query->one();
                self::$_formByIdCache[$this->formId] = $this->_form;
            }

            // If no form found yet, and the submission has been trashed, maybe the form has been trashed?
            if (!$this->_form && $this->trashed) {
                $query = Form::find()->id($this->formId)->trashed(true);
                $this->_form = $query->one();
                self::$_formByIdCache[$this->formId] = $this->_form;
            }

            $this->_applySnapshotSettingsIfNeeded();
        }

        return $this->_form;
    }

    public function setForm(Form $form): void
    {
        $this->_form = $form;
        $this->formId = $form->id;
        ($this->_contentManager ??= new SubmissionContentManager())->resetFieldCollection($this);
        $this->_snapshotSettingsApplied = false;

        $this->_applySnapshotSettingsIfNeeded();
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
            $settings = FieldAttributesHelper::applyToFieldSettings(
                $settings,
                $field->containerAttributes,
                $field->inputAttributes,
            );
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

        if ($form = $this->getForm()) {
            return $this->_status = $form->getDefaultStatus();
        }

        if ($status = Formie::$plugin->getStatuses()->getDefaultStatus()) {
            return $this->_status = $status;
        }

        // Just in case there's no default status set in settings, pick the first available
        return $this->_status = Formie::$plugin->getStatuses()->getAllStatuses()[0];
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
                if ($paymentIntegration->getField() && $paymentIntegration->getField()->id !== $field->id) {
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
        $this->getContentManager()->setFieldValuesFromRequest($this, $paramNamespace);
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $this->getContentManager()->setFieldValueFromRequest($this, $fieldHandle, $value);
    }

    public function getValues($page): array
    {
        return $this->getContentManager()->getValues($this, $page);
    }

    public function getFieldValueAsString(string $fieldKey): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::string());
    }

    public function getFieldValueAsArray(string $fieldKey): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::array());
    }

    public function getFieldValueForExport(string $fieldKey): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::export());
    }

    public function getFieldValueForSummary(string $fieldKey): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::summary());
    }

    public function getFieldValueForReference(string $fieldKey, mixed $notification = null): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::reference($notification));
    }

    public function getFieldValueForReferenceBlock(string $fieldKey, mixed $notification): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::referenceBlock($notification));
    }

    public function getFieldValueForIntegration(string $fieldKey, mixed $integrationField, mixed $integration, string $integrationFieldKey = ''): mixed
    {
        return $this->getFieldValue($fieldKey, ValueContext::integration($integrationField, $integration, $integrationFieldKey));
    }

    public function getFieldValueForCondition(string $fieldKey): mixed
    {
        // Conditions must always route through the same projection path as the
        // standalone evaluators so builder rules, Twig checks, and workflow
        // logic compare against one canonical representation.
        return $this->getFieldValue($fieldKey, ValueContext::condition());
    }

    public function getValuesAsString(): array
    {
        return $this->getContentManager()->getValuesAsString($this);
    }

    public function getValuesAsArray(): array
    {
        return $this->getContentManager()->getValuesAsArray($this);
    }

    public function getValuesForExport(): array
    {
        return $this->getContentManager()->getValuesForExport($this);
    }

    public function getValuesForSummary(): array
    {
        $items = $this->getContentManager()->getValuesForSummary($this);

        foreach ($items as &$item) {
            $summary = $item['html'] ?? null;

            $item['html'] = $summary instanceof Markup ? $summary : null;
            $item['text'] = $summary instanceof Markup ? null : (string)$summary;
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

        if (!$this->statusId && ($form = $this->getForm()) && ($defaultStatus = $form->getDefaultStatus())) {
            $this->setStatus($defaultStatus);
        }

        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementSave($this, $isNew)) {
                return false;
            }
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

        // Preserve unknown/orphaned persisted keys so form schema changes don't silently
        // drop historical submission payload data on resave.
        $record->content = $this->_mergeSerializedContentPreservingUnknown($record->content, $this->serializeFieldValues());
        $record->formId = $this->formId;
        $record->statusId = $this->statusId;
        $record->userId = $this->userId;
        $record->isIncomplete = $this->isIncomplete;
        $record->isSpam = $this->isSpam;
        $record->spamReason = $this->spamReason;
        $record->spamClass = $this->spamClass;
        $record->snapshot = $this->snapshot;
        $record->ipAddress = $this->ipAddress;
        $record->integrationDispatchContext = $this->integrationDispatchContext;
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

        foreach ($this->getFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        if ($this->_updateTitle) {
            $this->updateTitle($this->getForm());
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

        // Delete associated file upload assets when the submission is permanently deleted
        // and the form is configured to remove files.
        if ($form && $form->fileUploadsAction === 'delete' && $this->hardDelete) {
            foreach ($this->getFieldValuesForField(FileUpload::class) as $value) {
                $this->_assetsToDelete = array_merge($this->_assetsToDelete, $value->all());
            }
        }

        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementDelete($this)) {
                return false;
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

        foreach ($this->getFields() as $field) {
            $field->afterElementDelete($this);
        }

        parent::afterDelete();
    }

    public function beforeDeleteForSite(): bool
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementDeleteForSite($this)) {
                return false;
            }
        }

        return parent::beforeDeleteForSite();
    }

    public function afterDeleteForSite(): void
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementDeleteForSite($this);
        }

        parent::afterDeleteForSite();
    }

    public function beforeRestore(): bool
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementRestore($this)) {
                return false;
            }
        }

        return parent::beforeRestore();
    }

    public function afterRestore(): void
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementRestore($this);
        }

        parent::afterRestore();
    }

    public function afterValidate(): void
    {
        // Lift from `craft\base\Element::afterValidate()` all so we can modify the `RequiredValidator` message
        // for our custom error message. Might ask the Craft crew if there's a better way to access private methods
        if ($formLayout = $this->getFormLayout()) {
            $fields = $formLayout->getFieldsToValidate($this);

            foreach ($fields as $field) {
                $attribute = $this->_getFieldValidationAttribute($field);

                if (isset($this->_validationAttributeNames) && !isset($this->_validationAttributeNames[$attribute])) {
                    continue;
                }

                $requiredMessage = null;

                if (ValidationMessagesHelper::override($field, ValidationMessagesHelper::KEY_REQUIRED) !== null) {
                    $requiredMessage = $field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED);
                } elseif ($field->errorMessage) {
                    $requiredMessage = Craft::t('formie', $field->errorMessage);
                }

                ValidationHelper::validateField(
                    $this,
                    $field,
                    $this->getFieldValue($field->handle),
                    $attribute,
                    $requiredMessage
                );
            }
        }

        // Bubble up past the `Element::afterValidate()` to prevent this happening twice
        Component::afterValidate();
    }

    public function afterPropagate(bool $isNew): void
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementPropagate($this, $isNew);
        }

        parent::afterPropagate($isNew);
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

        if (preg_match('/^(field):(.+)/', $attribute, $matches)) {
            $uid = $matches[2];

            if ($matches[1] === 'field') {
                $field = $this->getContentManager()->getFieldByUid($this, $uid);
            }

            if ($field instanceof PreviewableFieldInterface) {
                // The field might not actually belong to this element
                try {
                    $value = $this->getFieldValue($field->handle);
                } catch (Throwable) {
                    return '';
                }

                return $field->getPreviewHtml($value, $this);
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

    protected function inlineAttributeInputHtml(string $attribute): string
    {
        $field = null;

        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            try {
                $uid = $matches[1];
                $field = $this->getContentManager()->getFieldByUid($this, $uid);
            } catch (Throwable $e) {
                // Ignore any fields that don't belong to this element
            }
        }

        if ($field instanceof InlineEditableFieldInterface) {
            try {
                $value = $this->getFieldValue($field->handle);
            } catch (Throwable $e) {
                return '';
            }

            return $field->getInlineInputHtml($value, $this);
        }

        return $this->attributeHtml($attribute);
    }

    private function _getFieldValidationAttribute(Field $field): string
    {
        return ValidationHelper::fieldValidationAttribute($field);
    }

    private function _mergeSerializedContentPreservingUnknown(mixed $existingContent, array $serializedContent): array
    {
        $existingContent = $this->_normalizeSerializedContent($existingContent);

        if (!$existingContent) {
            return $serializedContent;
        }

        foreach ($existingContent as $key => $value) {
            if (!array_key_exists($key, $serializedContent)) {
                $serializedContent[$key] = $value;
            }
        }

        return $serializedContent;
    }

    private function _normalizeSerializedContent(mixed $content): array
    {
        if (is_array($content)) {
            return $content;
        }

        if (is_string($content) && $content !== '') {
            try {
                $decoded = Json::decode($content);

                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (Throwable) {
            }
        }

        return [];
    }

    private function _applySnapshotSettingsIfNeeded(): void
    {
        if ($this->_snapshotSettingsApplied || !$this->_form) {
            return;
        }

        // When setting the form on a front-end request, merge in-session snapshot data
        // before applying settings. Saved submission snapshots are already on the element.
        if (Craft::$app->getRequest()->getIsSiteRequest() && !$this->snapshot) {
            if ($snapshotData = $this->_form->getSnapshotData()) {
                $this->snapshot = $snapshotData;
            }
        }

        $fields = $this->snapshot['fields'] ?? [];
        $formSettings = $this->snapshot['form'] ?? null;

        if ($fields === [] && $formSettings === null) {
            $this->_snapshotSettingsApplied = true;

            return;
        }

        foreach ($fields as $handle => $settings) {
            $this->setFieldSettings($handle, $settings);
        }

        if ($formSettings) {
            $this->_form->settings->setAttributes($formSettings, false);
        }

        $this->getContentState()->normalizedValuesByUid = [];

        $this->_snapshotSettingsApplied = true;
    }
}

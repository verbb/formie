<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\content\FieldValueProjectionContext;
use verbb\formie\content\SubmissionContentManager;
use verbb\formie\content\SubmissionContentNormalizer;
use verbb\formie\content\SubmissionContentState;
use verbb\formie\deprecations\SubmissionValueDeprecations;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\conditions\SubmissionCondition;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\events\SubmissionCompleteEvent;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\SubmissionLimitHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\ValidationHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\FieldLayout as FormLayout;
use verbb\formie\models\FormInstanceConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionConfig;
use verbb\formie\models\SubmissionErrors;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\records\Submission as SubmissionRecord;
use verbb\formie\services\RuntimeConfiguration;
use verbb\formie\workflow\WorkflowContext;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\events\DefineElementHtmlEvent;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\SiteIdValidator;

use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;
use yii\db\Expression;

use Throwable;

use Twig\Markup;

class Submission extends Element
{
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
        return Formie::$plugin->getSubmissionStatuses()->getStatusesArray();
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
        } elseif ($element->isSpam) {
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
        $activeSiteId = Formie::$plugin->getFormSiteOverrides()->getActiveSiteId();
        $canViewAllSubmissions = Formie::$plugin->getPermissions()->hasGlobalSubmissionAccess($currentUser);

        $formGroups = Formie::$plugin->getFormGroups()->getAllGroups();

        $cacheKey = implode(':', [
            (string)($currentUser->id ?? 0),
            (string)$activeSiteId,
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
            'e.uid',
            'f.handle',
            'es.title',
        ];

        $formColumns[] = 'f.groupId';
        $formColumns[] = 'g.handle AS groupHandle';

        $formsQuery = (new Query())
            ->select($formColumns)
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => CraftTable::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->innerJoin(['es' => CraftTable::ELEMENTS_SITES], '[[es.elementId]] = [[f.id]] AND [[es.siteId]] = :siteId', [
                ':siteId' => $activeSiteId,
            ]);

        $formsQuery->leftJoin(['g' => Table::FORMIE_FORM_GROUPS], '[[g.id]] = [[f.groupId]]');

        $forms = $formsQuery
            ->where(['e.dateDeleted' => null])
            ->orderBy(['es.title' => SORT_ASC])
            ->all();

        $canonicalTitles = [];

        foreach ($forms as $form) {
            $formId = (int)($form['id'] ?? 0);

            if ($formId) {
                $canonicalTitles[$formId] = (string)($form['title'] ?? $form['handle'] ?? '');
            }
        }

        $displayTitles = Formie::$plugin->getFormSiteOverrides()->resolveFormTitlesForSite(
            $canonicalTitles,
            $activeSiteId,
        );

        usort($forms, function(array $left, array $right) use ($displayTitles): int {
            $leftId = (int)($left['id'] ?? 0);
            $rightId = (int)($right['id'] ?? 0);
            $leftTitle = $displayTitles[$leftId] ?? (string)($left['title'] ?? '');
            $rightTitle = $displayTitles[$rightId] ?? (string)($right['title'] ?? '');

            return strcasecmp($leftTitle, $rightTitle);
        });

        $sources = [];

        if ($canViewAllSubmissions) {
            $sources[] = [
                'key' => '*',
                'label' => Craft::t('formie', 'All Forms'),
                // Default submission title format is a timestamp; sort chronologically, not alphabetically.
                'defaultSort' => ['elements.dateCreated', 'desc'],
            ];
        }

        $formItemsByGroupId = [];
        $ungroupedFormItems = [];

        foreach ($forms as $form) {
            if (!$canViewAllSubmissions && !Formie::$plugin->getPermissions()->userCanViewSubmissionsForFormRecord($currentUser, $form)) {
                continue;
            }

            $formUid = (string)($form['uid'] ?? '');

            $formId = (int)($form['id'] ?? 0);
            $formHandle = (string)($form['handle'] ?? '');
            $formTitle = $displayTitles[$formId] ?? (string)($form['title'] ?? $formHandle);
            $key = "form:{$formId}";

            $formItem = [
                'key' => $key,
                'label' => $formTitle,
                'data' => [
                    'handle' => $formHandle,
                ],
                'criteria' => ['formId' => $formId],
                // Default submission title format is a timestamp; sort chronologically, not alphabetically.
                'defaultSort' => ['elements.dateCreated', 'desc'],
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
        $form = Formie::$plugin->getForms()->getFormById($formId);
        $permissions = Formie::$plugin->getPermissions();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $canSaveSubmissions = $permissions->canSaveSubmissions($currentUser, $form);
        $canDeleteSubmissions = $permissions->canDeleteSubmissions($currentUser, $form);

        if ($canSaveSubmissions) {
            $actions[] = $elementsService->createAction([
                'type' => SetSubmissionStatus::class,
                'statuses' => Formie::$plugin->getSubmissionStatuses()->getSubmissionStatusesForForm($form),
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
            'title' => ['label' => Craft::t('formie', 'Submission')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'form' => ['label' => Craft::t('formie', 'Form')],
            'spamReason' => ['label' => Craft::t('app', 'Spam Reason')],
            'ipAddress' => ['label' => Craft::t('app', 'IP Address')],
            'userId' => ['label' => Craft::t('app', 'User')],
            'updatedBy' => ['label' => Craft::t('formie', 'Last Edited By')],
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
                'label' => Craft::t('formie', 'Submission'),
                // Titles are usually generated from {timestamp}; sort by creation date instead of the formatted string.
                'orderBy' => 'elements.dateCreated',
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


    // Constants
    // =========================================================================

    public const EVENT_DEFINE_RULES = 'defineSubmissionRules';
    public const EVENT_BEFORE_MARKED_AS_SPAM = 'beforeMarkedAsSpam';

    /**
     * Fired when a submission becomes complete: last reachable page submitted,
     * payment replay that finishes the form, or a control-panel mark-complete.
     * Does not fire on intermediate page steps or later edits of a complete submission.
     */
    public const EVENT_AFTER_COMPLETE = 'afterComplete';


    // Traits
    // =========================================================================

    use SubmissionValueDeprecations;


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $formId = null;
    public ?int $statusId = null;
    public ?int $userId = null;
    public ?int $updatedById = null;
    public ?string $ipAddress = null;
    public int $stateVersion = 0;
    public bool $isIncomplete = false;
    public bool $isSpam = false;
    public ?string $spamReason = null;
    public ?string $spamClass = null;
    public array $snapshot = [];
    public ?array $metadata = null;
    public ?bool $validateCurrentPageOnly = null;

    private ?Form $_form = null;
    private ?SubmissionStatus $_status = null;
    private ?User $_user = null;
    private ?FormLayout $_formLayout = null;
    private ?string $_fieldContext = null;
    private ?array $_pagesForField = null;
    private array $_uploadsToDelete = [];
    private bool $_previousIsSpam = false;
    private bool $_previousIsIncomplete = false;
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

    public function getMetadata(?string $key = null): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        if ($key === null) {
            return $metadata;
        }

        $value = $metadata[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function mergeMetadata(array $metadata): void
    {
        $this->metadata = ArrayHelper::merge($this->getMetadata(), $metadata);
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

        return Formie::$plugin->getPermissions()->canViewSubmissions($user, $this->getForm());
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return Formie::$plugin->getPermissions()->canSaveSubmissions($user, $this->getForm());
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return Formie::$plugin->getPermissions()->canDeleteSubmissions($user, $this->getForm());
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

        $pdfDownloadUrl = $this->getPdfDownloadUrl();

        if ($pdfDownloadUrl) {
            $actions[] = [
                'icon' => 'share',
                'label' => Craft::t('formie', 'Download PDF'),
                'href' => $pdfDownloadUrl,
            ];
        }

        return array_values($actions);
    }

    public function getPdfDownloadUrl(?int $pdfTemplateId = null, ?int $notificationId = null): ?string
    {
        if (!$this->id) {
            return null;
        }

        return Formie::$plugin->getSubmissions()->getSubmissionPdfDownloadUrl($this, $pdfTemplateId, $notificationId);
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $processFields = function($fields) use (&$processFields, &$labels) {
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

    public function validateAllowedStatus(): void
    {
        if (!$this->statusId) {
            return;
        }

        $form = $this->getForm();

        if (!$form) {
            return;
        }

        if (!Formie::$plugin->getFormGroupPolicy()->isStatusAllowed($form, (int)$this->statusId)) {
            $this->addError('statusId', Craft::t('formie', 'This submission status is not allowed for this form.'));
        }
    }

    public function getSubmissionErrors(): SubmissionErrors
    {
        return SubmissionErrors::fromSubmission($this);
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $this->_validationAttributeNames = $attributeNames ? array_flip((array)$attributeNames) : null;

        try {
            $validates = parent::validate($attributeNames, $clearErrors);
        } finally {
            $this->_validationAttributeNames = null;
        }

        $command = WorkflowContext::current()?->command;

        if ($command?->submission === $this && $command->authority->type === SubmissionAuthorityType::GRAPHQL_ADMIN) {
            return $validates && !$this->hasErrors();
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
        if (($this->isIncomplete || !$this->id) && $form && SubmissionLimitHelper::isEnabled($form->settings)) {
            if (!$form->isWithinSubmissionsLimit($this)) {
                $this->addError('form', Craft::t('formie', 'This form has met the number of allowed submissions.'));
            }
        }

        return $validates && !$this->hasErrors();
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

    public function getEnabledRows(): array
    {
        return $this->getFormLayout()?->getEnabledRows() ?? [];
    }

    public function getEnabledFields(): array
    {
        return $this->getFormLayout()?->getEnabledFields() ?? [];
    }

    public function getFieldsRecursively(): array
    {
        return $this->getFormLayout()?->getFieldsRecursively() ?? [];
    }

    public function getRows(): array
    {
        return $this->getFormLayout()?->getRows() ?? [];
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

    public function getFieldValue(string $fieldKey): mixed
    {
        return $this->getContentManager()->getFieldValue($this, $fieldKey);
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
            $siteId = (int)($this->siteId ?: Craft::$app->getSites()->getCurrentSite()->id);
            $cacheKey = $this->formId . ':' . $siteId;

            if (array_key_exists($cacheKey, self::$_formByIdCache)) {
                $this->_form = self::$_formByIdCache[$cacheKey];
            } else {
                // Historical submissions still need their original site's schema after availability changes.
                // Resolve the relationship independently of CP index visibility; permissions use this form.
                $this->_form = Form::find()->withoutCpIndexScope()->id($this->formId)->siteId($siteId)->status(null)->one();

                // If no form found yet, and the submission has been trashed, maybe the form has been trashed?
                if (!$this->_form && $this->trashed) {
                    $this->_form = Form::find()->withoutCpIndexScope()->id($this->formId)->siteId($siteId)->status(null)->trashed(true)->one();
                }

                if ($this->_form && Formie::$plugin->getFormSiteOverrides()->isEnabled()) {
                    $this->_form = Formie::$plugin->getFormSiteOverrides()->applyToForm($this->_form, $siteId, true);
                }

                self::$_formByIdCache[$cacheKey] = $this->_form;
            }

            if ($this->_form) {
                $this->_form = clone $this->_form;
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
        $form = $this->getForm();
        $form->setFieldSettings($handle, $settings);
        $this->snapshot = SubmissionConfig::capture($form->getInstanceConfig());
        $this->setForm($form);
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

    public function getStatusModel(): SubmissionStatus
    {
        if (!$this->_status && $this->statusId) {
            $this->_status = Formie::$plugin->getSubmissionStatuses()->getStatusById($this->statusId);
        }

        if ($this->_status) {
            return $this->_status;
        }

        if ($form = $this->getForm()) {
            return $this->_status = $form->getDefaultStatus();
        }

        if ($status = Formie::$plugin->getSubmissionStatuses()->getDefaultStatus()) {
            return $this->_status = $status;
        }

        // Just in case there's no default status set in settings, pick the first available
        return $this->_status = Formie::$plugin->getSubmissionStatuses()->getAllStatuses()[0];
    }

    public function setStatus(SubmissionStatus|string $status): void
    {
        if (is_string($status)) {
            if ($foundStatus = Formie::$plugin->getSubmissionStatuses()->getStatusByHandle($status)) {
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

    public function getUpdatedBy(): ?User
    {
        if (!$this->updatedById) {
            return null;
        }

        return Craft::$app->getUsers()->getUserById($this->updatedById);
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
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::string());
    }

    public function getFieldValueAsData(string $fieldKey): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::data());
    }

    public function getFieldValueForExport(string $fieldKey): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::export());
    }

    public function getFieldValueForSummary(string $fieldKey): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::summary());
    }

    public function getFieldValueForReference(string $fieldKey, ?Notification $notification = null): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::reference());
    }

    public function getFieldValueForReferenceBlock(string $fieldKey, Notification $notification): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::referenceBlock($notification));
    }

    public function getFieldValueForIntegration(string $fieldKey, IntegrationField $integrationField, IntegrationInterface $integration, string $integrationFieldKey = ''): mixed
    {
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::integration($integrationField, $integration, $integrationFieldKey));
    }

    public function getFieldValueForCondition(string $fieldKey): mixed
    {
        // Conditions must always route through the same projection path as the
        // standalone evaluators so builder rules, Twig checks, and workflow
        // logic compare against one canonical representation.
        return $this->getContentManager()->getProjectedFieldValue($this, $fieldKey, FieldValueProjectionContext::condition());
    }

    public function getValuesAsString(): array
    {
        return $this->getContentManager()->getValuesAsString($this);
    }

    public function getValuesAsData(): array
    {
        return $this->getContentManager()->getValuesAsData($this);
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
        $command = WorkflowContext::current()?->command;
        $administrative = $command?->submission === $this && $command->authority->type === SubmissionAuthorityType::GRAPHQL_ADMIN;

        if ($this->isSpam && !$administrative && $request->getIsSiteRequest()) {
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
                ->select(['statusId', 'isSpam', 'isIncomplete'])
                ->from([Table::FORMIE_SUBMISSIONS])
                ->where(['id' => $this->id])
                ->one();

            $this->_previousStatusId = $previousSettings['statusId'] ?? null;
            $this->_previousIsSpam = (bool)($previousSettings['isSpam'] ?? false);
            $this->_previousIsIncomplete = (bool)($previousSettings['isIncomplete'] ?? false);
        }

        if (!$this->statusId && ($form = $this->getForm()) && ($defaultStatus = $form->getDefaultStatus())) {
            $this->setStatus($defaultStatus);
        }

        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementSave($this, $isNew)) {
                return false;
            }
        }

        if ($request->getIsCpRequest() && ($userId = Craft::$app->getUser()->getId())) {
            $this->updatedById = $userId;
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

        $record->updatedById = $this->updatedById;

        $record->isIncomplete = $this->isIncomplete;
        $record->isSpam = $this->isSpam;
        $record->spamReason = $this->spamReason;
        $record->spamClass = $this->spamClass;
        $record->snapshot = $this->snapshot;
        $record->ipAddress = $this->ipAddress;

        if ($isNew) {
            $record->signatureAccessKey = Craft::$app->getSecurity()->generateRandomString(64);
        }

        $record->metadata = $this->metadata;

        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

        $record->save(false);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
            'stateVersion' => new Expression('[[stateVersion]] + 1'),
        ], ['id' => $this->id])->execute();
        $this->stateVersion = (int)(new Query())->select('stateVersion')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $this->id])->scalar();

        // Reset cache as we might be acting on statuses below
        $this->_status = null;

        // Check to see if we need to save any relations
        Formie::$plugin->getRelations()->saveRelations($this);

        // Side effects are initiated by explicit workflow/administrative operations, not persistence.

        foreach ($this->getFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        if ($this->_updateTitle) {
            $this->updateTitle($this->getForm());
        }

        parent::afterSave($isNew);

        $this->_raiseAfterCompleteIfNeeded($isNew);
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
        $this->_uploadsToDelete = [];

        if ($form && $form->fileUploadsAction === 'delete' && $this->hardDelete) {
            $this->_uploadsToDelete = Formie::$plugin->getFileUploads()->getUploadsForSubmissionDeletion($this);
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
        Formie::$plugin->getFileUploads()->deleteSubmissionUploads($this->_uploadsToDelete);
        $this->_uploadsToDelete = [];

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
                    $requiredMessage = $field->errorMessage;
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
        $rules[] = [['statusId'], 'validateAllowedStatus'];

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

            return Html::encode($form->title ?? '');
        }

        if ($attribute == 'userId') {
            $user = $this->getUser();

            return $user ? Cp::elementChipHtml($user) : '';
        }

        if ($attribute == 'updatedBy') {
            $user = $this->getUpdatedBy();

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
                ]) . Html::encode($status->name ?? ''), [
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

                if (in_array($color, ['success', 'succeeded'], true)) {
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
            $currentUser = Craft::$app->getUser()->getIdentity();
            $form = $this->getForm();

            // Side-effect action — require save, not just view.
            if (
                $form
                && $form->getNotifications()
                && $currentUser
                && Formie::$plugin->getPermissions()->canSaveSubmissions($currentUser, $form)
            ) {
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


    // Private Methods
    // =========================================================================

    /**
     * Direct element saves (CP mark-complete, imports) that flip incomplete →
     * complete. Workflow persist skips this path so `EVENT_AFTER_COMPLETE`
     * waits until the save stage finishes (payment may still fail).
     */
    private function _raiseAfterCompleteIfNeeded(bool $isNew): void
    {
        if ($this->isIncomplete || WorkflowContext::current()?->command->submission === $this) {
            return;
        }

        $becameComplete = $isNew || $this->_previousIsIncomplete;

        if (!$becameComplete) {
            return;
        }

        $this->trigger(self::EVENT_AFTER_COMPLETE, new SubmissionCompleteEvent([
            'submission' => $this,
            'form' => $this->getForm(),
        ]));
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
        return SubmissionContentNormalizer::decodeStoredPayload($content) ?? [];
    }

    private function _applySnapshotSettingsIfNeeded(): void
    {
        if ($this->_snapshotSettingsApplied || !$this->_form) {
            return;
        }

        $this->_snapshotSettingsApplied = true;
        $config = $this->snapshot ? SubmissionConfig::decode($this->snapshot, $this->_form) : $this->_form->getInstanceConfig();

        if (!$this->snapshot) {
            $completion = array_intersect_key($this->_form->settings->toArray(), array_flip(RuntimeConfiguration::DURABLE_FORM_SETTINGS));
            // Provider connections remain globally owned; only explicit runtime
            // integration overrides belong to the durable instance config.
            unset($completion['integrations']);

            if ($this->_form->settings->completionRedirectSource === 'entry' && $this->_form->getRedirectEntry()) {
                $completion['redirectUrl'] = $this->_form->getRedirectEntry()->url;
                $completion['completionRedirectSource'] = 'url';
            }
            $config = new FormInstanceConfig(...array_replace(get_object_vars($config), [
                'form' => FormInstanceConfig::merge($completion, $config->form),
            ]));
        }
        $this->_form->markInstanceEstablished();
        $this->_form->replaceInstanceConfig($config);
        $this->snapshot = SubmissionConfig::capture($config);
    }
}

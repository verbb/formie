<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\FormDefaultableTrait;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Miscellaneous;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\actions\MoveFormToGroup;
use verbb\formie\elements\conditions\FormCondition;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\events\ModifyFormSlotTagEvent;
use verbb\formie\deprecations\FormDeprecations;
use verbb\formie\deprecations\ThemeConfigLegacyKeys;
use verbb\formie\gql\interfaces\FieldInterface as GqlFieldInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\References;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\FieldLayout as FormLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Status;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\client\bootstrap\models\FormDefinition;
use verbb\formie\client\models\LoadContext;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\services\Statuses;
use verbb\formie\state\ResumeToken;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\MissingComponentException;
use craft\events\DefineElementHtmlEvent;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\i18n\Locale;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\web\View;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

use Throwable;
use DateTime;
use DateTimeZone;

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;

class Form extends Element implements FormInterface
{
    // Constants
    // =========================================================================

    public const BUILDER_ENTITY_TYPE_FORM = 'form';
    public const BUILDER_ENTITY_TYPE_STENCIL = 'stencil';

    public const EVENT_MODIFY_SLOT_TAG = 'modifySlotTag';
    public const EVENT_MODIFY_HTML_TAG = 'modifyHtmlTag';


    // Traits
    // =========================================================================

    use FormDefaultableTrait;
    use FormDeprecations;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Form');
    }

    public static function refHandle(): ?string
    {
        return 'form';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function find(): FormQuery
    {
        return new FormQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(FormCondition::class, [static::class]);
    }
    
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Form';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['formieForms.' . $context->uid];
    }

    public static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'defaultSort' => ['title', 'desc'],
            ],
        ];

        $groups = Formie::$plugin->getFormGroups()->getAllGroups();

        if ($groups) {
            $sources[] = ['heading' => Craft::t('formie', 'Groups')];
        }

        foreach ($groups as $group) {
            $sources[] = [
                'key' => "group:{$group->uid}",
                'label' => $group->name,
                'data' => [
                    'handle' => $group->handle,
                    'group-id' => $group->id,
                ],
                'criteria' => ['groupId' => $group->id],
            ];
        }

        if ($groups) {
            $sources[] = [
                'key' => 'ungrouped',
                'label' => Craft::t('formie', 'Ungrouped'),
                'data' => ['handle' => 'ungrouped'],
                'criteria' => ['groupId' => ':empty:'],
            ];
        }

        return $sources;
    }

    public static function defineElementChipHtml(DefineElementHtmlEvent $event): void
    {
        $element = $event->element;

        if (!($element instanceof self)) {
            return;
        }

        // Remove the quik-edit ability
        $event->html = str_replace(['data-editable'], [''], $event->html);
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $canDeleteForms = Craft::$app->getUser()->checkPermission('formie-deleteForms');

        $actions[] = DuplicateForm::class;

        if (Formie::$plugin->getFormGroups()->getAllGroups()) {
            $actions[] = MoveFormToGroup::class;
        }

        if ($canDeleteForms) {
            $actions[] = [
                'type' => Delete::class,
                'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected forms?'),
                'successMessage' => Craft::t('formie', 'Forms deleted.'),
            ];
        }

        $actions[] = [
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Forms restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some forms restored.'),
            'failMessage' => Craft::t('formie', 'Forms not restored.'),
        ];

        return $actions;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Name')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'handle' => ['label' => Craft::t('app', 'Handle')],
            'template' => ['label' => Craft::t('app', 'Template')],
            'pageCount' => ['label' => Craft::t('formie', 'Page Count')],
            'usageCount' => ['label' => Craft::t('formie', 'Usage Count')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'createdBy' => ['label' => Craft::t('formie', 'Created By')],
            'updatedBy' => ['label' => Craft::t('formie', 'Updated By')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'title';
        $attributes[] = 'handle';
        $attributes[] = 'template';
        $attributes[] = 'dateCreated';
        $attributes[] = 'dateUpdated';

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'handle'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Name'),
            'handle' => Craft::t('app', 'Handle'),
            [
                'label' => Craft::t('app', 'Page Count'),
                'orderBy' => 'pageCount',
                'attribute' => 'pageCount',
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
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }


    //  Properties
    // =========================================================================

    public ?string $handle = null;
    public ?int $layoutId = null;
    public ?int $templateId = null;
    public ?int $groupId = null;
    public ?int $submitActionEntryId = null;
    public ?int $submitActionEntrySiteId = null;
    public ?int $defaultStatusId = null;
    public string $dataRetention = 'forever';
    public ?string $dataRetentionValue = null;
    public string $userDeletedAction = 'retain';
    public string $fileUploadsAction = 'retain';
    public ?int $createdById = null;
    public ?int $updatedById = null;
    public ?FormSettings $settings = null;
    public string $builderEntityType = self::BUILDER_ENTITY_TYPE_FORM;

    public ?int $pageCount = null;
    public bool $isApplyingStencil = false;

    private ?FieldLayout $_fieldLayout = null;
    private ?FormLayout $_formLayout = null;
    private ?FormTemplate $_template = null;
    private ?FormGroup $_group = null;
    private ?Status $_defaultStatus = null;
    private ?Entry $_submitActionEntry = null;
    private ?array $_notifications = null;
    private ?FieldLayoutPage $_currentPage = null;
    private ?Submission $_currentSubmission = null;
    private ?Submission $_editingSubmission = null;
    private ?string $_formId = null;
    private ?int $_renderSequence = null;
    private bool $_appliedFieldSettings = false;
    private bool $_appliedFormSettings = false;
    private array $_relations = [];
    private array $_populatedFieldValues = [];
    private ?string $_redirectUrl = null;
    private ?string $_actionUrl = null;
    private ?string $_draftContext = null;
    private ?string $_requestToken = null;
    private ?string $_submissionEditToken = null;
    private bool $_resumeTokenHydrated = false;
    private bool $_routeContextHydrated = false;
    private array $_submitData = [];

    private array $_themeConfig = [];
    private string $_frontendTheme = 'formie';
    private ?string $_sessionKey = null;
    private static array $_renderSequenceCounters = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('settings', $config)) {
            if (is_string($config['settings'])) {
                $config['settings'] = new FormSettings(Json::decodeIfJson($config['settings']));
            }

            if (!($config['settings'] instanceof FormSettings)) {
                $config['settings'] = new FormSettings();
            }
        } else {
            $config['settings'] = new FormSettings();
        }

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function init(): void
    {
        parent::init();

        if ($this->settings instanceof FormSettings) {
            $this->settings->setForm($this);
        }
    }

    public function getScenario()
    {
        // Only set to "live" after creating the form. Otherwise Form Template fields validate.
        if ($this->id) {
            $newLayoutId = $this->templateId;
            $savedLayoutId = (new Query())
                ->select(['templateId'])
                ->from(['{{%formie_forms}}'])
                ->where(['id' => $this->id])
                ->scalar();

            // To make things more complicated, we need to check if we're applying a new template, and not validate
            // immediately, only on next save. This is because the UI needs to catch up once the form template has changed.
            if (!$savedLayoutId || (!$newLayoutId && $savedLayoutId)) {
                return self::SCENARIO_ESSENTIALS;
            }

            return self::SCENARIO_LIVE;
        }

        return parent::getScenario();
    }

    public function canSave(User $user): bool
    {
        return true;
    }
    
    public function canView(User $user): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can('formie-deleteForms');
    }

    public function canDuplicate(User $user): bool
    {
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

    public function getSettings(): ?FormSettings
    {
        return $this->settings;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function validateFormSettings(): void
    {
        $settings = $this->getSettings();

        if ($settings && !$settings->validate()) {
            foreach ($settings->getErrors() as $key => $error) {
                $this->addError('settings.' . $key, $error[0]);
            }
        }
    }

    public function getFormLayout(): FormLayout
    {
        if ($this->_formLayout) {
            return $this->_formLayout;
        }

        if (!$this->layoutId) {
            return $this->_formLayout = new FormLayout();
        }

        return $this->_formLayout = (Formie::$plugin->getFields()->getLayoutById($this->layoutId) ?? new FormLayout());
    }

    public function setFormLayout(FormLayout $formLayout): void
    {
        $this->_formLayout = $formLayout;
    }

    public function validateFormLayout(): void
    {
        $formLayout = $this->getFormLayout();

        if (!$formLayout->validate()) {
            // Element models can't handle nested errors
            $errors = ArrayHelper::flatten($formLayout->getErrors());

            foreach ($errors as $errorKey => $error) {
                $this->addError($errorKey, $error);
            }
        }
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        try {
            $template = $this->getTemplate();
        } catch (InvalidConfigException) {
            // The entry type was probably deleted
            return null;
        }

        if (!$template) {
            return null;
        }

        return $this->_fieldLayout = $template->getFieldLayout();
    }

    public function getTemplate(): ?FormTemplate
    {
        if (!$this->_template) {
            if ($this->templateId) {
                $this->_template = Formie::$plugin->getFormTemplates()->getTemplateById($this->templateId);
            } else {
                return null;
            }
        }

        return $this->_template;
    }

    public function setTemplate(?FormTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

    public function getGroup(): ?FormGroup
    {
        if (!$this->_group) {
            if ($this->groupId) {
                $this->_group = Formie::$plugin->getFormGroups()->getGroupById($this->groupId);
            } else {
                return null;
            }
        }

        return $this->_group;
    }

    public function setGroup(?FormGroup $group): void
    {
        if ($group) {
            $this->_group = $group;
            $this->groupId = $group->id;
        } else {
            $this->_group = $this->groupId = null;
        }
    }

    public function getDefaultStatus(): ?Status
    {
        if (!$this->_defaultStatus) {
            if ($this->defaultStatusId) {
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getStatusById($this->defaultStatusId);
            } else {
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getAllStatuses()[0] ?? null;
            }
        }

        // Check if for whatever reason there isn't a default status - create it
        if ($this->_defaultStatus === null) {
            // But check for admin changes, as it's a project config setting change to make.
            if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                $projectConfig = Craft::$app->getProjectConfig();

                // Maybe the project config didn't get applied? Check for existing values
                // This can likely be removed later, as this fix is already in place when installing Formie
                $statuses = $projectConfig->get(Statuses::CONFIG_STATUSES_KEY, true) ?? [];

                foreach ($statuses as $statusUid => $statusData) {
                    $projectConfig->processConfigChanges(Statuses::CONFIG_STATUSES_KEY . '.' . $statusUid, true);
                }

                // If there's _still_ not a status, just go ahead and create it...
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getAllStatuses()[0] ?? null;

                if ($this->_defaultStatus === null) {
                    $this->_defaultStatus = new Status([
                        'name' => 'New',
                        'handle' => 'new',
                        'color' => 'green',
                        'sortOrder' => 1,
                        'isDefault' => 1,
                    ]);

                    Formie::$plugin->getStatuses()->saveStatus($this->_defaultStatus);
                }
            }
        }

        return $this->_defaultStatus;
    }

    public function setDefaultStatus(?Status $status): void
    {
        if ($status) {
            $this->_defaultStatus = $status;
            $this->defaultStatusId = $status->id;
        } else {
            $this->_defaultStatus = $this->defaultStatusId = null;
        }
    }

    public function getCreatedBy(): ?User
    {
        if (!$this->createdById) {
            return null;
        }

        return Craft::$app->getUsers()->getUserById($this->createdById);
    }

    public function getUpdatedBy(): ?User
    {
        if (!$this->updatedById) {
            return null;
        }

        return Craft::$app->getUsers()->getUserById($this->updatedById);
    }

    public function getCreatedByLabel(): string
    {
        return $this->_getFormUserLabel($this->getCreatedBy());
    }

    public function getUpdatedByLabel(): string
    {
        return $this->_getFormUserLabel($this->getUpdatedBy());
    }

    public function getFormMetaDetails(): ?array
    {
        if (!$this->id) {
            return null;
        }

        $formatter = Craft::$app->getFormatter();

        return [
            'createdBy' => $this->_getFormUserMeta($this->getCreatedBy()),
            'updatedBy' => $this->_getFormUserMeta($this->getUpdatedBy()),
            'dateCreated' => $formatter->asDatetime($this->dateCreated, Locale::LENGTH_SHORT),
            'dateUpdated' => $formatter->asDatetime($this->dateUpdated, Locale::LENGTH_SHORT),
        ];
    }

    public function getDefaultSubmissionTitle(?Submission $submission = null): string
    {
        $format = trim((string)($this->settings->submissionTitleFormat ?? ''));

        if ($submission !== null && $format !== '') {
            $parsed = trim(References::parseContent($format, $submission, [
                'includeSummary' => false,
            ]));

            if ($parsed !== '') {
                return $parsed;
            }
        }

        $timeZone = Craft::$app->getTimeZone();
        $now = new DateTime('now', new DateTimeZone($timeZone));

        return $now->format('Y-m-d H:i');
    }

    public function getDirtyAttributes(): array
    {
        // This is here to prompt Blitz that a change has been made on the form when it saves
        // because the form settings don't use delta updates, which Blitz relies on. Keep an eye on
        // what potential issues this might bring up...
        $this->setDirtyAttributes(['title']);

        return parent::getDirtyAttributes();
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'errors' => $this->getErrors(),
            'templateId' => $this->templateId,
            'groupId' => $this->groupId,
            'defaultStatusId' => $this->defaultStatusId,
            'pages' => $this->getFormLayout()->getFormBuilderConfig(),
            'settings' => $this->getSettings()->getFormBuilderConfig(),
        ];
    }

    public function getNotificationsConfig(): array
    {
        return Formie::$plugin->getNotifications()->getNotificationsConfig($this->getNotifications());
    }

    public function getClientConfig(): array
    {
        return [
            'formId' => (string)$this->id,
            'handle' => $this->handle,
            'pages' => array_map(static function(FieldLayoutPage $page) {
                return $page->getClientConfig();
            }, $this->getPages()),
            'settings' => [
                'currentPageId' => (string)($this->getCurrentPage()?->id ?? ''),
                'errorMessage' => $this->getFrontendErrorMessage(),
                'loadingIndicator' => (string)($this->settings->loadingIndicator ?? ''),
                'loadingIndicatorText' => (string)($this->settings->loadingIndicatorText ?? ''),
                'progressCalculation' => (string)$this->settings->progressCalculation,
                'scrollToTop' => (bool)$this->settings->scrollToTop,
                'submitMethod' => (string)$this->settings->submitMethod,
                'validationOnFocus' => (bool)$this->settings->validationOnFocus,
                'validationOnSubmit' => (bool)$this->settings->validationOnSubmit,
                'disableSubmitButtonUntilValid' => (bool)$this->settings->disableSubmitButtonUntilValid,
            ],
            'modules' => Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($this, ClientModule::RENDER_TARGET_CP_EDIT),
        ];
    }

    public function getClientPayload(LoadContext $context): FormDefinition
    {
        $pages = array_map(function(FieldLayoutPage $page, int $index) {
            return $page->getClientPayload($this, $index);
        }, $this->getPages(), array_keys($this->getPages()));

        return new FormDefinition([
            'id' => (string)$this->id,
            'handle' => (string)$this->handle,
            'title' => $this->title,
            'locale' => $context->locale,
            'siteId' => $context->siteId,
            'settings' => [
                'initialPageId' => (string)($this->getPages()[0]->id ?? ''),
                'submitMethod' => 'ajax',
                'validation' => [
                    'onBlur' => (bool)$this->settings->validationOnFocus,
                    'onSubmit' => (bool)$this->settings->validationOnSubmit,
                    'disableSubmitUntilValid' => (bool)$this->settings->disableSubmitButtonUntilValid,
                    'formErrorMessage' => $this->getFrontendErrorMessage(),
                ],
                'progress' => [
                    'enabled' => $this->hasMultiplePages(),
                    'calculation' => (string)$this->settings->progressCalculation,
                ],
            ],
            'pages' => $pages,
            'modules' => Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($this, ClientModule::RENDER_TARGET_FRONTEND),
            'submission' => [
                'endpoint' => UrlHelper::actionUrl('formie/client/submissions/submit'),
                'method' => 'POST',
                'encoding' => 'application/json',
                'actions' => ['back', 'save', 'submit'],
                'response' => [
                    'successMessageMode' => 'inline',
                    'redirectMode' => 'same-tab',
                ],
            ],
        ]);
    }

    public function getFrontendErrorMessage(): string
    {
        $message = $this->settings->errorMessage;

        if ($message->isEmpty()) {
            return '';
        }

        return $message->toHtml(null, true);
    }

    public function getPages(): array
    {
        return $this->getFormLayout()->getPages();
    }

    public function getRows(bool $includeDisabled = true): array
    {
        return $this->getFormLayout()->getRows($includeDisabled);
    }

    public function getFields(): array
    {
        return $this->getFormLayout()->getFields();
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        return $this->getFormLayout()->getFieldByHandle($handle);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return $this->getFormLayout()->getFieldById($id);
    }

    public function hasFieldConditions(): bool
    {
        foreach ($this->getFields() as $field) {
            if ($field->enableConditions) {
                return true;
            }

            if ($field instanceof ParentFieldInterface) {
                foreach ($field->getFields() as $nestedField) {
                    if ($nestedField->enableConditions) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function hasButtonConditions(): bool
    {
        foreach ($this->getPages() as $page) {
            if ($page->getPageSettings()->enableNextButtonConditions) {
                return true;
            }
        }

        return false;
    }

    public function hasPageConditions(): bool
    {
        foreach ($this->getPages() as $page) {
            if ($page->getPageSettings()->enablePageConditions) {
                return true;
            }
        }

        return false;
    }

    public function hasConditions(): bool
    {
        return $this->hasFieldConditions() || $this->hasButtonConditions() || $this->hasPageConditions();
    }

    public function getCpSubmissionFieldConditions(): string
    {
        $pluginDefault = Formie::$plugin->getSettings()->defaultCpSubmissionFieldConditions;

        return CpSubmissionFieldConditions::normalize($this->settings->cpSubmissionFieldConditions, $pluginDefault);
    }

    public function cpSubmissionFollowsFieldConditions(): bool
    {
        return $this->getCpSubmissionFieldConditions() !== CpSubmissionFieldConditions::SHOW_ALL;
    }

    public function cpSubmissionUsesMutedFieldConditions(): bool
    {
        return $this->getCpSubmissionFieldConditions() === CpSubmissionFieldConditions::MUTED;
    }

    public function hasMultiplePages(): bool
    {
        return count($this->getPages()) > 1;
    }

    public function getCurrentPage(): ?FieldLayoutPage
    {
        $this->_hydrateCurrentSubmissionFromRouteContext();
        $this->_hydrateCurrentSubmissionFromStorage();

        if ($this->_currentPage) {
            return $this->_currentPage;
        }

        $pages = $this->getPages();

        if (!$pages) {
            return null;
        }

        return $pages[0];
    }

    public function getPreviousPage(FieldLayoutPage $currentPage = null, Submission $submission = null, bool $defaultToFirst = false): ?FieldLayoutPage
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        $currentKey = end($pages);
        $currentPageId = $currentPage?->id ? (int)$currentPage->id : null;

        if ($currentPage) {
            while ($currentKey !== null && (int)$currentKey->id !== $currentPageId) {
                prev($pages);
                $currentKey = current($pages);
            }
        }

        $prev = prev($pages);

        // Handle if the next page should be conditionally skipped
        if ($prev && $submission && $prev->isConditionallyHidden($submission)) {
            // Call again to get the next non-hidden page.
            $prev = $this->getPreviousPage($prev, $submission, $defaultToFirst);
        }

        // Check to see if we've gone past the first page
        if (!$prev && $defaultToFirst) {
            return $pages[0] ?? null;
        }

        return $prev ?: null;
    }

    public function getNextPage(FieldLayoutPage $currentPage = null, Submission $submission = null): ?FieldLayoutPage
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        $currentKey = reset($pages);
        $currentPageId = $currentPage?->id ? (int)$currentPage->id : null;

        if ($currentPage) {
            while ($currentKey !== null && (int)$currentKey->id !== $currentPageId) {
                next($pages);
                $currentKey = current($pages);
            }
        }

        $next = next($pages);

        // Handle if the next page should be conditionally skipped
        if ($next && $submission && $next->isConditionallyHidden($submission)) {
            // Call again to get the next non-hidden page.
            $next = $this->getNextPage($next, $submission);
        }

        return $next ?: null;
    }

    public function getCurrentPageIndex(FieldLayoutPage $currentPage = null): int
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        // Return the index of the current page, in all our pages. Just for convenience
        if ($currentPage) {
            $index = array_search($currentPage->id, ArrayHelper::getColumn($pages, 'id'), true);

            if ($index) {
                return $index;
            }
        }

        return 0;
    }

    public function getPageProgressPercent(FieldLayoutPage $page = null): int
    {
        $pages = $this->getPages();
        $totalPages = count($pages);

        if ($totalPages < 1) {
            return 0;
        }

        $pageIndex = $this->getCurrentPageIndex($page);
        $mode = $this->settings->progressCalculation === 'page-position' ? 'page-position' : 'completion';

        if ($mode === 'page-position') {
            return (int)round((($pageIndex + 1) / $totalPages) * 100);
        }

        return (int)round(($pageIndex / $totalPages) * 100);
    }

    public function getPageIndex(FieldLayoutPage $page = null): ?int
    {
        $pages = $this->getPages();

        // Return the index of the page, in all our pages. Just for convenience
        if ($page) {
            return array_search($page->id, ArrayHelper::getColumn($pages, 'id'), true);
        }

        return null;
    }

    public function setCurrentPage(FieldLayoutPage $page = null): void
    {
        $this->_currentPage = $page;
    }

    public function resetCurrentPage(): void
    {
        $this->_currentPage = null;
    }

    public function isLastPage(FieldLayoutPage $currentPage = null): bool
    {
        return !((bool)$this->getNextPage($currentPage));
    }

    public function isFirstPage(FieldLayoutPage $currentPage = null): bool
    {
        return !((bool)$this->getPreviousPage($currentPage));
    }

    public function getCurrentSubmission(): ?Submission
    {
        // Check to see if we have any field settings applied. Because field settings are applied before
        // render, we don't have an easy way to check when we _don't_ set field settings. This function is
        // called most commonly for rendering a form without relying on `formie.renderForm()`.
        //
        // `setFieldSettings()` sets session variables for fields before render. So these variables don't
        // "bleed" between rendering the same form we need to remove them when necessary. This will check
        // when we _haven't_ set settings via `setFieldSettings()` and reset the session.
        if (!$this->_appliedFieldSettings && !$this->_appliedFormSettings) {
            $this->resetSnapshotData();
        }

        $this->_hydrateCurrentSubmissionFromRouteContext();
        $this->_hydrateCurrentSubmissionFromStorage();

        // If we have a current submission in the session, use that
        if ($this->_currentSubmission) {
            return $this->_currentSubmission;
        }

        // Or, if we're editing a submission
        if ($this->_editingSubmission) {
            return $this->_currentSubmission = $this->_editingSubmission;
        }

        $this->_hydrateCurrentSubmissionFromResumeToken();

        if ($this->_currentSubmission) {
            return $this->_currentSubmission;
        }

        return null;
    }

    public function setCurrentSubmission(?Submission $submission): void
    {
        $this->_currentSubmission = $submission;
    }

    public function setDraftContext(mixed $context): void
    {
        if ($context === null) {
            $this->_draftContext = null;
            return;
        }

        $value = trim((string)$context);
        $this->_draftContext = $value !== '' ? $value : null;
    }

    public function getDraftContext(): ?string
    {
        if ($this->_draftContext !== null) {
            return $this->_draftContext;
        }

        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return null;
        }

        $siteId = (int)Craft::$app->getSites()->getCurrentSite()->id;
        $path = trim((string)$request->getPathInfo(), '/');
        $path = $path !== '' ? $path : '__home__';

        return "url:{$siteId}:{$path}";
    }

    public function getDraftContextToken(): ?string
    {
        $context = $this->getDraftContext();

        if ($context === null) {
            return null;
        }

        $payload = Json::encode([
            'context' => $context,
            'formId' => (int)$this->id,
            'siteId' => (int)Craft::$app->getSites()->getCurrentSite()->id,
        ]);

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey($payload, $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public function resolveDraftContextToken(?string $token): ?string
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $decoded = base64_decode($token, true);

        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $decrypted = Craft::$app->getSecurity()->decryptByKey($decoded, $key);

        if (!is_string($decrypted) || $decrypted === '') {
            return null;
        }

        $payload = Json::decodeIfJson($decrypted);

        if (!is_array($payload)) {
            return null;
        }

        $context = isset($payload['context']) && is_string($payload['context']) ? trim($payload['context']) : null;
        $formId = isset($payload['formId']) ? (int)$payload['formId'] : null;
        $siteId = isset($payload['siteId']) ? (int)$payload['siteId'] : null;
        $currentSiteId = (int)Craft::$app->getSites()->getCurrentSite()->id;

        if ($context === null || $context === '') {
            return null;
        }

        if ($formId !== null && (int)$this->id !== 0 && $formId !== (int)$this->id) {
            return null;
        }

        if ($siteId !== null && $siteId !== $currentSiteId) {
            return null;
        }

        return $context;
    }

    public function getSubmitStateIdentity(): string
    {
        $context = $this->getDraftContext();
        $handle = $this->handle ?: 'form';

        if (!$context) {
            return $handle;
        }

        return "{$handle}:ctx:" . hash('sha256', $context);
    }

    public function getSubmitStateKey(): string
    {
        $key = $this->getSubmitStateIdentity();

        if ($this->_formId) {
            $key .= ':rid:' . hash('sha256', $this->_formId);
        }

        return $key;
    }

    public function getSubmitData(): array
    {
        return $this->_submitData;
    }

    public function addSubmitData(array $value): void
    {
        $this->_submitData[] = $value;
    }

    public function getFlashNamespace(): string
    {
        return 'flash:' . hash('sha256', $this->getSubmitStateKey());
    }

    public function resetCurrentSubmission(): void
    {
        $this->resetCurrentPage();

        $this->_currentSubmission = null;
    }

    public function setSubmission(?Submission $submission): void
    {
        $this->_editingSubmission = $submission;
        $this->_submissionEditToken = null;
    }

    public function isEditingSubmission(): bool
    {
        return (bool)$this->_editingSubmission;
    }

    public function getSubmissionEditToken(): ?string
    {
        if (!$this->_editingSubmission?->id || !$this->_editingSubmission->uid) {
            return null;
        }

        if ($this->_submissionEditToken === null) {
            $this->_submissionEditToken = Formie::$plugin->getSubmissionDrafts()->issueSubmissionEditToken($this, $this->_editingSubmission)?->token;
        }

        return $this->_submissionEditToken;
    }

    public function getActionUrl(): string
    {
        // In case people want to use `setSubmission()` but not change the endpoint so integrations will fire.
        if ($this->_actionUrl) {
            return $this->_actionUrl;
        }

        // If editing a submission, assume we're saving, not submitting. Unless this is an incomplete submission
        if ($this->isEditingSubmission() && !$this->_editingSubmission->isIncomplete) {
            return 'formie/submissions/save-submission';
        }

        return 'formie/submissions/submit';
    }

    public function setActionUrl(string $url): void
    {
        // In case people want to use `setSubmission()` but not change the endpoint so integrations will fire.
        $this->_actionUrl = $url;
    }

    public function getRelations(): string
    {
        if ($values = $this->_relations) {
            return StringHelper::encenc(Json::encode($values));
        }

        return '';
    }

    public function setRelations(array $elements = []): void
    {
        foreach ($elements as $element) {
            $this->_relations[] = [
                'id' => $element['id'],
                'siteId' => $element['siteId'],
                'type' => $element::class,
            ];
        }
    }

    public function getRelationsFromRequest()
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        $value = (string)Craft::$app->getRequest()->getBodyParam('relations', '');

        return Json::decode(StringHelper::decdec($value));
    }

    public function getPopulatedFieldValues(): string
    {
        if ($values = $this->_populatedFieldValues) {
            return StringHelper::encenc(Json::encode($values));
        }

        return '';
    }

    public function setPopulatedFieldValues(array $values): void
    {
        $this->_populatedFieldValues = $values;
    }

    public function getPopulatedFieldValuesFromRequest()
    {
        $value = (string)Craft::$app->getRequest()->getBodyParam('extraFields', '');

        return Json::decode(StringHelper::decdec($value));
    }

    public function getNotifications(): ?array
    {
        if ($this->_notifications === null) {
            $this->_notifications = Formie::$plugin->getNotifications()->getFormNotifications($this);
        }

        return $this->_notifications;
    }

    public function setNotifications(array $notifications): void
    {
        $this->_notifications = $notifications;
    }

    public function getEnabledNotifications(): array
    {
        return ArrayHelper::where($this->getNotifications(), 'enabled', true);
    }

    public function validateNotifications(): void
    {
        foreach ($this->getNotifications() as $notificationIndex => $notification) {
            if (!$notification->validate()) {
                foreach ($notification->getErrors() as $key => $error) {
                    $this->addError("notifications.$notificationIndex.$key", $error[0]);
                }
            }
        }
    }

    public function setRedirectUrl(string $value): void
    {
        $this->_redirectUrl = StringHelper::sanitizeRedirectUrl($value);
    }

    public function getRedirectUrl(bool $checkLastPage = true, bool $includeQueryString = true): string
    {
        $request = Craft::$app->getRequest();
        $url = '';

        // We don't want to show the redirect URL on unfinished multi-page forms, so check first
        if ($this->settings->submitMethod == 'page-reload') {
            if ($checkLastPage && !$this->isLastPage()) {
                return $url;
            }
        }

        // Allow specific override of redirect URL, likely from templates
        if ($this->_redirectUrl) {
            return $this->_redirectUrl;
        }

        // Allow settings to statically set the redirect URL (from templates)
        if ($this->settings->redirectUrl) {
            $url = $this->settings->redirectUrl;
        } else if ($this->settings->submitAction == 'entry' && $this->getRedirectEntry()) {
            $url = $this->getRedirectEntry()->url;
        } else if ($this->settings->submitAction == 'url' && $this->settings->submitActionUrl) {
            $url = $this->settings->submitActionUrl;

            if (($submission = $this->getCurrentSubmission()) && is_string($url)) {
                $url = References::parseContent($url, $submission);
            }
        }

        // Add any query params to the URL automatically (think utm)
        if ($url && $request->getIsSiteRequest() && $includeQueryString) {
            // But only add query strings if they don't override any set for the redirect URL already
            // For example, the request URL might be `submissionId=12` but the redirect is `submissionId={id}`
            // we wouldn't want to overwrite the latter with the former. Specifically set URLs take precedence.
            $requestParams = $request->getQueryStringWithoutPath();
            $urlParams = explode('?', $url)[1] ?? '';

            // UrlHelper will take care of normalization. The important bit is to override request params if
            // there's any duplication.
            $url = UrlHelper::url($url, $requestParams . '&' . $urlParams);
        }

        // Handle any UTF characters defined in the URL and encode them properly
        $url = mb_convert_encoding($url, 'UTF-8', 'ISO-8859-1');
        $url = StringHelper::sanitizeRedirectUrl($url);

        return $url;
    }

    public function getRedirectEntry(): ?Entry
    {
        if (!$this->submitActionEntryId) {
            return null;
        }

        if (!$this->_submitActionEntry) {
            $siteId = $this->submitActionEntrySiteId ?: '*';

            $this->_submitActionEntry = Craft::$app->getEntries()->getEntryById($this->submitActionEntryId, $siteId);
        }

        return $this->_submitActionEntry;
    }

    public function setRedirectEntry(Entry $entry): void
    {
        $this->_submitActionEntry = $entry;
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    public function getPageFieldErrors(Submission $submission): array
    {
        $errors = [];

        foreach ($this->getPages() as $page) {
            $errors[$page->id] = $page->getFieldErrors($submission);
        }

        return array_filter($errors);
    }

    public function renderTemplate(array|string $components, array $variables = []): string
    {
        $view = Craft::$app->getView();
        
        // Normalise the components to allow for a single component
        if (!is_array($components)) {
            $components = [$components];
        }

        // Check for form templates, and a custom set of templates
        if (($template = $this->getTemplate()) && $template->useCustomTemplates && $template->template) {
            // Find the first available, resolved template in potential multiple components
            foreach ($components as $component) {
                $path = $template->template . DIRECTORY_SEPARATOR . $component;

                // Ensure that the path exists in site templates
                if ($view->doesTemplateExist($path, View::TEMPLATE_MODE_SITE)) {
                    return $view->renderTemplate($path, $variables, View::TEMPLATE_MODE_SITE);
                }
            }
        }

        // Otherwise, fall back on the default Formie templates.
        // Find the first available, resolved template in potential multiple components
        foreach ($components as $component) {
            $templatePath = 'formie/_special/form-template' . DIRECTORY_SEPARATOR . $component;

            // Note we need to include `.html` for default templates, because of users potentially setting `defaultTemplateExtensions`
            // which would be unable to find our templates if they disallow `.html`.
            // Check for `form.html` or `form/index.html` because we have to try resolving on our own...
            $paths = [
                $templatePath . '.html',
                $templatePath . DIRECTORY_SEPARATOR . 'index.html',

                // Also include searching the component path itself, for custom fields that don't resolve to Formie
                $component,
            ];

            foreach ($paths as $path) {
                if ($view->doesTemplateExist($path, View::TEMPLATE_MODE_CP)) {
                    return $view->renderTemplate($path, $variables, View::TEMPLATE_MODE_CP);
                }
            }
        }

        return '';
    }

    public function getRenderId(bool $useCache = true): string
    {
        if ($this->_formId && $useCache) {
            return $this->_formId;
        }

        // Keep render identity deterministic per render-slot instance (state identity + sequence),
        // so it stays stable across redirect reloads while avoiding collisions for duplicates.
        $handle = $this->handle ?: 'form';
        $stateHash = substr(hash('sha256', $this->getSubmitStateIdentity()), 0, 10);
        $this->_renderSequence = $this->_nextRenderSequence();

        return $this->_formId = "formie-{$handle}-{$stateHash}-{$this->_renderSequence}";
    }

    public function setRenderId(string $value): void
    {
        $this->_formId = $value;
        $this->_renderSequence = null;
    }

    public function renderSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = $this->defineFieldSlotTag($key, $context);
        $tag = Formie::$plugin->getThemeConfigService()->applyFormTagConfig($this, $key, $tag, $context);

        $event = new ModifyFormSlotTagEvent([
            'form' => $this,
            'tag' => $tag,
            'key' => $key,
            'context' => $context->toArray(),
        ]);

        $this->trigger(static::EVENT_MODIFY_SLOT_TAG, $event);
        $this->triggerDeprecatedHtmlTagEvent($event);

        return $event->tag;
    }

    public function getFrontendTheme(): string
    {
        return $this->_frontendTheme;
    }

    public function setFrontendTheme(string $value): void
    {
        $this->_frontendTheme = $value;
    }

    public function getThemeConfig(): array
    {
        return $this->_themeConfig;
    }

    public function setThemeConfig(array $value): void
    {
        /* @var Settings $pluginSettings */
        $pluginSettings = Formie::$plugin->getSettings();

        $this->_themeConfig = Formie::$plugin->getThemeConfigService()->mergeConfigLayers($pluginSettings->themeConfig, $value);
    }

    public function getThemeConfigItem(string $key): array|bool|null
    {
        return ThemeConfigLegacyKeys::getMergedThemeConfigItem($this->_themeConfig, __METHOD__, $key);
    }

    public function getFrontendThemeClasses(): array
    {
        return Formie::$plugin->getThemeConfigService()->buildFrontendClassMap($this);
    }

    public function getFrontendThemeClassMap(): array
    {
        return $this->getFrontendThemeClasses();
    }

    public function getFrontendTemplateOption(string $option): bool
    {
        $output = true;

        if ($template = $this->getTemplate()) {
            $output = (bool)$template->$option;
        }

        return $output;
    }

    public function getFrontendTemplateLocation(string $location)
    {
        $output = null;
        if ($location === 'outputCssLocation') {
            $output = FormTemplate::PAGE_HEADER;
        }

        if ($location === 'outputJsLocation') {
            $output = FormTemplate::PAGE_FOOTER;
        }

        if ($template = $this->getTemplate()) {
            $output = $template->$location;
        }

        return $output;
    }

    public function getSessionKey(): ?string
    {
        return $this->_sessionKey;
    }

    public function setSessionKey(?string $value): void
    {
        $this->_sessionKey = $value;
    }

    public function setSettings(array $settings, bool $updateSnapshot = true): void
    {
        $this->settings->setAttributes($settings, false);

        // Set snapshot data to ensure it's persisted
        if ($updateSnapshot) {
            $this->setSnapshotData('form', $settings);

            // Save this, so we know when we're applying form settings later
            $this->_appliedFormSettings = true;
        }
    }

    public function setPageSettings(int|string $handleOrIndex, array $settings): void
    {
        $pages = $this->pages;

        if (is_string($handleOrIndex)) {
            $pages = ArrayHelper::index($this->pages, 'handle');
        }

        // Get the page settings so we only override what we want
        $pageSettings = $pages[$handleOrIndex]->pageSettings ?? null;

        if ($pageSettings) {
            $pageSettings->setAttributes($settings, false);
        }
    }

    public function setFieldSettings(string $handle, array $settings, bool $updateSnapshot = true): void
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

            // Update our snapshot data with these settings
            if ($updateSnapshot) {
                $this->setSnapshotData('fields', [$handle => $settings]);
            }
        }

        // Save this, so we know when we're applying field settings later
        $this->_appliedFieldSettings = true;
    }

    public function setIntegrationSettings(string $handle, array $settings, bool $updateSnapshot = true): void
    {
        // Get the integration settings so we only override what we want
        $integrationSettings = $this->settings->integrations[$handle] ?? [];
        
        // Update the integration settings
        $this->settings->integrations[$handle] = array_merge($integrationSettings, $settings);

        // Save just the integrations (all integrations)
        $this->settings->setAttributes(['integrations' => $this->settings->integrations], false);

        // Set snapshot data to ensure it's persisted
        if ($updateSnapshot) {
            // We have to save _all_ integration settings due to how it's applied later by `setAttributes()`
            $this->setSnapshotData('form', ['integrations' => $this->settings->integrations]);

            // Save this, so we know when we're applying form settings later
            $this->_appliedFormSettings = true;
        }
    }

    public function getSnapshotData(string $key = null)
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return [];
        }

        $snapshotData = Session::get($this->_getSessionKey('snapshot'));

        if ($key) {
            return $snapshotData[$key] ?? [];
        }

        return $snapshotData ?? [];
    }

    public function setSnapshotData(string $key, mixed $data): void
    {
        // The lack of `Session::exists()` is deliberate, as we want to set snapshot data before the session might be ready
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        // Get any existing snapshot data and merge, in case we set multiple times
        $snapshotData = $this->getSnapshotData();
        $currentData = $snapshotData[$key] ?? [];
        $snapshotData[$key] = array_merge($currentData, $data);

        Session::set($this->_getSessionKey('snapshot'), $snapshotData);
    }

    public function resetSnapshotData(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return;
        }

        Session::remove($this->_getSessionKey('snapshot'));
    }

    public function getRequestToken(): string
    {
        if (is_string($this->_requestToken) && trim($this->_requestToken) !== '') {
            return $this->_requestToken;
        }

        $requestToken = Craft::$app->getSecurity()->generateRandomString();
        $this->_requestToken = $requestToken;

        return $requestToken;
    }

    public function setRequestToken(?string $requestToken): void
    {
        if (!is_string($requestToken)) {
            return;
        }

        $requestToken = trim($requestToken);

        if ($requestToken === '') {
            return;
        }

        $this->_requestToken = $requestToken;
    }

    public function resetRequestToken(): void
    {
        $this->_requestToken = null;
    }

    public function isAvailable(): bool
    {
        if ($this->settings->requireUser) {
            if (!Craft::$app->getUser()->getIdentity()) {
                return false;
            }
        }

        if ($this->settings->scheduleForm) {
            if (!$this->isScheduleActive()) {
                return false;
            }
        }

        if ($this->settings->limitSubmissions) {
            if (!$this->isWithinSubmissionsLimit()) {
                return false;
            }
        }

        return true;
    }

    public function isScheduleActive(): bool
    {
        return !$this->isBeforeSchedule() && !$this->isAfterSchedule();
    }

    public function isBeforeSchedule(): bool
    {
        if ($this->settings->scheduleForm && $this->settings->scheduleFormStart) {
            return !DateTimeHelper::isInThePast($this->settings->scheduleFormStart);
        }
        
        return false;
    }

    public function isAfterSchedule(): bool
    {
        if ($this->settings->scheduleForm && $this->settings->scheduleFormEnd) {
            return DateTimeHelper::isInThePast($this->settings->scheduleFormEnd);
        }
        
        return false;
    }

    public function isWithinSubmissionsLimit(): bool
    {
        if ($this->settings->limitSubmissions) {
            $limit = $this->settings->limitSubmissionsNumber;

            if (!$limit || $limit < 1) {
                return true;
            }

            $query = Submission::find()->formId($this->id);

            if ($this->settings->limitSubmissionsType === 'total') {
                $submissions = $query->count();
            } else if ($this->settings->limitSubmissionsType === 'day') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('today'));
                $endDate = DateTimeHelper::toDateTime(new DateTime('tomorrow'));

                $submissions = $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)])->count();
            } else if ($this->settings->limitSubmissionsType === 'week') {
                // PHP dates start on a Monday, but we assume to backtrack to Sunday
                $startDate = DateTimeHelper::toDateTime(new DateTime('monday this week'))->modify('-1 day');
                $endDate = DateTimeHelper::toDateTime(new DateTime('monday next week'))->modify('-1 day');

                $submissions = $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)])->count();
            } else if ($this->settings->limitSubmissionsType === 'month') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('first day of this month'))->setTime(0, 0, 0);
                $endDate = DateTimeHelper::toDateTime(new DateTime('first day of next month'))->setTime(0, 0, 0);

                $submissions = $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)])->count();
            } else if ($this->settings->limitSubmissionsType === 'year') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('first day of January'))->setTime(0, 0, 0);
                $endDate = DateTimeHelper::toDateTime(new DateTime('first day of January next year'))->setTime(0, 0, 0);

                $submissions = $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)])->count();
            } else {
                $submissions = $query->count();
            }

            if ($submissions >= $limit) {
                return false;
            }
        }
        
        return true;
    }

    public function getDuplicateAttributes(): array
    {
        // Generate a new handle, nicely
        $formHandles = (new Query())
            ->select(['handle'])
            ->from(Table::FORMIE_FORMS)
            ->column();

        // Prepare the layout/pages/rows/fields by stripping out IDs and UIDs. 
        // Use `unserialize/serialize` instead of `clone()` to deeply clone objects.
        $formLayout = unserialize(serialize($this->getFormLayout()));
        $this->_clearLayoutIdentifiers($formLayout);

        $formSettings = clone $this->settings;
        $formSettings->setForm(null);

        $notifications = [];

        foreach ($this->getNotifications() as $notification) {
            $newNotification = clone $notification;
            $newNotification->id = null;
            $newNotification->formId = null;
            $newNotification->uid = null;

            $notifications[] = $newNotification;
        }

        // Prepare new data for the duplicated form
        return [
            'handle' => HandleHelper::getUniqueHandle($formHandles, $this->handle),
            'title' => Craft::t('formie', '{title} Copy', ['title' => $this->title]),
            'formLayout' => $formLayout,
            'notifications' => $notifications,
            'settings' => $formSettings,
        ];
    }

    public function beforeSave(bool $isNew): bool
    {
        $settings = Formie::$plugin->getSettings();
        $fieldsService = Craft::$app->getFields();
        $userId = Craft::$app->getUser()->getId();

        if ($userId) {
            if ($isNew) {
                $this->createdById = $userId;
            }

            $this->updatedById = $userId;
        }

        // Set the default template from settings, if not already set - for new forms
        if ($isNew && !$this->templateId) {
            $this->templateId = $settings->getDefaultFormTemplateId();
        }

        // Set the default status, if not set
        if (!$this->defaultStatusId) {
            $this->defaultStatusId = $this->getDefaultStatus()?->id;
        }

        // Ensure any parent validations run first
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        // If a new form, apply captcha integration defaults - but not if applying a stencil
        if ($isNew && !$this->isApplyingStencil) {
            Formie::$plugin->getFormDefaults()->applyCaptchaDefaultsToNewForm($this);
        }

        // Save the field layout as the last step
        if (!Formie::$plugin->getFields()->saveLayout($this->getFormLayout())) {
            $this->addErrors($this->getFormLayout()->getErrors());

            return false;
        }

        return true;
    }

    public function afterSave(bool $isNew): void
    {
        // Get the form record
        if (!$isNew) {
            $record = FormRecord::findOne($this->id);

            if (!$record) {
                throw new Exception("Invalid form ID: $this->id");
            }
        } else {
            $record = new FormRecord();
            $record->id = $this->id;
        }

        $record->handle = $this->handle;
        $record->settings = $this->getSettings();
        $record->layoutId = $this->getFormLayout()->id;
        $record->templateId = $this->templateId;
        $record->groupId = $this->groupId ?: null;
        $record->submitActionEntryId = $this->submitActionEntryId;
        $record->submitActionEntrySiteId = $this->submitActionEntrySiteId;
        $record->defaultStatusId = $this->defaultStatusId;
        $record->dataRetention = $this->dataRetention;
        $record->dataRetentionValue = $this->dataRetentionValue;
        $record->fileUploadsAction = $this->fileUploadsAction;
        $record->userDeletedAction = $this->userDeletedAction;
        $record->createdById = $this->createdById;
        $record->updatedById = $this->updatedById;

        $record->save(false);
        
        $this->layoutId = (int)$record->layoutId;

        // Handle notifications
        $notificationsService = Formie::$plugin->getNotifications();
        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            $notification->formId = $this->id;
            $notificationsService->saveNotification($notification);
        }

        // Prune deleted notifications
        if (!$isNew) {
            foreach ($notificationsService->getFormNotifications($this) as $notification) {
                if (!ArrayHelper::contains($notifications, 'id', $notification->id)) {
                    $notificationsService->deleteNotificationById($notification->id);
                }
            }
        }

        // Check if we need to update any submission content due to field changes
        Formie::$plugin->getSubmissions()->updateSubmissionContent($this);

        parent::afterSave($isNew);
    }

    public function afterDelete(): void
    {
        $layoutId = $this->layoutId;

        // Delete any submissions made on this form.
        $submissions = Submission::find()->formId($this->id)->all();
        $elementsService = Craft::$app->getElements();

        foreach ($submissions as $submission) {
            if (!$elementsService->deleteElement($submission)) {
                Formie::error("Unable to delete submission ”{$submission->id}” for form ”{$this->id}”: " . Json::encode($submission->getErrors()) . ".");
            }
        }

        if ($layoutId) {
            Formie::$plugin->getFields()->deleteLayoutById($layoutId);
        }
    }

    public function beforeRestore(): bool
    {
        if (!parent::beforeRestore()) {
            return false;
        }

        $i = 0;
        $handle = $this->handle;

        while (Form::find()->handle($handle)->exists()) {
            $i++;
            $handle = $this->handle . $i;
        }

        // Ensure that when restoring the handle is still valid and unique
        Db::update(Table::FORMIE_FORMS, ['handle' => $handle], ['id' => $this->id]);

        $this->handle = $handle;

        return true;
    }

    public function afterRestore(): void
    {
        $db = Craft::$app->getDb();

        // Restore any submissions deleted
        $submissions = Submission::find()->formId($this->id)->trashed(true)->all();
        $elementsService = Craft::$app->getElements();

        foreach ($submissions as $submission) {
            if (!$elementsService->restoreElement($submission)) {
                Formie::error("Unable to restore submission ”{$submission->id}” for form ”{$this->id}”: " . Json::encode($submission->getErrors()) . ".");
            }
        }

        parent::afterRestore();
    }

    public function defineFieldsSchema(): array
    {
        return SchemaHelper::schemaNode([
            [
                '$cmp' => 'FieldBuilder',
            ],
        ]);
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return SchemaHelper::schemaNode([
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Form Appearance'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Display Form Title'),
                'instructions' => Craft::t('formie', 'Whether the title of this form should be included on the page when rendering the form.'),
                'name' => 'settings.displayFormTitle',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Display Current Page Title'),
                'instructions' => Craft::t('formie', 'Whether the title of the current page should be included when rendering the form.'),
                'name' => 'settings.displayCurrentPageTitle',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Display Page Tabs'),
                'instructions' => Craft::t('formie', 'Whether tabs of all pages should be included on the page when rendering the form. This is only applicable for forms with more than one page..'),
                'name' => 'settings.displayPageTabs',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Display Page Progress'),
                'instructions' => Craft::t('formie', 'Whether to show a progress bar of the page completion. This is only applicable for forms with more than one page.'),
                'name' => 'settings.displayPageProgress',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Page Progress Calculation'),
                'instructions' => Craft::t('formie', 'Choose whether the progress bar should reflect completed progress or current page position.'),
                'name' => 'settings.progressCalculation',
                'options' => [
                    [
                        'value' => 'completion',
                        'label' => Craft::t('formie', 'Completion'),
                    ],
                    [
                        'value' => 'page-position',
                        'label' => Craft::t('formie', 'Page position'),
                    ],
                ],
                'if' => 'settings.displayPageProgress',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Page Progress Position'),
                'instructions' => Craft::t('formie', 'Select the position of the page progress indicator in the form.'),
                'name' => 'settings.progressPosition',
                'options' => [
                    [
                        'value' => 'start',
                        'label' => Craft::t('formie', 'Start of form'),
                    ],
                    [
                        'value' => 'end',
                        'label' => Craft::t('formie', 'End of form'),
                    ],
                ],
                'if' => 'settings.displayPageProgress',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Scroll To Top'),
                'instructions' => Craft::t('formie', 'Whether for multi-page forms, the page should automatically scroll to the top of the next page after submission.'),
                'name' => 'settings.scrollToTop',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Form Templates'),
                'instructions' => Craft::t('formie', 'Select the templates this form should use.'),
                'name' => 'templateId',
                'options' => array_merge([
                    [
                        'value' => '',
                        'label' => Craft::t('formie', 'Default Formie Template'),
                    ],
                ], array_map(function($template) {
                    return [
                        'value' => $template->id,
                        'label' => $template->name,
                    ];
                }, Formie::$plugin->getFormTemplates()->getAllTemplates())),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Label Position'),
                'instructions' => Craft::t('formie', 'Fields will by default have their label position set to this option.'),
                'name' => 'settings.defaultLabelPosition',
                'options' => Formie::$plugin->getFields()->getLabelPositionsOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Instructions Position'),
                'instructions' => Craft::t('formie', 'Fields will by default have their instructions position set to this option.'),
                'name' => 'settings.defaultInstructionsPosition',
                'options' => Formie::$plugin->getFields()->getInstructionsPositionsOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Field Error Position'),
                'instructions' => Craft::t('formie', 'Fields will by default have their validation error position set to this option.'),
                'name' => 'settings.defaultErrorMessagePosition',
                'options' => Formie::$plugin->getFields()->getErrorMessagePositionsOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Required Field Indicator'),
                'instructions' => Craft::t('formie', 'Select how to show required fields.'),
                'name' => 'settings.requiredIndicator',
                'options' => [
                    [
                        'value' => 'asterisk',
                        'label' => Craft::t('formie', 'Asterisk for required fields'),
                    ],
                    [
                        'value' => 'optional',
                        'label' => Craft::t('formie', 'Optional indicator for non-required fields'),
                    ],
                ],
            ]),
        ]);
    }

    public function defineBehaviourSchema(): array
    {
        return SchemaHelper::schemaNode([
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Form Behaviour'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Submission Method'),
                'instructions' => Craft::t('formie', 'Whether to reload the page when this form is submitted, or use Ajax to send this form without reloading the page.'),
                'name' => 'settings.submitMethod',
                'options' => [
                    [
                        'label' => Craft::t('formie', 'Page Reload (Server-side)'),
                        'value' => 'page-reload',
                    ],
                    [
                        'label' => Craft::t('formie', 'Ajax (Client-side)'),
                        'value' => 'ajax',
                    ],
                ],
                'if' => '!formBuilder.ajaxSubmissionForced',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Submission Method'),
                'instructions' => Craft::t('formie', 'Whether to reload the page when this form is submitted, or use Ajax to send this form without reloading the page.'),
                'name' => 'settings.submitMethod',
                'options' => [
                    [
                        'label' => Craft::t('formie', 'Ajax (Client-side)'),
                        'value' => 'ajax',
                    ],
                ],
                'warning' => Craft::t('formie', 'You must use Ajax submissions when using some payment integrations in your form.'),
                'if' => 'formBuilder.ajaxSubmissionForced',
            ]),
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'After Submit'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::selectField([
                'name' => 'settings.submitAction',
                'label' => Craft::t('formie', 'Action on Submit'),
                'instructions' => Craft::t('formie', 'When a user submits this form, I want to:'),
                'options' => [
                    [
                        'label' => Craft::t('formie', 'Display a message'),
                        'value' => 'message',
                    ],
                    [
                        'label' => Craft::t('formie', 'Redirect to an entry'),
                        'value' => 'entry',
                    ],
                    [
                        'label' => Craft::t('formie', 'Redirect to a URL'),
                        'value' => 'url',
                    ],
                    [
                        'label' => Craft::t('formie', 'Reload the page'),
                        'value' => 'reload',
                        'if' => 'settings.submitMethod == "page-reload"',
                    ],
                    [
                        'label' => Craft::t('formie', 'Reset form values'),
                        'value' => 'reset',
                        'if' => 'settings.submitMethod == "ajax"',
                    ],
                ],
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.submitAction == "message"',
                'children' => [
                    SchemaHelper::lightswitchField([
                        'label' => Craft::t('formie', 'Hide Form'),
                        'instructions' => Craft::t('formie', 'Whether to hide the form and only show the success message.'),
                        'name' => 'settings.submitActionFormHide',
                    ]),
                    SchemaHelper::richTextField(array_merge([
                        'label' => Craft::t('formie', 'Submission Message'),
                        'instructions' => Craft::t('formie', 'This text will be shown after submission, as a success message.'),
                        'name' => 'settings.submitActionMessage',
                    ], RichTextHelper::getRichTextConfig('forms.submitActionMessage'))),
                    SchemaHelper::numberField([
                        'label' => Craft::t('formie', 'Submission Message Timeout'),
                        'instructions' => Craft::t('formie', 'The number of seconds to automatically hide the message. Leave empty to disable hiding.'),
                        'name' => 'settings.submitActionMessageTimeout',
                    ]),
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Submission Message Position'),
                        'instructions' => Craft::t('formie', 'Where to position the success message in the form, when shown.'),
                        'name' => 'settings.submitActionMessagePosition',
                        'options' => [
                            ['label' => Craft::t('formie', 'None'), 'value' => ''],
                            ['label' => Craft::t('formie', 'Top of Form'), 'value' => 'top-form'],
                            ['label' => Craft::t('formie', 'Bottom of Form'), 'value' => 'bottom-form'],
                        ],
                    ]),
                ],
            ],
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.submitAction == "entry"',
                'children' => [
                    SchemaHelper::elementSelectField([
                        'label' => Craft::t('formie', 'Redirect Entry'),
                        'instructions' => Craft::t('formie', 'Select an entry for the user to be redirected to.'),
                        'name' => 'submitActionEntry',
                        'limit' => 1,
                        'elementType' => 'craft\\elements\\Entry',
                        'showSiteMenu' => true,
                    ]),
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Redirect Option'),
                        'instructions' => Craft::t('formie', 'How to redirect the user after submission, whether in the same tab, or a new tab.'),
                        'name' => 'settings.submitActionTab',
                        'options' => [
                            ['label' => Craft::t('formie', 'Redirect on the same tab'), 'value' => 'same-tab'],
                            ['label' => Craft::t('formie', 'Redirect on a new tab'), 'value' => 'new-tab'],
                        ],
                    ]),
                ],
            ],
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.submitAction == "url"',
                'children' => [
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Redirect URL'),
                        'instructions' => Craft::t('formie', 'The full URL that the user to be redirected to.'),
                        'name' => 'settings.submitActionUrl',
                    ]),
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Redirect Option'),
                        'instructions' => Craft::t('formie', 'How to redirect the user after submission, whether in the same tab, or a new tab.'),
                        'name' => 'settings.submitActionTab',
                        'options' => [
                            ['label' => Craft::t('formie', 'Redirect on the same tab'), 'value' => 'same-tab'],
                            ['label' => Craft::t('formie', 'Redirect on a new tab'), 'value' => 'new-tab'],
                        ],
                    ]),
                ],
            ],
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.submitAction == "reload"',
                'children' => Craft::t('formie', 'This will reload the page, clearing the form of values, and showing no success message.'),
            ],
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.submitAction == "reset"',
                'children' => Craft::t('formie', 'This will clear the form of values, and showing no success message.'),
            ],
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Loading Indicator'),
                'instructions' => Craft::t('formie', 'Whether to show a loading indicator when submitting the form. This will be shown on the submit button.'),
                'name' => 'settings.loadingIndicator',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Spinner'), 'value' => 'spinner'],
                    ['label' => Craft::t('formie', 'Text'), 'value' => 'text'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Loading Indicator Text'),
                'instructions' => Craft::t('formie', 'Text shown over the submit button, when in the loading state.'),
                'name' => 'settings.loadingIndicatorText',
                'if' => 'settings.loadingIndicator == "text"',
            ]),
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Validation'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Validate Form on Submit'),
                'instructions' => Craft::t('formie', 'Whether to validate the form client-side, when the user submits the form. This will show errors as soon as the submit button is pressed. Forms will also always be validated server-side.'),
                'name' => 'settings.validationOnSubmit',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Validate When Typing'),
                'instructions' => Craft::t('formie', 'Whether to validate each field as the user types, so that errors will appear immediately.'),
                'name' => 'settings.validationOnFocus',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Disable Submit Button Until Valid'),
                'instructions' => Craft::t('formie', 'Whether to disable the submit button until the current page passes validation. This can help users identify missing required fields before submitting.'),
                'name' => 'settings.disableSubmitButtonUntilValid',
            ]),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Error Message'),
                'instructions' => Craft::t('formie', 'This text will be shown when an error on the submission occurs.'),
                'name' => 'settings.errorMessage',
            ], RichTextHelper::getRichTextConfig('forms.errorMessage'))),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Error Message Position'),
                'instructions' => Craft::t('formie', 'Where to position the error message in the form, when shown.'),
                'name' => 'settings.errorMessagePosition',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Top of Form'), 'value' => 'top-form'],
                    ['label' => Craft::t('formie', 'Bottom of Form'), 'value' => 'bottom-form'],
                ],
            ]),
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Restrictions'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Require Logged-in User'),
                'instructions' => Craft::t('formie', 'Whether this form can be viewed only by logged-in users.'),
                'name' => 'settings.requireUser',
            ]),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Message'),
                'instructions' => Craft::t('formie', 'The message displayed to users who are not logged in.'),
                'name' => 'settings.requireUserMessage',
                'if' => 'settings.requireUser',
            ], RichTextHelper::getRichTextConfig('forms.requireUserMessage'))),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Schedule Form'),
                'instructions' => Craft::t('formie', 'Whether this form should only be available on a schedule.'),
                'name' => 'settings.scheduleForm',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.scheduleForm',
                'children' => [
                    SchemaHelper::dateField([
                        'label' => Craft::t('formie', 'Schedule Start Date'),
                        'instructions' => Craft::t('formie', 'Set when the form should be available from.'),
                        'name' => 'settings.scheduleFormStart',
                    ]),
                    SchemaHelper::dateField([
                        'label' => Craft::t('formie', 'Schedule End Date'),
                        'instructions' => Craft::t('formie', 'Set when the form should be available until.'),
                        'name' => 'settings.scheduleFormEnd',
                    ]),
                    SchemaHelper::richTextField(array_merge([
                        'label' => Craft::t('formie', 'Pending Message'),
                        'instructions' => Craft::t('formie', 'The message displayed when the current time is before the scheduled start date.'),
                        'name' => 'settings.scheduleFormPendingMessage',
                    ], RichTextHelper::getRichTextConfig('forms.scheduleFormPendingMessage'))),
                    SchemaHelper::richTextField(array_merge([
                        'label' => Craft::t('formie', 'Expired Message'),
                        'instructions' => Craft::t('formie', 'The message displayed when the current time is after the scheduled start date.'),
                        'name' => 'settings.scheduleFormExpiredMessage',
                    ], RichTextHelper::getRichTextConfig('forms.scheduleFormExpiredMessage'))),
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Submissions'),
                'instructions' => Craft::t('formie', 'Whether submissions for this form should be limited to a number.'),
                'name' => 'settings.limitSubmissions',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'form-builder-group',
                ],
                'if' => 'settings.limitSubmissions',
                'children' => [
                    SchemaHelper::fieldWrap([
                        'label' => Craft::t('formie', 'Number of Submissions'),
                        'instructions' => Craft::t('formie', 'The number of submissions to allow.'),
                        'children' => [
                            SchemaHelper::numberField([
                                'name' => 'settings.limitSubmissionsNumber',
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'settings.limitSubmissionsType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'total'), 'value' => 'total'],
                                    ['label' => Craft::t('formie', 'per day'), 'value' => 'day'],
                                    ['label' => Craft::t('formie', 'per week'), 'value' => 'week'],
                                    ['label' => Craft::t('formie', 'per month'), 'value' => 'month'],
                                    ['label' => Craft::t('formie', 'per year'), 'value' => 'year'],
                                ],
                            ]),
                        ],
                    ]),
                    SchemaHelper::richTextField(array_merge([
                        'label' => Craft::t('formie', 'Message'),
                        'instructions' => Craft::t('formie', 'The message displayed to once the limit has been reached.'),
                        'name' => 'settings.limitSubmissionsMessage',
                    ], RichTextHelper::getRichTextConfig('forms.limitSubmissionsMessage'))),
                ],
            ],
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Advanced'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Restore In-Progress Submissions Automatically'),
                'instructions' => Craft::t('formie', 'When enabled, visitors who return to this form can continue their in-progress submission automatically. Disable this to start fresh unless they use a resume link.'),
                'name' => 'settings.automaticSubmissionState',
            ]),
        ]);
    }

    public function defineNotificationsSchema(): array
    {
        $notificationsSchema = Formie::$plugin->getNotifications()->getNotificationsSchema();
        $compiledSchema = (is_array($notificationsSchema) && isset($notificationsSchema['schema']) && isset($notificationsSchema['fieldEntries']))
            ? $notificationsSchema
            : SchemaHelper::compileSchema($notificationsSchema);

        return SchemaHelper::schemaNode([        
            '$cmp' => 'Notifications',
            'schema' => $compiledSchema['schema'],
            'schemaIndex' => $compiledSchema,
            'schemaChildPrefix' => 'notifications.*.',
        ]);
    }

    public function defineIntegrationsSchema(): array
    {
        return SchemaHelper::schemaNode([        
            [
                '$cmp' => 'Integrations',
            ],
        ]);
    }

    public function defineUsageSchema(): array
    {
        return SchemaHelper::schemaNode([
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Form Usage'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            [
                '$cmp' => 'FormUsage',
            ],
        ]);
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        $maxFormHandleLength = HandleHelper::getMaxFormHandle();
        $reservedFormHandles = $this->getBuilderReservedHandles();
        $builderEntityLabel = $this->getBuilderEntityLabel();
        $builderEntityTitle = $this->getBuilderEntityLabel(true);

        return SchemaHelper::schemaNode([
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', '{entity} Settings', ['entity' => $builderEntityTitle]),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Name'),
                'instructions' => Craft::t('formie', 'What this {entity} will be called in the control panel.', ['entity' => $builderEntityLabel]),
                'name' => 'title',
                'required' => true,
            ]),
            ...($formGroupField = $this->_formGroupSelectSchemaField()) ? [$formGroupField] : [],
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Submissions'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Status'),
                'instructions' => Craft::t('formie', 'The default status to be assigned to new submissions.'),
                'name' => 'defaultStatusId',
                'options' => array_map(function($status) {
                    return [
                        'value' => $status->id,
                        'label' => $status->name,
                        'status' => $status->color,
                    ];
                }, Formie::$plugin->getStatuses()->getAllStatuses()),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Status Rules'),
                'instructions' => Craft::t('formie', 'Automatically change the submission status when conditions match. Rules are evaluated in order; the first match wins.'),
                'name' => 'settings.enableStatusRules',
            ]),
            [
                '$field' => 'statusRules',
                'name' => 'settings.statusRules',
                'if' => 'settings.enableStatusRules',
                'label' => Craft::t('formie', 'Status Rules'),
                'instructions' => Craft::t('formie', 'Set the submission status when the following conditions match.'),
                'statusOptions' => array_map(function($status) {
                    return [
                        'value' => $status->id,
                        'label' => $status->name,
                        'status' => $status->color,
                    ];
                }, Formie::$plugin->getStatuses()->getAllStatuses()),
                'triggerOptions' => [
                    ['label' => Craft::t('formie', 'Final submit'), 'value' => 'finalSubmit'],
                    ['label' => Craft::t('formie', 'Every page'), 'value' => 'everyPage'],
                ],
                'fieldOptions' => ConditionsHelper::getConditionFieldOptions([
                    'includeSubmissionDate' => true,
                ]),
                'conditionOptions' => ConditionsHelper::getConditionOptions(),
            ],
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Submission Title Format'),
                'instructions' => Craft::t('formie', 'Enter the format of the auto-generated submission titles. If left blank, the date/time of submission will be used.'),
                'name' => 'settings.submissionTitleFormat',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Control Panel Field Conditions'),
                'instructions' => Craft::t('formie', 'How field conditions should behave when viewing or editing submissions in the control panel.'),
                'name' => 'settings.cpSubmissionFieldConditions',
                'options' => CpSubmissionFieldConditions::formOptions(),
            ]),
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Privacy'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Collect IP Addresses'),
                'instructions' => Craft::t('formie', 'Whether this form should collect the users‘ IP address.'),
                'name' => 'settings.collectIp',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Collect User'),
                'instructions' => Craft::t('formie', 'Whether this form should keep a record of the logged-in user.'),
                'name' => 'settings.collectUser',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Data Retention'),
                'instructions' => Craft::t('formie', 'How long to retain form submission data for.'),
                'name' => 'dataRetention',
                'options' => [
                    [
                        'value' => 'forever',
                        'label' => Craft::t('formie', 'Forever'),
                    ],
                    [
                        'value' => 'minutes',
                        'label' => Craft::t('formie', 'Number of minutes'),
                    ],
                    [
                        'value' => 'hours',
                        'label' => Craft::t('formie', 'Number of hours'),
                    ],
                    [
                        'value' => 'days',
                        'label' => Craft::t('formie', 'Number of days'),
                    ],
                    [
                        'value' => 'weeks',
                        'label' => Craft::t('formie', 'Number of weeks'),
                    ],
                    [
                        'value' => 'months',
                        'label' => Craft::t('formie', 'Number of months'),
                    ],
                    [
                        'value' => 'years',
                        'label' => Craft::t('formie', 'Number of years'),
                    ],
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Data Retention Duration'),
                'instructions' => Craft::t('formie', 'After this duration has been met, submissions will be deleted.'),
                'name' => 'dataRetentionValue',
                'if' => 'dataRetention != "forever"',
                'warning' => Craft::t('formie', 'We use Craft‘s [garbage collection]({link}) mechanism to remove submissions, so this may not always be actioned immediately.', [
                    'link' => 'https://craftcms.com/docs/4.x/gc.html',
                ]),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'File Uploads'),
                'instructions' => Craft::t('formie', 'Select how to handle file uploads when a submission is deleted.'),
                'name' => 'fileUploadsAction',
                'options' => [
                    [
                        'value' => 'retain',
                        'label' => Craft::t('formie', 'Retain files'),
                    ],
                    [
                        'value' => 'delete',
                        'label' => Craft::t('formie', 'Delete files'),
                    ],
                ],
            ]),
            [
                '$el' => 'hr',
            ],
            [
                '$el' => 'h3',
                'children' => Craft::t('formie', 'Advanced'),
                'attrs' => [
                    'class' => 'form-builder-h3',
                ],
            ],
            SchemaHelper::handleField([
                'instructions' => Craft::t('formie', 'How you‘ll refer to this {entity} in your templates. Use the refresh icon to re-generate this from your {entity} name.', ['entity' => $builderEntityLabel]),
                'name' => 'handle',
                'required' => true,
                'source' => 'title',
                'syncFromSource' => false,
                'maxLength' => $maxFormHandleLength,
                'reservedHandles' => $reservedFormHandles,
                'warning' => Craft::t('formie', 'Changing this may result in your {entity} not working as expected.', ['entity' => $builderEntityLabel]),
            ]),
            [
                '$el' => 'hr',
                'if' => 'id',
            ],
            [
                '$cmp' => 'FormMetaDetails',
            ],
        ]);
    }

    public function getBuilderEntityType(): string
    {
        return $this->builderEntityType === self::BUILDER_ENTITY_TYPE_STENCIL
            ? self::BUILDER_ENTITY_TYPE_STENCIL
            : self::BUILDER_ENTITY_TYPE_FORM;
    }

    public function getBuilderHandleNames(): array
    {
        $query = (new Query())
            ->select(['handle'])
            ->from($this->getBuilderEntityType() === self::BUILDER_ENTITY_TYPE_STENCIL ? Table::FORMIE_STENCILS : Table::FORMIE_FORMS);

        if ($this->id) {
            $query->where(['not', ['id' => $this->id]]);
        }

        $currentHandle = strtolower((string)$this->handle);

        return array_values(array_filter($query->column(), static function($handle) use ($currentHandle) {
            return $handle && strtolower((string)$handle) !== $currentHandle;
        }));
    }

    public function getBuilderReservedHandles(): array
    {
        return array_values(array_unique(array_merge($this->getBuilderHandleNames(), [
            'id',
            'dateCreated',
            'dateUpdated',
            'uid',
            'title',
        ])));
    }

    private function getBuilderEntityLabel(bool $titleCase = false): string
    {
        $label = $this->getBuilderEntityType() === self::BUILDER_ENTITY_TYPE_STENCIL
            ? Craft::t('formie', 'stencil')
            : Craft::t('formie', 'form');

        return $titleCase ? ucfirst($label) : $label;
    }

    private function _formGroupSelectSchemaField(): ?array
    {
        if ($this->getBuilderEntityType() !== self::BUILDER_ENTITY_TYPE_FORM) {
            return null;
        }

        $groups = Formie::$plugin->getFormGroups()->getAllGroups();

        if (!$groups) {
            return null;
        }

        return SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Form Group'),
            'instructions' => Craft::t('formie', 'Organise this form in the control panel forms list.'),
            'name' => 'groupId',
            'options' => array_merge([
                [
                    'value' => '',
                    'label' => Craft::t('formie', 'Ungrouped'),
                ],
            ], array_map(function(FormGroup $group) {
                return [
                    'value' => $group->id,
                    'label' => $group->name,
                ];
            }, $groups)),
        ]);
    }

    public function definePageSettingsSchema(): array
    {
        $schema = [
            [
                '$field' => 'list',
                'name' => 'pages',
                'showGroupedErrors' => false,
                'schemaChildPrefix' => 'pages.*.',
                'schema' => [
                    [
                        '$el' => 'div',
                        'if' => 'activePage == $item._handle',
                        'hideOnIf' => true,
                        'attrs' => [
                            'class' => 'space-y-4',
                        ],
                        'children' => [
                            SchemaHelper::textField([
                                'label' => Craft::t('formie', 'Page Label'),
                                'instructions' => Craft::t('formie', 'The label for the page.'),
                                'name' => 'label',
                                'required' => true,
                            ]),
                            SchemaHelper::lightswitchField([
                                'label' => Craft::t('formie', 'Enable Conditions'),
                                'instructions' => Craft::t('formie', 'Whether to enable conditional logic to control how this page is shown.'),
                                'name' => 'settings.enablePageConditions',
                            ]),
                            [
                                '$field' => 'pageConditions',
                                'name' => 'settings.pageConditions',
                                'if' => '$item.settings.enablePageConditions',
                                'fieldOptions' => ConditionsHelper::getConditionFieldOptions([
                                    'includeSubmissionDate' => true,
                                ]),
                                'conditionOptions' => ConditionsHelper::getConditionOptions(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return SchemaHelper::compileSchema($schema);
    }

    public function definePageButtonSettingsSchema(): array
    {
        $conditionOptions = ConditionsHelper::getConditionOptions();

        $fieldOptions = ConditionsHelper::getConditionFieldOptions([
            'includeSubmissionDate' => true,
        ]);

        $tabs = [
            [
                'handle' => 'general',
                'label' => Craft::t('formie', 'General'),
                'content' => [
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Button Label'),
                        'instructions' => Craft::t('formie', 'The label for the submit button.'),
                        'name' => 'settings.submitButtonLabel',
                        'required' => true,
                    ]),
                    SchemaHelper::lightswitchField([
                        'label' => Craft::t('formie', 'Show Save Button'),
                        'instructions' => Craft::t('formie', 'Whether to show the save button, allowing users to save progress on a submission to return later.'),
                        'name' => 'settings.showSaveButton',
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Save Button Label'),
                        'instructions' => Craft::t('formie', 'The label for the save submit button.'),
                        'name' => 'settings.saveButtonLabel',
                        'if' => '$item.settings.showSaveButton',
                        'required' => true,
                    ]),
                    SchemaHelper::lightswitchField([
                        'label' => Craft::t('formie', 'Show Back Button'),
                        'instructions' => Craft::t('formie', 'Whether to show the back button, to go back to a previous page.'),
                        'name' => 'settings.showBackButton',
                        'if' => '$key > 0',
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Back Button Label'),
                        'instructions' => Craft::t('formie', 'The label for the back submit button.'),
                        'name' => 'settings.backButtonLabel',
                        'if' => '$key > 0 && $item.settings.showBackButton',
                        'required' => true,
                    ]),
                ],
            ],
            [
                'handle' => 'appearance',
                'label' => Craft::t('formie', 'Appearance'),
                'content' => [
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Form Buttons Position'),
                        'instructions' => Craft::t('formie', 'How the form buttons should be positioned.'),
                        'name' => 'settings.buttonsPosition',
                        'options' => [
                            ['label' => Craft::t('formie', 'Left'), 'value' => 'left'],
                            ['label' => Craft::t('formie', 'Right'), 'value' => 'right'],
                            ['label' => Craft::t('formie', 'Center'), 'value' => 'center'],
                            ['label' => Craft::t('formie', 'Left & Right'), 'value' => 'left-right'],
                            ['label' => Craft::t('formie', 'Right (Save on Left)'), 'value' => 'right-save-left'],
                            ['label' => Craft::t('formie', 'Center (Save on Left)'), 'value' => 'center-save-left'],
                            ['label' => Craft::t('formie', 'Center (Save on Right)'), 'value' => 'center-save-right'],
                            ['label' => Craft::t('formie', 'Save on Right'), 'value' => 'save-right'],
                            ['label' => Craft::t('formie', 'Save on Left'), 'value' => 'save-left'],
                        ],
                    ]),
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Submit Button Placement'),
                        'instructions' => Craft::t('formie', 'Where the submit button should appear on the page.'),
                        'name' => 'settings.submitButtonPlacement',
                        'options' => [
                            ['label' => Craft::t('formie', 'Page Footer'), 'value' => 'page-footer'],
                            ['label' => Craft::t('formie', 'End of Last Row'), 'value' => 'end-of-last-row'],
                        ],
                    ]),
                    SchemaHelper::selectField([
                        'label' => Craft::t('formie', 'Save Button Style'),
                        'instructions' => Craft::t('formie', 'Select the style for the save button.'),
                        'name' => 'settings.saveButtonStyle',
                        'if' => '$item.settings.showSaveButton',
                        'options' => [
                            ['label' => Craft::t('formie', 'Link'), 'value' => 'link'],
                            ['label' => Craft::t('formie', 'Button'), 'value' => 'button'],
                        ],
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'CSS Classes'),
                        'instructions' => Craft::t('formie', 'Add classes that will be output on the form buttons container.'),
                        'name' => 'settings.cssClasses',
                    ]),
                    SchemaHelper::tableField([
                        'label' => Craft::t('formie', 'Container Attributes'),
                        'instructions' => Craft::t('formie', 'Add attributes to be output on the form buttons container.'),
                        'name' => 'settings.containerAttributes',
                        'schemaChildPrefix' => 'settings.containerAttributes.*.',
                        'columns' => [
                            [
                                'type' => 'text',
                                'name' => 'label',
                                'label' => Craft::t('formie', 'Name'),
                                'required' => true,
                            ],
                            [
                                'type' => 'value',
                                'name' => 'value',
                                'label' => Craft::t('formie', 'Value'),
                                'source' => 'label',
                            ],
                        ],
                    ]),
                    SchemaHelper::tableField([
                        'label' => Craft::t('formie', 'Input Attributes'),
                        'instructions' => Craft::t('formie', 'Add attributes to be output on the form buttons `input` elements.'),
                        'name' => 'settings.inputAttributes',
                        'schemaChildPrefix' => 'settings.inputAttributes.*.',
                        'columns' => [
                            [
                                'type' => 'text',
                                'name' => 'label',
                                'label' => Craft::t('formie', 'Name'),
                                'required' => true,
                            ],
                            [
                                'type' => 'value',
                                'name' => 'value',
                                'label' => Craft::t('formie', 'Value'),
                                'source' => 'label',
                            ],
                        ],
                    ]),
                ],
            ],
            [
                'handle' => 'conditions',
                'label' => Craft::t('formie', 'Conditions'),
                'content' => [
                    SchemaHelper::lightswitchField([
                        'labelPosition' => 'before',
                        'label' => Craft::t('formie', 'Enable Conditions'),
                        'instructions' => Craft::t('formie', 'Whether to enable conditional logic to control how the next button is shown.'),
                        'name' => 'settings.enableNextButtonConditions',
                    ]),
                    [
                        '$field' => 'nextButtonConditions',
                        'name' => 'settings.nextButtonConditions',
                        'if' => '$item.settings.enableNextButtonConditions',
                        'fieldOptions' => $fieldOptions,
                        'conditionOptions' => $conditionOptions,
                    ],
                ],
            ],
            [
                'handle' => 'advanced',
                'label' => Craft::t('formie', 'Advanced'),
                'content' => [
                    SchemaHelper::lightswitchField([
                        'labelPosition' => 'before',
                        'label' => Craft::t('formie', 'Enable Client Events'),
                        'instructions' => Craft::t('formie', 'When enabled, a payload will be emitted in the browser after this page submits successfully.'),
                        'name' => 'settings.enableClientEvents',
                    ]),
                    SchemaHelper::tableField([
                        'label' => Craft::t('formie', 'Client Event Data'),
                        'instructions' => Craft::t('formie', 'Each option name becomes a property on the payload object. Include an `event` key when using Google Tag Manager or similar tools.'),
                        'name' => 'settings.clientEventFields',
                        'if' => '$item.settings.enableClientEvents',
                        'schemaChildPrefix' => 'settings.clientEventFields.*.',
                        'columns' => [
                            [
                                'type' => 'text',
                                'name' => 'label',
                                'label' => Craft::t('formie', 'Option'),
                            ],
                            [
                                'type' => 'text',
                                'name' => 'value',
                                'label' => Craft::t('formie', 'Value'),
                            ],
                        ],
                    ]),
                ],
            ],
        ];

        $schema = [
            [
                '$field' => 'list',
                'name' => 'pages',
                'schemaChildPrefix' => 'pages.*.',
                'schema' => [
                    [
                        '$el' => 'div',
                        'if' => 'activePage == $item._handle',
                        'hideOnIf' => true,
                        'children' => [
                            SchemaHelper::modalTabs($tabs),
                        ],
                    ],
                ],
            ],
        ];

        return SchemaHelper::compileSchema($schema);
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return Formie::$plugin->getFormSlotRegistry()->resolve($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title', 'handle'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['templateId', 'groupId', 'submitActionEntryId', 'submitActionEntrySiteId', 'defaultStatusId'], 'number', 'integerOnly' => true];
        $rules[] = [['formLayout'], 'validateFormLayout'];
        $rules[] = [['settings'], 'validateFormSettings'];
        $rules[] = [['notifications'], 'validateNotifications'];

        // Make sure the column name is under the database’s maximum allowed column length
        $rules[] = [['handle'], 'string', 'max' => HandleHelper::getMaxFormHandle()];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];

        $rules[] = [
            'handle', function($attribute, $params, Validator $validator): void {
                $query = static::find()->handle($this->$attribute);

                if ($this->id) {
                    $query = $query->id("not {$this->id}");
                }

                if ($query->exists()) {
                    $error = Craft::t('formie', '{label} "{value}" has already been taken.', [
                        'label' => $this->getAttributeLabel($attribute),
                        'value' => $this->$attribute,
                    ]);

                    $validator->addError($this, $attribute, $error);
                }
            },
        ];

        return $rules;
    }

    protected function attributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'usageCount' => count(Formie::$plugin->getForms()->getFormUsage($this)),
            'pageCount' => count($this->getPages()),
            'createdBy' => ($user = $this->getCreatedBy()) ? Cp::elementChipHtml($user) : '',
            'updatedBy' => ($user = $this->getUpdatedBy()) ? Cp::elementChipHtml($user) : '',
            default => parent::attributeHtml($attribute),
        };
    }

    protected function cpEditUrl(): ?string
    {
        $userSession = Craft::$app->getUser();

        // Check if the user has permission to edit this form
        if ($userSession && !$userSession->checkPermission('formie-manageForms')) {
            if (!$userSession->checkPermission('formie-manageForms:' . $this->uid)) {
                return null;
            }
        }
        
        return UrlHelper::cpUrl("formie/forms/edit/{$this->id}");
    }
    

    // Private Methods
    // =========================================================================

    private function _getFormUserMeta(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'name' => $user->getName(),
            'url' => $user->getCpEditUrl(),
        ];
    }

    private function _getFormUserLabel(?User $user): string
    {
        if (!$user) {
            return Craft::t('app', 'Unknown');
        }

        return $user->getName();
    }

    private function _getSessionKey(string $key, bool $useSubmissionId = true): string
    {
        $keys = ['formie', $this->id, $this->_sessionKey];

        // Return a different session namespace when editing a submission
        if ($useSubmissionId && $this->_editingSubmission && $this->_editingSubmission->id) {
            $keys[] = $this->_editingSubmission->id;
        }

        $keys[] = $key;

        return implode(':', array_filter($keys));
    }

    private function _hydrateCurrentSubmissionFromStorage(): void
    {
        if ($this->_currentSubmission) {
            return;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();
        $draftState = $submissionDrafts->getProgressState($this);

        if (!$draftState) {
            return;
        }

        if ($draftState->currentPageId) {
            foreach ($this->getPages() as $page) {
                if ((int)$page->id === (int)$draftState->currentPageId) {
                    $this->setCurrentPage($page);
                    break;
                }
            }
        }

        if (!$draftState->submissionId) {
            return;
        }

        $submission = Submission::find()
            ->id((int)$draftState->submissionId)
            ->isIncomplete(true)
            ->status(null)
            ->one();

        if (!$submission || (int)$submission->formId !== (int)$this->id) {
            return;
        }

        if (is_array($draftState->content) && $draftState->content) {
            $submission->getContentManager()->normalizeFromDb($submission, $draftState->content);
        }

        $this->_currentSubmission = $submission;
    }

    private function _hydrateCurrentSubmissionFromRouteContext(): void
    {
        if ($this->_routeContextHydrated) {
            return;
        }

        $this->_routeContextHydrated = true;
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return;
        }

        $params = Craft::$app->getUrlManager()->getRouteParams();
        $routeForm = $params['form'] ?? null;
        $routeSubmission = $params['submission'] ?? null;
        $routeRenderId = isset($params['renderId']) && is_string($params['renderId']) ? trim($params['renderId']) : '';
        $currentRenderId = $this->_formId ?: $this->getRenderId(false);

        $matchesRouteForm = $routeForm instanceof self && (int)$routeForm->id === (int)$this->id;
        $matchesRouteSubmission = $routeSubmission instanceof Submission && (int)$routeSubmission->formId === (int)$this->id;

        if (!$matchesRouteForm && !$matchesRouteSubmission) {
            return;
        }

        // On pages with duplicate form renders, only hydrate the submitted render instance.
        if ($routeRenderId !== '') {
            if ($currentRenderId !== $routeRenderId) {
                return;
            }
        }

        if ($matchesRouteForm && $routeForm !== $this) {
            if ($routeCurrentPage = $routeForm->getCurrentPage()) {
                $this->setCurrentPage($routeCurrentPage);
            }

            $this->setRequestToken($routeForm->getRequestToken());

            $routeDraftContext = $routeForm->getDraftContext();
            if (is_string($routeDraftContext) && trim($routeDraftContext) !== '') {
                $this->setDraftContext($routeDraftContext);
            }
        }

        $routePageId = $params['pageId'] ?? null;
        if (is_numeric($routePageId)) {
            $routePageId = (int)$routePageId;

            foreach ($this->getPages() as $page) {
                if ((int)$page->id === $routePageId) {
                    $this->setCurrentPage($page);
                    break;
                }
            }
        }

        if ($matchesRouteSubmission) {
            $this->_currentSubmission = $routeSubmission;
        }
    }

    private function _hydrateCurrentSubmissionFromResumeToken(): void
    {
        if ($this->_resumeTokenHydrated) {
            return;
        }

        $this->_resumeTokenHydrated = true;

        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return;
        }

        $resumeToken = trim((string)$request->getParam('resumeToken', ''));

        if ($resumeToken !== '') {
            $this->_markStatefulResponseNoCache();
        }

        if ($resumeToken === '') {
            return;
        }

        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();
        $verifiedResumeToken = $submissionDrafts->verifyResumeToken($resumeToken, [
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ]);

        if (!$verifiedResumeToken || (int)$verifiedResumeToken->formId !== (int)$this->id || !$verifiedResumeToken->submissionId) {
            return;
        }

        $submission = Submission::find()
            ->id((int)$verifiedResumeToken->submissionId)
            ->isIncomplete(true)
            ->status(null)
            ->one();

        if (!$submission || (int)$submission->formId !== (int)$this->id) {
            return;
        }

        $draftState = $submissionDrafts->loadDraftState(new ResumeToken([
            'token' => $resumeToken,
        ]));

        if ($draftState && $draftState->formInstanceKey) {
            $previousState = clone $draftState;
            $previousStorageKey = $previousState->formInstanceKey->toStorageKey();
            $nextFormInstanceKey = $submissionDrafts->resolveFormInstanceKey($this, null, [
                'scope' => 'submit',
                'instance' => $this->getSubmitStateKey(),
            ]);

            // Resume links can be opened in a different render/session context
            // than the one that originally created the draft. Rebind the draft to
            // the active form-instance key so later page navigation and autosave
            // requests continue on the caller's current continuity stream.
            $draftState->formInstanceKey = $nextFormInstanceKey;
            $draftState->submissionId = (int)$submission->id;
            $submissionDrafts->saveDraftState($draftState);

            if ($previousStorageKey !== $nextFormInstanceKey->toStorageKey()) {
                $submissionDrafts->deleteDraftState($previousState);
            }

            if ($draftState->currentPageId) {
                foreach ($this->getPages() as $page) {
                    if ((int)$page->id === (int)$draftState->currentPageId) {
                        $this->setCurrentPage($page);
                        break;
                    }
                }
            }
        }

        $this->_currentSubmission = $submission;
    }

    private function _markStatefulResponseNoCache(): void
    {
        $response = Craft::$app->getResponse();
        $headers = $response->getHeaders();

        $headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $headers->set('Pragma', 'no-cache');
        $headers->set('Expires', '0');
    }

    private function _nextRenderSequence(): int
    {
        $counterKey = hash('sha256', $this->getSubmitStateIdentity());
        $current = self::$_renderSequenceCounters[$counterKey] ?? 0;
        $next = $current + 1;
        self::$_renderSequenceCounters[$counterKey] = $next;

        return $next;
    }

    private function _findErrors($array, &$errors = [])
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->_findErrors($value, $errors);
            }

            if ($key === 'errors') {
                $errors[] = $value;
            }
        }

        return $errors;
    }

    private function _clearLayoutIdentifiers(FormLayout $layout): void
    {
        $layout->id = null;
        $layout->uid = '';

        foreach ($layout->getPages() as $page) {
            $page->id = null;
            $page->layoutId = null;
            $page->uid = '';

            foreach ($page->getRows() as $row) {
                $row->id = null;
                $row->layoutId = null;
                $row->pageId = null;
                $row->uid = '';

                foreach ($row->getFields() as $field) {
                    $field->id = null;
                    $field->layoutId = null;
                    $field->pageId = null;
                    $field->rowId = null;
                    $field->reference = null;
                    $field->uid = '';

                    if ($field instanceof ParentFieldInterface) {
                        $this->_clearLayoutIdentifiers($field->getFieldLayout());

                        // Set after processing
                        $field->nestedLayoutId = null;
                    }
                }
            }
        }
    }
}

<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Miscellaneous;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\StorageInterface;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\conditions\FormCondition;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use verbb\formie\fields\SingleLineText;
use verbb\formie\gql\interfaces\FieldInterface as GqlFieldInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\FieldLayout as FormLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\services\Statuses;

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
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\web\View;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

use Throwable;
use DateTime;

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;

class Form extends Element
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_HTML_TAG = 'modifyHtmlTag';


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

    public static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => 'All forms',
                'defaultSort' => ['title', 'desc'],
            ],
        ];

        $templates = Formie::$plugin->getFormTemplates()->getAllTemplates();

        if ($templates) {
            $sources[] = ['heading' => Craft::t('formie', 'Templates')];
        }

        foreach ($templates as $template) {
            // TODO Change at the next breakpoint
            // https://github.com/verbb/formie/discussions/1696
            if ($context === 'modal') {
                $key = "template:{$template->uid}";
            } else {
                $key = "template:{$template->id}";
            }

            $sources[] = [
                'key' => $key,
                'label' => $template->name,
                'data' => ['id' => $template->id],
                'criteria' => ['templateId' => $template->id],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $canDeleteForms = Craft::$app->getUser()->checkPermission('formie-deleteForms');

        $actions[] = DuplicateForm::class;

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
    public ?int $submitActionEntryId = null;
    public ?int $submitActionEntrySiteId = null;
    public ?int $defaultStatusId = null;
    public string $dataRetention = 'forever';
    public ?string $dataRetentionValue = null;
    public string $userDeletedAction = 'retain';
    public string $fileUploadsAction = 'retain';
    public ?FormSettings $settings = null;

    public bool $resetClasses = false;
    public ?int $pageCount = null;
    public bool $isApplyingStencil = false;

    private ?FieldLayout $_fieldLayout = null;
    private ?FormLayout $_formLayout = null;
    private ?FormTemplate $_template = null;
    private ?Status $_defaultStatus = null;
    private ?Entry $_submitActionEntry = null;
    private ?array $_notifications = null;
    private ?Submission $_currentSubmission = null;
    private ?Submission $_editingSubmission = null;
    private ?string $_formId = null;
    private bool $_appliedFieldSettings = false;
    private bool $_appliedFormSettings = false;
    private array $_relations = [];
    private array $_populatedFieldValues = [];
    private array $_submitData = [];
    private ?string $_redirectUrl = null;
    private ?string $_actionUrl = null;
    private string $_storageBehaviour = 'session';

    // Render Options
    private array $_renderOptions = [];
    private array $_themeConfig = [];
    private ?string $_sessionKey = null;


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

    public function getConsolidatedErrors()
    {
        $errors = [
            $this->getErrors(),
            $this->_findErrors($this->getFormBuilderConfig()),
        ];

        return array_values(ArrayHelper::recursiveFilter(array_merge(...$errors)));
    }

    public function getSettings(): ?FormSettings
    {
        return $this->settings;
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

    public function getFormId(bool $useCache = true): string
    {
        if ($this->_formId && $useCache) {
            return $this->_formId;
        }

        // Provide a unique ID for this field, used as a namespace for IDs of elements in the form
        return $this->_formId = 'fui-' . $this->handle . '-' . StringHelper::randomString(6);
    }

    public function setFormId(string $value): void
    {
        $this->_formId = $value;
    }

    public function getDirtyAttributes(): array
    {
        // This is here to prompt Blitz that a change has been made on the form when it saves
        // because the form settings don't use delta updates, which Blitz relies on. Keep an eye on
        // what potential issues this might bring up...
        $this->setDirtyAttributes(['title']);

        return parent::getDirtyAttributes();
    }

    public function getConfigJson(): ?string
    {
        return Json::encode($this->getFrontEndJsVariables());
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'errors' => $this->getErrors(),
            'templateId' => $this->templateId,
            'pages' => $this->getFormLayout()->getFormBuilderConfig(),
            'settings' => $this->getSettings()->getFormBuilderConfig(),
        ];
    }

    public function getNotificationsConfig(): array
    {
        return Formie::$plugin->getNotifications()->getNotificationsConfig($this->getNotifications());
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
        return ArrayHelper::firstWhere($this->getFields(), 'handle', $handle);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return ArrayHelper::firstWhere($this->getFields(), 'id', $id);
    }

    public function getCustomFields(): array
    {
        // Required for compatibility with GQL `craft\gql\base\Generator`
        return $this->getFields();
    }

    public function hasFieldConditions(): bool
    {
        foreach ($this->getFields() as $field) {
            if ($field->enableConditions) {
                return true;
            }

            if ($field instanceof NestedFieldInterface) {
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

    public function hasMultiplePages(): bool
    {
        return count($this->getPages()) > 1;
    }

    public function getCurrentPage(): ?FieldLayoutPage
    {
        $currentPage = null;
        $pages = $this->getPages();

        if ($pages) {
            // Check if there's a session variable
            $pageId = $this->getStorage()->getCurrentPageId($this);

            if ($pageId) {
                $currentPage = ArrayHelper::firstWhere($pages, 'id', $pageId);
            }

            // Separate check from the above. Maybe we're trying to fetch a page that doesn't
            // belong to this form? If so, that'll freak things out. We always need a page
            if (!$currentPage) {
                $currentPage = $pages[0];
            }
        }

        // TODO: Maybe blow away the session variable as soon as its fetched. What if we have tabs
        // on the template to go directly to a specific page? It'll otherwise always go to the same
        // page, which is not what we want. Maybe look at adding a `form->getPageUrl()` which does this

        return $currentPage;
    }

    public function getPreviousPage(FieldLayoutPage $currentPage = null, Submission $submission = null, bool $defaultToFirst = false): ?FieldLayoutPage
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        $currentKey = end($pages);

        if ($currentPage) {
            while ($currentKey !== null && $currentKey !== $currentPage) {
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

        if ($currentPage) {
            while ($currentKey !== null && $currentKey !== $currentPage) {
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
        if ($page) {
            $this->getStorage()->setCurrentPageId($this, $page->id);
        }
    }

    public function resetCurrentPage(): void
    {
        $this->getStorage()->resetCurrentPageId($this);
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

        // If we have a current submission in the session, use that
        if ($this->_currentSubmission) {
            return $this->_currentSubmission;
        }

        // See if there's a submission on routeParams - an error has occurred.
        $params = Craft::$app->getUrlManager()->getRouteParams();

        // Make sure to check the right submission
        if (isset($params['submission']) && $params['submission']->form->id == $this->id) {
            return $params['submission'];
        }

        // Check if there's a session variable
        $submissionId = Session::get($this->_getSessionKey('submissionId'));

        if ($submissionId) {
            /* @var Submission $submission */
            $submission = Submission::find()->id($submissionId)->isIncomplete(true)->one();

            // Ensure that the submission still exists. If it doesn't, reset
            if (!$submission) {
                $this->resetCurrentSubmission();
            }

            return $this->_currentSubmission = $submission;
        }

        // Or, if we're editing a submission
        if ($this->_editingSubmission) {
            return $this->_currentSubmission = $this->_editingSubmission;
        }

        return null;
    }

    public function setCurrentSubmission(?Submission $submission): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return;
        }

        if (!$submission) {
            $this->resetCurrentSubmission();
        } else {
            Session::set($this->_getSessionKey('submissionId'), $submission->id);
        }

        $this->_currentSubmission = $submission;
    }

    public function resetCurrentSubmission(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return;
        }

        $this->resetCurrentPage();
        Session::remove($this->_getSessionKey('submissionId'));

        $this->_currentSubmission = null;
    }

    public function getStorageBehaviour(): string
    {
        return $this->_storageBehaviour;
    }

    public function setStorageBehaviour(string $behaviour): void
    {
        $this->_storageBehaviour = $behaviour;
    }

    public function getStorage(): StorageInterface
    {
        return Formie::$plugin->getStorage()->getStorage($this->getStorageBehaviour());
    }

    public function setSubmission(?Submission $submission): void
    {
        $this->_editingSubmission = $submission;
    }

    public function isEditingSubmission(): bool
    {
        return (bool)$this->_editingSubmission;
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
        foreach ($this->getNotifications() as $notification) {
            if (!$notification->validate()) {
                foreach ($notification->getErrors() as $key => $error) {
                    $this->addError('notifications.' . $notification->id . '.' . $key, $error[0]);
                }
            }
        }
    }

    public function setRedirectUrl(string $value): void
    {
        $this->_redirectUrl = $value;
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
            // Parse Twig
            $url = Craft::$app->getView()->renderString($this->settings->submitActionUrl);
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

        // Handle any special characters defined in the URL and encode them properly
        $url = str_replace("&amp;", "&", htmlspecialchars($url));

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

    public function renderHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        // Get the HtmlTag definition
        $tag = $this->defineHtmlTag($key, $context);

        if ($tag) {
            // Find if there's a config option for this key, either in plugin config or template render options
            $config = $this->getThemeConfigItem($key);

            // Check if the config is falsey - then don't render
            if ($config === false || $config === null) {
                $tag = null;
            } else {
                // Are we resetting classes globally?
                if ($this->resetClasses) {
                    $config['resetClass'] = true;
                }

                $tag->setFromConfig($config, $context);
            }
        }

        $event = new ModifyFormHtmlTagEvent([
            'form' => $this,
            'tag' => $tag,
            'key' => $key,
            'context' => $context,
        ]);

        $this->trigger(static::EVENT_MODIFY_HTML_TAG, $event);

        return $event->tag;
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        if ($key === 'formWrapper') {
            return new HtmlTag('div', [
                'class' => 'fui-i',
            ]);
        }

        if ($key === 'form') {
            $defaultLabelPosition = new $this->settings->defaultLabelPosition;

            return new HtmlTag('form', [
                'id' => $this->getFormId(),
                'class' => [
                    'fui-form',
                    'fui-labels-' . $defaultLabelPosition,
                    $this->settings->displayPageProgress ? "fui-progress-{$this->settings->progressPosition}" : false,
                    $this->settings->displayPageProgress ? "fui-progress-value-{$this->settings->progressValuePosition}" : false,
                    $this->settings->validationOnFocus ? 'fui-validate-on-focus' : false,
                ],
                'method' => 'post',
                'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8',
                'data' => [
                    'fui-form' => $this->getConfigJson(),
                    'form-submit-method' => $this->settings->submitMethod ?: false,
                    'form-submit-action' => $this->settings->submitAction ?: false,
                    'loading-indicator' => $this->settings->loadingIndicator ?: false,
                    'loading-text' => $this->settings->loadingIndicatorText ?: false,
                    'redirect' => $this->getRedirectUrl() ?: false,
                ],
            ]);
        }

        if ($key === 'formContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-form-container',
            ]);
        }

        if ($key === 'alertError') {
            return new HtmlTag('div', [
                'class' => [
                    'fui-alert fui-alert-error',
                    'fui-alert-' . $this->settings->errorMessagePosition,
                ],
                'role' => 'alert',
                'data-fui-alert' => true,
                'data-fui-alert-error' => true,
            ]);
        }

        if ($key === 'alertSuccess') {
            return new HtmlTag('div', [
                'class' => [
                    'fui-alert fui-alert-success',
                    'fui-alert-' . $this->settings->submitActionMessagePosition,
                ],
                'role' => 'alert',
                'data-fui-alert' => true,
                'data-fui-alert-success' => true,
            ]);
        }

        if ($key === 'formTitle') {
            return new HtmlTag('h2', [
                'class' => 'fui-title',
            ]);
        }

        if ($key === 'pageTabs') {
            return new HtmlTag('div', [
                'class' => 'fui-tabs',
                'data-fui-page-tabs' => true,
            ]);
        }

        if ($key === 'pageTab') {
            $submission = $context['submission'] ?? null;
            $currentPage = $context['currentPage'] ?? null;
            $currentPageId = $currentPage->id ?? null;
            $currentPageIndex = $this->getPageIndex($currentPage);
            $page = $context['page'] ?? null;
            $pageId = $page->id ?? null;
            $pageIndex = $this->getPageIndex($page);

            return new HtmlTag('div', [
                'id' => 'fui-tab-' . $pageId,
                'class' => [
                    'fui-tab',
                    ($currentPageIndex > $pageIndex) ? 'fui-tab-complete' : false,
                    ($pageId == $currentPageId) ? 'fui-tab-active' : false,
                    $page->getFieldErrors($submission) ? 'fui-tab-error' : false,
                ],
                'data-fui-page-tab' => true,
                'data-field-conditions' => $page->getConditionsJson(),
            ]);
        }

        if ($key === 'pageTabLink') {
            $params = $context['params'] ?? null;
            $page = $context['page'] ?? null;
            $pageId = $page->id ?? null;
            $pageIndex = $context['pageIndex'] ?? null;

            return new HtmlTag('a', [
                'href' => UrlHelper::actionUrl('formie/submissions/set-page', $params),
                'data-fui-page-tab-anchor' => true,
                'data-fui-page-index' => $pageIndex,
                'data-fui-page-id' => $pageId ?? false,
            ]);
        }

        if ($key === 'page') {
            $page = $context['page'] ?? null;
            $pageId = $page->id ?? null;
            $currentPageId = $context['currentPage']->id ?? null;

            return new HtmlTag('div', [
                'id' => "{$this->getFormId()}-p-{$pageId}",
                'class' => 'fui-page',
                'data' => [
                    'index' => $page->sortOrder ?? null,
                    'id' => $pageId,
                    'fui-page' => true,
                    'fui-page-hidden' => $this->hasMultiplePages() && $pageId != $currentPageId ? true : false,
                ],
            ]);
        }

        if ($key === 'pageContainer') {
            $tag = $this->settings->displayCurrentPageTitle ? 'fieldset' : 'div';

            return new HtmlTag($tag, [
                'class' => [
                    'fui-page-container',
                    $this->settings->displayCurrentPageTitle ? 'fui-fieldset' : false,
                ],
            ]);
        }

        if ($key === 'pageTitle') {
            return new HtmlTag('legend', [
                'class' => 'fui-page-title',
            ]);
        }

        if ($key === 'row') {
            $row = $context['row'] ?? null;

            return new HtmlTag('div', [
                'class' => [
                    'fui-row fui-page-row',
                     $row->getIsHidden() ? 'fui-row-empty' : false,
                ],
                'data-fui-field-count' => count($row->getFields(false, false)),
            ]);
        }

        if ($key === 'buttonWrapper') {
            $page = $context['page'] ?? null;
            $containerAttributes = $page->getPageSettings()->getContainerAttributes() ?? [];

            return new HtmlTag('div', [
                'class' => [
                    'fui-btn-wrapper',
                    "fui-btn-{$page->getPageSettings()->buttonsPosition}",
                ],
            ], $containerAttributes, $page->getPageSettings()->cssClasses);
        }

        if ($key === 'buttonContainer') {
            $page = $context['page'] ?? null;
            $showSaveButton = $page->getPageSettings()->showSaveButton ?? false;

            // Don't output if no save button
            if (!$showSaveButton) {
                return null;
            }

            return new HtmlTag('div', [
                'class' => 'fui-btn-container',
            ]);
        }

        if ($key === 'submitButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->getPageSettings()->getInputAttributes() ?? [];
            $nextPage = $this->getNextPage($page);

            return new HtmlTag('button', [
                'class' => [
                    'fui-btn fui-submit',
                    $nextPage ? 'fui-next' : false,
                ],
                'type' => 'submit',
                'data-submit-action' => 'submit',
                'data-field-conditions' => $page->getPageSettings()->getConditionsJson(),
            ], $inputAttributes);
        }

        if ($key === 'saveButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->getPageSettings()->getInputAttributes() ?? [];
            $saveButtonStyle = $page->getPageSettings()->saveButtonStyle ?? 'link';
            
            return new HtmlTag('button', [
                'class' => [
                    'fui-btn fui-save',
                    $saveButtonStyle === 'button' ? 'fui-submit' : 'fui-btn-link',
                ],
                'type' => 'submit',
                'data-submit-action' => 'save',
            ], $inputAttributes);
        }

        if ($key === 'backButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->getPageSettings()->getInputAttributes() ?? [];

            return new HtmlTag('button', [
                'class' => 'fui-btn fui-prev',
                'type' => 'submit',
                'data-submit-action' => 'back',
            ], $inputAttributes);
        }

        if ($key === 'progressWrapper') {
            return new HtmlTag('div', [
                'class' => 'fui-progress-container',
                'data-fui-progress-container' => true,
            ]);
        }

        if ($key === 'progress') {
            return new HtmlTag('div', [
                'class' => 'fui-progress',
                'data-fui-progress' => true,
            ]);
        }

        if ($key === 'progressContainer') {
            $progress = $context['progress'] ?? null;

            return new HtmlTag('div', [
                'style' => "width: {$progress}%",
                'class' => 'fui-progress-bar',
                'role' => 'progressbar',
                'data-fui-progress-bar' => true,
                'aria' => [
                    'valuenow' => $progress,
                    'valuemin' => 0,
                    'valuemax' => 100,
                ],
            ]);
        }

        if ($key === 'progressValue') {
            $progress = $context['progress'] ?? null;

            return new HtmlTag('span', [
                'class' => 'fui-progress-value',
                'data-fui-progress-value' => $progress,
            ]);
        }

        if ($key === 'errors') {
            return new HtmlTag('ul', [
                'class' => 'fui-errors',
            ]);
        }

        if ($key === 'error') {
            return new HtmlTag('li', [
                'class' => 'fui-error-message',
            ]);
        }

        if ($key === 'captcha') {
            return new HtmlTag('div', [
                'class' => 'fui-captcha',
            ]);
        }

        return null;
    }

    public function applyRenderOptions(array $renderOptions = []): void
    {
        $this->_renderOptions = $renderOptions;

        // Allow a session key to be provided to scope incomplete submission content.
        // Base64 encode it not for security, just so it's not plain text an "obvious".
        $sessionKey = $renderOptions['sessionKey'] ?? null;
        $this->setSessionKey(base64_encode((string)$sessionKey));

        // Theme options
        $templateConfig = $renderOptions['themeConfig'] ?? [];
        $pluginConfig = Formie::$plugin->getSettings()->themeConfig ?? [];

        // If not set at the template level, check if it's set a the plugin level.
        // If set for both, `setThemeConfig()` will merge.
        if ($templateConfig) {
            $this->setThemeConfig($templateConfig);
        } else if ($pluginConfig) {
            // Pass in an empty array, because we already merge in plugin settings config
            $this->setThemeConfig([]);
        }
    }

    public function getThemeConfig(): array
    {
        return $this->_themeConfig;
    }

    public function setThemeConfig(array $value): void
    {
        /* @var Settings $pluginSettings */
        $pluginSettings = Formie::$plugin->getSettings();

        // Merge config and template-level tags - template overrides
        $this->_themeConfig = Html::mergeHtmlConfigs($pluginSettings->themeConfig, $value);

        // Rip out the `resetClasses`, if set as this is set globally and checked on each tag-render
        $this->resetClasses = ArrayHelper::remove($this->_themeConfig, 'resetClasses', false);
    }

    public function getThemeConfigItem(string $key): array|bool|null
    {
        return ArrayHelper::getValue($this->_themeConfig, $key, []);
    }

    public function getFrontEndJsVariables(): array
    {
        /* @var Settings $pluginSettings */
        $pluginSettings = Formie::$plugin->getSettings();

        // Only provide what we need, both for security/privacy but also DOM size
        $settings = [
            'submitMethod' => $this->settings->submitMethod,
            'submitActionMessage' => $this->settings->getSubmitActionMessage() ?? '',
            'submitActionMessageTimeout' => $this->settings->submitActionMessageTimeout,
            'submitActionMessagePosition' => $this->settings->submitActionMessagePosition,
            'submitActionFormHide' => $this->settings->submitActionFormHide,
            'submitAction' => $this->settings->submitAction,
            'submitActionTab' => $this->settings->submitActionTab,
            'errorMessage' => $this->settings->getErrorMessage() ?? '',
            'errorMessagePosition' => $this->settings->errorMessagePosition,
            'loadingIndicator' => $this->settings->loadingIndicator,
            'loadingIndicatorText' => $this->settings->loadingIndicatorText,
            'validationOnSubmit' => $this->settings->validationOnSubmit,
            'validationOnFocus' => $this->settings->validationOnFocus,
            'scrollToTop' => $this->settings->scrollToTop,
            'hasMultiplePages' => $this->hasMultiplePages(),
            'pages' => array_map(function(FieldLayoutPage $page) {
                return [
                    'id' => $page->id,
                    'label' => $page->label,
                    'settings' => $page->getSettings(),
                ];
            }, $this->getPages()),
            'themeConfig' => $this->getThemeConfigAttributes(),
            'redirectUrl' => $this->getRedirectUrl(),
            'currentPageId' => $this->getCurrentPage()->id ?: '',
            'outputJsTheme' => $this->getFrontEndTemplateOption('outputJsTheme'),
            'enableUnloadWarning' => $pluginSettings->enableUnloadWarning,
            'enableBackSubmission' => $pluginSettings->enableBackSubmission,
            'ajaxTimeout' => $pluginSettings->ajaxTimeout,
            'outputConsoleMessages' => $pluginSettings->outputConsoleMessages,
            'baseActionUrl' => rtrim(UrlHelper::siteActionUrl(''), '/'),

            // Generate the refresh token here to make use of `UrlHelper` generation
            'refreshTokenUrl' => UrlHelper::siteActionUrl('formie/forms/refresh-tokens', ['form' => 'FORM_PLACEHOLDER']),
        ];

        // Render options could contain settings for script tag attributes (CSP)
        $settings['scriptAttributes'] = $this->_renderOptions['scriptAttributes'] ?? [];

        $registeredJs = [];

        // Add any JS per-field
        foreach ($this->getFields() as $field) {
            if ($fieldJs = $this->_getFrontEndJsModules($field)) {
                $registeredJs[] = $fieldJs;
            }
        }

        // Add any JS for enabled captchas - force fetch because we're dealing with potential ajax forms
        // Normally, this function returns only if the `showAllPages` property is set.
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($this, null, true);

        // Don't show captchas for the CP
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            foreach ($captchas as $captcha) {
                if ($js = $captcha->getFrontEndJsVariables($this)) {
                    $registeredJs[] = [$js];
                }
            }
        }

        // Add any JS for other integrations (that don't handle things themselves)
        $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($this);

        foreach ($integrations as $integration) {
            // Some integration types take care of front-end JS in other ways
            if ($integration instanceof Crm || $integration instanceof EmailMarketing || $integration instanceof Miscellaneous) {
                if ($js = $integration->getFrontEndJsVariables()) {
                    $registeredJs[] = [$js];
                }
            }
        }

        // See if we have any condition's setup for the form. No need to include otherwise
        if ($this->hasConditions()) {
            $registeredJs[] = [[
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/conditions.js'),
                'module' => 'FormieConditions',
            ]];
        }

        // For performance, merge after building
        $registeredJs = array_merge(...$registeredJs);

        // Cleanup - Ensure we don't include JS multiple times
        $registeredJs = array_values(array_unique(array_filter($registeredJs), SORT_REGULAR));

        return [
            'formHashId' => $this->getFormId(),
            'formId' => $this->id,
            'formHandle' => $this->handle,
            'registeredJs' => $registeredJs,
            'settings' => $settings,
        ];
    }

    public function getSubmitData(): ?array
    {
        return $this->_submitData;
    }

    public function addSubmitData(array $value): void
    {
        $this->_submitData[] = $value;
    }

    public function getFrontEndJsEvents(): ?array
    {
        // Deprecated, use `getSubmitData`
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `getFrontEndJsEvents` method has been deprecated. Use the `getSubmitData` method instead.');

        return $this->getSubmitData();
    }

    public function addFrontEndJsEvents(array $value): void
    {
        // Deprecated, use `addSubmitData`
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `addFrontEndJsEvents` method has been deprecated. Use the `addSubmitData` method instead.');
        
        $this->addSubmitData($value);
    }

    public function getThemeConfigAttributes()
    {
        $allAttributes = [];

        // Provide defaults to fallback on, which aren't in Theme Config
        $configKeys = [
            'loading' => 'fui-loading',
            'errorMessage' => 'fui-error-message',
            'disabled' => 'fui-disabled',
            'tabError' => 'fui-tab-error',
            'tabActive' => 'fui-tab-active',
            'tabComplete' => 'fui-tab-complete',
            'successMessage' => 'fui-alert-success',
            'alert' => 'fui-alert',
            'alertError' => 'fui-alert-error',
            'alertSuccess' => 'fui-alert-success',
            'page' => 'fui-page',
            'progress' => 'fui-progress-bar',
            'tab' => 'fui-tab',
            'success' => 'fui-success',
            'successMessage' => 'fui-success-message',
            'error' => 'fui-error',
            'fieldContainerError' => 'fui-error',
            'fieldInputError' => 'fui-error',
            'fieldErrors' => 'fui-errors',
            'fieldError' => 'fui-error-message',
        ];

        $context = [
            'form' => $this,
            'page' => $this->getPages()[0] ?? null,
            'currentPage' => $this->getCurrentPage(),
        ];

        // Create a generic field in case we want to grab some generic field theme config
        $field = new SingleLineText();

        // Get all the classes JS components require from Theme Config
        foreach ($configKeys as $configKey => $fallback) {
            $tag = $this->renderHtmlTag($configKey, $context);
            $fieldTag = $field->renderHtmlTag($configKey, $context);

            if ($tag) {
                $classes = $tag->attributes['class'] ?? $fallback;

                if (!is_array($classes)) {
                    $classes = [$classes];
                }

                $allAttributes[$configKey] = Html::getTagAttributes($tag->attributes);
                $allAttributes[$configKey]['class'] = implode(' ', $classes);
            } else if ($fieldTag) {
                $classes = $fieldTag->attributes['class'] ?? $fallback;

                if (!is_array($classes)) {
                    $classes = [$classes];
                }

                $allAttributes[$configKey] = Html::getTagAttributes($fieldTag->attributes);
                $allAttributes[$configKey]['class'] = implode(' ', $classes);
            } else {
                if (!$this->resetClasses) {
                    $allAttributes[$configKey]['class'] = $fallback;
                }
            }
        }

        return $allAttributes;
    }

    public function getFrontEndTemplateOption(string $option): bool
    {
        $output = true;

        if ($template = $this->getTemplate()) {
            $output = (bool)$template->$option;
        }

        return $output;
    }

    public function getFrontEndTemplateLocation(string $location)
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

    public function isAvailable(): bool
    {
        // Disable for CP-based submissions
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return true;
        }

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
        // Disable for CP-based submissions
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return false;
        }

        return !$this->isBeforeSchedule() && !$this->isAfterSchedule();
    }

    public function isBeforeSchedule(): bool
    {
        // Disable for CP-based submissions
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return true;
        }

        if ($this->settings->scheduleForm && $this->settings->scheduleFormStart) {
            return !DateTimeHelper::isInThePast($this->settings->scheduleFormStart);
        }
        
        return false;
    }

    public function isAfterSchedule(): bool
    {
        // Disable for CP-based submissions
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return true;
        }

        if ($this->settings->scheduleForm && $this->settings->scheduleFormEnd) {
            return DateTimeHelper::isInThePast($this->settings->scheduleFormEnd);
        }
        
        return false;
    }

    public function isWithinSubmissionsLimit(): bool
    {
        // Disable for CP-based submissions
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return true;
        }

        if ($this->settings->limitSubmissions) {
            $query = Submission::find()->formId($this->id);

            // Get the appropriate settings for the limit type
            if ($this->settings->limitSubmissions === 'ipAddress') {
                $limitSubmissionsType = $this->settings->limitSubmissionsIpAddressType;
                $limitSubmissionsNumber = $this->settings->limitSubmissionsIpAddressNumber;

                // Ensure that we actually are storing IPs, otherwise nothing really to compare
                if (!$this->settings->collectIp) {
                    return true;
                }

                $query->ipAddress(Craft::$app->getRequest()->userIP);
            } else {
                $limitSubmissionsType = $this->settings->limitSubmissionsType;
                $limitSubmissionsNumber = $this->settings->limitSubmissionsNumber;
            }

            if ($limitSubmissionsType === 'day') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('today'));
                $endDate = DateTimeHelper::toDateTime(new DateTime('tomorrow'));

                $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)]);
            } else if ($limitSubmissionsType === 'week') {
                // PHP dates start on a Monday, but we assume to backtrack to Sunday
                $startDate = DateTimeHelper::toDateTime(new DateTime('monday this week'))->modify('-1 day');
                $endDate = DateTimeHelper::toDateTime(new DateTime('monday next week'))->modify('-1 day');

                $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)]);
            } else if ($limitSubmissionsType === 'month') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('first day of this month'))->setTime(0, 0, 0);
                $endDate = DateTimeHelper::toDateTime(new DateTime('first day of next month'))->setTime(0, 0, 0);

                $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)]);
            } else if ($limitSubmissionsType === 'year') {
                $startDate = DateTimeHelper::toDateTime(new DateTime('first day of January'))->setTime(0, 0, 0);
                $endDate = DateTimeHelper::toDateTime(new DateTime('first day of January next year'))->setTime(0, 0, 0);

                $query->dateCreated(['and', '>= ' . Db::prepareDateForDb($startDate), '<= ' . Db::prepareDateForDb($endDate)]);
            }

            $submissions = $query->count();

            if ($submissions >= $limitSubmissionsNumber) {
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

    public static function defineElementChipHtml(\craft\events\DefineElementHtmlEvent $event): void
    {
        $element = $event->element;

        if (!($element instanceof self)) {
            return;
        }

        // Remove the quik-edit ability
        $event->html = str_replace(['data-editable'], [''], $event->html);
    }

    public function beforeSave(bool $isNew): bool
    {
        $settings = Formie::$plugin->getSettings();
        $fieldsService = Craft::$app->getFields();

        // Set the default template from settings, if not already set - for new forms
        if ($isNew && !$this->templateId) {
            $this->templateId = $settings->getDefaultFormTemplateId();
        }

        // Ensure any parent validations run first
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        // If a new form, enable any globally-enabled captchas - but not if applying a stencil
        if ($isNew && !$this->isApplyingStencil) {
            foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $captcha) {
                if ($captcha->getEnabled() && $captcha->hasFormSettings()) {
                    $this->settings->integrations[$captcha->handle]['enabled'] = true;
                }
            }
        }

        // Save the field layout as the last step
        return Formie::$plugin->getFields()->saveLayout($this->getFormLayout());
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
        $record->submitActionEntryId = $this->submitActionEntryId;
        $record->submitActionEntrySiteId = $this->submitActionEntrySiteId;
        $record->defaultStatusId = $this->defaultStatusId;
        $record->dataRetention = $this->dataRetention;
        $record->dataRetentionValue = $this->dataRetentionValue;
        $record->fileUploadsAction = $this->fileUploadsAction;
        $record->userDeletedAction = $this->userDeletedAction;

        $record->save(false);

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
        // Delete any submissions made on this form.
        $submissions = Submission::find()->formId($this->id)->all();
        $elementsService = Craft::$app->getElements();

        foreach ($submissions as $submission) {
            if (!$elementsService->deleteElement($submission)) {
                Formie::error("Unable to delete submission ”{$submission->id}” for form ”{$this->id}”: " . Json::encode($submission->getErrors()) . ".");
            }
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title', 'handle'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['templateId', 'submitActionEntryId', 'submitActionEntrySiteId', 'defaultStatusId'], 'number', 'integerOnly' => true];
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
                    $error = Craft::t('formie', '{attribute} "{value}" has already been taken.', [
                        'attribute' => $attribute,
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

    private function _getFrontEndJsModules(FieldInterface $field): array
    {
        // Rip out any settings for clarity. These are output directly by the individual fields
        // all we want here is the module src and name to supply the form rendering with what additional
        // JS classes/modules we actually need - no config!
        if ($js = $field->getFrontEndJsModules()) {
            // Normalise for processing. Fields can have multiple modules
            if (!isset($js[0])) {
                $js = [$js];
            }

            foreach ($js as &$config) {
                ArrayHelper::remove($config, 'settings');
            }

            return $js;
        }

        return [];
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
                    $field->uid = '';

                    if ($field instanceof NestedFieldInterface) {
                        $this->_clearLayoutIdentifiers($field->getFieldLayout());

                        // Set after processing
                        $field->nestedLayoutId = null;
                    }
                }
            }
        }
    }
}

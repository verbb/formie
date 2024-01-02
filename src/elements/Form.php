<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Miscellaneous;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FormFieldLayout;
use verbb\formie\models\FormPage;
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
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\errors\MissingComponentException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout as CraftFieldLayout;
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
            $key = "template:{$template->id}";

            $sources[] = [
                'key' => $key,
                'label' => $template->name,
                'data' => ['id' => $template->id],
                'criteria' => ['templateId' => $template->id],
            ];
        }

        return $sources;
    }
    
    protected static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        $userSession = Craft::$app->getUser();
        $elements = $elementQuery->all();

        // Filter out any elements the user doesn't have access to view
        // Can the user edit _every_ form?
        if (!$userSession->checkPermission('formie-viewForms')) {
            // Find all UIDs the user has permission to
            foreach ($elements as $key => $element) {
                if (!$userSession->checkPermission('formie-manageForm:' . $element->uid)) {
                    unset($elements[$key]);
                }
            }
        }

        return array_values($elements);
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

    public static function actions(string $source): array
    {
        $actions = parent::actions($source);

        // Remove some actions Craft adds by default
        foreach ($actions as $key => $action) {
            if (is_array($action) && isset($action['type']) && ($action['type'] === Edit::class || is_subclass_of($action['type'], Edit::class))) {
                    unset($actions[$key]);
            }
        }

        return array_values($actions);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Name')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'handle' => ['label' => Craft::t('app', 'Handle')],
            'template' => ['label' => Craft::t('app', 'Template')],
            'pageCount' => ['label' => Craft::t('formie', 'Pages')],
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
                'label' => Craft::t('app', 'Pages'),
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

    private ?CraftFieldLayout $_fieldLayout = null;
    private ?FormFieldLayout $_formFieldLayout = null;
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
    private array $_frontEndJsEvents = [];
    private ?string $_redirectUrl = null;
    private ?string $_actionUrl = null;

    // Render Options
    private array $_themeConfig = [];
    private ?string $_sessionKey = null;

    private static ?array $_layoutsByType = null;


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

    public function getFormFieldLayout(): ?FormFieldLayout
    {
        return $this->_formFieldLayout;
    }

    public function setFormFieldLayout(mixed $formFieldLayout): void
    {
        if (!($formFieldLayout instanceof FormFieldLayout)) {
            $formFieldLayout = new FormFieldLayout($formFieldLayout);
        }

        $this->_formFieldLayout = $formFieldLayout;
    }

    public function validateFormFieldLayout(): void
    {
        $fieldLayout = $this->getFormFieldLayout();

        if (!$fieldLayout->validate()) {
            // Element models can't handle nested errors
            $errors = ArrayHelper::flatten($fieldLayout->getErrors());

            foreach ($errors as $errorKey => $error) {
                $this->addError($errorKey, $error);
            }
        }
    }

    public function getFieldLayout(): ?CraftFieldLayout
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

    public function setFormId($value): void
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
            'pages' => $this->getFormFieldLayout()?->getFormBuilderConfig() ?? [],
            'settings' => $this->getSettings()->getFormBuilderConfig(),
        ];
    }

    public function getNotificationsConfig(): array
    {
        return Formie::$plugin->getNotifications()->getNotificationsConfig($this->getNotifications());
    }

    public function getPages(): array
    {
        return $this->getFormFieldLayout()?->getPages() ?? [];
    }

    public function getRows(): array
    {
        return $this->getFormFieldLayout()?->getRows() ?? [];
    }

    public function getFields(): array
    {
        return $this->getFormFieldLayout()?->getFields() ?? [];
    }

    public function getFieldByHandle(string $handle): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getFields(), 'handle', $handle);
    }

    public function getFieldById(int $id): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getFields(), 'id', $id);
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
            if ($page->settings->enableNextButtonConditions) {
                return true;
            }
        }

        return false;
    }

    public function hasPageConditions(): bool
    {
        foreach ($this->getPages() as $page) {
            if ($page->settings->enablePageConditions) {
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

    public function getCurrentPage(): ?FormPage
    {
        $currentPage = null;
        $pages = $this->getPages();

        if ($pages) {
            // Check if there's a session variable
            $pageHandle = Craft::$app->getSession()->get($this->_getSessionKey('page'));

            if ($pageHandle) {
                $currentPage = ArrayHelper::firstWhere($pages, function($page) use ($pageHandle) {
                    return $page->handle === $pageHandle;
                });
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

    public function getPreviousPage(FormPage $currentPage = null, Submission $submission = null, bool $defaultToFirst = false): ?FormPage
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

    public function getNextPage(FormPage $currentPage = null, Submission $submission = null): ?FormPage
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

    public function getCurrentPageIndex(FormPage $currentPage = null): int
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        // Return the index of the current page, in all our pages. Just for convenience
        if ($currentPage) {
            $index = array_search($currentPage->handle, ArrayHelper::getColumn($pages, 'handle'), true);

            if ($index) {
                return $index;
            }
        }

        return 0;
    }

    public function getPageIndex(FormPage $page = null): ?int
    {
        $pages = $this->getPages();

        // Return the index of the page, in all our pages. Just for convenience
        if ($page) {
            return array_search($page->handle, ArrayHelper::getColumn($pages, 'handle'), true);
        }

        return null;
    }

    public function setCurrentPage(FormPage $page = null): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!$page) {
            return;
        }

        Craft::$app->getSession()->set($this->_getSessionKey('page'), $page->handle);
    }

    public function resetCurrentPage(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getSession()->remove($this->_getSessionKey('page'));
    }

    public function isLastPage(FormPage $currentPage = null): bool
    {
        return !((bool)$this->getNextPage($currentPage));
    }

    public function isFirstPage(FormPage $currentPage = null): bool
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
        $submissionId = Craft::$app->getSession()->get($this->_getSessionKey('submissionId'));

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
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!$submission) {
            $this->resetCurrentSubmission();
        } else {
            Craft::$app->getSession()->set($this->_getSessionKey('submissionId'), $submission->id);
        }

        $this->_currentSubmission = $submission;
    }

    public function resetCurrentSubmission(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $this->resetCurrentPage();
        Craft::$app->getSession()->remove($this->_getSessionKey('submissionId'));

        $this->_currentSubmission = null;
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

        // Handle any UTF characters defined in the URL and encode them properly
        $url = utf8_encode($url);

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

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    public function getPageFieldErrors(Submission $submission): array
    {
        $errors = [];

        foreach ($this->getPages() as $page) {
            $errors[$page->handle] = $page->getFieldErrors($submission);
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
            $currentPageHandle = $currentPage->handle ?? null;
            $currentPageIndex = $this->getPageIndex($currentPage);
            $page = $context['page'] ?? null;
            $pageHandle = $page->handle ?? null;
            $pageIndex = $this->getPageIndex($page);

            return new HtmlTag('div', [
                'id' => 'fui-tab-' . $pageHandle,
                'class' => [
                    'fui-tab',
                    ($currentPageIndex > $pageIndex) ? 'fui-tab-complete' : false,
                    ($pageHandle == $currentPageHandle) ? 'fui-tab-active' : false,
                    $page->getFieldErrors($submission) ? 'fui-tab-error' : false,
                ],
                'data-fui-page-tab' => true,
                'data-field-conditions' => $page->getConditionsJson(),
            ]);
        }

        if ($key === 'pageTabLink') {
            $params = $context['params'] ?? null;
            $page = $context['page'] ?? null;
            $pageHandle = $page->handle ?? null;
            $pageIndex = $context['pageIndex'] ?? null;

            return new HtmlTag('a', [
                'href' => UrlHelper::actionUrl('formie/submissions/set-page', $params),
                'data-fui-page-tab-anchor' => true,
                'data-fui-page-index' => $pageIndex,
                'data-fui-page-handle' => $pageHandle ?? false,
            ]);
        }

        if ($key === 'page') {
            $page = $context['page'] ?? null;
            $pageHandle = $page->handle ?? null;
            $currentPage = $context['currentPage']->handle ?? null;

            return new HtmlTag('div', [
                'id' => "{$this->getFormId()}-p-{$pageHandle}",
                'class' => 'fui-page',
                'data' => [
                    'index' => $page->sortOrder ?? null,
                    'id' => $pageHandle,
                    'fui-page' => true,
                    'fui-page-hidden' => $this->hasMultiplePages() && $pageHandle != $currentPage ? true : false,
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

            $fields = [];
            $rowFields = $row['fields'] ?? [];

            foreach ($rowFields as $field) {
                if (!$field->getIsHidden()) {
                    $fields[] = $field;
                }
            }

            return new HtmlTag('div', [
                'class' => [
                    'fui-row fui-page-row',
                    $fields ? false : 'fui-row-empty',
                ],
            ]);
        }

        if ($key === 'buttonWrapper') {
            $page = $context['page'] ?? null;
            $containerAttributes = $page->settings->getContainerAttributes() ?? [];

            return new HtmlTag('div', [
                'class' => [
                    'fui-btn-wrapper',
                    "fui-btn-{$page->settings->buttonsPosition}",
                ],
            ], $containerAttributes, $page->settings->cssClasses);
        }

        if ($key === 'buttonContainer') {
            $page = $context['page'] ?? null;
            $showSaveButton = $page->settings->showSaveButton ?? false;

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
            $inputAttributes = $page->settings->getInputAttributes() ?? [];
            $nextPage = $this->getNextPage($page);

            return new HtmlTag('button', [
                'class' => [
                    'fui-btn fui-submit',
                    $nextPage ? 'fui-next' : false,
                ],
                'type' => 'submit',
                'data-submit-action' => 'submit',
                'data-field-conditions' => $page->settings->getConditionsJson(),
            ], $inputAttributes);
        }

        if ($key === 'saveButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->settings->getInputAttributes() ?? [];
            $saveButtonStyle = $page->settings->saveButtonStyle ?? 'link';
            
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
            $inputAttributes = $page->settings->getInputAttributes() ?? [];

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
            return new HtmlTag('span', [
                'class' => 'fui-progress-value',
            ]);
        }

        return null;
    }

    public function applyRenderOptions(array $renderOptions = []): void
    {
        // Allow a session key to be provided to scope incomplete submission content.
        // Base64 encode it not for security, just so it's not plain text an "obvious".
        $sessionKey = $renderOptions['sessionKey'] ?? null;
        $this->setSessionKey(base64_encode($sessionKey));

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
            'pages' => $this->getPages(),
            'themeConfig' => $this->getThemeConfigAttributes(),
            'redirectUrl' => $this->getRedirectUrl(),
            'currentPageHandle' => $this->getCurrentPage()->handle ?: '',
            'outputJsTheme' => $this->getFrontEndTemplateOption('outputJsTheme'),
            'enableUnloadWarning' => $pluginSettings->enableUnloadWarning,
            'enableBackSubmission' => $pluginSettings->enableBackSubmission,
            'ajaxTimeout' => $pluginSettings->ajaxTimeout,
        ];

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
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/conditions.js', true),
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

    public function getFrontEndJsEvents(): ?array
    {
        return $this->_frontEndJsEvents;
    }

    public function addFrontEndJsEvents(array $value): void
    {
        $this->_frontEndJsEvents[] = $value;
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
                $allAttributes[$configKey]['class'] = $fallback;
            }

            if ($this->resetClasses) {
                unset($allAttributes[$configKey]['class']);
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
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return [];
        }

        $snapshotData = Craft::$app->getSession()->get($this->_getSessionKey('snapshot'));

        if ($key) {
            return $snapshotData[$key] ?? [];
        }

        return $snapshotData ?? [];
    }

    public function setSnapshotData(string $key, mixed $data): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        // Get any existing snapshot data and merge, in case we set multiple times
        $snapshotData = $this->getSnapshotData();
        $currentData = $snapshotData[$key] ?? [];
        $snapshotData[$key] = array_merge($currentData, $data);

        Craft::$app->getSession()->set($this->_getSessionKey('snapshot'), $snapshotData);
    }

    public function resetSnapshotData(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getSession()->remove($this->_getSessionKey('snapshot'));
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
            }

            if ($submissions >= $this->settings->limitSubmissionsNumber) {
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
            ->from('{{%formie_forms}}')
            ->column();

        // Prepare the fields by stripping out IDs and UIDs. 
        // Use `unserialize/serialize` instead of `clone()` to deeply clone objects.
        $formFieldLayout = unserialize(serialize($this->getFormFieldLayout()));
        $formFieldLayout->id = null;
        $formFieldLayout->uid = '';

        foreach ($formFieldLayout->getFields() as $fieldKey => $field) {
            $field->id = null;
            $field->context = null;
            $field->uid = null;
        }

        // Prepare new data for the duplicated form
        return [
            'handle' => HandleHelper::getUniqueHandle($formHandles, $this->handle),
            'title' => Craft::t('formie', '{title} Copy', ['title' => $this->title]),
            'formFieldLayout' => $formFieldLayout,
        ];
    }

    public function beforeSave(bool $isNew): bool
    {
        $settings = Formie::$plugin->getSettings();
        $fieldsService = Craft::$app->getFields();

        // Set the default template from settings, if not already set - for new forms
        if ($isNew && !$this->templateId) {
            $this->templateId = $settings->getDefaultFormTemplateId();
        }

        // Save all the fields in the form builder
        $this->getFormFieldLayout()?->saveLayout($this);

        return parent::beforeSave($isNew);
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
        $record->formFieldLayout = $this->getFormFieldLayout()?->getSerializedConfig();
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
        Db::update('{{%formie_forms}}', ['handle' => $handle], ['id' => $this->id]);

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
        $rules[] = [['formFieldLayout'], 'validateFormFieldLayout'];
        $rules[] = [['settings'], 'validateFormSettings'];

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
        return UrlHelper::cpUrl("formie/forms/edit/{$this->id}");
    }
    

    // Private methods
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

    private function _getFrontEndJsModules(FormFieldInterface $field): array
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
}

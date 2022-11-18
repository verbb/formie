<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Miscellaneous;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\behaviors\FieldLayoutBehavior;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Html;
use verbb\formie\models\FieldLayout;
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
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
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

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Form');
    }

    /**
     * @inheritDoc
     */
    public static function refHandle(): ?string
    {
        return 'form';
    }

    /**
     * @inheritDoc
     */
    public static function hasTitles(): bool
    {
        return true;
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
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function find(): FormQuery
    {
        return new FormQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Form';
    }

    /**
     * @inheritDoc
     */
    public static function defineSources(string $context = null): array
    {
        $ids = self::_getAvailableFormIds();

        $sources = [
            [
                'key' => '*',
                'label' => 'All forms',
                'defaultSort' => ['title', 'desc'],
                'criteria' => ['id' => $ids],
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
                'criteria' => ['templateId' => $template->id, 'id' => $ids],
            ];
        }

        return $sources;
    }

    /**
     * @inheritDoc
     */
    protected static function defineFieldLayouts(string $source): array
    {
        if (self::$_layoutsByType !== null) {
            return self::$_layoutsByType;
        }

        return self::$_layoutsByType = Craft::$app->getFields()->getLayoutsByType(static::class);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'handle' => ['label' => Craft::t('app', 'Handle')],
            'template' => ['label' => Craft::t('app', 'Template')],
            'usageCount' => ['label' => Craft::t('formie', 'Usage Count')],
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
        $attributes[] = 'handle';
        $attributes[] = 'template';
        $attributes[] = 'dateCreated';
        $attributes[] = 'dateUpdated';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'handle'];
    }

    /**
     * @inheritDoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'handle' => Craft::t('app', 'Handle'),
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

    private static function _getAvailableFormIds(): int|array
    {
        $userSession = Craft::$app->getUser();

        $editableIds = [];

        // Fetch all form UIDs
        $formInfo = (new Query())
            ->from('{{%formie_forms}}')
            ->select(['id', 'uid'])
            ->all();

        // Can the user edit _every_ form?
        if ($userSession->checkPermission('formie-viewForms')) {
            $editableIds = ArrayHelper::getColumn($formInfo, 'id');
        } else {
            // Find all UIDs the user has permission to
            foreach ($formInfo as $form) {
                if ($userSession->checkPermission('formie-manageForm:' . $form['uid'])) {
                    $editableIds[] = $form['id'];
                }
            }
        }

        // Important to check if empty, there are zero editable forms, but as we use this as a criteria param
        // that would return all forms, not what we want.
        if (!$editableIds) {
            $editableIds = 0;
        }

        return $editableIds;
    }


    //  Properties
    // =========================================================================

    public ?string $handle = null;
    public ?string $oldHandle = null;
    public ?string $fieldContentTable = null;
    public ?int $templateId = null;
    public ?int $submitActionEntryId = null;
    public ?int $submitActionEntrySiteId = null;
    public ?int $defaultStatusId = null;
    public string $dataRetention = 'forever';
    public ?string $dataRetentionValue = null;
    public string $userDeletedAction = 'retain';
    public string $fileUploadsAction = 'retain';
    public ?int $fieldLayoutId = null;
    public ?FormSettings $settings = null;

    public bool $resetClasses = false;

    private ?CraftFieldLayout $_fieldLayout = null;
    private ?FieldLayout $_formFieldLayout = null;
    private ?array $_fields = null;
    private ?array $_rows = null;
    private ?array $_pages = null;
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

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->title;
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->settings instanceof FormSettings) {
            $this->settings->setForm($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => static::class,
        ];

        return $behaviors;
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
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can('formie-deleteForms');
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getFormFieldLayout(): FieldLayout
    {
        if ($this->_formFieldLayout !== null) {
            return $this->_formFieldLayout;
        }

        /* @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');

        return $this->_formFieldLayout = $behavior->getFieldLayout();
    }

    public function setFormFieldLayout(FieldLayout $fieldLayout): void
    {
        /* @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        $behavior->setFieldLayout($fieldLayout);

        $this->_formFieldLayout = $fieldLayout;
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

    /**
     * Returns the form's field context.
     */
    public function getFormFieldContext(): string
    {
        return "formie:{$this->uid}";
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl("formie/forms/edit/{$this->id}");
    }

    /**
     * Returns the form's template, or null if not set.
     *
     * @return FormTemplate|null
     */
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

    /**
     * Sets the form template.
     *
     * @param FormTemplate|null $template
     */
    public function setTemplate(?FormTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

    /**
     * Returns the default status for a form.
     */
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
                $projectConfig = Craft::$app->projectConfig;

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

                    Formie::getInstance()->getStatuses()->saveStatus($this->_defaultStatus);
                }
            }
        }

        return $this->_defaultStatus;
    }

    /**
     * Sets the default status.
     *
     * @param Status|null $status
     */
    public function setDefaultStatus(?Status $status): void
    {
        if ($status) {
            $this->_defaultStatus = $status;
            $this->defaultStatusId = $status->id;
        } else {
            $this->_defaultStatus = $this->defaultStatusId = null;
        }
    }

    /**
     * Gets a form's JSON encodable config for rendering the form builder.
     *
     * @throws InvalidConfigException
     */
    public function getFormConfig(): array
    {
        $pages = [];
        $fieldLayout = $this->getFormFieldLayout();

        foreach ($fieldLayout->getPages() as $page) {
            /* @var FormFieldInterface[] $pageFields */
            $rows = $page->getRows();
            $rowConfig = Formie::$plugin->getFields()->getRowConfig($rows);

            $pages[] = [
                'id' => $page->id,
                'label' => $page->name,
                'errors' => $page->getErrors(),
                'hasError' => (bool)$page->getErrors(),
                'rows' => $rowConfig,
                'settings' => $page->settings->toArray(),
            ];
        }

        // Must always provide at least one page. If not, seems to really mess
        // up Vue's reactivity of pages as an array.
        if (!$pages) {
            $pages[] = [
                'id' => StringHelper::appendRandomString('new', 16),
                'label' => Craft::t('formie', 'Page 1'),
                'rows' => [],
            ];
        }

        $attributes = $this->toArray();

        return array_merge($attributes, [
            'pages' => $pages,
            'errors' => $this->getErrors(),
            'hasError' => (bool)$this->getErrors(),
        ]);
    }

    public function getFormId(): string
    {
        if ($this->_formId) {
            return $this->_formId;
        }

        // Provide a unique ID for this field, used as a namespace for IDs of elements in the form
        return $this->_formId = 'fui-' . $this->handle . '-' . StringHelper::randomString(6);
    }

    public function setFormId($value): void
    {
        $this->_formId = $value;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * Returns the form’s pages.
     *
     * @return FieldLayoutPage[] The form’s pages.
     */
    public function getPages(): array
    {
        if ($this->_pages !== null) {
            return $this->_pages;
        }

        // Check for a deleted form
        try {
            $fieldLayout = $this->getFormFieldLayout();
        } catch (InvalidConfigException) {
            return [];
        }

        return $this->_pages = $fieldLayout->getPages();
    }

    /**
     * Returns the form’s rows.
     *
     * @return FieldInterface[][] The form’s rows.
     */
    public function getRows(): array
    {
        if ($this->_rows !== null) {
            return $this->_rows;
        }

        $pages = $this->getPages();

        $rows = [];

        foreach ($pages as $page) {
            $rows[] = $page->getRows();
        }

        return $this->_rows = array_merge(...$rows);
    }

    /**
     * Returns true if any form field has conditions configured.
     */
    public function hasFieldConditions(): bool
    {
        foreach ($this->getCustomFields() as $field) {
            if ($field->enableConditions) {
                return true;
            }

            if ($field instanceof NestedFieldInterface) {
                foreach ($field->getCustomFields() as $nestedField) {
                    if ($nestedField->enableConditions) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns true if any page buttons has conditions configured.
     */
    public function hasButtonConditions(): bool
    {
        foreach ($this->getPages() as $page) {
            if ($page->settings->enableNextButtonConditions) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if any page has conditions configured.
     */
    public function hasPageConditions(): bool
    {
        foreach ($this->getPages() as $page) {
            if ($page->settings->enablePageConditions) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if any field, page buttons or page has conditions configured.
     */
    public function hasConditions(): bool
    {
        return $this->hasFieldConditions() || $this->hasButtonConditions() || $this->hasPageConditions();
    }

    /**
     * Returns true if the form has more than 1 page.
     */
    public function hasMultiplePages(): bool
    {
        return count($this->getPages()) > 1;
    }

    /**
     * Returns the current page.
     *
     */
    public function getCurrentPage(): ?FieldLayoutPage
    {
        $currentPage = null;
        $pages = $this->getPages();

        if ($pages) {
            // Check if there's a session variable
            $pageId = Craft::$app->getSession()->get($this->_getSessionKey('pageId'));

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

    /**
     * Returns the previous page.
     *
     * @param FieldLayoutPage|null $currentPage
     */
    public function getPreviousPage(FieldLayoutPage $currentPage = null, $submission = null): ?FieldLayoutPage
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
            $prev = $this->getPreviousPage($prev, $submission);
        }

        return $prev ?: null;
    }

    /**
     * Returns the next page.
     *
     * @param FieldLayoutPage|null $currentPage
     */
    public function getNextPage(FieldLayoutPage $currentPage = null, $submission = null): ?FieldLayoutPage
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

    /**
     * Returns the zero-based index of the current page in the array of all pages.
     *
     * @param FieldLayoutPage|null $currentPage
     * @return int
     */
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

    /**
     * Returns the index of a page in the array of all pages.
     *
     * @param FieldLayoutPage|null $page
     * @return int|null
     */
    public function getPageIndex(FieldLayoutPage $page = null): ?int
    {
        $pages = $this->getPages();

        // Return the index of the page, in all our pages. Just for convenience
        if ($page) {
            return array_search($page->id, ArrayHelper::getColumn($pages, 'id'), true);
        }

        return null;
    }

    /**
     * Sets the current page.
     *
     * @param $page
     * @throws MissingComponentException
     */
    public function setCurrentPage($page): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!$page) {
            return;
        }

        Craft::$app->getSession()->set($this->_getSessionKey('pageId'), $page->id);
    }

    /**
     * Removes the current page from the session.
     *
     * @throws MissingComponentException
     */
    public function resetCurrentPage(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getSession()->remove($this->_getSessionKey('pageId'));
    }

    /**
     * Returns true if the current page is the last page.
     *
     * @param null $currentPage
     */
    public function isLastPage($currentPage = null): bool
    {
        return !((bool)$this->getNextPage($currentPage));
    }

    /**
     * Returns true if the current page is the first page.
     *
     * @param null $currentPage
     */
    public function isFirstPage($currentPage = null): bool
    {
        return !((bool)$this->getPreviousPage($currentPage));
    }

    /**
     * Returns the current submission.
     *
     * @return Submission|null
     * @throws MissingComponentException
     */
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

    /**
     * Sets the current submission.
     *
     * @param Submission|null $submission
     * @throws MissingComponentException
     */
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

    /**
     * Removes the current submission from the session.
     *
     * @throws MissingComponentException
     */
    public function resetCurrentSubmission(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $this->resetCurrentPage();
        Craft::$app->getSession()->remove($this->_getSessionKey('submissionId'));

        $this->_currentSubmission = null;
    }

    /**
     * Sets the current submission, when editing.
     *
     * @param Submission|null $submission
     */
    public function setSubmission(?Submission $submission): void
    {
        $this->_editingSubmission = $submission;
    }

    /**
     * Whether we're editing a submission or not. Useful to turn off captchas.
     */
    public function isEditingSubmission(): bool
    {
        return (bool)$this->_editingSubmission;
    }

    /**
     * Returns the action URL for form submissions. Changes depending on whether we're editing
     * a form on the front-end, or submitting as normal.
     */
    public function getActionUrl(): string
    {
        if ($this->isEditingSubmission()) {
            return 'formie/submissions/save-submission';
        }

        return 'formie/submissions/submit';
    }

    public function getRelations(): string
    {
        if ($values = $this->_relations) {
            return StringHelper::encenc(Json::encode($values));
        }

        return '';
    }

    public function setRelations($elements = []): void
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

    public function setPopulatedFieldValues($values): void
    {
        $this->_populatedFieldValues = $values;
    }

    public function getPopulatedFieldValuesFromRequest()
    {
        $value = (string)Craft::$app->getRequest()->getBodyParam('extraFields', '');

        return Json::decode(StringHelper::decdec($value));
    }

    /**
     * Returns the form’s fields.
     *
     * @return FormFieldInterface[] The form’s fields.
     * @throws InvalidConfigException
     */
    public function getCustomFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $fieldLayout = $this->getFormFieldLayout();

        return $this->_fields = $fieldLayout->getCustomFields();
    }

    /**
     * Returns a field by its handle.
     */
    public function getFieldByHandle(string $handle): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getCustomFields(), 'handle', $handle);
    }

    /**
     * Returns a field by its id.
     *
     * @param int $id
     * @return FormFieldInterface|null
     * @throws InvalidConfigException
     */
    public function getFieldById(int $id): ?FormFieldInterface
    {
        return ArrayHelper::firstWhere($this->getCustomFields(), 'id', $id);
    }

    /**
     * Returns the form's notifications.
     *
     * @return array|null
     */
    public function getNotifications(): ?array
    {
        if ($this->_notifications === null) {
            $this->_notifications = Formie::$plugin->getNotifications()->getFormNotifications($this);
        }

        return $this->_notifications;
    }

    /**
     * Sets the form's notifications.
     *
     * @param Notification[] $notifications
     */
    public function setNotifications(array $notifications): void
    {
        $this->_notifications = $notifications;
    }

    /**
     * Returns the form's enabled notifications.
     *
     * @return Notification[]
     */
    public function getEnabledNotifications(): array
    {
        return ArrayHelper::where($this->getNotifications(), 'enabled', true);
    }

    /**
     * Gets the form's redirect URL.
     *
     * @param bool $checkLastPage
     * @return String
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function getRedirectUrl(bool $checkLastPage = true): string
    {
        $request = Craft::$app->getRequest();
        $url = '';

        // We don't want to show the redirect URL on unfinished mutli-page forms, so check first
        if ($this->settings->submitMethod == 'page-reload') {
            if ($checkLastPage && !$this->isLastPage()) {
                return $url;
            }
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
        if ($url && $request->getIsSiteRequest()) {
            $url = UrlHelper::url($url, $request->getQueryStringWithoutPath());
        }

        return $url;
    }

    /**
     * Gets the form's redirect entry, or null if not set.
     *
     * @return Entry|null
     */
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

    /**
     * @inheritdoc
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    public function getPageFieldErrors($submission): array
    {
        $errors = [];

        foreach ($this->getPages() as $page) {
            $errors[$page->id] = $page->getFieldErrors($submission);
        }

        return array_filter($errors);
    }

    /**
     * Returns the template for a form component.
     *
     * @param array|string $components can be 'form', 'page' or ['field1', 'field2'].
     * @param array $variables any variables to use with `$view->renderTemplate()`.
     * @return string
     * @throws Exception
     * @throws LoaderError
     */
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

        // Otherwise, fall bacl on the default Formie templates.
        // Find the first available, resolved template in potential multiple components
        foreach ($components as $component) {
            $templatePath = 'formie/_special/form-template' . DIRECTORY_SEPARATOR . $component;

            // Note we need to include `.html` for default templates, because of users potentially setting `defaultTemplateExtensions`
            // which would be unable to find our templates if they disallow `.html`.
            // Check for `form.html` or `form/index.html` because we have to try resolving on our own...
            $paths = [
                $templatePath . '.html',
                $templatePath . DIRECTORY_SEPARATOR . 'index.html',
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
                    'submit-method' => $this->settings->submitMethod ?: false,
                    'submit-action' => $this->settings->submitAction ?: false,
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
            ]);
        }

        if ($key === 'alertSuccess') {
            return new HtmlTag('div', [
                'class' => [
                    'fui-alert fui-alert-success',
                    'fui-alert-' . $this->settings->submitActionMessagePosition,
                ],
                'role' => 'alert',
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
            $currentPageId = $context['currentPage']->id ?? null;
            $page = $context['page'] ?? null;
            $pageId = $page->id ?? null;

            return new HtmlTag('div', [
                'id' => 'fui-tab-' . $pageId,
                'class' => [
                    'fui-tab',
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

            return new HtmlTag('div', array_merge([
                'class' => [
                    'fui-btn-wrapper',
                    "fui-btn-{$page->settings->buttonsPosition}",
                ],
            ], $containerAttributes), $page->settings->cssClasses);
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

            return new HtmlTag('button', array_merge([
                'class' => [
                    'fui-btn fui-submit',
                    $nextPage ? 'fui-next' : false,
                ],
                'type' => 'submit',
                'data-submit-action' => 'submit',
                'data-field-conditions' => $page->settings->getConditionsJson(),
            ], $inputAttributes));
        }

        if ($key === 'saveButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->settings->getInputAttributes() ?? [];
            $saveButtonStyle = $page->settings->saveButtonStyle ?? 'link';
            
            return new HtmlTag('button', array_merge([
                'class' => [
                    'fui-btn fui-save',
                    $saveButtonStyle === 'button' ? 'fui-submit' : 'fui-btn-link',
                ],
                'type' => 'submit',
                'data-submit-action' => 'save',
            ], $inputAttributes));
        }

        if ($key === 'backButton') {
            $page = $context['page'] ?? null;
            $inputAttributes = $page->settings->getInputAttributes() ?? [];

            return new HtmlTag('button', array_merge([
                'class' => 'fui-btn fui-prev',
                'type' => 'submit',
                'data-submit-action' => 'back',
            ], $inputAttributes));
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
        // Allow a session key to be provided to scope incomplete submission content
        $this->_sessionKey = $renderOptions['sessionKey'] ?? null;

        // Theme options
        $templateConfig = $renderOptions['themeConfig'] ?? [];
        $pluginConfig = Formie::$plugin->getSettings()->themeConfig ?? [];

        // If not set at the template level, check if it's set a the plugin level.
        // If set for both, `setThemeConfig()` will merge.
        if ($templateConfig) {
            $this->setThemeConfig($templateConfig);
        } else if ($pluginConfig) {
            $this->setThemeConfig($pluginConfig);
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
            'classes' => $this->getFrontEndClasses(),
            'redirectUrl' => $this->getRedirectUrl(),
            'currentPageId' => $this->getCurrentPage()->id ?: '',
            'outputJsTheme' => $this->getFrontEndTemplateOption('outputJsTheme'),
            'enableUnloadWarning' => $pluginSettings->enableUnloadWarning,
            'enableBackSubmission' => $pluginSettings->enableBackSubmission,
            'ajaxTimeout' => $pluginSettings->ajaxTimeout,
        ];

        $registeredJs = [];

        // Add any JS per-field
        foreach ($this->getCustomFields() as $field) {
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
                if ($js = $integration->getFrontEndJsVariables($this)) {
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

    public function addFrontEndJsEvents($value): void
    {
        $this->_frontEndJsEvents[] = $value;
    }

    public function getFrontEndClasses()
    {
        $allClasses = [];

        // Provide defaults to fallback on, which aren't in Theme Config
        $configKeys = [
            'loading' => 'fui-loading',
            'errorMessage' => 'fui-error-message',
            'disabled' => 'fui-disabled',
            'tabError' => 'fui-tab-error',
            'tabActive' => 'fui-tab-active',
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

                $allClasses[$configKey] = implode(' ', $classes);
            } else if ($fieldTag) {
                $classes = $fieldTag->attributes['class'] ?? $fallback;

                if (!is_array($classes)) {
                    $classes = [$classes];
                }

                $allClasses[$configKey] = implode(' ', $classes);
            } else {
                $allClasses[$configKey] = $fallback;
            }
        }

        return $allClasses;
    }

    public function getFrontEndTemplateOption($option): bool
    {
        $output = true;

        if ($template = $this->getTemplate()) {
            $output = (bool)$template->$option;
        }

        return $output;
    }

    public function getFrontEndTemplateLocation($location)
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

    public function setSettings($settings, $updateSnapshot = true): void
    {
        $this->settings->setAttributes($settings, false);

        // Set snapshot data to ensure it's persisted
        if ($updateSnapshot) {
            $this->setSnapshotData('form', $settings);

            // Save this, so we know when we're applying form settings later
            $this->_appliedFormSettings = true;
        }
    }

    public function setFieldSettings($handle, $settings, $updateSnapshot = true): void
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

    public function setIntegrationSettings(string $handle, array $settings, $updateSnapshot = true): void
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

    public function getSnapshotData($key = null)
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

    public function setSnapshotData($key, $data): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $snapshotData = $this->getSnapshotData();
        $snapshotData[$key] = $data;

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
        if ($this->settings->scheduleForm) {
            return !DateTimeHelper::isInThePast($this->settings->scheduleFormStart);
        }
        
        return false;
    }

    public function isAfterSchedule(): bool
    {
        if ($this->settings->scheduleForm) {
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

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        // Run basic model validation first.
        $validates = parent::validate($attributeNames, $clearErrors);

        // Run form field validation as well.
        if (!Formie::$plugin->getForms()->validateFormFields($this)) {
            $validates = false;

            // Compile the errors
            foreach ($this->getCustomFields() as $field) {
                if ($field->hasErrors()) {
                    $this->addError('fields.' . $field->handle, $field->getErrors());
                }
            }
        }

        // Lastly, run notification validation.
        foreach ($this->getNotifications() as $notification) {
            if (!$notification->validate()) {
                $validates = false;

                $this->addError('notifications.' . $notification->handle, $notification->getErrors());
                break;
            }
        }

        return $validates;
    }

    /**
     * @inheritDoc
     */
    public function hasErrors($attribute = null): bool
    {
        $hasErrors = parent::hasErrors($attribute);

        // Be careful here, this will be called immediately, and if there's some issues with the form
        // (lack of fieldLayout for a soft-deleted form), we'll get in real trouble
        try {
            if (!$hasErrors) {
                $hasErrors = Formie::$plugin->getForms()->pagesHasErrors($this);
            }

            if (!$hasErrors) {
                foreach ($this->getNotifications() as $notification) {
                    if ($notification->hasErrors()) {
                        $hasErrors = true;
                        break;
                    }
                }
            }
        } catch (Throwable) {
        }

        return $hasErrors;
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew): void
    {
        // Get the node record
        if (!$isNew) {
            $record = FormRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid form ID: ' . $this->id);
            }
        } else {
            $record = new FormRecord();
            $record->id = $this->id;
        }

        $record->handle = $this->handle;
        $record->fieldContentTable = $this->fieldContentTable;
        $record->settings = $this->settings;
        $record->templateId = $this->templateId;
        $record->submitActionEntryId = $this->submitActionEntryId;
        $record->submitActionEntrySiteId = $this->submitActionEntrySiteId;
        $record->defaultStatusId = $this->defaultStatusId;
        $record->dataRetention = $this->dataRetention;
        $record->dataRetentionValue = $this->dataRetentionValue;
        $record->fileUploadsAction = $this->fileUploadsAction;
        $record->userDeletedAction = $this->userDeletedAction;
        $record->fieldLayoutId = $this->fieldLayoutId;
        $record->uid = $this->uid;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete(): bool
    {
        if (parent::beforeDelete()) {
            return Formie::$plugin->getForms()->deleteForm($this);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

        Craft::$app->getDb()->createCommand()
            ->update('{{%formie_forms}}', ['handle' => $handle], [
                'id' => $this->id,
            ])->execute();

        $this->handle = $handle;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterRestore(): void
    {
        // Restore the field layout too
        if ($this->fieldLayoutId && !Craft::$app->getFields()->restoreLayoutById($this->fieldLayoutId)) {
            Craft::warning("Form {$this->id} restored, but its field layout ({$this->fieldLayoutId}) was not.");
        }

        $db = Craft::$app->getDb();

        // Rename the content table - if it's still around
        if ($db->tableExists($this->fieldContentTable)) {
            $newContentTableName = Formie::$plugin->getForms()->defineContentTableName($this);

            Db::renameTable($this->fieldContentTable, $newContentTableName);

            $db->createCommand()
                ->update('{{%formie_forms}}', ['fieldContentTable' => $newContentTableName], [
                    'id' => $this->id,
                ])->execute();

            $this->fieldContentTable = $newContentTableName;
        } else {
            Craft::warning("Form {$this->id} content table {$this->fieldContentTable} not found.");
        }

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


    // Protected methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title', 'handle'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['templateId', 'submitActionEntryId', 'submitActionEntrySiteId', 'defaultStatusId', 'fieldLayoutId'], 'number', 'integerOnly' => true];

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

    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'usageCount' => (new Query())
                ->from([Table::RELATIONS])
                ->where(['targetId' => $this->id])
                ->count(),
            default => parent::tableAttributeHtml($attribute),
        };
    }

    // Private methods
    // =========================================================================

    private function _getSessionKey($key, $useSubmissionId = true): string
    {
        $keys = ['formie', $this->id, $this->_sessionKey];

        // Return a different session namespace when editing a submission
        if ($useSubmissionId && $this->_editingSubmission && $this->_editingSubmission->id) {
            $keys[] = $this->_editingSubmission->id;
        }

        $keys[] = $key;

        return implode(':', array_filter($keys));
    }

    private function _getFrontEndJsModules($field): array
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

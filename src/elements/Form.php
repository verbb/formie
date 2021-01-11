<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\behaviors\FieldLayoutBehavior;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\Status;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\services\Statuses;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout as CraftFieldLayout;
use craft\validators\HandleValidator;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\Validator;
use Throwable;

class Form extends Element
{
    // Public Properties
    // =========================================================================

    /**
     * @var FormSettings
     */
    public $settings;

    public $handle;
    public $oldHandle;
    public $fieldContentTable;
    public $templateId;
    public $submitActionEntryId;
    public $requireUser = false;
    public $availability = 'always';
    public $availabilityFrom;
    public $availabilityTo;
    public $availabilitySubmissions;
    public $defaultStatusId;
    public $dataRetention = 'forever';
    public $dataRetentionValue;
    public $userDeletedAction = 'retain';
    public $fileUploadsAction = 'retain';
    public $fieldLayoutId;


    // Private Properties
    // =========================================================================

    private $_fieldLayout;
    private $_formFieldLayout;
    private $_fields;
    private $_pages;
    private $_template;
    private $_defaultStatus;
    private $_submitActionEntry;
    private $_notifications;
    private $_editingSubmission;
    private $_formId;
    private static $_layoutsByType;


    // Static
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
    public static function refHandle()
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
    public static function find(): ElementQueryInterface
    {
        return new FormQuery(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function gqlTypeNameByContext($context): string
    {
        return 'Form';
    }

    /**
     * @inheritDoc
     */
    public static function defineSources(string $context = null): array
    {
        $ids = self::_getEditableFormIds();
        
        $sources = [
            [
                'key' => '*',
                'label' => 'All forms',
                'defaultSort' => ['title', 'desc'],
                'criteria' => ['id' => $ids],
            ]
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
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        $canDeleteForms = Craft::$app->getUser()->checkPermission('formie-deleteForms');

        if ($canDeleteForms) {
            $actions[] = $elementsService->createAction([
                'type' => Delete::class,
                'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected forms?'),
                'successMessage' => Craft::t('formie', 'Forms deleted.'),
            ]);
        }

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Forms restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some forms restored.'),
            'failMessage' => Craft::t('formie', 'Forms not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title', 'handle'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['templateId', 'submitActionEntryId', 'defaultStatusId', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], 'string', 'max' => 60];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
        ];

        $rules[] = ['handle', function($attribute, $params, Validator $validator) {
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
        }];

        return $rules;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->title;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = new FormSettings();
        } else {
            $settings = Json::decodeIfJson($this->settings);
            $this->settings = new FormSettings($settings);
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => static::class,
        ];

        return $behaviors;
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getFormFieldLayout()
    {
        if ($this->_formFieldLayout !== null) {
            return $this->_formFieldLayout;
        }

        /* @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        
        return $this->_formFieldLayout = $behavior->getFieldLayout();
    }

    /**
     * @param FieldLayout $fieldLayout
     */
    public function setFormFieldLayout(FieldLayout $fieldLayout)
    {
        /* @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->setFieldLayout($fieldLayout);
    }

    /**
     * @return CraftFieldLayout|null
     */
    public function getFieldLayout()
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        try {
            $template = $this->getTemplate();
        } catch (InvalidConfigException $e) {
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
     *
     * @return string
     */
    public function getFormFieldContext(): string
    {
        return "formie:{$this->uid}";
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl("formie/forms/edit/{$this->id}");
    }

    /**
     * Returns the form's template, or null if not set.
     *
     * @return FormTemplate|null
     */
    public function getTemplate()
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
    public function setTemplate($template)
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
     *
     * @return Status
     */
    public function getDefaultStatus(): Status
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
                        'isDefault' => 1
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
    public function setDefaultStatus($status)
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
     * @return array
     * @throws InvalidConfigException
     */
    public function getFormConfig(): array
    {
        $pages = [];
        $fieldLayout = $this->getFormFieldLayout();

        if (!$fieldLayout) {
            return [];
        }

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
                'id' => uniqid('new'),
                'label' => Craft::t('site', 'Page 1'),
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

    /**
     * @inheritDoc
     */
    public function getFormId()
    {
        if ($this->_formId) {
            return $this->_formId;
        }
        
        return $this->_formId = uniqid("formie-form-{$this->id}");
    }

    /**
     * @inheritDoc
     */
    public function setFormId($value)
    {
        $this->_formId = $value;
    }

    /**
     * @inheritDoc
     */
    public function getConfigJson()
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
        } catch (InvalidConfigException $e) {
            return [];
        }

        if (!$fieldLayout) {
            return [];
        }

        return $this->_pages = $fieldLayout->getTabs();
    }

    /**
     * Returns true if the form has more than 1 page.
     *
     * @return bool
     */
    public function hasMultiplePages(): bool
    {
        return count($this->getPages()) > 1;
    }

    /**
     * Returns the current page.
     *
     * @return FieldLayoutPage|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getCurrentPage()
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
     * @return FieldLayoutPage|null
     */
    public function getPreviousPage($currentPage = null)
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

        return $prev ?: null;
    }

    /**
     * Returns the next page.
     *
     * @param FieldLayoutPage|null $currentPage
     * @return FieldLayoutPage|null
     */
    public function getNextPage($currentPage = null)
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

        return $next ?: null;
    }

    /**
     * Returns the index of the current page in the array of all pages.
     *
     * @param FieldLayoutPage|null $currentPage
     * @return int|null
     */
    public function getCurrentPageIndex($currentPage = null)
    {
        $pages = $this->getPages();

        if (!$currentPage) {
            $currentPage = $this->getCurrentPage();
        }

        // Return the index of the current page, in all our pages. Just for convenience
        if ($currentPage) {
            $index = array_search($currentPage, $pages);

            if ($index) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Sets the current page.
     *
     * @param $page
     * @throws MissingComponentException
     */
    public function setCurrentPage($page)
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
    public function resetCurrentPage()
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
     * @return bool
     */
    public function isLastPage($currentPage = null)
    {
        return !((bool)$this->getNextPage($currentPage));
    }

    /**
     * Returns true if the current page is the first page.
     *
     * @param null $currentPage
     * @return bool
     */
    public function isFirstPage($currentPage = null)
    {
        return !((bool)$this->getPreviousPage($currentPage));
    }

    /**
     * Returns the current submission.
     *
     * @return Submission|null
     * @throws MissingComponentException
     */
    public function getCurrentSubmission()
    {
        // See if there's a submission on routeParams - an error has occurred.
        $params = Craft::$app->getUrlManager()->getRouteParams();

        // Make sure to check the right submission
        if (isset($params['submission']) && $params['submission']->form->id == $this->id) {
            return $params['submission'];
        }

        // Check if there's a session variable
        $submissionId = Craft::$app->getSession()->get($this->_getSessionKey('submissionId'));

        if ($submissionId && $submission = Submission::find()->id($submissionId)->isIncomplete(true)->one()) {
            return $submission;
        }

        // Or, if we're editing a submission
        if ($submission = $this->_editingSubmission) {
            return $submission;
        }

        return null;
    }

    /**
     * Sets the current submission.
     *
     * @param Submission|null $submission
     * @throws MissingComponentException
     */
    public function setCurrentSubmission($submission)
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!$submission) {
            $this->resetCurrentSubmission();
        } else {
            Craft::$app->getContent()->populateElementContent($submission);
            Craft::$app->getSession()->set($this->_getSessionKey('submissionId'), $submission->id);
        }
    }

    /**
     * Removes the current submission from the session.
     *
     * @throws MissingComponentException
     */
    public function resetCurrentSubmission()
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $this->resetCurrentPage();
        Craft::$app->getSession()->remove($this->_getSessionKey('submissionId'));
    }

    /**
     * Sets the current submission, when editing.
     *
     * @param Submission|null $submission
     * @throws MissingComponentException
     */
    public function setSubmission($submission)
    {
        $this->_editingSubmission = $submission;
    }

    /**
     * Whether we're editing a submission or not. Useful to turn off captchas.
     */
    public function isEditingSubmission()
    {
        return (bool)$this->_editingSubmission;
    }

    /**
     * Returns the action URL for form submissions. Changes depending on whether we're editing
     * a form on the front-end, or submitting as normal.
     */
    public function getActionUrl()
    {
        if ($this->isEditingSubmission()) {
            return 'formie/submissions/save-submission';
        }

        return 'formie/submissions/submit';
    }

    /**
     * Returns the form’s fields.
     *
     * @return FormFieldInterface[] The form’s fields.
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $fieldLayout = $this->getFormFieldLayout();

        if (!$fieldLayout) {
            return [];
        }

        return $this->_fields = $fieldLayout->getFields();
    }

    /**
     * Returns a field by it's handle.
     *
     * @param string $handle
     * @return FormFieldInterface|null
     */
    public function getFieldByHandle(string $handle)
    {
        $fields = $this->getFields();
        return ArrayHelper::firstWhere($fields, 'handle', $handle);
    }

    /**
     * Returns the form's notifications.
     *
     * @return Notification[]
     */
    public function getNotifications(): array
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
    public function setNotifications(array $notifications)
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
     * @return String
     */
    public function getRedirectUrl()
    {
        // We don't want to show the redirect URL on unfinished mutli-page forms, so check first
        if (!$this->isLastPage() && $this->settings->submitMethod == 'page-reload') {
            return '';
        }

        // Allow settings to statically set the redirect URL (from templates)
        if ($this->settings->redirectUrl) {
            return $this->settings->redirectUrl;
        }

        if ($this->settings->submitAction == 'entry' && $this->getRedirectEntry()) {
            return $this->getRedirectEntry()->url;
        }

        if ($this->settings->submitAction == 'url') {
            // Parse Twig
            return Craft::$app->getView()->renderString($this->settings->submitActionUrl);
        }

        return '';
    }

    /**
     * Gets the form's redirect entry, or null if not set.
     *
     * @return Entry|null
     */
    public function getRedirectEntry()
    {
        if (!$this->submitActionEntryId) {
            return null;
        }

        if (!$this->_submitActionEntry) {
            $this->_submitActionEntry = Craft::$app->getEntries()->getEntryById($this->submitActionEntryId);
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

    /**
     * @inheritdoc
     */
    public function getFrontEndJsVariables(): array
    {   
        $pluginSettings = Formie::$plugin->getSettings();

        // Only provide what we need, both for security/privacy but also DOM size
        $settings = [
            // Send the site-relative root. This is to ensure submission requests are setup
            // to go to the root of a multi site. Important for sub-directory setups where
            // posting to '/' would be the incorrect primary site.
            'siteRootUrl' => rtrim(UrlHelper::siteUrl(''), '/'),

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

            'redirectUrl' => $this->getRedirectUrl(),
            'currentPageId' => $this->getCurrentPage()->id ?? '',
            'outputJsTheme' => $this->getFrontEndTemplateOption('outputJsTheme'),
            'enableUnloadWarning' => $pluginSettings->enableUnloadWarning,
        ];

        $registeredJs = [];

        // Add any JS per-field
        foreach ($this->getFields() as $field) {
            $js = $field->getFrontEndJsModules();

            // Handle multiple registrations
            if (isset($js[0])) {
                $registeredJs = array_merge($registeredJs, $js);
            } else {
                $registeredJs[] = $js;
            }
        }

        // Add any JS for enabled captchas - force fetch because we're dealing with potential ajax forms
        // Normally, this function returns only if the `showAllPages` property is set.
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($this, null, true);

        foreach ($captchas as $captcha) {
            $js = $captcha->getFrontEndJsVariables($this);

            if (isset($js[0])) {
                $registeredJs = array_merge($registeredJs, $js);
            } else {
                $registeredJs[] = $js;
            }
        }

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

    /**
     * @inheritdoc
     */
    public function getFrontEndTemplateOption($option): bool
    {
        $output = true;

        if ($template = $this->getTemplate()) {
            $output = (bool)$template->$option;
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndTemplateLocation($location)
    {
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

    /**
     * @inheritDoc
     */
    public function setSettings($settings)
    {
        $this->settings->setAttributes($settings, false);
    }

    /**
     * @inheritDoc
     */
    public function setFieldSettings($handle, $settings)
    {
        $field = $this->getFieldByHandle($handle);

        if ($field) {
            $field->setAttributes($settings, false);
        }
    }


    // Events
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        // Run basic model validation first.
        $validates = parent::validate($attributeNames, $clearErrors);

        // Run form field validation as well.
        if (!Formie::$plugin->getForms()->validateFormFields($this)) {
            $validates = false;
        }

        // Lastly, run notification validation.
        foreach ($this->getNotifications() as $notification) {
            if (!$notification->validate()) {
                $validates = false;
                break;
            }
        }

        return $validates;
    }

    /**
     * @inheritDoc
     */
    public function hasErrors($attribute = null)
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
        } catch (Throwable $e) {}

        return $hasErrors;
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew)
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
        $record->requireUser = $this->requireUser;
        $record->availability = $this->availability;
        $record->availabilityFrom = $this->availabilityFrom;
        $record->availabilityTo = $this->availabilityTo;
        $record->availabilitySubmissions = $this->availabilitySubmissions;
        $record->defaultStatusId = $this->defaultStatusId;
        $record->dataRetention = $this->dataRetention;
        $record->dataRetentionValue = $this->dataRetentionValue;
        $record->fileUploadsAction = $this->fileUploadsAction;
        $record->userDeletedAction = $this->userDeletedAction;
        $record->fieldLayoutId = $this->fieldLayoutId;
        $record->uid = $this->uid;

        $record->save(false);

        return parent::afterSave($isNew);
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
    public function afterDelete()
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
    public function afterRestore()
    {
        // Restore the field layout too
        if ($this->fieldLayoutId && !Craft::$app->getFields()->restoreLayoutById($this->fieldLayoutId)) {
            Craft::warning("Form {$this->id} restored, but its field layout ({$this->fieldLayoutId}) was not.");
        }

        $db = Craft::$app->getDb();

        // Rename the content table - if its still around
        if ($db->tableExists($this->fieldContentTable)) {
            $newContentTableName = Formie::$plugin->getForms()->defineContentTableName($this);

            MigrationHelper::renameTable($this->fieldContentTable, $newContentTableName);

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
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'handle' => ['label' => Craft::t('app', 'Handle')],
            'template' => ['label' => Craft::t('app', 'Template')],
            'dateCreated' => ['label' =>Craft::t('app', 'Date Created')],
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
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }


    // Private methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getSessionKey($key)
    {
        // Return a different session namespace when editing a submission
        if ($this->_editingSubmission && $this->_editingSubmission->id) {
            return 'formie:' . $this->id . ':' . $this->_editingSubmission->id . ':' . $key;
        }

        return 'formie:' . $this->id . ':' . $key;
    }

    /**
     * @inheritDoc
     */
    private static function _getEditableFormIds()
    {
        $userSession = Craft::$app->getUser();

        $editableIds = [];

        // Fetch all form UIDs
        $formInfo = (new Query())
            ->from('{{%formie_forms}}')
            ->select(['id', 'uid'])
            ->all();

        // Can the user edit _every_ form?
        if ($userSession->checkPermission('formie-editForms')) {
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
}

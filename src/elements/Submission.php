<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\ModifyFieldValueForIntegrationEvent;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\fields\formfields\FileUpload;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Exception;
use yii\behaviors\AttributeTypecastBehavior;

class Submission extends Element
{
    // Constants
    // =========================================================================

    const EVENT_DEFINE_RULES = 'defineSubmissionRules';
    const EVENT_BEFORE_MARKED_AS_SPAM = 'beforeMarkedAsSpam';
    const EVENT_MODIFY_FIELD_VALUE_FOR_INTEGRATION = 'modifyFieldValueForIntegration';


    // Public Properties
    // =========================================================================

    public $id;
    public $formId;
    public $statusId;
    public $userId;
    public $ipAddress;
    public $isIncomplete = false;
    public $isSpam = false;
    public $spamReason;

    public $validateCurrentPageOnly;


    // Private Properties
    // =========================================================================

    /**
     * @var Form
     */
    private $_form;

    /**
     * @var Status
     */
    private $_status;

    /**
     * @var User|null
     */
    private $_user;

    private $_fieldLayout;
    private $_fieldContext;
    private $_contentTable;
    private $_pagesForField;


    // Static
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
    public static function refHandle()
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
    public static function find(): ElementQueryInterface
    {
        return new SubmissionQuery(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function gqlTypeNameByContext($context): string
    {
        return $context->handle . '_Submission';
    }

    /**
     * @inheritdoc
     */
    public static function gqlScopesByContext($context): array
    {
        return ['formieSubmissions.' . $context->uid];
    }

    /**
     * @inheritdoc
     */
    public static function gqlMutationNameByContext($context): string
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
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $fieldsByHandle = [];
        
        if ($fieldLayout = $this->getFieldLayout()) {
            // Check when we're doing a submission from the front-end, and we choose to validate the current page only
            // Remove any custom fields that aren't in the current page. These are added by default
            if ($this->validateCurrentPageOnly) {
                $currentPageFields = $this->form->getCurrentPage()->getFields();

                // Organise fields, so they're easier to check against
                $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

                foreach ($rules as $key => $rule) {
                    list($attribute, $validator) = $rule;
                    $attribute = is_array($attribute) ? $attribute[0] : $attribute;

                    if (strpos($attribute, 'field:') !== false) {
                        $handle = str_replace('field:', '', $attribute);

                        if (!in_array($handle, $currentPageFieldHandles)) {
                            unset($rules[$key]);
                        }
                    }
                }
            }

            $fields = $this->getFieldLayout()->getFields();

            $fieldsByHandle = ArrayHelper::getColumn($fields, 'handle');

            // Evaulate field conditions. What if this is a required field, but conditionally hidden?
            foreach ($rules as $key => $rule) {
                foreach ($fields as $field) {
                    list($attribute, $validator) = $rule;
                    $attribute = is_array($attribute) ? $attribute[0] : $attribute;

                    if ($attribute === "field:{$field->handle}") {
                        // If this field is conditionally hidden, remove it from validation
                        if ($field->isConditionallyHidden($this)) {
                            unset($rules[$key]);
                        }
                    }
                }
            }

            // Add custom error messages to fields with custom message set.
            foreach ($rules as $key => $rule) {
                /* @var FormField|FormFieldTrait $field */
                foreach ($fields as $field) {
                    if (!$field->errorMessage) {
                        continue;
                    }

                    list($attribute, $validator) = $rule;
                    $attribute = is_array($attribute) ? $attribute[0] : $attribute;

                    if ($attribute === "field:{$field->handle}") {
                        $rules[$key]['message'] = $field->errorMessage;
                        break;
                    }
                }
            }
        }

        // Reset keys just in case
        $rules = array_values($rules);

        $rules[] = [['title'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['formId'], 'number', 'integerOnly' => true];

        // Fire a 'defineSubmissionRules' event
        $event = new SubmissionRulesEvent([
            'rules' => $rules,
            'submission' => $this,
        ]);
        $this->trigger(self::EVENT_DEFINE_RULES, $event);

        // Ensure that any rules defined in events actually exists and are valid for this submission/form.
        // Otherwise fatal errors will occur trying to validate a field that doesn't exist in this context.
        foreach ($event->rules as $key => $rule) {
            list($attribute, $validator) = $rule;
            $attr = is_array($attribute) ? $attribute[0] : $attribute;

            if (strpos($attr, 'field:') !== false) {
                $fieldHandle = str_replace('field:', '', $attr);

                // Remove any rules for fields not defined in this form for safety.
                if (!in_array($fieldHandle, $fieldsByHandle)) {
                    unset($event->rules[$key]);
                }
            }
        }

        return $event->rules;
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
                'defaultSort' => ['formie_submissions.title', 'desc']
            ]
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
    public static function eagerLoadingMap(array $sourceElements, string $handle)
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
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::className(),
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'formId' => AttributeTypecastBehavior::TYPE_STRING,
                'statusId' => AttributeTypecastBehavior::TYPE_INTEGER,
            ]
        ];

        return $behaviors;
    }

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        $form = $this->getForm();

        if ($form && $form->requireUser) {
            if (!Craft::$app->getUser()->getIdentity()) {
                $this->addError('form', Craft::t('formie', 'You must be logged in to submit this form.'));
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
    public function getFieldLayout()
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
    public function getCpEditUrl()
    {
        $form = $this->getForm();
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

    /**
     * @inheritDoc
     */
    public function updateTitle($form)
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
     *
     * @return Form
     */
    public function getForm()
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
     *
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->_form = $form;
        $this->formId = $form->id;
    }

    /**
     * @inheritDoc
     */
    public function getFormRecord()
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

    /**
     * @inheritDoc
     */
    public function getFormName()
    {
        if ($form = $this->getForm()) {
            return $form->title;
        }
    }

    /**
     * Gets the submission's status handle.
     *
     * @param bool $returnStatus return the status model
     * @return string|null
     */
    public function getStatus($returnStatus = false)
    {
        if (!$this->_status && $this->statusId) {
            $this->_status = Formie::$plugin->getStatuses()->getStatusById($this->statusId);
        }

        if ($this->_status) {
            if ($returnStatus) {
                return $this->_status;
            }

            return $this->_status->handle;
        }

        if ($form = $this->getForm()) {
            return $this->_status = $form->getDefaultStatus();
        }

        return $this->_status = Formie::$plugin->getStatuses()->getDefaultStatus();
    }

    /**
     * Sets the submission's status.
     *
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->_status = $status;
        $this->statusId = $status->id;
    }

    /**
     * Returns the user who created the submission.
     *
     * @return User|null
     */
    public function getUser()
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
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->_user = $user;
        $this->userId = $user->id;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = '')
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
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }
        }

        parent::setFieldValuesFromRequest($paramNamespace);

        // Any conditionally hidden fields should have their content excluded when saving.
        if ($this->getFieldLayout()) {
            foreach ($this->getFieldLayout()->getFields() as $field) {
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
    }

    /**
     * Returns all field values.
     *
     * @param FieldLayoutPage|null
     * @return array
     */
    public function getValues($page)
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

    /**
     * @inheritdoc
     */
    public function getSerializedFieldValuesForIntegration(array $fieldHandles = null): array
    {
        $serializedValues = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $value = $this->getFieldValue($field->handle);

                // Allow fields to override their field values, just for integrations
                if (method_exists($field, 'serializeValueForIntegration')) {
                    $value = $field->serializeValueForIntegration($value, $this);
                } else {
                    $value = $field->serializeValue($value, $this);
                }

                // Fire a 'modifyFieldValueForIntegration' event
                $event = new ModifyFieldValueForIntegrationEvent([
                    'field' => $field,
                    'value' => $value,
                    'submission' => $this,
                ]);
                $this->trigger(self::EVENT_MODIFY_FIELD_VALUE_FOR_INTEGRATION, $event);

                $serializedValues[$field->handle] = $event->value;
            }
        }

        return $serializedValues;
    }

    /**
     * @inheritdoc
     */
    public function getFieldPages()
    {
        if ($this->_pagesForField) {
            return $this->_pagesForField;
        }

        $this->_pagesForField = [];

        if ($fieldLayout = $this->getForm()->getFormFieldLayout()) {
            foreach ($fieldLayout->getPages() as $page) {
                foreach ($page->getFields() as $field) {
                    $this->_pagesForField[$field->handle] = $page;
                }
            }
        }

        return $this->_pagesForField;
    }

    /**
     * @inheritdoc
     */
    public function getRelations()
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


    // Events
    // =========================================================================

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
                if (!$settings->saveSpam) {
                    return false;
                }
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew)
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
        $record->ipAddress = $this->ipAddress;
        $record->spamReason = $this->spamReason;
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

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            if ($form && ($submission = $form->getCurrentSubmission()) && $submission->id == $this->id) {
                $form->resetCurrentSubmission();
            }
        }

        return parent::beforeDelete();
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        $form = $this->getForm();
        $elementsService = Craft::$app->getElements();

        // Check if we should hard-delete any file uploads - note once an asset is soft-deleted
        // it's file is hard-deleted gone, so we cannot restore a file upload. I'm aware of `keepFileOnDelete`, but there's
        // no way to remove that file on hard-delete, so that won't work.
        // See https://github.com/craftcms/cms/issues/5074
        if ($form && $form->fileUploadsAction === 'delete') {
            foreach ($form->getFields() as $field) {
                if ($field instanceof FileUpload) {
                    $assets = $this->getFieldValue($field->handle)->all();

                    foreach ($assets as $asset) {
                        if (!$elementsService->deleteElement($asset)) {
                            Formie::error("Unable to delete file ”{$asset->id}” for submission ”{$this->id}”: " . Json::encode($asset->getErrors()) . ".");
                        }
                    }
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
                $statusHandle = $this->getStatus();
                $status = self::statuses()[$statusHandle] ?? null;
                
                return Html::tag('span', Html::tag('span', '', [
                    'class' => array_filter([
                        'status',
                        $statusHandle,
                        ($status ? $status['color'] : null)
                    ]),
                ]) . ($status ? $status['label'] : null), [
                    'style' => [
                        'display' => 'flex',
                        'align-items' => 'center',
                    ],
                ]);
            case 'sendNotification':
                if ($form = $this->getForm()) {
                    if ($notifications = $form->getNotifications()) {
                        return Html::a(Craft::t('formie', 'Send'), '#', [
                            'class' => 'btn small formsubmit js-fui-submission-modal-send-btn',
                            'data-id' => $this->id,
                            'title' => Craft::t('formie', 'Send'),
                        ]);
                    }
                }

                return '';
            default:
                return parent::tableAttributeHtml($attribute);
        }
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
                'attribute' => 'title'
            ],
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
        ];
    }


    // Private methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private static function _getAvailableFormIds()
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
}

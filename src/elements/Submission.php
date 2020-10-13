<?php
namespace verbb\formie\elements;

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use verbb\formie\events\SubmissionRulesEvent;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Settings;
use verbb\formie\models\Status;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\helpers\ArrayHelper;

use yii\base\Exception;
use yii\behaviors\AttributeTypecastBehavior;

class Submission extends Element
{
    // Constants
    // =========================================================================

    const EVENT_DEFINE_RULES = 'defineSubmissionRules';
    const EVENT_BEFORE_MARKED_AS_SPAM = 'beforeMarkedAsSpam';


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

    public $notification;
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
        return ['submission.' . $context->uid];
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
        $fields = $this->getFieldLayout()->getFields();

        // Check when we're doing a submission from the front-end, and we choose to validate the current page only
        // Remove any custom fields that aren't in the current page. These are added by default
        if ($this->validateCurrentPageOnly) {
            $currentPageFields = $this->form->getCurrentPage()->getFields();

            // Organise fields, so they're easier to check against
            $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

            foreach ($rules as $key => $rule) {
                // foreach ($fields as $field) {
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

        // Add custom error messages to fields with custom message set.
        foreach ($rules as &$rule) {
            /* @var FormField|FormFieldTrait $field */
            foreach ($fields as $field) {
                if (!$field->errorMessage) {
                    continue;
                }

                list($attribute, $validator) = $rule;
                $attribute = is_array($attribute) ? $attribute[0] : $attribute;

                if ($attribute === "field:{$field->handle}" && $validator === 'required') {
                    $rule['message'] = $field->errorMessage;
                    break;
                }
            }
        }

        $rules[] = [['title'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['formId'], 'number', 'integerOnly' => true];

        // Fire a 'defineSubmissionRules' event
        $event = new SubmissionRulesEvent([
            'rules' => $rules,
        ]);
        $this->trigger(self::EVENT_DEFINE_RULES, $event);

        return $event->rules;
    }

    /**
     * @inheritDoc
     */
    protected static function defineSources(string $context = null): array
    {
        $forms = Form::find()->all();
        $formIds = ArrayHelper::getColumn($forms, 'id');

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'criteria' => ['formId' => $formIds],
                'defaultSort' => ['formie_submissions.title', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('formie', 'Forms')];

        foreach ($forms as $form) {
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
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        $form = $this->getForm();
        if ($form->requireUser) {
            if (!Craft::$app->getUser()->getIdentity()) {
                $this->addError('form', Craft::t('formie', 'You must be logged in to submit this form.'));
            }
        }

        return $validates;
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
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        // Only support the site he submission is being made on
        $siteId = $this->siteId ?: Craft::$app->getSites()->getPrimarySite()->id;

        return [$siteId];
    }

    /**
     * @inheritDoc
     */
    public function getFieldLayout()
    {
        $form = $this->getForm();
        return $form->getFormFieldLayout();
    }

    /**
     * @inheritDoc
     */
    public function getFieldContext(): string
    {
        $form = $this->getForm();
        return "formie:{$form->uid}";
    }

    /**
     * @inheritDoc
     */
    public function getContentTable(): string
    {
        $form = $this->getForm();
        return $form->fieldContentTable;
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl()
    {
        $url = UrlHelper::cpUrl('formie/submissions/edit/' . $this->id);

        if (Craft::$app->getIsMultiSite()) {
            $url .= '/' . $this->getSite()->handle;
        }

        return $url;
    }

    /**
     * Gets the submission's form.
     *
     * @return Form
     */
    public function getForm(): Form
    {
        if (!$this->_form) {
            $this->_form = Form::find()->id($this->formId)->one();
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

        return null;
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
     * Returns all field values.
     *
     * @param FieldLayoutPage|null
     * @return array
     */
    public function getValues($page)
    {
        $form = $this->getForm();
        $fields = $page ? $page->getFields() : $form->getFields();

        $values = [];
        foreach ($fields as $field) {
            $values[$field->handle] = $field->getValue($this);
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

                $serializedValues[$field->handle] = $value;
            }
        }

        return $serializedValues;
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

        parent::afterSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function beforeDelete(): bool
    {
        $form = $this->getForm();

        if (($submission = $form->getCurrentSubmission()) && $submission->id == $this->id) {
            $form->resetCurrentSubmission();
        }

        return parent::beforeDelete();
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
            'form' => ['label' => Craft::t('formie', 'Form')],
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
                return $form->title;
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
}

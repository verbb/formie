<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\elements\actions\ResendNotifications;
use verbb\formie\elements\db\SentNotificationQuery;
use verbb\formie\records\SentNotification as SentNotificationRecord;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

class SentNotification extends Element
{
    // Public Properties
    // =========================================================================

    public $id;
    public $title;
    public $formId;
    public $submissionId;
    public $subject;
    public $to;
    public $cc;
    public $bcc;
    public $replyTo;
    public $replyToName;
    public $from;
    public $fromName;
    public $body;
    public $htmlBody;
    public $info;


    // Private Properties
    // =========================================================================

    private $_form;
    private $_submission;


    // Static
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Sent Notification');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('formie', 'Sent Notifications');
    }

    /**
     * @inheritDoc
     */
    public static function find(): ElementQueryInterface
    {
        return new SentNotificationQuery(static::class);
    }

    /**
     * @inheritDoc
     */
    protected static function defineSources(string $context = null): array
    {
        $forms = Form::find()->all();

        $ids = self::_getEditableFormIds();

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'criteria' => ['formId' => $ids],
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
            'type' => ResendNotifications::class,
        ]);

        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected sent notification?'),
            'successMessage' => Craft::t('formie', 'Sent Notification deleted.'),
        ]);

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Sent Notification restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some Sent Notifications restored.'),
            'failMessage' => Craft::t('formie', 'Sent Notifications not restored.'),
        ]);

        return $actions;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if ($this->info) {
            $this->info = Json::decodeIfJson($this->info);
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->dateCreated->format('M j, Y H:i:s A');
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('formie/sent-notifications/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    public function getForm()
    {
        if (!$this->_form) {
            $this->_form = Form::find()->id($this->formId)->one();
        }

        return $this->_form;
    }

    /**
     * @inheritDoc
     */
    public function getSubmission()
    {
        if (!$this->_submission) {
            $this->_submission = Submission::find()->id($this->submissionId)->one();
        }

        return $this->_submission;
    }


    // Events
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = SentNotificationRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid sent notification ID: ' . $this->id);
            }
        } else {
            $record = new SentNotificationRecord();
            $record->id = $this->id;
        }

        $record->title = $this->title;
        $record->formId = $this->formId;
        $record->submissionId = $this->submissionId;
        $record->subject = $this->subject;
        $record->to = $this->to;
        $record->cc = $this->cc;
        $record->bcc = $this->bcc;
        $record->replyTo = $this->replyTo;
        $record->replyToName = $this->replyToName;
        $record->from = $this->from;
        $record->fromName = $this->fromName;
        $record->body = $this->body;
        $record->htmlBody = $this->htmlBody;
        $record->info = $this->info;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

        $record->save(false);

        parent::afterSave($isNew);
    }


    // Protected methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'dateCreated' => ['label' => Craft::t('formie', 'Date Sent')],
            'form' => ['label' => Craft::t('formie', 'Form')],
            'to' => ['label' => Craft::t('formie', 'Recipient')],
            'subject' => ['label' => Craft::t('formie', 'Subject')],
            'resend' => ['label' => Craft::t('formie', 'Resend')],
            'preview' => ['label' => Craft::t('formie', 'Preview'), 'icon' => 'view'],
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'dateCreated';

        if ($source === '*') {
            $attributes[] = 'form';
        }

        $attributes[] = 'to';
        $attributes[] = 'subject';
        $attributes[] = 'resend';

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'form':
                return $this->getForm()->title;
            case 'resend':
                return Html::a(Craft::t('formie', 'Resend'), '#', [
                    'class' => 'btn small formsubmit js-fui-notification-modal-resend-btn',
                    'data-id' => $this->id,
                    'title' => Craft::t('formie', 'Resend'),
                ]);
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['to', 'subject'];
    }

    /**
     * @inheritDoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Date Sent'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
        ];
    }


    // Private methods
    // =========================================================================

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
        if ($userSession->checkPermission('formie-editSubmissions')) {
            $editableIds = ArrayHelper::getColumn($formInfo, 'id');
        } else {
            // Find all UIDs the user has permission to
            foreach ($formInfo as $form) {
                if ($userSession->checkPermission('formie-manageSubmission:' . $form['uid'])) {
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

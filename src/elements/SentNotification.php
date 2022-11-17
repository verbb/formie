<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\elements\actions\ResendNotifications;
use verbb\formie\elements\db\SentNotificationQuery;
use verbb\formie\models\Notification;
use verbb\formie\records\SentNotification as SentNotificationRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\base\Model;

use Exception;

use LitEmoji\LitEmoji;

class SentNotification extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';


    // Static Methods
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
    public static function find(): SentNotificationQuery
    {
        return new SentNotificationQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_SUCCESS => Craft::t('formie', 'Success'),
            self::STATUS_FAILED => Craft::t('formie', 'Failed'),
        ];
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
                'defaultSort' => ['elements.dateCreated', 'desc'],
            ],
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
                'defaultSort' => ['elements.dateCreated', 'desc'],
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

    /**
     * @inheritDoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'dateCreated' => ['label' => Craft::t('formie', 'Date Sent')],
            'form' => ['label' => Craft::t('formie', 'Form')],
            'submission' => ['label' => Craft::t('formie', 'Submission')],
            'notification' => ['label' => Craft::t('formie', 'Email Notification')],
            'to' => ['label' => Craft::t('formie', 'Recipient')],
            'subject' => ['label' => Craft::t('formie', 'Subject')],
            'resend' => ['label' => Craft::t('formie', 'Resend')],
            'preview' => ['label' => Craft::t('formie', 'Preview'), 'icon' => 'view'],
            'status' => ['label' => Craft::t('formie', 'Status')],
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
                'attribute' => 'dateCreated',
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
        if ($userSession->checkPermission('formie-viewSubmissions')) {
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


    // Properties
    // =========================================================================
    
    public ?int $id = null;
    public ?string $title = null;
    public ?string $formId = null;
    public ?string $submissionId = null;
    public ?string $notificationId = null;
    public ?string $subject = null;
    public ?string $to = null;
    public ?string $cc = null;
    public ?string $bcc = null;
    public ?string $replyTo = null;
    public ?string $replyToName = null;
    public ?string $from = null;
    public ?string $fromName = null;
    public ?string $sender = null;
    public ?string $body = null;
    public ?string $htmlBody = null;
    public ?array $info = null;
    public ?string $success = null;
    public ?string $message = null;

    private ?Form $_form = null;
    private ?Submission $_submission = null;
    private ?Notification $_notification = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->info) {
            $this->info = Json::decodeIfJson($this->info);
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->dateCreated->format('M j, Y H:i:s A');
    }
    
    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/sent-notifications/edit/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if (!$this->success) {
            return self::STATUS_FAILED;
        }

        return self::STATUS_SUCCESS;
    }

    public function getForm(): ElementInterface|Model|array|null
    {
        if (!$this->_form) {
            $this->_form = Form::find()->id($this->formId)->one();
        }

        return $this->_form;
    }

    public function getSubmission(): ElementInterface|Model|array|null
    {
        if (!$this->_submission) {
            $this->_submission = Submission::find()->id($this->submissionId)->one();
        }

        return $this->_submission;
    }

    public function getNotification(): ?Notification
    {
        if (!$this->_notification) {
            $this->_notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);
        }

        return $this->_notification;
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew): void
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
        $record->notificationId = $this->notificationId;
        $record->subject = $this->subject;
        $record->to = $this->to;
        $record->cc = $this->cc;
        $record->bcc = $this->bcc;
        $record->replyTo = $this->replyTo;
        $record->replyToName = $this->replyToName;
        $record->from = $this->from;
        $record->fromName = $this->fromName;
        $record->sender = $this->sender;
        $record->body = $this->body;
        $record->htmlBody = $this->htmlBody;
        $record->info = $this->info;
        $record->success = $this->success;
        $record->message = $this->message;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

        // Ensure we take care of any emoji's in content
        $record->body = LitEmoji::encodeHtml($record->body);
        $record->htmlBody = LitEmoji::encodeHtml($record->htmlBody);

        $record->save(false);

        parent::afterSave($isNew);
    }


    // Protected methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'form' => $this->getForm()->title ?? '',
            'submission' => $this->getSubmission()->title ?? '',
            'notification' => $this->getNotification()->title ?? '',
            'resend' => Html::a(Craft::t('formie', 'Resend'), '#', [
                'class' => 'btn small formsubmit js-fui-notification-modal-resend-btn',
                'data-id' => $this->id,
                'title' => Craft::t('formie', 'Resend'),
            ]),
            'preview' => $this->body ? StringHelper::safeTruncate($this->body, 50) : '',
            'status' => '<span class="status ' . $this->status . '"></span>',
            default => parent::tableAttributeHtml($attribute),
        };
    }
}

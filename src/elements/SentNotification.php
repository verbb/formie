<?php
namespace verbb\formie\elements;

use verbb\formie\Formie;
use verbb\formie\elements\actions\ResendNotifications;
use verbb\formie\elements\db\SentNotificationQuery;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Notification;
use verbb\formie\records\SentNotification as SentNotificationRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Model;

use Exception;

class SentNotification extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Sent Notification');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('formie', 'Sent Notifications');
    }

    public static function find(): SentNotificationQuery
    {
        return new SentNotificationQuery(static::class);
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SUCCESS => Craft::t('formie', 'Success'),
            self::STATUS_FAILED => Craft::t('formie', 'Failed'),
        ];
    }

    protected static function defineSources(string $context = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $forms = Form::find()->all();

        $sources = [];

        if ($currentUser->can('formie-viewSentNotifications')) {
            $sources[] = [
                'key' => '*',
                'label' => Craft::t('formie', 'All forms'),
                'defaultSort' => ['elements.dateCreated', 'desc'],
            ];
        }

        $formItems = [];

        foreach ($forms as $form) {
            if (!$currentUser->can('formie-viewSentNotifications') && !$currentUser->can("formie-viewSentNotifications:$form->uid")) {
                continue;
            }

            /* @var Form $form */
            $key = "form:{$form->id}";

            $formItems[$key] = [
                'key' => $key,
                'label' => $form->title,
                'data' => [
                    'handle' => $form->handle,
                ],
                'criteria' => ['formId' => $form->id],
                'defaultSort' => ['elements.dateCreated', 'desc'],
            ];
        }

        if ($formItems) {
            $sources[] = ['heading' => Craft::t('formie', 'Forms')];

            $sources += $formItems;
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        // Get the UID from the ID (for the source)
        $formId = (int)str_replace('form:', '', $source);
        $formUid = Formie::$plugin->getForms()->getFormById($formId)?->uid ?? null;

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canResendNotifications = $currentUser->can('formie-resendSentNotifications') || $currentUser->can("formie-resendSentNotifications:$formUid");
        $canDeleteNotifications = $currentUser->can('formie-deleteSentNotifications') || $currentUser->can("formie-deleteSentNotifications:$formUid");

        if ($canResendNotifications) {
            $actions[] = $elementsService->createAction([
                'type' => ResendNotifications::class,
            ]);
        }

        if ($canDeleteNotifications) {
            $actions[] = $elementsService->createAction([
                'type' => Delete::class,
                'confirmationMessage' => Craft::t('formie', 'Are you sure you want to delete the selected sent notification?'),
                'successMessage' => Craft::t('formie', 'Sent Notification deleted.'),
            ]);
        }

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('formie', 'Sent Notification restored.'),
            'partialSuccessMessage' => Craft::t('formie', 'Some Sent Notifications restored.'),
            'failMessage' => Craft::t('formie', 'Sent Notifications not restored.'),
        ]);

        return $actions;
    }

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

    protected static function defineSearchableAttributes(): array
    {
        return ['to', 'subject'];
    }

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
    public bool $success = false;
    public ?string $message = null;

    private ?Form $_form = null;
    private ?Submission $_submission = null;
    private ?Notification $_notification = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        if ($this->info) {
            $this->info = Json::decodeIfJson($this->info);
        }
    }

    public function __toString(): string
    {
        // Just in case we try and render the element before a `dateCreated` exists
        return $this->dateCreated?->format('M j, Y H:i:s A') ?? parent::__toString();
    }
    
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        if ($user->can('formie-viewSentNotifications')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            // Viewing without a form is fine, in case the form's been deleted
            return true;
        }

        if (!$user->can("formie-viewSentNotifications:$form->uid")) {
            return false;
        }

        return true;
    }
    
    public function canSave(User $user): bool
    {
        return false;
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        if ($user->can('formie-deleteSentNotifications')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            return false;
        }

        if (!$user->can("formie-deleteSentNotifications:$form->uid")) {
            return false;
        }

        return true;
    }

    public function canResend(User $user): bool
    {
        if ($user->can('formie-resendSentNotifications')) {
            return true;
        }

        $form = $this->getForm();

        if (!$form) {
            return false;
        }

        if (!$user->can("formie-resendSentNotifications:$form->uid")) {
            return false;
        }

        return true;
    }

    public function getStatus(): ?string
    {
        if (!$this->success) {
            return self::STATUS_FAILED;
        }

        return self::STATUS_SUCCESS;
    }

    public function getForm(): ?Form
    {
        if (!$this->_form && $this->formId) {
            $this->_form = Form::find()->id($this->formId)->one();
        }

        return $this->_form;
    }

    public function getSubmission(): ?Submission
    {
        if (!$this->_submission && $this->submissionId) {
            $this->_submission = Submission::find()->id($this->submissionId)->one();
        }

        return $this->_submission;
    }

    public function getNotification(): ?Notification
    {
        if (!$this->_notification && $this->notificationId) {
            $this->_notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);
        }

        return $this->_notification;
    }

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

        $record->save(false);

        parent::afterSave($isNew);
    }


    // Protected Methods
    // =========================================================================

    protected function attributeHtml(string $attribute): string
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        return match ($attribute) {
            'form' => $this->getForm()->title ?? '-',
            'submission' => $this->getSubmission()->title ?? '-',
            'notification' => $this->getNotification()->title ?? '-',
            'resend' => $this->canResend($currentUser) ? Html::a(Craft::t('formie', 'Resend'), '#', [
                'class' => 'btn small formsubmit js-fui-notification-modal-resend-btn',
                'data-id' => $this->id,
                'title' => Craft::t('formie', 'Resend'),
            ]) : '-',
            'preview' => $this->body ? StringHelper::safeTruncate($this->body, 50) : '',
            'status' => '<span class="status ' . $this->status . '"></span>',
            default => parent::attributeHtml($attribute),
        };
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/sent-notifications/edit/' . $this->id);
    }
}

<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\NotificationEvent;
use verbb\formie\events\ModifyExistingNotificationsEvent;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\records\Notification as NotificationRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\db\Exception;

use Throwable;

use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use Twig\Error\LoaderError;

class Notifications extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_NOTIFICATION = 'beforeSaveNotification';
    public const EVENT_AFTER_SAVE_NOTIFICATION = 'afterSaveNotification';
    public const EVENT_BEFORE_DELETE_NOTIFICATION = 'beforeDeleteNotification';
    public const EVENT_AFTER_DELETE_NOTIFICATION = 'afterDeleteNotification';
    public const EVENT_MODIFY_EXISTING_NOTIFICATIONS = 'modifyExistingNotifications';

    private ?MemoizableArray $_notifications = null;
    private ?array $_existingNotifications = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all notifications.
     *
     * @return Notification[]
     */
    public function getAllNotifications(): array
    {
        return $this->_notifications()->all();
    }

    /**
     * Returns all notifications for a form.
     *
     * @param Form $form
     * @return Notification[]
     */
    public function getFormNotifications(Form $form): array
    {
        return $this->_notifications()->where('formId', $form->id)->all();
    }

    /**
     * Returns a form notification by its ID.
     *
     * @param int $id
     * @return Notification|null
     */
    public function getNotificationById(int $id): ?Notification
    {
        return $this->_notifications()->firstWhere('id', $id);
    }

    /**
     * Saves a notification.
     *
     * @param Notification $notification
     * @param bool $runValidation
     * @return bool
     * @throws Throwable
     */
    public function saveNotification(Notification $notification, bool $runValidation = true): bool
    {
        $isNewNotification = !(bool)$notification->id;

        // Fire a 'beforeSaveNotification' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_NOTIFICATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_NOTIFICATION, new NotificationEvent([
                'notification' => $notification,
                'isNew' => $isNewNotification,
            ]));
        }

        if ($runValidation && !$notification->validate()) {
            Formie::log('Notification not saved due to validation error.');

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $notificationRecord = $this->_getNotificationRecord($notification->id);
            $notificationRecord->formId = $notification->formId;
            $notificationRecord->templateId = $notification->templateId;
            $notificationRecord->pdfTemplateId = $notification->pdfTemplateId;
            $notificationRecord->name = $notification->name;
            $notificationRecord->enabled = $notification->enabled;
            $notificationRecord->subject = $notification->subject;
            $notificationRecord->recipients = $notification->recipients;
            $notificationRecord->to = $notification->to;
            $notificationRecord->toConditions = $notification->toConditions;
            $notificationRecord->cc = $notification->cc;
            $notificationRecord->bcc = $notification->bcc;
            $notificationRecord->replyTo = $notification->replyTo;
            $notificationRecord->replyToName = $notification->replyToName;
            $notificationRecord->from = $notification->from;
            $notificationRecord->fromName = $notification->fromName;
            $notificationRecord->sender = $notification->sender;
            $notificationRecord->content = $notification->content;
            $notificationRecord->attachFiles = $notification->attachFiles;
            $notificationRecord->attachPdf = $notification->attachPdf;
            $notificationRecord->attachAssets = $notification->attachAssets;
            $notificationRecord->enableConditions = $notification->enableConditions;
            $notificationRecord->conditions = $notification->conditions;

            $notificationRecord->save(false);

            $notification->id = $notificationRecord->id;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire a 'afterSaveNotification' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_NOTIFICATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_NOTIFICATION, new NotificationEvent([
                'notification' => $this->getNotificationById($notificationRecord->id),
                'isNew' => $isNewNotification,
            ]));
        }

        return true;
    }

    /**
     * Deletes a notification by its ID.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deleteNotificationById(int $id): bool
    {
        $notification = $this->getNotificationById($id);

        if (!$notification) {
            return false;
        }

        return $this->deleteNotification($notification);
    }

    /**
     * Deletes a notification.
     *
     * @param Notification $notification
     * @return bool
     * @throws Exception
     */
    public function deleteNotification(Notification $notification): bool
    {
        // Fire a 'beforeDeleteNotification' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_NOTIFICATION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_NOTIFICATION, new NotificationEvent([
                'notification' => $notification,
            ]));
        }

        Db::delete('{{%formie_notifications}}', [
            'uid' => $notification->uid,
        ]);

        // Fire a 'afterDeleteNotification' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_NOTIFICATION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_NOTIFICATION, new NotificationEvent([
                'notification' => $notification,
            ]));
        }

        return true;
    }

    /**
     * Builds an array of notifications from POST data.
     *
     * @return Notification[]
     */
    public function buildNotificationsFromPost(): array
    {
        $request = Craft::$app->getRequest();

        $notifications = [];
        $notificationsData = $request->getParam('notifications');
        $notificationsData = Json::decode($notificationsData) ?? [];

        $duplicate = $request->getParam('duplicate');

        foreach ($notificationsData as $notificationData) {
            if (isset($notificationData['hasError'])) {
                unset($notificationData['hasError']);
            }

            if (isset($notificationData['errors'])) {
                unset($notificationData['errors']);
            }

            // Remove IDs if we're duplicating
            if ($duplicate) {
                unset($notificationData['id'], $notificationData['formId'], $notificationData['uid']);
            }

            // Discard some Vue-specific things
            if (isset($notificationData['attachAssetsOptions'])) {
                unset($notificationData['attachAssetsOptions']);
            }

            if (isset($notificationData['attachAssetsHtml'])) {
                unset($notificationData['attachAssetsHtml']);
            }

            if (isset($notificationData['id'])) {
                if (str_starts_with($notificationData['id'], 'new')) {
                    $notificationData['id'] = null;
                }
            }

            $notifications[] = new Notification($notificationData);
        }

        return $notifications;
    }

    /**
     * Gets the config for an array of notifications.
     *
     * @param Notification[] $notifications
     * @return mixed
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     */
    public function getNotificationsConfig(array $notifications): mixed
    {
        $notificationsConfig = [];

        foreach ($notifications as $notification) {
            $config = $notification->getAttributes();
            $config['errors'] = $notification->getErrors();
            $config['hasError'] = (bool)$notification->getErrors();

            $attachAssets = Json::decodeIfJson($notification->attachAssets) ?? [];

            // For assets to attach, supply extra content that can't be called directly in Vue, like it can in Twig.
            if ($ids = ArrayHelper::getColumn($attachAssets, 'id')) {
                $elements = Asset::find()->id($ids)->all();

                // Maintain an options array, so we can keep track of the label in Vue, not just the saved value
                $config['attachAssetsOptions'] = array_map(function($input) {
                    return ['label' => $input->title, 'value' => $input->id];
                }, $elements);

                // Render the HTML needed for the element select field (for default value). jQuery needs DOM manipulation
                // so while gross, we have to supply the raw HTML, as opposed to models in the Vue-way.
                $config['attachAssetsHtml'] = Craft::$app->getView()->renderTemplate('formie/_includes/element-select-input-elements', ['elements' => $elements]);
            }

            $notificationsConfig[] = $config;
        }

        return $notificationsConfig;
    }

    /**
     * Returns an array of existing form notifications.
     *
     * @param Form|Stencil|null $excludeForm
     * @return array
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     */
    public function getExistingNotifications(Form|Stencil $excludeForm = null): array
    {
        if ($this->_existingNotifications !== null) {
            return $this->_existingNotifications;
        }

        $query = Form::find()->orderBy('title ASC');

        // Exclude the current form.
        if ($excludeForm instanceof Form) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();
        $stencils = Formie::$plugin->getStencils()->getAllStencils();

        // Exclude the current stencil.
        if ($excludeForm instanceof Stencil) {
            $filteredStencils = [];

            foreach ($stencils as $stencil) {
                if ($stencil->id != $excludeForm->id) {
                    $filteredStencils[] = $stencil;
                }
            }

            $stencils = $filteredStencils;
        }

        $existingNotifications = [];
        $formNotifications = [];
        $stencilNotifications = [];

        foreach ($forms as $form) {
            $formNotifications[] = $this->getNotificationsConfig($form->getNotifications());
        }

        foreach ($stencils as $stencil) {
            $stencilNotifications[] = $this->getNotificationsConfig($stencil->getNotifications());
        }

        // For performance
        $formNotifications = array_merge(...$formNotifications);
        $stencilNotifications = array_merge(...$stencilNotifications);

        // Stencils will always have no ID, so generate one
        foreach ($stencilNotifications as $key => $stencilNotification) {
            $stencilNotifications[$key]['id'] = StringHelper::appendRandomString('new', 16);
        }

        $existingNotifications[] = [
            'key' => '*',
            'label' => Craft::t('formie', 'All notifications'),
            'notifications' => array_merge($formNotifications, $stencilNotifications),
        ];

        if ($formNotifications) {
            $existingNotifications[] = [
                'heading' => Craft::t('formie', 'Forms'),
                'notifications' => [],
            ];
        }

        foreach ($forms as $form) {
            $formNotifications = $this->getNotificationsConfig($form->getNotifications());

            if ($formNotifications) {
                $existingNotifications[] = [
                    'key' => $form->handle,
                    'label' => $form->title,
                    'notifications' => $formNotifications,
                ];
            }
        }

        if ($stencilNotifications) {
            $existingNotifications[] = [
                'heading' => Craft::t('formie', 'Stencils'),
                'notifications' => [],
            ];
        }

        foreach ($stencils as $stencil) {
            $formNotifications = $this->getNotificationsConfig($stencil->getNotifications());

            if ($formNotifications) {
                $existingNotifications[] = [
                    'key' => $stencil->handle,
                    'label' => $stencil->title,
                    'notifications' => $formNotifications,
                ];
            }
        }

        // Fire a 'modifyExistingNotifications' event
        $event = new ModifyExistingNotificationsEvent([
            'notifications' => $existingNotifications,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXISTING_NOTIFICATIONS, $event);

        return $this->_existingNotifications = $event->notifications;
    }

    /**
     * Returns whether the notification has passed conditional evaluation. A `true` result means the notification
     * should be sent, whilst a `false` result means the notification should not send.
     *
     * @param $notification
     * @param Submission $submission
     * @return bool
     */
    public function evaluateConditions($notification, Submission $submission): bool
    {
        if ($notification->enableConditions) {
            $conditionSettings = $notification->conditions ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Lastly, check to see if we should return true or false depending on if we want to send or not
                if ($conditionSettings['sendRule'] === 'send') {
                    return $result;
                }

                return !$result;
            }
        }

        return true;
    }

    /**
     * Returns the notifications settings schema.
     *
     * @return array
     */
    public function getNotificationsSchema(): array
    {
        $user = Craft::$app->getUser();

        $tabs = [];
        $fields = [];

        // Define the tabs we have for editing a field. Only these can be used.
        $definedTabs = [
            'Content',
        ];

        if ($user->checkPermission('formie-manageNotificationsAdvanced')) {
            $definedTabs[] = 'Advanced';
        }

        if ($user->checkPermission('formie-manageNotificationsTemplates')) {
            $definedTabs[] = 'Templates';
        }

        $definedTabs[] = 'Preview';
        $definedTabs[] = 'Conditions';

        foreach ($definedTabs as $definedTab) {
            $methodName = 'define' . $definedTab . 'Schema';

            if (method_exists($this, $methodName) && $this->$methodName()) {
                $tabLabel = Craft::t('formie', $definedTab);

                $fieldSchema = $this->$methodName();

                // Add `name` and `id` attributes automatically for every FormKit input
                SchemaHelper::setFieldAttributes($fieldSchema);

                $fields[] = [
                    '$cmp' => 'TabPanel',
                    'attrs' => [
                        'data-tab-panel' => $tabLabel,
                    ],
                    'children' => $fieldSchema,
                ];

                $tabs[] = [
                    'label' => $tabLabel,
                    'fields' => SchemaHelper::extractFieldsFromSchema($fieldSchema),
                ];
            }
        }

        // Return the DOM schema for Vue to render
        return [
            'tabsSchema' => $tabs,
            'fieldsSchema' => [
                [
                    '$cmp' => 'TabPanels',
                    'attrs' => [
                        'class' => 'fui-modal-content',
                    ],
                    'children' => $fields,
                ],
            ],
        ];
    }

    /**
     * Defines the content settings schema.
     *
     * @return array
     */
    public function defineContentSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enabled'),
                'help' => Craft::t('formie', 'Whether this notification is enabled to send.'),
                'name' => 'enabled',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Name'),
                'help' => Craft::t('formie', 'What this notification will be called in the control panel.'),
                'name' => 'name',
                'validation' => 'required',
                'required' => true,
                'variables' => 'plainTextVariables',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Recipients'),
                'help' => Craft::t('formie', 'Define who should receive this email notification.'),
                'name' => 'recipients',
                'validation' => 'required',
                'required' => true,
                'options' => [
                    ['label' => Craft::t('formie', 'Email Addresses'), 'value' => 'email'],
                    ['label' => Craft::t('formie', 'Conditions'), 'value' => 'conditions'],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Recipient Emails'),
                'help' => Craft::t('formie', 'Email addresses who receive this email notification. Separate multiple emails with a comma.'),
                'name' => 'to',
                'validation' => 'required',
                'required' => true,
                'variables' => 'emailVariables',
                'if' => '$get(recipients).value == email',
            ]),
            [
                '$formkit' => 'notificationRecipients',
                'label' => Craft::t('formie', 'Recipient Conditions'),
                'help' => Craft::t('formie', 'Add conditional logic to determine which email addresses receive this email notification.'),
                'name' => 'toConditions',
                'id' => 'toConditions',
                'if' => '$get(recipients).value == conditions',
            ],
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Subject'),
                'help' => Craft::t('formie', 'The subject of the email notification.'),
                'name' => 'subject',
                'validation' => 'required',
                'required' => true,
                'variables' => 'plainTextVariables',
            ]),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Email Content'),
                'help' => Craft::t('formie', 'The body content for this notification.'),
                'name' => 'content',
                'validation' => 'required',
                'required' => true,
                'variables' => 'plainTextVariables',
            ], RichTextHelper::getRichTextConfig('notifications.content'))),
        ];
    }

    /**
     * Defines the advanced settings schema.
     *
     * @return array
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'From Name'),
                'help' => Craft::t('formie', 'The name the notification email will be sent from.'),
                'name' => 'fromName',
                'variables' => 'plainTextVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'From Email'),
                'help' => Craft::t('formie', 'The email address the notification email will be sent from. Leave empty to use the default email address'),
                'name' => 'from',
                'validation' => '?emailOrVariable',
                'variables' => 'emailVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Reply-To Email'),
                'help' => Craft::t('formie', 'The email address to be used as the reply to address for the notification email.'),
                'name' => 'replyTo',
                'validation' => '?emailOrVariable',
                'variables' => 'emailVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'CC'),
                'help' => Craft::t('formie', 'Email addresses who will receive a CC of the notification email. Separate multiple emails with a comma.'),
                'name' => 'cc',
                'variables' => 'emailVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'BCC'),
                'help' => Craft::t('formie', 'Email addresses who will receive a BCC of the notification email. Separate multiple emails with a comma.'),
                'name' => 'bcc',
                'variables' => 'emailVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Sender Email'),
                'help' => Craft::t('formie', 'The email address for the notification email "sender" header, for advanced usage. Leave empty to use the "From Email".'),
                'name' => 'sender',
                'validation' => '?emailOrVariable',
                'variables' => 'emailVariables',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Attach File Uploads'),
                'help' => Craft::t('formie', 'Whether to attach file uploads to this email notification.'),
                'name' => 'attachFiles',
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Attach Assets'),
                'help' => Craft::t('formie', 'Select assets to be attached to this email notification.'),
                'name' => 'attachAssets',
                'selectionLabel' => Craft::t('formie', 'Add an asset'),
                'config' => [
                    'jsClass' => 'Craft.AssetSelectInput',
                    'elementType' => Asset::class,
                    'limit' => false,
                    'sources' => '*',
                ],
            ]),
        ];
    }

    /**
     * Defines the templates settings schema.
     *
     * @return array
     */
    public function defineTemplatesSchema(): array
    {
        $emailTemplates = [['label' => Craft::t('formie', 'Select an option'), 'value' => '']];

        foreach (Formie::$plugin->getEmailTemplates()->getAllTemplates() as $template) {
            $emailTemplates[] = ['label' => $template->name, 'value' => $template->id];
        }

        $pdfTemplates = [['label' => Craft::t('formie', 'Select an option'), 'value' => '']];

        foreach (Formie::$plugin->getPdfTemplates()->getAllTemplates() as $template) {
            $pdfTemplates[] = ['label' => $template->name, 'value' => $template->id];
        }

        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Email Template'),
                'help' => Craft::t('formie', 'Select a template to use for the Email, or leave empty to use Formie‘s default.'),
                'name' => 'templateId',
                'options' => $emailTemplates,
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Attach PDF Template'),
                'help' => Craft::t('formie', 'Whether to attach a PDF template to this email notification.'),
                'name' => 'attachPdf',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'PDF Template'),
                'help' => Craft::t('formie', 'Select a template to use for the PDF, or leave empty to use Formie‘s default.'),
                'name' => 'pdfTemplateId',
                'options' => $pdfTemplates,
                'if' => '$get(attachPdf).value',
            ]),
        ];
    }

    /**
     * Defines the templates preview schema.
     *
     * @return array
     */
    public function definePreviewSchema(): array
    {
        return [
            [
                '$cmp' => 'NotificationPreview',
            ],
            [
                '$el' => 'hr',
            ],
            [
                '$cmp' => 'NotificationTest',
                'props' => [
                    'userEmail' => Craft::$app->getUser()->getIdentity()->email ?? '',
                ],
            ],
        ];
    }

    /**
     * Defines the templates conditions schema.
     *
     * @return array
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Conditions'),
                'help' => Craft::t('formie', 'Whether to enable conditional logic to control how this email notification is sent.'),
                'name' => 'enableConditions',
            ]),
            [
                '$formkit' => 'notificationConditions',
                'name' => 'conditions',
                'if' => '$get(enableConditions).value',
            ],
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a memoizable array of all notifications.
     *
     * @return MemoizableArray<Notification>
     */
    private function _notifications(): MemoizableArray
    {
        if (!isset($this->_notifications)) {
            $notifications = [];

            foreach ($this->_createNotificationsQuery()->all() as $result) {
                $notifications[] = new Notification($result);
            }

            $this->_notifications = new MemoizableArray($notifications);
        }

        return $this->_notifications;
    }

    /**
     * Returns a query prepped for querying notifications.
     *
     * @return Query
     */
    private function _createNotificationsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'formId',
                'templateId',
                'pdfTemplateId',
                'name',
                'enabled',
                'subject',
                'recipients',
                'to',
                'toConditions',
                'cc',
                'bcc',
                'replyTo',
                'replyToName',
                'from',
                'fromName',
                'sender',
                'content',
                'attachFiles',
                'attachPdf',
                'attachAssets',
                'enableConditions',
                'conditions',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from(['{{%formie_notifications}}']);
    }

    /**
     * Gets a notification record by its ID, or a new notification record
     * if it wasn't provided or was not found.
     *
     * @param int|string|null $id
     * @return NotificationRecord
     */
    private function _getNotificationRecord(int|string|null $id): NotificationRecord
    {
        /** @var NotificationRecord $notification */
        if ($id && $notification = NotificationRecord::find()->where(['id' => $id])->one()) {
            return $notification;
        }

        return new NotificationRecord();
    }
}

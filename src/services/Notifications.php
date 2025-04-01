<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\NotificationEvent;
use verbb\formie\events\ModifyExistingNotificationsEvent;
use verbb\formie\events\ModifyNotificationSchemaEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\records\Notification as NotificationRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\Db;
use craft\helpers\Json;

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
    public const EVENT_MODIFY_NOTIFICATION_SCHEMA = 'modifyNotificationSchema';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_notifications = null;
    private ?array $_existingNotifications = null;


    // Public Methods
    // =========================================================================

    public function getAllNotifications(): array
    {
        return $this->_notifications()->all();
    }

    public function getFormNotifications(Form $form): array
    {
        return $this->_notifications()->where('formId', $form->id)->all();
    }

    public function getNotificationById(int $id): ?Notification
    {
        return $this->_notifications()->firstWhere('id', $id);
    }

    public function getFormNotificationByHandle(Form $form, string $handle): ?Notification
    {
        return ArrayHelper::firstWhere(
            $this->_notifications(),
            fn(Notification $notification) => $notification->formId === $form->id && $notification->handle === $handle,
        );
    }

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
            Formie::info('Notification not saved due to validation error.');

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $notificationRecord = $this->_getNotificationRecord($notification->id);
            $notificationRecord->formId = $notification->formId;
            $notificationRecord->templateId = $notification->templateId;
            $notificationRecord->pdfTemplateId = $notification->pdfTemplateId;
            $notificationRecord->name = $notification->name;
            $notificationRecord->handle = $notification->handle ?? $this->getUniqueNotificationHandle($notification);
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
            $notificationRecord->customSettings = $notification->customSettings;

            // Clear content for conditionally-set recipients to prevent zombie data
            if ($notificationRecord->recipients === 'conditions') {
                $notificationRecord->to = null;
            } else {
                $notificationRecord->toConditions = null;
            }

            // Clear content for conditionally-set recipients to prevent zombie data
            if ($notificationRecord->recipients === 'conditions') {
                $notificationRecord->to = null;
            } else {
                $notificationRecord->toConditions = null;
            }

            $notificationRecord->save(false);

            $notification->id = $notificationRecord->id;
            $notification->to = $notificationRecord->to;

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

    public function deleteNotificationById(int $id): bool
    {
        $notification = $this->getNotificationById($id);

        if (!$notification) {
            return false;
        }

        return $this->deleteNotification($notification);
    }

    public function deleteNotification(Notification $notification): bool
    {
        // Fire a 'beforeDeleteNotification' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_NOTIFICATION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_NOTIFICATION, new NotificationEvent([
                'notification' => $notification,
            ]));
        }

        Db::delete(Table::FORMIE_NOTIFICATIONS, [
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

    public function getUniqueNotificationHandle(Notification $notification): string
    {
        $increment = 1;
        $notificationHandle = StringHelper::toHandle($notification->name);
        $handle = $notificationHandle;

        // Generate a unique notification handle. Note that they're not unique globally, just per-form.
        while (true) {
            $existingNotification = (new Query())
                ->select(['*'])
                ->from([Table::FORMIE_NOTIFICATIONS])
                ->where(['handle' => $handle, 'formId' => $notification->formId])
                ->one();

            if (!$existingNotification) {
                return substr($handle, 0, 50);
            }

            $handle = $notificationHandle . $increment;

            $increment++;
        }
    }

    public function buildNotificationsFromPost(): array
    {
        $request = Craft::$app->getRequest();

        $notifications = [];
        $notificationsData = $request->getParam('notifications');
        $notificationsData = Json::decodeIfJson($notificationsData) ?? [];

        foreach ($notificationsData as $notificationData) {
            $notifications[] = new Notification($notificationData);
        }

        return $notifications;
    }

    public function getNotificationsConfig(array $notifications): mixed
    {
        $notificationsConfig = [];

        foreach ($notifications as $notification) {
            $config = $notification->getAttributes();
            $config['errors'] = $notification->getErrors();

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

    public function getNotificationsSchema(): array
    {
        $user = Craft::$app->getUser();

        $tabs = [];
        $fields = [];

        // Define the tabs we have for editing a field. Only these can be used.
        $definedTabs = [
            'Content',
        ];

        if ($user->checkPermission('formie-showNotificationsAdvanced')) {
            $definedTabs[] = 'Advanced';
        }

        if ($user->checkPermission('formie-showNotificationsTemplates')) {
            $definedTabs[] = 'Templates';
        }
        
        $definedTabs[] = 'Settings';
        $definedTabs[] = 'Preview';
        $definedTabs[] = 'Conditions';

        foreach ($definedTabs as $definedTab) {
            $methodName = 'define' . $definedTab . 'Schema';

            if (method_exists($this, $methodName) && $this->$methodName()) {
                $tabLabel = Craft::t('formie', $definedTab);

                $fieldSchema = $this->$methodName();

                // Add `name` and `id` attributes automatically for every FormKit input
                SchemaHelper::setFieldAttributes($fieldSchema);

                $tabs[] = SchemaHelper::tab($tabLabel, $fieldSchema);
                $fields[] = SchemaHelper::tabPanel($tabLabel, $fieldSchema);
            }
        }

        // Fire a 'modifyNotificationSchema' event
        $event = new ModifyNotificationSchemaEvent([
            'tabs' => $tabs,
            'fields' => $fields,
        ]);
        $this->trigger(self::EVENT_MODIFY_NOTIFICATION_SCHEMA, $event);

        // Return the DOM schema for Vue to render
        return [
            'tabsSchema' => $event->tabs,
            'fieldsSchema' => [
                [
                    '$cmp' => 'TabPanels',
                    'attrs' => [
                        'class' => 'fui-modal-content',
                    ],
                    'children' => $event->fields,
                ],
            ],
        ];
    }

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
                'help' => Craft::t('formie', 'Define who should receive this email notification. Define either specific emails, or emails based on conditions.'),
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
                'help' => Craft::t('formie', 'Use conditional logic to determine which email addresses receive this email notification.'),
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
                'help' => Craft::t('formie', 'The email address the notification email will be sent from. Leave empty to use the default email address for your site.'),
                'name' => 'from',
                'validation' => '?emailOrVariable',
                'variables' => 'emailVariables',
                'info' => Craft::t('formie', 'If not correctly configured, setting the "From" setting can lead to deliverability issues. Read [our guide](https://verbb.io/craft-plugins/formie/user-guides/how-to-keep-email-notifications-out-of-your-junk-emails) for tips.'),
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Reply-To Name'),
                'help' => Craft::t('formie', 'The name to be used as the reply to for the notification email.'),
                'name' => 'replyToName',
                'variables' => 'plainTextVariables',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Reply-To Email'),
                'help' => Craft::t('formie', 'The email address to be used as the reply to address for the notification email.'),
                'name' => 'replyTo',
                'validation' => '?emailOrVariable',
                'variables' => 'emailVariables',
                'info' => Craft::t('formie', 'Do not use the same email for "From" and "Reply-To". Read [our guide](https://verbb.io/craft-plugins/formie/user-guides/how-to-keep-email-notifications-out-of-your-junk-emails) for tips.'),
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

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::handleField([
                'help' => Craft::t('formie', 'How you’ll refer to this notification in your templates. Use the refresh icon to re-generate this from your notification name.'),
                'warning' => Craft::t('formie', 'Changing this may result in your notification not working as expected.'),
            ]),
        ];
    }

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

    private function _createNotificationsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'formId',
                'templateId',
                'pdfTemplateId',
                'name',
                'handle',
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
                'customSettings',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_NOTIFICATIONS]);
    }

    private function _getNotificationRecord(int|string|null $id): NotificationRecord
    {
        /** @var NotificationRecord $notification */
        if ($id && $notification = NotificationRecord::find()->where(['id' => $id])->one()) {
            return $notification;
        }

        return new NotificationRecord();
    }
}

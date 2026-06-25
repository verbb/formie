<?php
namespace verbb\formie\services;

use verbb\formie\base\FormInterface;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\NotificationEvent;
use verbb\formie\events\ModifyExistingNotificationsEvent;
use verbb\formie\events\ModifyNotificationSchemaEvent;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\models\Stencil;
use verbb\formie\records\Notification as NotificationRecord;

use Craft;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Queue;

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
    public const EVENT_BEFORE_SEND_NOTIFICATION = 'beforeSendNotification';


    // Properties
    // =========================================================================

    private ?array $_existingNotifications = null;


    // Public Methods
    // =========================================================================

    public function getAllNotifications(): array
    {
        $notifications = [];

        foreach ($this->_createNotificationsQuery()->all() as $result) {
            $notifications[] = new Notification($result);
        }

        return $notifications;
    }

    public function getFormNotifications(Form $form): array
    {
        $notifications = [];

        foreach ($this->_createNotificationsQuery()->where(['formId' => $form->id])->all() as $result) {
            $notifications[] = new Notification($result);
        }

        return $notifications;
    }

    public function getNotificationById(int $id): ?Notification
    {
        $result = $this->_createNotificationsQuery()->where(['id' => $id])->one();

        if (!$result) {
            return null;
        }

        return new Notification($result);
    }

    public function getFormNotificationByHandle(Form $form, string $handle): ?Notification
    {
        $result = $this->_createNotificationsQuery()->where([
            'formId' => $form->id,
            'handle' => $handle,
        ])->one();

        if (!$result) {
            return null;
        }

        return new Notification($result);
    }

    public function sendNotifications(Submission $submission): void
    {
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        foreach ($form->getEnabledNotifications() as $notification) {
            $this->sendNotification($notification, $submission);
        }
    }

    public function sendNotification(Notification $notification, Submission $submission, ?bool $useQueue = null): void
    {
        $settings = Formie::$plugin->getSettings();
        $useQueue ??= $settings->useQueueForNotifications;

        if (!$this->evaluateConditions($notification, $submission)) {
            return;
        }

        if ($useQueue) {
            // Queue after evaluating conditions so delayed jobs do not have to
            // recompute submission-dependent rules against a potentially changed
            // submission state.
            Queue::push(new SendNotification([
                'submissionId' => $submission->id,
                'notificationId' => $notification->id,
            ]), $settings->queuePriority);

            return;
        }

        $this->sendNotificationEmail($notification, $submission);
    }

    public function sendNotificationEmail(Notification $notification, Submission $submission, $queueJob = null): array|bool
    {
        $event = new SendNotificationEvent([
            'submission' => $submission,
            'notification' => $notification,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION, $event);

        if (!$event->isValid) {
            return true;
        }

        return Formie::$plugin->getEmails()->sendEmail($event->notification, $event->submission, $queueJob);
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
            $notificationRecord->dispatchTiming = $notification->dispatchTiming;
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
            $notification = new Notification($notificationData);

            if (empty($notificationData['id'])) {
                Formie::$plugin->getFormDefaults()->applyToNewNotification($notification, $notificationData);
            }

            $notifications[] = $notification;
        }

        return $notifications;
    }

    public function getNotificationsConfig(array $notifications): mixed
    {
        $notificationsConfig = [];

        foreach ($notifications as $notification) {
            $config = $notification->getAttributes();

            if (($config['content'] ?? null) !== null && $config['content'] !== '') {
                $config['content'] = RichText::from($config['content'])->getSchema();
            }

            $config['errors'] = $notification->getErrors();

            $notificationsConfig[] = $config;
        }

        return $notificationsConfig;
    }

    public function getExistingNotifications(FormInterface $excludeForm = null): array
    {
        if ($this->_existingNotifications !== null) {
            return $this->_existingNotifications;
        }

        $existingNotifications = [];
        $sources = $this->_getExistingNotificationSources($excludeForm);
        $allNotifications = [];
        $hasFormNotifications = false;
        $hasStencilNotifications = false;
        $stencilInsertIndex = null;

        foreach ($sources['forms'] as $source) {
            $notifications = $this->getNotificationsConfig($source['model']->getNotifications());

            if ($notifications) {
                $hasFormNotifications = true;
                $existingNotifications[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'notifications' => $notifications,
                ];

                $allNotifications = array_merge($allNotifications, $notifications);
            }
        }

        foreach ($sources['stencils'] as $source) {
            $notifications = $this->getNotificationsConfig($source['model']->getNotifications());

            if ($notifications) {
                $hasStencilNotifications = true;

                if ($stencilInsertIndex === null) {
                    $stencilInsertIndex = count($existingNotifications);
                }

                foreach ($notifications as $index => $notification) {
                    if (empty($notification['id'])) {
                        $notifications[$index]['id'] = StringHelper::appendRandomString('new', 16);
                    }
                }

                $existingNotifications[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'notifications' => $notifications,
                ];

                $allNotifications = array_merge($allNotifications, $notifications);
            }
        }

        array_unshift($existingNotifications, [
            'key' => '*',
            'label' => Craft::t('formie', 'All notifications'),
            'notifications' => $allNotifications,
        ]);

        if ($hasFormNotifications) {
            array_splice($existingNotifications, 1, 0, [[
                'heading' => Craft::t('formie', 'Forms'),
                'notifications' => [],
            ]]);
        }

        if ($hasStencilNotifications) {
            $insertIndex = ($stencilInsertIndex ?? count($existingNotifications)) + ($hasFormNotifications ? 1 : 0);

            array_splice($existingNotifications, $insertIndex, 0, [[
                'heading' => Craft::t('formie', 'Stencils'),
                'notifications' => [],
            ]]);
        }

        // Fire a 'modifyExistingNotifications' event
        $event = new ModifyExistingNotificationsEvent([
            'notifications' => $existingNotifications,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXISTING_NOTIFICATIONS, $event);

        return $this->_existingNotifications = $event->notifications;
    }

    public function getExistingNotificationFormOptions(FormInterface $excludeForm = null): array
    {
        $sources = $this->_getExistingNotificationSources($excludeForm);
        $formsWithNotifications = array_values(array_filter($sources['forms'], function(array $source): bool {
            return !empty($source['model']->getNotifications());
        }));
        $stencilsWithNotifications = array_values(array_filter($sources['stencils'], function(array $source): bool {
            return !empty($source['model']->getNotifications());
        }));

        $options = [[
            'key' => '*',
            'label' => Craft::t('formie', 'All notifications'),
            'notifications' => [],
        ]];

        if (!empty($formsWithNotifications)) {
            $options[] = [
                'heading' => Craft::t('formie', 'Forms'),
                'notifications' => [],
            ];

            foreach ($formsWithNotifications as $source) {
                $options[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'notifications' => [],
                ];
            }
        }

        if (!empty($stencilsWithNotifications)) {
            $options[] = [
                'heading' => Craft::t('formie', 'Stencils'),
                'notifications' => [],
            ];

            foreach ($stencilsWithNotifications as $source) {
                $options[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'notifications' => [],
                ];
            }
        }

        return $options;
    }

    public function getExistingNotificationSummaries(FormInterface $excludeForm = null, ?string $formKey = null, string $search = ''): array
    {
        $sources = $this->_getExistingNotificationSources($excludeForm);
        $trimmedSearch = trim($search);

        if ($formKey === '*' && $trimmedSearch === '') {
            return [[
                'key' => '*',
                'label' => Craft::t('formie', 'All notifications'),
                'notifications' => [],
            ]];
        }

        $resolveNotifications = function(array $notificationConfigs) use ($trimmedSearch): array {
            if ($trimmedSearch === '') {
                return $notificationConfigs;
            }

            return array_values(array_filter($notificationConfigs, function(array $notification) use ($trimmedSearch) {
                return $this->_notificationMatchesSearch($notification, $trimmedSearch);
            }));
        };

        if ($formKey === '*') {
            $allNotifications = [];

            foreach (array_merge($sources['forms'], $sources['stencils']) as $source) {
                $notifications = $resolveNotifications($this->getNotificationsConfig($source['model']->getNotifications()));

                if ($notifications) {
                    $allNotifications = array_merge($allNotifications, $notifications);
                }
            }

            return [[
                'key' => '*',
                'label' => Craft::t('formie', 'All notifications'),
                'notifications' => $allNotifications,
            ]];
        }

        $filteredSources = array_merge($sources['forms'], $sources['stencils']);

        if ($formKey) {
            $filteredSources = array_values(array_filter($filteredSources, function(array $source) use ($formKey) {
                return $source['key'] === $formKey;
            }));
        }

        $existingNotifications = [];

        foreach ($filteredSources as $source) {
            $notifications = $resolveNotifications($this->getNotificationsConfig($source['model']->getNotifications()));

            $existingNotifications[] = [
                'key' => $source['key'],
                'label' => $source['label'],
                'notifications' => $notifications,
            ];
        }

        return $existingNotifications;
    }

    public function evaluateConditions($notification, Submission $submission): bool
    {
        if ($notification->enableConditions) {
            $conditionSettings = $notification->conditions ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Notification conditions are authored as "match rows" plus a
                // separate send/don't-send rule. Inverting here preserves that
                // builder model instead of forcing authors to negate each row.
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

        $tabs = [
            [
                'handle' => 'content',
                'label' => Craft::t('formie', 'Content'),
                'content' => $this->defineContentSchema(),
            ],
        ];

        if ($user->checkPermission('formie-showNotificationsAdvanced')) {
            $tabs[] = [
                'handle' => 'advanced',
                'label' => Craft::t('formie', 'Advanced'),
                'content' => $this->defineFormBuilderAdvancedSchema(),
            ];
        }

        if ($user->checkPermission('formie-showNotificationsTemplates')) {
            $tabs[] = [
                'handle' => 'templates',
                'label' => Craft::t('formie', 'Templates'),
                'content' => $this->defineTemplatesSchema(),
            ];
        }
        
        $tabs[] = [
            'handle' => 'settings',
            'label' => Craft::t('formie', 'Settings'),
            'content' => $this->defineFormBuilderSettingsSchema(),
        ];

        $tabs[] = [
            'handle' => 'preview',
            'label' => Craft::t('formie', 'Preview'),
            'content' => $this->definePreviewSchema(),
        ];

        $tabs[] = [
            'handle' => 'conditions',
            'label' => Craft::t('formie', 'Conditions'),
            'content' => $this->defineFormBuilderConditionsSchema(),
        ];

        // Filter out tabs with empty content
        $tabs = array_values(array_filter($tabs, function ($tab) {
            return $tab['content'];
        }));

        // Fire a 'modifyNotificationSchema' event
        $event = new ModifyNotificationSchemaEvent([
            'tabs' => $tabs,
        ]);
        $this->trigger(self::EVENT_MODIFY_NOTIFICATION_SCHEMA, $event);

        $compiled = SchemaHelper::compileSchema(SchemaHelper::modalTabs($event->tabs));
        $compiled['schema'] = SchemaHelper::applyTranslatableToSchema($compiled['schema'], Notification::translatableProperties());

        return $compiled;
    }

    public function supportedNotificationDefaults(): array
    {
        return [
            'fromName',
            'from',
            'replyToName',
            'replyTo',
            'subject',
            'attachFiles',
            'attachPdf',
            'pdfTemplateId',
            'enabled',
        ];
    }

    public function getDefaultableSettingsSchema(): array
    {
        $fields = [
            'enabled' => 'enabled',
            'subject' => 'subject',
            'fromName' => 'fromName',
            'from' => 'from',
            'replyToName' => 'replyToName',
            'replyTo' => 'replyTo',
            'attachFiles' => 'attachFiles',
            'attachPdf' => 'attachPdf',
            'pdfTemplateId' => 'pdfTemplateId',
        ];

        $schema = SchemaHelper::extractDefaultsSchema([
            $this->defineContentSchema(),
            $this->defineFormBuilderAdvancedSchema(),
            $this->defineTemplatesSchema(),
        ], $fields);

        foreach ($schema as &$node) {
            $name = $node['name'] ?? null;

            if (in_array($name, ['attachFiles', 'attachPdf', 'enabled'], true)) {
                $node = SchemaHelper::inheritBooleanField([
                    'name' => $name,
                    'label' => $node['label'] ?? null,
                    'instructions' => $node['instructions'] ?? null,
                ]);

                continue;
            }

            if ($name !== 'pdfTemplateId') {
                continue;
            }

            unset($node['if']);

            $options = [
                ['label' => Craft::t('formie', 'Default Formie Template'), 'value' => ''],
            ];

            foreach (Formie::$plugin->getPdfTemplates()->getAllTemplates() as $template) {
                $options[] = ['label' => $template->name, 'value' => $template->id];
            }

            $node['$field'] = 'select';
            $node['options'] = $options;
        }
        unset($node);

        return $schema;
    }

    public function defineContentSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enabled'),
                'instructions' => Craft::t('formie', 'Whether this notification is enabled to send.'),
                'name' => 'enabled',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Name'),
                'instructions' => Craft::t('formie', 'What this notification will be called in the control panel.'),
                'name' => 'name',
                'required' => true,
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Recipients'),
                'instructions' => Craft::t('formie', 'Define who should receive this email notification. Define either specific emails, or emails based on conditions.'),
                'name' => 'recipients',
                'required' => true,
                'options' => [
                    ['label' => Craft::t('formie', 'Email Addresses'), 'value' => 'email'],
                    ['label' => Craft::t('formie', 'Conditions'), 'value' => 'conditions'],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Recipient Emails'),
                'instructions' => Craft::t('formie', 'Email addresses who receive this email notification. Separate multiple emails with a comma.'),
                'name' => 'to',
                'required' => true,
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
                'if' => 'recipients == "email"',
            ]),
            [
                '$field' => 'notificationRecipients',
                'label' => Craft::t('formie', 'Recipient Conditions'),
                'instructions' => Craft::t('formie', 'Use conditional logic to determine which email addresses receive this email notification.'),
                'name' => 'toConditions',
                'if' => 'recipients == "conditions"',
                'fieldOptions' => ConditionsHelper::getConditionFieldOptions($this->_getConditionFieldOptionConfig()),
                'conditionOptions' => ConditionsHelper::getConditionOptions(),
            ],
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Subject'),
                'instructions' => Craft::t('formie', 'The subject of the email notification.'),
                'name' => 'subject',
                'required' => true,
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Email Content'),
                'instructions' => Craft::t('formie', 'The body content for this notification.'),
                'name' => 'content',
                'required' => true,
                'variableConfig' => [
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ], RichTextHelper::getRichTextConfig('notifications.content'))),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'From Name'),
                'instructions' => Craft::t('formie', 'The name the notification email will be sent from.'),
                'name' => 'fromName',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'From Email'),
                'instructions' => Craft::t('formie', 'The email address the notification email will be sent from. Leave empty to use the default email address for your site.'),
                'name' => 'from',
                'validation' => 'emailOrVariable',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
                'info' => Craft::t('formie', 'If not correctly configured, setting the "From" setting can lead to deliverability issues. Read [our guide](https://verbb.io/craft-plugins/formie/user-guides/how-to-keep-email-notifications-out-of-your-junk-emails) for tips.'),
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Reply-To Name'),
                'instructions' => Craft::t('formie', 'The name to be used as the reply to for the notification email.'),
                'name' => 'replyToName',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Reply-To Email'),
                'instructions' => Craft::t('formie', 'The email address to be used as the reply to address for the notification email.'),
                'name' => 'replyTo',
                'validation' => 'emailOrVariable',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
                'info' => Craft::t('formie', 'Do not use the same email for "From" and "Reply-To". Read [our guide](https://verbb.io/craft-plugins/formie/user-guides/how-to-keep-email-notifications-out-of-your-junk-emails) for tips.'),
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'CC'),
                'instructions' => Craft::t('formie', 'Email addresses who will receive a CC of the notification email. Separate multiple emails with a comma.'),
                'name' => 'cc',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'BCC'),
                'instructions' => Craft::t('formie', 'Email addresses who will receive a BCC of the notification email. Separate multiple emails with a comma.'),
                'name' => 'bcc',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Sender Email'),
                'instructions' => Craft::t('formie', 'The email address for the notification email "sender" header, for advanced usage. Leave empty to use the "From Email".'),
                'name' => 'sender',
                'validation' => 'emailOrVariable',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_EMAIL],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Dispatch Timing'),
                'instructions' => Craft::t('formie', 'When to send this notification relative to integrations. Requires Integration Dispatch to be enabled on the form.'),
                'name' => 'dispatchTiming',
                'defaultValue' => Notification::DISPATCH_TIMING_DEFAULT,
                'options' => [
                    ['label' => Craft::t('formie', 'Use form default'), 'value' => Notification::DISPATCH_TIMING_DEFAULT],
                    ['label' => Craft::t('formie', 'Before integrations'), 'value' => Notification::DISPATCH_TIMING_BEFORE],
                    ['label' => Craft::t('formie', 'After integrations'), 'value' => Notification::DISPATCH_TIMING_AFTER],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Attach File Uploads'),
                'instructions' => Craft::t('formie', 'Whether to attach file uploads to this email notification.'),
                'name' => 'attachFiles',
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Attach Assets'),
                'instructions' => Craft::t('formie', 'Select assets to be attached to this email notification. Assets over the maximum size configured in Formie Settings → Notifications cannot be saved.'),
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
                'instructions' => Craft::t('formie', 'Select a template to use for the Email, or leave empty to use Formie‘s default.'),
                'name' => 'templateId',
                'options' => $emailTemplates,
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Attach PDF Template'),
                'instructions' => Craft::t('formie', 'Whether to attach a PDF template to this email notification.'),
                'name' => 'attachPdf',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'PDF Template'),
                'instructions' => Craft::t('formie', 'Select a template to use for the PDF, or leave empty to use Formie‘s default.'),
                'name' => 'pdfTemplateId',
                'options' => $pdfTemplates,
                'if' => 'attachPdf',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::handleField([
                'instructions' => Craft::t('formie', 'How you’ll refer to this notification in your templates. Use the refresh icon to re-generate this from your notification name.'),
                'warning' => Craft::t('formie', 'Changing this may result in your notification not working as expected.'),
                'source' => 'name',
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

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Conditions'),
                'instructions' => Craft::t('formie', 'Whether to enable conditional logic to control how this email notification is sent.'),
                'name' => 'enableConditions',
            ]),
            [
                '$field' => 'notificationConditions',
                'name' => 'conditions',
                'if' => 'enableConditions',
                'fieldOptions' => ConditionsHelper::getConditionFieldOptions($this->_getConditionFieldOptionConfig()),
                'conditionOptions' => ConditionsHelper::getConditionOptions(),
            ],
        ];
    }


    // Private Methods
    // =========================================================================

    private function _createNotificationsQuery(): Query
    {
        $select = [
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
        ];

        if (DbSchema::columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            array_splice($select, -2, 0, ['dispatchTiming']);
        }

        return (new Query())
            ->select($select)
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_NOTIFICATIONS]);
    }

    private function _getExistingNotificationSources(FormInterface $excludeForm = null): array
    {
        $query = Form::find()->orderBy('title ASC');

        if ($excludeForm instanceof Form) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();
        $stencils = Formie::$plugin->getStencils()->getAllStencils();

        if ($excludeForm instanceof Stencil) {
            $stencils = array_values(array_filter($stencils, function($stencil) use ($excludeForm) {
                return $stencil->id != $excludeForm->id;
            }));
        }

        // Keys must be unique across forms and stencils: handles (and titles shown
        // as labels) can match between a Form and a Stencil, so never use handle alone.
        $formSources = array_map(function(Form $form) {
            return [
                'key' => 'form:' . $form->id,
                'label' => $form->title,
                'model' => $form,
            ];
        }, $forms);

        $stencilSources = array_map(function(Stencil $stencil) {
            return [
                'key' => 'stencil:' . $stencil->id,
                'label' => $stencil->title,
                'model' => $stencil,
            ];
        }, $stencils);

        return [
            'forms' => $formSources,
            'stencils' => $stencilSources,
            'hasForms' => !empty($formSources),
            'hasStencils' => !empty($stencilSources),
        ];
    }

    private function _notificationMatchesSearch(array $notification, string $search): bool
    {
        $query = mb_strtolower(trim($search));

        if ($query === '') {
            return true;
        }

        $name = mb_strtolower($this->_getSearchableRichText($notification['name'] ?? ''));
        $subject = mb_strtolower($this->_getSearchableRichText($notification['subject'] ?? ''));
        $handle = mb_strtolower((string)($notification['handle'] ?? ''));

        return str_contains($name, $query)
            || str_contains($subject, $query)
            || str_contains($handle, $query);
    }

    private function _getSearchableRichText(mixed $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        try {
            $html = RichTextHelper::getHtmlContent($content, null, false);
            $text = strip_tags((string)$html);

            return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text)) ?? '');
        } catch (Throwable $e) {
            // Fallback for plain strings or malformed rich-text payloads.
            $text = is_string($content) ? $content : Json::encode($content);
            $text = strip_tags((string)$text);

            return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text)) ?? '');
        }
    }

    private function _getNotificationRecord(int|string|null $id): NotificationRecord
    {
        /** @var NotificationRecord $notification */
        if ($id && $notification = NotificationRecord::find()->where(['id' => $id])->one()) {
            return $notification;
        }

        return new NotificationRecord();
    }

    private function _getConditionFieldOptionConfig(): array
    {
        return [
            'includeSubmissionDate' => true,
            'siteNameOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($site) {
                return [
                    'label' => $site->name,
                    'value' => $site->name,
                ];
            }, Craft::$app->getSites()->getAllSites())),
            'siteHandleOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($site) {
                return [
                    'label' => $site->name,
                    'value' => $site->handle,
                ];
            }, Craft::$app->getSites()->getAllSites())),
            'statusOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($status) {
                return [
                    'label' => $status->name,
                    'value' => $status->handle,
                ];
            }, Formie::$plugin->getSubmissionStatuses()->getAllStatuses())),
        ];
    }
}

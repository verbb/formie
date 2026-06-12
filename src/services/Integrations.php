<?php
namespace verbb\formie\services;

use verbb\formie\cache\IntegrationLookupCache;
use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\events\ModifyFormIntegrationEvent;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\events\TriggerIntegrationFailureEvent;
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\gql\types\input\CaptchaInputType;
use verbb\formie\integrations\addressproviders;
use verbb\formie\integrations\captchas;
use verbb\formie\integrations\crm;
use verbb\formie\integrations\elements;
use verbb\formie\integrations\emailmarketing;
use verbb\formie\integrations\helpdesk;
use verbb\formie\integrations\miscellaneous;
use verbb\formie\integrations\messaging;
use verbb\formie\integrations\payments;
use verbb\formie\integrations\automations;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\MissingIntegration;
use verbb\formie\models\Settings;
use verbb\formie\records\Integration as IntegrationRecord;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\Queue;
use craft\queue\Queue as CraftQueue;

use yii\base\Component;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;

use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class Integrations extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_INTEGRATIONS = 'registerFormieIntegrations';
    public const EVENT_MODIFY_FORM_INTEGRATIONS = 'modifyFormIntegrations';
    public const EVENT_MODIFY_FORM_INTEGRATION = 'modifyFormIntegration';
    public const EVENT_BEFORE_SAVE_INTEGRATION = 'beforeSaveIntegration';
    public const EVENT_AFTER_SAVE_INTEGRATION = 'afterSaveIntegration';
    public const EVENT_BEFORE_DELETE_INTEGRATION = 'beforeDeleteIntegration';
    public const EVENT_BEFORE_APPLY_INTEGRATION_DELETE = 'beforeApplyIntegrationDelete';
    public const EVENT_AFTER_DELETE_INTEGRATION = 'afterDeleteIntegration';
    public const EVENT_BEFORE_TRIGGER_INTEGRATION = 'beforeTriggerIntegration';
    public const EVENT_AFTER_TRIGGER_INTEGRATION_FAILED = 'afterTriggerIntegrationFailed';
    public const CONFIG_INTEGRATIONS_KEY = 'formie.integrations';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_integrations = null;
    private ?IntegrationLookupCache $_lookupCache = null;


    // Public Methods
    // =========================================================================

    public function getAllIntegrationTypes(): array
    {
        if ($this->_getLookupCache()->groupedIntegrationTypes) {
            return $this->_getLookupCache()->groupedIntegrationTypes;
        }

        $addressProviders = [
            addressproviders\Google::class,
            addressproviders\AddressFinder::class,
            addressproviders\Loqate::class,
            addressproviders\PlaceKit::class,
        ];

        $captchas = [
            captchas\Akismet::class,
            captchas\CaptchaEu::class,
            captchas\CleanTalk::class,
            captchas\Turnstile::class,
            captchas\FriendlyCaptcha::class,
            captchas\Hcaptcha::class,
            captchas\OopSpam::class,
            captchas\Question::class,
            captchas\Recaptcha::class,
            captchas\Snaptcha::class,
        ];

        $elements = [
            elements\CalendarEvent::class,
            elements\Entry::class,
            elements\EventsEvent::class,
            elements\Product::class,
            elements\User::class,
        ];

        $emailMarketing = [
            emailmarketing\ActiveCampaign::class,
            emailmarketing\Adestra::class,
            emailmarketing\AWeber::class,
            emailmarketing\Beehiiv::class,
            emailmarketing\Benchmark::class,
            emailmarketing\Brevo::class,
            emailmarketing\Campaign::class,
            emailmarketing\CampaignMonitor::class,
            emailmarketing\CleverReach::class,
            emailmarketing\ConstantContact::class,
            emailmarketing\ConvertKit::class,
            emailmarketing\CustomerIo::class,
            emailmarketing\Drip::class,
            emailmarketing\Ecomail::class,
            emailmarketing\EmailOctopus::class,
            emailmarketing\GetResponse::class,
            emailmarketing\IContact::class,
            emailmarketing\IterableIntegration::class,
            emailmarketing\Klaviyo::class,
            emailmarketing\Mailchimp::class,
            emailmarketing\Mailcoach::class,
            emailmarketing\Mailjet::class,
            emailmarketing\MailerLite::class,
            emailmarketing\Moosend::class,
            emailmarketing\Omnisend::class,
            emailmarketing\Ontraport::class,
            emailmarketing\Ortto::class,
            emailmarketing\Sender::class,
            emailmarketing\Vero::class,
        ];

        $crm = [
            crm\ActiveCampaign::class,
            crm\Agile::class,
            crm\Attio::class,
            crm\Avochato::class,
            crm\Capsule::class,
            crm\CiviCrm::class,
            crm\Copper::class,
            crm\Dotdigital::class,
            crm\Flowlu::class,
            crm\Freshsales::class,
            crm\HubSpot::class,
            crm\Infusionsoft::class,
            crm\Insightly::class,
            crm\IterableIntegration::class,
            crm\Klaviyo::class,
            crm\Marketo::class,
            crm\Maximizer::class,
            crm\Mercury::class,
            crm\MicrosoftDynamics365::class,
            crm\NoCrm::class,
            crm\OneCrm::class,
            crm\Outseta::class,
            crm\Pardot::class,
            crm\Pipedrive::class,
            crm\Pipeliner::class,
            crm\Procurios::class,
            crm\Salesflare::class,
            crm\Salesforce::class,
            crm\Salesmate::class,
            crm\Scoro::class,
            crm\SharpSpring::class,
            crm\SugarCrm::class,
            crm\SuiteCrm::class,
            crm\VCita::class,
            crm\Xero::class,
            crm\Zoho::class,
        ];

        $helpDesk = [
            helpdesk\Freshdesk::class,
            helpdesk\Front::class,
            helpdesk\Gorgias::class,
            helpdesk\HelpScout::class,
            helpdesk\Intercom::class,
            helpdesk\LiveChat::class,
            helpdesk\Zendesk::class,
        ];

        $messaging = [
            messaging\Discord::class,
            messaging\Plivo::class,
            messaging\Slack::class,
            messaging\Telegram::class,
            messaging\Twilio::class,
        ];

        $payments = [
            payments\Bpoint::class,
            payments\Eway::class,
            payments\GoCardless::class,
            payments\Mollie::class,
            payments\Moneris::class,
            payments\Opayo::class,
            payments\Paddle::class,
            payments\PayPal::class,
            payments\PayWay::class,
            payments\Square::class,
            payments\Stripe::class,
        ];

        $automations = [
            automations\Ifttt::class,
            automations\Make::class,
            automations\N8n::class,
            automations\WebRequest::class,
            automations\Zapier::class,
        ];

        $miscellaneous = [
            miscellaneous\ClickUp::class,
            miscellaneous\GoogleSheets::class,
            miscellaneous\Monday::class,
            miscellaneous\Recruitee::class,
            miscellaneous\Trello::class,
        ];

        $event = new RegisterIntegrationsEvent([
            'addressProviders' => $addressProviders,
            'captchas' => $captchas,
            'elements' => $elements,
            'emailMarketing' => $emailMarketing,
            'crm' => $crm,
            'helpDesk' => $helpDesk,
            'messaging' => $messaging,
            'payments' => $payments,
            'automations' => $automations,
            'miscellaneous' => $miscellaneous,
        ]);

        $this->trigger(self::EVENT_REGISTER_INTEGRATIONS, $event);

        $groupedTypes = [
            Integration::TYPE_ADDRESS_PROVIDER => $event->addressProviders,
            Integration::TYPE_CAPTCHA => $event->captchas,
            Integration::TYPE_ELEMENT => $event->elements,
            Integration::TYPE_EMAIL_MARKETING => $event->emailMarketing,
            Integration::TYPE_CRM => $event->crm,
            Integration::TYPE_HELP_DESK => $event->helpDesk,
            Integration::TYPE_MESSAGING => $event->messaging,
            Integration::TYPE_PAYMENT => $event->payments,

            Integration::TYPE_AUTOMATION => $event->automations,
            
            Integration::TYPE_MISC => $event->miscellaneous,
        ];

        // Fetch all for performance
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        $groupedTypes = array_filter(array_map(function(array $integrations) use ($plugins) {
            return array_values(array_filter($integrations, function(string $integrationClass) use ($plugins) {
                foreach ((array)$integrationClass::getRequiredPlugins() as $pluginInfo) {
                    $handle = $pluginInfo;
                    $version = 0;

                    if (is_array($pluginInfo)) {
                        $handle = $pluginInfo['handle'] ?? '';
                        $version = $pluginInfo['version'] ?? 0;
                    }

                    if (
                        !Plugin::isPluginInstalledAndEnabled($handle) ||
                        !isset($plugins[$handle]) ||
                        version_compare($plugins[$handle]->getVersion(), $version, '<')
                    ) {
                        return false;
                    }
                }

                return true;
            }));
        }, $groupedTypes));

        return $this->_getLookupCache()->groupedIntegrationTypes = $groupedTypes;
    }

    public function getIntegrationTypes($type)
    {
        return $this->getAllIntegrationTypes()[$type] ?? [];
    }

    public function getAllIntegrations(): array
    {
        return $this->_integrations()->all();
    }

    public function getAllIntegrationsForType($type): array
    {
        if (array_key_exists($type, $this->_getLookupCache()->integrationsByType)) {
            return $this->_getLookupCache()->integrationsByType[$type];
        }

        $this->_getLookupCache()->integrationsByType[$type] = [];

        $getIntegrationTypes = $this->getIntegrationTypes($type);

        foreach ($this->getAllIntegrations() as $integration) {
            if (in_array(get_class($integration), $getIntegrationTypes)) {
                $this->_getLookupCache()->integrationsByType[$type][] = $integration;
            }
        }

        return $this->_getLookupCache()->integrationsByType[$type];
    }

    public function triggerIntegrations(Submission $submission, string $processMode = SubmissionWorkflow::PROCESS_MODE_SUBMIT): void
    {
        $settings = Formie::$plugin->getSettings();
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        $isSubmissionEdit = $processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING;

        foreach ($this->getAllEnabledIntegrationsForForm($form) as $integration) {
            if (!$integration->supportsPayloadSending()) {
                continue;
            }

            if ($isSubmissionEdit && !$integration->shouldTriggerOnSubmissionEdit()) {
                continue;
            }

            if (!$integration->shouldTrigger($submission, [
                'processMode' => $processMode,
                'isSubmissionEdit' => $isSubmissionEdit,
            ])) {
                continue;
            }

            $integration->populateContext();

            if ($settings->useQueueForIntegrations) {
                Queue::push(new TriggerIntegration([
                    'submissionId' => $submission->id,
                    'integrationId' => $integration->id,
                    'integrationHandle' => $integration->handle,
                    'integrationContext' => $integration->context,
                    'formId' => $form->id ?? null,
                    'formHandle' => $form->handle ?? null,
                    'formTitle' => $form->title ?? null,
                ]), $settings->queuePriority);

                continue;
            }

            $this->sendIntegrationPayload($integration, $submission);
        }
    }

    public function sendIntegrationPayload(Integration $integration, Submission $submission): bool|IntegrationResponse
    {
        $event = new TriggerIntegrationEvent([
            'submission' => $submission,
            'type' => get_class($integration),
            'integration' => $integration,
        ]);
        $this->trigger(self::EVENT_BEFORE_TRIGGER_INTEGRATION, $event);

        if (!$event->isValid) {
            return true;
        }

        try {
            $response = $integration->sendPayLoad($event->submission);
        } catch (Throwable $e) {
            $this->handleTriggerIntegrationFailed($integration, $submission, $e);
            throw $e;
        }

        if ($response instanceof IntegrationResponse && !$response->success) {
            $this->handleTriggerIntegrationFailed(
                $integration,
                $submission,
                new IntegrationException(Craft::t('formie', 'Failed to trigger integration: {message}.', [
                    'message' => Json::encode($response->message),
                ])),
                $response,
            );

            return $response;
        }

        if (!$response) {
            $this->handleTriggerIntegrationFailed(
                $integration,
                $submission,
                new IntegrationException(Craft::t('formie', 'Failed to trigger integration. Check the Formie log files.')),
            );

            return false;
        }

        return $response;
    }

    public function handleTriggerIntegrationFailed(
        Integration $integration,
        Submission $submission,
        Throwable $exception,
        IntegrationResponse|array|null $integrationResponse = null,
        ?int $queueJobId = null,
    ): void {
        $queueJob = $integration->getQueueJob();
        $fromQueue = $queueJob instanceof TriggerIntegration;
        $payload = $fromQueue ? ($queueJob->payload ?? null) : null;

        if ($fromQueue && $queueJobId === null) {
            $queue = Craft::$app->getQueue();

            if ($queue instanceof CraftQueue) {
                $currentJobId = (int)$queue->getJobId();

                if ($currentJobId > 0) {
                    $queueJobId = $currentJobId;
                }
            }
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED)) {
            $this->trigger(self::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, new TriggerIntegrationFailureEvent([
                'submission' => $submission,
                'integration' => $integration,
                'exception' => $exception,
                'integrationResponse' => $integrationResponse,
                'payload' => $payload,
                'queueJobId' => $queueJobId,
                'fromQueue' => $fromQueue,
            ]));
        }

        $this->sendFailAlertEmail($integration, $submission, $exception, $integrationResponse, $payload, $queueJobId);
    }

    public function sendFailAlertEmail(
        Integration $integration,
        Submission $submission,
        Throwable $exception,
        IntegrationResponse|array|null $integrationResponse = null,
        mixed $payload = null,
        ?int $queueJobId = null,
    ): ?array {
        /** @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!$settings->sendIntegrationAlerts) {
            return null;
        }

        if (!$settings->validate()) {
            $error = Craft::t('formie', 'Integration fail alert settings are invalid: “{errors}”.', [
                'errors' => Json::encode($settings->getErrors()),
            ]);

            Formie::error($error);

            return ['error' => $error];
        }

        $form = $submission->getForm();
        $renderVariables = [
            'integration' => $integration,
            'submission' => $submission,
            'form' => $form,
            'exception' => $exception,
            'errorMessage' => $exception->getMessage(),
            'integrationResponse' => $integrationResponse instanceof IntegrationResponse
                ? $integrationResponse->toArray()
                : $integrationResponse,
            'payload' => $payload,
            'queueJobId' => $queueJobId,
        ];

        $recipients = $settings->getIntegrationFailAlertRecipients();

        if (!$recipients) {
            $error = Craft::t('formie', 'No integration fail alert recipients are configured.');

            Formie::error($error);

            return ['error' => $error];
        }

        foreach ($recipients as $recipient) {
            try {
                Craft::$app->getMailer()
                    ->composeFromKey('formie_failed_integration', $renderVariables)
                    ->setTo($recipient['email'])
                    ->send();
            } catch (Throwable $e) {
                Craft::$app->getErrorHandler()->logException($e);

                $error = Craft::t('formie', 'Integration failure alert email could not be sent for submission “{submission}”. Error: {error} {file}:{line}', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'submission' => $submission->id ?: 'new',
                ]);

                Formie::error($error);

                return ['error' => $error];
            }
        }

        return null;
    }

    public function getIntegrationById(int $integrationId): ?IntegrationInterface
    {
        if (!$integrationId) {
            return null;
        }

        if (array_key_exists($integrationId, $this->_getLookupCache()->integrationsById)) {
            return $this->_getLookupCache()->integrationsById[$integrationId];
        }

        return $this->_getLookupCache()->integrationsById[$integrationId] = ArrayHelper::firstWhere($this->getAllIntegrations(), 'id', $integrationId);
    }

    public function getIntegrationByUid(string $integrationUid): ?IntegrationInterface
    {
        if ($integrationUid === '') {
            return null;
        }

        if (array_key_exists($integrationUid, $this->_getLookupCache()->integrationsByUid)) {
            return $this->_getLookupCache()->integrationsByUid[$integrationUid];
        }

        return $this->_getLookupCache()->integrationsByUid[$integrationUid] = ArrayHelper::firstWhere($this->getAllIntegrations(), 'uid', $integrationUid);
    }

    public function getIntegrationByHandle(string $handle): ?IntegrationInterface
    {
        if ($handle === '') {
            return null;
        }

        $normalizedHandle = mb_strtolower($handle);

        if (array_key_exists($normalizedHandle, $this->_getLookupCache()->integrationsByHandle)) {
            return $this->_getLookupCache()->integrationsByHandle[$normalizedHandle];
        }

        return $this->_getLookupCache()->integrationsByHandle[$normalizedHandle] = ArrayHelper::firstWhere($this->getAllIntegrations(), 'handle', $handle, true);
    }

    /**
     * Returns an integration or captcha by handle from the form builder list (getAllIntegrationsForForm).
     * Use this when resolving by handle for form settings config; getIntegrationByHandle only checks DB integrations.
     */
    public function getFormIntegrationByHandle(string $handle): ?IntegrationInterface
    {
        $grouped = $this->getAllIntegrationsForForm();
        foreach ($grouped as $integrations) {
            $found = ArrayHelper::firstWhere($integrations, 'handle', $handle, true);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Returns form builder config for one integration: compiled schema and current values for settings.integrations[handle].
     */
    public function getIntegrationFormSettingsConfig(string $handle, FormInterface $form): ?array
    {
        $integration = $this->getFormIntegrationByHandle($handle);

        if ($integration === null) {
            $integration = $this->getIntegrationByHandle($handle) ?? $this->getCaptchaByHandle($handle);
        }

        if ($integration === null) {
            return null;
        }

        $formSettings = $form->getSettings();
        $saved = $formSettings ? ($formSettings->integrations[$handle] ?? []) : [];
        if (is_object($saved)) {
            if (method_exists($saved, 'getAttributes')) {
                $saved = $saved->getAttributes() ?? [];
            } else {
                // Form settings can contain stdClass payloads from decoded JSON.
                // Normalize recursively to arrays so defaults hydrate correctly.
                $saved = Json::decode(Json::encode($saved));
            }
        }
        if (!is_array($saved)) {
            $saved = [];
        }
        $integration->setAttributes($saved, false);

        $schema = $integration->getFormSettingsSchema($form);
        $compiled = SchemaHelper::compileSchema($schema);

        return [
            'schema' => $compiled['schema'],
            'schemaIndex' => $compiled,
            'defaultValues' => $saved,
        ];
    }

    public function createIntegrationConfig(IntegrationInterface $integration): array
    {
        return [
            'name' => $integration->name,
            'handle' => $integration->handle,
            'type' => get_class($integration),
            'enabled' => $integration->getEnabled(false),
            'sortOrder' => (int)$integration->sortOrder,
            'settings' => ProjectConfigHelper::packAssociativeArrays($integration->getSettings()),
        ];
    }

    public function saveIntegration(IntegrationInterface $integration, bool $runValidation = true): bool
    {
        $isNewIntegration = $integration->getIsNew();

        // Fire a 'beforeSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
                'isNew' => $isNewIntegration,
            ]));
        }

        if (!$integration->beforeSave($isNewIntegration)) {
            return false;
        }

        if ($runValidation && !$integration->validate()) {
            Formie::info('Integration not saved due to validation error.');

            return false;
        }

        if ($isNewIntegration) {
            $integration->uid = StringHelper::UUID();
            
            $integration->sortOrder = (new Query())
                    ->from([Table::FORMIE_INTEGRATIONS])
                    ->max('[[sortOrder]]') + 1;
        } else if (!$integration->uid) {
            $integration->uid = Db::uidById(Table::FORMIE_INTEGRATIONS, $integration->id);
        }

        $configPath = self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->uid;
        $configData = $this->createIntegrationConfig($integration);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$integration->handle}” integration");

        if ($isNewIntegration) {
            $integration->id = Db::idByUid(Table::FORMIE_INTEGRATIONS, $integration->uid);
        }

        return true;
    }

    public function handleChangedIntegration(ConfigEvent $event): void
    {
        $integrationUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Skip captchas - already done, and are PC-only.
        if (in_array($data['type'], $this->getIntegrationTypes(Integration::TYPE_CAPTCHA))) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $integrationRecord = $this->_getIntegrationRecord($integrationUid, true);
            $isNewIntegration = $integrationRecord->getIsNewRecord();

            $settings = $data['settings'] ?? [];

            // Don't merge any attributes in `extraAttributes()` which are environment-specific
            if ($integrationRecord->id) {
                $integration = $this->getIntegrationById($integrationRecord->id);

                if ($integration) {
                    foreach ($integration->extraAttributes() as $attribute) {
                        $settings[$attribute] = $settings[$attribute] ?? $integration->$attribute;
                    }
                }
            }

            $integrationRecord->name = $data['name'];
            $integrationRecord->handle = $data['handle'];
            $integrationRecord->type = $data['type'];
            $integrationRecord->enabled = $data['enabled'];
            $integrationRecord->sortOrder = $data['sortOrder'];
            $integrationRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($settings);
            $integrationRecord->uid = $integrationUid;

            // Save the integration
            if ($wasTrashed = (bool)$integrationRecord->dateDeleted) {
                $integrationRecord->restore();
            } else {
                $integrationRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_resetIntegrationCaches();

        $integration = $this->getIntegrationById($integrationRecord->id);
        $integration->afterSave($isNewIntegration);

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $this->getIntegrationById($integrationRecord->id),
                'isNew' => $isNewIntegration,
            ]));
        }
    }

    public function reorderIntegrations(array $integrationIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::FORMIE_INTEGRATIONS, $integrationIds);

        foreach ($integrationIds as $integrationOrder => $integrationId) {
            if (!empty($uidsByIds[$integrationId])) {
                $integrationUid = $uidsByIds[$integrationId];
                $projectConfig->set(self::CONFIG_INTEGRATIONS_KEY . '.' . $integrationUid . '.sortOrder', $integrationOrder + 1, "Reorder integrations");
            }
        }

        return true;
    }

    public function createIntegration(mixed $config): IntegrationInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        // `cache` is stored as JSON in the DB (longText); decode like `settings` so in-memory merge/state stays correct.
        if (array_key_exists('cache', $config)) {
            if (is_string($config['cache']) && $config['cache'] !== '') {
                $config['cache'] = Json::decode($config['cache']) ?: [];
            }

            if (!is_array($config['cache'])) {
                unset($config['cache']);
            }
        }

        try {
            $integration = ComponentHelper::createComponent($config, IntegrationInterface::class);
        } catch (UnknownPropertyException $e) {
            throw $e;
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $integration = new MissingIntegration($config);
        }

        return $integration;
    }

    public function deleteIntegrationById(int $integrationId): bool
    {
        $integration = $this->getIntegrationById($integrationId);

        if (!$integration) {
            return false;
        }

        return $this->deleteIntegration($integration);
    }

    public function deleteIntegration(IntegrationInterface $integration): bool
    {
        // Fire a 'beforeDeleteIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        if (!$integration->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->uid, "Delete the “{$integration->handle}” integration");

        return true;
    }

    public function handleDeletedIntegration(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $integrationRecord = $this->_getIntegrationRecord($uid);

        if ($integrationRecord->getIsNewRecord()) {
            return;
        }

        $integration = $this->getIntegrationById($integrationRecord->id);

        // Fire a 'beforeApplyIntegrationDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_INTEGRATION_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_INTEGRATION_DELETE, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $integration->beforeApplyDelete();

            // Delete the integration
            $db->createCommand()
                ->softDelete(Table::FORMIE_INTEGRATIONS, ['id' => $integrationRecord->id])
                ->execute();

            $integration->afterDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_resetIntegrationCaches();

        // Fire an 'afterDeleteIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }
    }

    public function getAllIntegrationsForForm(): array
    {
        $grouped = [];
        $hasModifyFormIntegrationHandlers = $this->hasEventHandlers(self::EVENT_MODIFY_FORM_INTEGRATION);

        foreach ($this->getAllCaptchas() as $key => $captcha) {
            if ($captcha->getEnabled() && $captcha->hasFormSettings()) {
                $resolvedIntegration = $captcha;

                // Fire a 'modifyFormIntegration' event only when handlers exist.
                if ($hasModifyFormIntegrationHandlers) {
                    $event = new ModifyFormIntegrationEvent([
                        'integration' => $captcha,
                    ]);
                    $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATION, $event);
                    $resolvedIntegration = $event->integration;
                }

                $grouped[$resolvedIntegration->typeName()][] = $resolvedIntegration;
            }
        }

        foreach ($this->getAllIntegrations() as $key => $integration) {
            if ($integration->getEnabled() && $integration->hasFormSettings()) {
                $resolvedIntegration = $integration;

                // Fire a 'modifyFormIntegration' event only when handlers exist.
                if ($hasModifyFormIntegrationHandlers) {
                    $event = new ModifyFormIntegrationEvent([
                        'integration' => $integration,
                    ]);
                    $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATION, $event);
                    $resolvedIntegration = $event->integration;
                }

                $grouped[$resolvedIntegration->typeName()][] = $resolvedIntegration;
            }
        }

        return $grouped;
    }

    /**
     * Returns a lightweight list of integrations for the form builder left panel (group, handle, name, icon, enabled).
     * Use this in the form builder bootstrap instead of getAllIntegrationsForForm() to avoid sending full integration objects and schemas.
     */
    public function getIntegrationSummariesForForm(): array
    {
        $summaries = [];
        $hasModifyFormIntegrationHandlers = $this->hasEventHandlers(self::EVENT_MODIFY_FORM_INTEGRATION);
        $resolveSummaryIconUrl = function(IntegrationInterface $integration): string {
            return $integration->getCpIconUrl();
        };

        $appendSummary = function(IntegrationInterface $integration) use (&$summaries, $hasModifyFormIntegrationHandlers, $resolveSummaryIconUrl) {
            if (!$integration->getEnabled() || !$integration->hasFormSettings()) {
                return;
            }

            $resolvedIntegration = $integration;

            if ($hasModifyFormIntegrationHandlers) {
                $event = new ModifyFormIntegrationEvent([
                    'integration' => $integration,
                ]);
                $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATION, $event);
                $resolvedIntegration = $event->integration;
            }

            $groupName = $resolvedIntegration->typeName();
            
            $summaries[$groupName][] = [
                'handle' => $resolvedIntegration->getHandle(),
                'name' => $resolvedIntegration->getName(),
                'description' => $resolvedIntegration->getDescription(),
                'enabled' => $resolvedIntegration->getEnabled(false),
                'icon' => $resolveSummaryIconUrl($resolvedIntegration),
                'supportsRefresh' => method_exists($resolvedIntegration, 'supportsFormSettingsRefresh') ? $resolvedIntegration->supportsFormSettingsRefresh() : false,
            ];
        };

        foreach ($this->getAllCaptchas() as $captcha) {
            $appendSummary($captcha);
        }

        foreach ($this->getAllIntegrations() as $integration) {
            $appendSummary($integration);
        }

        return $summaries;
    }

    public function getAllEnabledIntegrationsForForm(Form $form): array
    {
        $cacheKey = $this->_getFormRequestCacheKey($form);
        $cache = $this->_getLookupCache();

        if (array_key_exists($cacheKey, $cache->enabledIntegrationsByForm)) {
            return $cache->enabledIntegrationsByForm[$cacheKey];
        }

        $enabledIntegrations = [];
        $integrationsByHandle = [];
        $hasModifyFormIntegrationHandlers = $this->hasEventHandlers(self::EVENT_MODIFY_FORM_INTEGRATION);

        // Use all integrations + captchas
        foreach (array_merge($this->getAllIntegrations(), $this->getAllCaptchas()) as $integration) {
            $resolvedIntegration = $integration;

            if ($hasModifyFormIntegrationHandlers) {
                $event = new ModifyFormIntegrationEvent([
                    'integration' => $integration,
                ]);
                $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATION, $event);
                $resolvedIntegration = $event->integration;
            }

            $integrationsByHandle[$resolvedIntegration->handle] = $resolvedIntegration;
        }

        // Find all the form-enabled integrations
        $formIntegrationSettings = $form->settings->integrations ?? [];
        $enabledFormSettings = ArrayHelper::where($formIntegrationSettings, 'enabled', true);

        foreach ($enabledFormSettings as $handle => $formSettings) {
            $integration = $integrationsByHandle[$handle] ?? null;

            // If this disabled globally? Then don't include it, otherwise populate the settings
            if ($integration && $integration->getEnabled()) {
                $resolvedIntegration = clone $integration;
                $resolvedIntegration->setAttributes($formSettings, false);

                $enabledIntegrations[] = $resolvedIntegration;
            }
        }

        // Fire a 'modifyFormIntegrations' event
        $event = new ModifyFormIntegrationsEvent([
            'allIntegrations' => array_values($integrationsByHandle),
            'integrations' => $enabledIntegrations,
            'form' => $form,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATIONS, $event);

        return $cache->enabledIntegrationsByForm[$cacheKey] = $event->integrations;
    }

    public function getAllCaptchas(): array
    {
        if ($this->_getLookupCache()->captchas !== null) {
            return $this->_getLookupCache()->captchas;
        }

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $captchas = [];

        foreach ($this->getIntegrationTypes(Integration::TYPE_CAPTCHA) as $captchaClass) {
            $class = new $captchaClass();

            // Load in any settings from PC
            $config = $settings->captchas[$class->getHandle()] ?? [];
            $config['type'] = $captchaClass;

            $captchas[] = $this->createIntegration($config);
        }

        return $this->_getLookupCache()->captchas = $captchas;
    }

    public function getAllGroupedCaptchas(): array
    {
        $grouped = [];

        $captchas = $this->getAllCaptchas();

        foreach ($captchas as $captcha) {
            $grouped[$captcha->typeName()][] = $captcha;
        }

        return $grouped;
    }

    public function getCaptchaByHandle(string $handle): ?IntegrationInterface
    {
        if ($handle === '') {
            return null;
        }

        if (array_key_exists($handle, $this->_getLookupCache()->captchasByHandle)) {
            return $this->_getLookupCache()->captchasByHandle[$handle];
        }

        return $this->_getLookupCache()->captchasByHandle[$handle] = ArrayHelper::firstWhere($this->getAllCaptchas(), 'handle', $handle, false);
    }

    public function getGqlCaptchaArgumentsForForm(Form $form): array
    {
        $cacheKey = $this->_getGqlCaptchaArgumentsCacheKey($form);

        if (array_key_exists($cacheKey, $this->_getLookupCache()->captchaArgumentsByForm)) {
            return $this->_getLookupCache()->captchaArgumentsByForm[$cacheKey];
        }

        $captchaArguments = [];

        foreach ($this->getAllEnabledCaptchasForForm($form) as $captcha) {
            $handle = $captcha->getGqlHandle();

            $captchaArguments[$handle] = [
                'name' => $handle,
                'type' => CaptchaInputType::getType(),
            ];
        }

        return $this->_getLookupCache()->captchaArgumentsByForm[$cacheKey] = $captchaArguments;
    }

    public function getAllEnabledCaptchasForForm(Form $form, FieldLayoutPage $page = null, bool $force = false): array
    {
        $captchas = [];

        // If we're editing a submission from the front-end, don't enable captchas
        if ($form->isEditingSubmission()) {
            return $captchas;
        }

        // Check if we've disabled captchas in the form settings
        if ($form->settings->disableCaptchas) {
            return $captchas;
        }

        $integrations = $this->getAllEnabledIntegrationsForForm($form);

        foreach ($integrations as $integration) {
            if ($integration instanceof Captcha) {
                // Check if this is a multipage form, because by default, we want to only show it
                // on the last page. But also check the form setting if this is enabled to show on each page.
                //
                // Lastly, check if we're forcing to return the captcha. Notably, when prepping the JS variables
                // for ajax forms. They might not show it immediately, but they need it prepped on-load.
                if ($form->hasMultiplePages() && !$force) {
                    // Only show the captcha on the last page - unless we specify otherwise in settings
                    if (!$integration->showAllPages && !$form->isLastPage($page)) {
                        continue;
                    }
                }

                $captchas[] = $integration;
            }
        }

        return $captchas;
    }

    public function getCaptchasHtmlForForm(Form $form, FieldLayoutPage $page = null): string
    {
        $html = '';

        $captchas = $this->getAllEnabledCaptchasForForm($form, $page);

        foreach ($captchas as $captcha) {
            $html .= $captcha->renderHtml($form, $page);
        }

        return $html;
    }

    public function saveCaptcha(Integration $integration): bool
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        // Allow integrations to perform actions before their settings are saved
        if (!$integration->beforeSave(false)) {
            return false;
        }

        $settings->captchas[$integration->getHandle()] = [
            'type' => get_class($integration),
            'enabled' => $integration->getEnabled(false),
            'saveSpam' => $integration->saveSpam,
            'settings' => $integration->getSettings(),
        ];

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            return false;
        }

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        $integration->afterSave(false);

        $this->_resetIntegrationCaches();

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _integrations(): MemoizableArray
    {
        if (!isset($this->_integrations)) {
            $integrations = [];

            foreach ($this->_createIntegrationQuery()->all() as $result) {
                $integrations[] = $this->createIntegration($result);
            }

            $this->_integrations = new MemoizableArray($integrations);
        }

        return $this->_integrations;
    }

    private function _getLookupCache(): IntegrationLookupCache
    {
        if ($this->_lookupCache === null) {
            $this->_lookupCache = new IntegrationLookupCache();
        }

        return $this->_lookupCache;
    }

    private function _resetIntegrationCaches(): void
    {
        $this->_integrations = null;
        $this->_lookupCache?->reset();
    }

    private function _getGqlCaptchaArgumentsCacheKey(Form $form): string
    {
        return $this->_getFormRequestCacheKey($form);
    }

    private function _getFormRequestCacheKey(Form $form): string
    {
        $formId = (int)($form->id ?? 0);
        $siteId = (int)($form->siteId ?? 0);

        if ($formId) {
            return 'id:' . $formId . ':site:' . $siteId;
        }

        return 'obj:' . spl_object_id($form);
    }

    private function _createIntegrationQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'type',
                'enabled',
                'sortOrder',
                'settings',
                'cache',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from([Table::FORMIE_INTEGRATIONS])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getIntegrationRecord(string $uid, bool $withTrashed = false): IntegrationRecord
    {
        $query = $withTrashed ? IntegrationRecord::findWithTrashed() : IntegrationRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new IntegrationRecord();
    }

}

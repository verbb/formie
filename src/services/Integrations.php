<?php
namespace verbb\formie\services;

use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\events\ModifyIntegrationsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\integrations\addressproviders;
use verbb\formie\integrations\captchas;
use verbb\formie\integrations\crm;
use verbb\formie\integrations\elements;
use verbb\formie\integrations\emailmarketing;
use verbb\formie\integrations\webhooks;
use verbb\formie\models\MissingIntegration;
use verbb\formie\records\Integration as IntegrationRecord;

use Craft;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\web\ServerErrorHttpException;

class Integrations extends Component
{
    // Constants
    // =========================================================================

    const EVENT_REGISTER_INTEGRATIONS = 'registerFormieIntegrations';
    const EVENT_MODIFY_INTEGRATIONS = 'modifyIntegrations';
    const EVENT_MODIFY_FORM_INTEGRATIONS = 'modifyFormIntegrations';
    const EVENT_BEFORE_SAVE_INTEGRATION = 'beforeSaveIntegration';
    const EVENT_AFTER_SAVE_INTEGRATION = 'afterSaveIntegration';
    const EVENT_BEFORE_DELETE_INTEGRATION = 'beforeDeleteIntegration';
    const EVENT_BEFORE_APPLY_INTEGRATION_DELETE = 'beforeApplyIntegrationDelete';
    const EVENT_AFTER_DELETE_INTEGRATION = 'afterDeleteIntegration';
    const CONFIG_INTEGRATIONS_KEY = 'formie.integrations';
    const CONFIG_CAPTCHAS_KEY = 'plugins.formie.settings.captchas';


    // Properties
    // =========================================================================

    private $_integrations;
    private $_integrationsByType;


    // Public Methods
    // =========================================================================

    /**
     * Returns all registered integrations.
     *
     * @return array
     */
    public function getAllIntegrationTypes(): array
    {
        $addressProviders = [
            addressproviders\Google::class,
            addressproviders\Algolia::class,
            addressproviders\AddressFinder::class,
        ];

        $captchas = [
            captchas\Recaptcha::class,
            captchas\Duplicate::class,
            captchas\Honeypot::class,
            captchas\Javascript::class,
        ];

        $elements = [
            elements\Entry::class,
        ];

        $emailMarketing = [
            emailmarketing\ActiveCampaign::class,
            emailmarketing\Autopilot::class,
            emailmarketing\AWeber::class,
            emailmarketing\Benchmark::class,
            emailmarketing\CampaignMonitor::class,
            emailmarketing\ConstantContact::class,
            emailmarketing\ConvertKit::class,
            emailmarketing\Drip::class,
            emailmarketing\GetResponse::class,
            emailmarketing\IContact::class,
            emailmarketing\Mailchimp::class,
            emailmarketing\MailerLite::class,
            emailmarketing\Moosend::class,
            emailmarketing\Omnisend::class,
            emailmarketing\Ontraport::class,
            emailmarketing\Sender::class,
            emailmarketing\Sendinblue::class,
        ];

        $crm = [
            crm\ActiveCampaign::class,
            crm\Avochato::class,
            // crm\Creatio::class,
            crm\Freshdesk::class,
            crm\HubSpot::class,
            crm\Infusionsoft::class,
            crm\Insightly::class,
            // crm\MethodCrm::class,
            crm\Monday::class,
            // crm\NetSuite::class,
            // crm\Pardot::class,
            crm\Pipedrive::class,
            crm\Pipeliner::class,
            crm\Salesflare::class,
            crm\Salesforce::class,
            crm\Scoro::class,
            // crm\SharpSpring::class,
            crm\VCita::class,
            // crm\Zengine::class,
            crm\Zoho::class,
        ];

        $webhooks = [
            webhooks\GoogleSheets::class,
            webhooks\Slack::class,
            webhooks\Trello::class,
            webhooks\Webhook::class,
            webhooks\Zapier::class,
        ];

        $event = new RegisterIntegrationsEvent([
            'addressProviders' => $addressProviders,
            'captchas' => $captchas,
            'elements' => $elements,
            'emailMarketing' => $emailMarketing,
            'crm' => $crm,
            'webhooks' => $webhooks,
        ]);

        $this->trigger(self::EVENT_REGISTER_INTEGRATIONS, $event);

        return [
            Integration::TYPE_ADDRESS_PROVIDER => $event->addressProviders,
            Integration::TYPE_CAPTCHA => $event->captchas,
            Integration::TYPE_ELEMENT => $event->elements,
            Integration::TYPE_EMAIL_MARKETING => $event->emailMarketing,
            Integration::TYPE_CRM => $event->crm,
            Integration::TYPE_WEBHOOK => $event->webhooks,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getIntegrationTypes($type)
    {
        return $this->getAllIntegrationTypes()[$type] ?? [];
    }

    /**
     * Returns all integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllIntegrations(): array
    {
        if ($this->_integrations !== null) {
            return $this->_integrations;
        }

        $this->_integrations = [];

        $results = $this->_createIntegrationQuery()
            ->all();

        foreach ($results as $result) {
            $this->_integrations[] = $this->createIntegration($result);
        }

        return $this->_integrations;
    }

    /**
     * @inheritDoc
     */
    public function getAllIntegrationsForType($type): array
    {
        if (!empty($this->_integrationsByType[$type])) {
            return $this->_integrationsByType[$type];
        }

        $this->_integrationsByType[$type] = [];

        $getIntegrationTypes = $this->getIntegrationTypes($type);

        foreach ($this->getAllIntegrations() as $key => $integration) {
            if (in_array(get_class($integration), $getIntegrationTypes)) {
                $this->_integrationsByType[$type][] = $integration;
            }
        }

        return $this->_integrationsByType[$type];
    }

    /**
     * Returns a integration by its ID.
     *
     * @param int $integrationId
     * @return IntegrationInterface|null
     */
    public function getIntegrationById(int $integrationId)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'id', $integrationId);
    }

    /**
     * Returns a integration by its UID.
     *
     * @param string $integrationUid
     * @return IntegrationInterface|null
     */
    public function getIntegrationByUid(string $integrationUid)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'uid', $integrationUid);
    }

    /**
     * Returns a integration by its handle.
     *
     * @param string $handle
     * @return IntegrationInterface|null
     */
    public function getIntegrationByHandle(string $handle)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'handle', $handle, true);
    }

    /**
     * Returns a integration by its tokenId.
     *
     * @param $tokenId
     * @return IntegrationInterface|null
     */
    public function getIntegrationByTokenId($tokenId)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'tokenId', $tokenId, true);
    }

    /**
     * Returns the field layout config for the given integration.
     *
     * @param VolumeInterface $volume
     * @return array
     */
    public function createIntegrationConfig(IntegrationInterface $integration): array
    {
        $config = [
            'name' => $integration->name,
            'handle' => $integration->handle,
            'type' => get_class($integration),
            'enabled' => $integration->enabled,
            'sortOrder' => (int)$integration->sortOrder,
            'settings' => ProjectConfigHelper::packAssociativeArrays($integration->getSettings()),
            'tokenId' => $integration->tokenId,
        ];

        return $config;
    }

    /**
     * Creates or updates a integration.
     *
     * @param IntegrationInterface $integration the integration to be saved.
     * @param bool $runValidation Whether the integration should be validated
     * @return bool Whether the integration was saved successfully
     * @throws \Throwable
     */
    public function saveIntegration(IntegrationInterface $integration, bool $runValidation = true): bool
    {
        $isNewIntegration = $integration->getIsNew();

        // Fire a 'beforeSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
                'isNew' => $isNewIntegration
            ]));
        }

        if (!$integration->beforeSave($isNewIntegration)) {
            return false;
        }

        if ($runValidation && !$integration->validate()) {
            Craft::info('Integration not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewIntegration) {
            $integration->uid = StringHelper::UUID();
            $integration->sortOrder = (new Query())
                    ->from(['{{%formie_integrations}}'])
                    ->max('[[sortOrder]]') + 1;
        } else if (!$integration->uid) {
            $integration->uid = Db::uidById('{{%formie_integrations}}', $integration->id);
        }

        $configPath = self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->uid;
        $configData = $this->createIntegrationConfig($integration);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$integration->handle}” integration");

        if ($isNewIntegration) {
            $integration->id = Db::idByUid('{{%formie_integrations}}', $integration->uid);
        }

        return true;
    }

    /**
     * Handle integration change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedIntegration(ConfigEvent $event)
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

            $integrationRecord->name = $data['name'];
            $integrationRecord->handle = $data['handle'];
            $integrationRecord->type = $data['type'];
            $integrationRecord->enabled = $data['enabled'];
            $integrationRecord->sortOrder = $data['sortOrder'];
            $integrationRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            $integrationRecord->tokenId = $data['tokenId'] ?? null;
            $integrationRecord->uid = $integrationUid;

            // Save the integration
            if ($wasTrashed = (bool)$integrationRecord->dateDeleted) {
                $integrationRecord->restore();
            } else {
                $integrationRecord->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_integrations = null;

        $integration = $this->getIntegrationById($integrationRecord->id);
        $integration->afterSave($isNewIntegration);

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $this->getIntegrationById($integrationRecord->id),
                'isNew' => $isNewIntegration
            ]));
        }
    }

    /**
     * Reorders integrations.
     *
     * @param array $integrationIds
     * @return bool
     * @throws \Throwable
     */
    public function reorderIntegrations(array $integrationIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%formie_integrations}}', $integrationIds);

        foreach ($integrationIds as $integrationOrder => $integrationId) {
            if (!empty($uidsByIds[$integrationId])) {
                $integrationUid = $uidsByIds[$integrationId];
                $projectConfig->set(self::CONFIG_INTEGRATIONS_KEY . '.' . $integrationUid . '.sortOrder', $integrationOrder + 1, "Reorder integrations");
            }
        }

        return true;
    }

    /**
     * Creates an integration with a given config.
     *
     * @param mixed $config The integration’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return IntegrationInterface The integration
     */
    public function createIntegration($config): IntegrationInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        try {
            $integration = ComponentHelper::createComponent($config, IntegrationInterface::class);
        } catch (UnknownPropertyException $e) {
            throw $e;
        } catch (MissingComponentException $e) {
            // Revert to the original config if it was overridden
            $config = $originalConfig ?? $config;

            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $integration = new MissingIntegration($config);
        }

        return $integration;
    }

    /**
     * Deletes an integration by its ID.
     *
     * @param int $integrationId
     * @return bool
     * @throws \Throwable
     */
    public function deleteIntegrationById(int $integrationId): bool
    {
        $integration = $this->getIntegrationById($integrationId);

        if (!$integration) {
            return false;
        }

        return $this->deleteIntegration($integration);
    }

    /**
     * Deletes an integration.
     *
     * @param IntegrationInterface $integration The integration to delete
     * @return bool
     * @throws \Throwable
     */
    public function deleteIntegration(IntegrationInterface $integration): bool
    {
        // Fire a 'beforeDeleteIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration
            ]));
        }

        if (!$integration->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->uid, "Delete the “{$integration->handle}” integration");
        
        return true;
    }

    /**
     * Handle integration getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedIntegration(ConfigEvent $event)
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
                ->softDelete('{{%formie_integrations}}', ['id' => $integrationRecord->id])
                ->execute();

            $integration->afterDelete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_integrations = null;

        // Fire an 'afterDeleteIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration
            ]));
        }
    }

    /**
     * @inheritDoc
     */
    public function getAllIntegrationsForForm(): array
    {
        $grouped = [];

        foreach ($this->getAllCaptchas() as $key => $captcha) {
            if ($captcha->enabled && $captcha->hasFormSettings()) {
                $grouped[$captcha->typeName()][] = $captcha;
            }
        }

        foreach ($this->getAllIntegrations() as $key => $integration) {
            if ($integration->enabled && $integration->hasFormSettings()) {
                $grouped[$integration->typeName()][] = $integration;
            }
        }

        return $grouped;
    }

    /**
     * Returns all enabled integrations for the provided form.
     *
     * @param Form $form
     * @return array
     */
    public function getAllEnabledIntegrationsForForm(Form $form): array
    {
        $enabledIntegrations = [];

        // Use all integrations + captchas
        $integrations = array_merge($this->getAllIntegrations(), $this->getAllCaptchas());

        // Find all the form-enabled integrations
        $formIntegrationSettings = $form->settings->integrations ?? [];
        $enabledFormSettings = ArrayHelper::where($formIntegrationSettings, 'enabled', true);

        foreach ($enabledFormSettings as $handle => $formSettings) {
            $integration = ArrayHelper::firstWhere($integrations, 'handle', $handle);

            // If this disabled globally? Then don't include it, otherwise populate the settings
            if ($integration && $integration->enabled) {
                $integration->setAttributes($formSettings, false);

                $enabledIntegrations[] = $integration;
            }
        }

        // Fire a 'modifyFormIntegrations' event
        $event = new ModifyFormIntegrationsEvent([
            'integrations' => $enabledIntegrations,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_INTEGRATIONS, $event);

        return $event->integrations;
    }

    /**
     * @inheritDoc
     */
    public function getAllCaptchas(): array
    {
        $captchas = [];

        $projectConfig = Craft::$app->getProjectConfig();

        foreach ($this->getIntegrationTypes(Integration::TYPE_CAPTCHA) as $captchaClass) {
            $class = new $captchaClass();

            // Load in any settings from PC
            $config = $projectConfig->get(self::CONFIG_CAPTCHAS_KEY . '.' . $class->getHandle());
            $config['type'] = $captchaClass;

            $captchas[] = $this->createIntegration($config);
        }

        return $captchas;
    }

    /**
     * @inheritDoc
     */
    public function getAllGroupedCaptchas(): array
    {
        $grouped = [];

        $captchas = $this->getAllCaptchas();

        foreach ($captchas as $captcha) {
            $grouped[$captcha->typeName()][] = $captcha;
        }

        return $grouped;
    }

    /**
     * Returns an captcha by its handle.
     *
     * @param string $handle
     * @return IntegrationInterface|null
     */
    public function getCaptchaByHandle($handle)
    {
        return ArrayHelper::firstWhere($this->getAllCaptchas(), 'handle', $handle, false);
    }

    /**
     * Returns all enabled captchas for the provided form.
     *
     * @param Form $form
     * @param FieldLayoutPage|null $page
     * @return string
     */
    public function getAllEnabledCaptchasForForm(Form $form, $page = null, $force = false): array
    {
        $captchas = [];
        $integrations = $this->getAllEnabledIntegrationsForForm($form, $page);

        foreach ($integrations as $integration) {
            if ($integration instanceof Captcha) {
                // Check if this is a multi-page form, because by default, we want to only show it
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

    /**
     * Returns CAPTCHA HTML for the provided form.
     *
     * @param Form $form
     * @param FieldLayoutPage|null $page
     * @return string
     */
    public function getCaptchasHtmlForForm(Form $form, $page = null): string
    {
        $html = '';

        $captchas = $this->getAllEnabledCaptchasForForm($form, $page);

        foreach ($captchas as $captcha) {
            $html .= $captcha->getFrontEndHtml($form, $page);
        }

        return $html;
    }

    /**
     * Saves a captcha.
     *
     * @param Integration $integration
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveCaptcha(Integration $integration): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

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

        $configData = [
            'type' => get_class($integration),
            'enabled' => $integration->enabled,
            'settings' => ProjectConfigHelper::packAssociativeArrays($integration->getSettings()),
        ];

        $configPath = self::CONFIG_CAPTCHAS_KEY . '.' . $integration->getHandle();
        $projectConfig->set($configPath, $configData);

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        $integration->afterSave(false);

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a DbCommand object prepped for retrieving integrations.
     *
     * @return Query
     */
    private function _createIntegrationQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'type',
                'enabled',
                'sortOrder',
                'settings',
                'cache',
                'tokenId',
                'dateCreated',
                'dateUpdated',
                'uid'
            ])
            ->from(['{{%formie_integrations}}'])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    /**
     * Gets a integration's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed integrations in search
     * @return IntegrationRecord
     */
    private function _getIntegrationRecord(string $uid, bool $withTrashed = false): IntegrationRecord
    {
        $query = $withTrashed ? IntegrationRecord::findWithTrashed() : IntegrationRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new IntegrationRecord();
    }

}

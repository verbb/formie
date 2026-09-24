<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAutomationPayloadEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use GuzzleHttp\Client;

use yii\helpers\Markdown;

abstract class Automation extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_AUTOMATION_PAYLOAD = 'modifyAutomationPayload';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Automations');
    }


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_AUTOMATION;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_AUTOMATIONS;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/automations/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/automations/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/automations/{$handle}/_plugin-settings", $variables);
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        return $schema;
    }

    protected function generatePayloadValues(Submission $submission): array
    {
        $payload = $this->generateSubmissionPayloadValues($submission);

        // Fire a 'modifyAutomationPayload' event
        $event = new ModifyAutomationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_AUTOMATION_PAYLOAD, $event);

        return $event->payload;
    }

    protected function getEndpointUrl(string $url, Submission $submission): bool|string|null
    {
        $url = Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate($url, $submission, autoescape: false);
        $url = trim((string)App::parseEnv($url));

        return $this->requirePublicHttpEndpoint($url);
    }

    /**
     * Automations call operator-configured URLs. Never follow redirects: the
     * first-hop public-IP check would otherwise be bypassed by a 302 to a private host.
     */
    protected function defineClient(): Client
    {
        return $this->createAutomationHttpClient();
    }

    protected function createAutomationHttpClient(array $config = [], ?string $endpointUrl = null): Client
    {
        $config['allow_redirects'] = false;

        if ($endpointUrl !== null) {
            return $this->createPublicEndpointClient($endpointUrl, $config);
        }

        if (App::devMode() && !array_key_exists('verify', $config)) {
            $config['verify'] = false;
        }

        return Craft::createGuzzleClient($config);
    }

    /**
     * Pin the connection to the public IPs we just validated so a DNS rebinding
     * race between resolve-time and connect-time cannot redirect to a private host.
     * Absolute automation URLs rebuild a pinned client per request while preserving
     * any injected handler (tests) and subclass client config (auth headers).
     */
    public function request(string $method, string $uri, array $options = []): mixed
    {
        if (preg_match('#^https?://#i', $uri) === 1) {
            $config = $this->getClient()->getConfig();
            $config['allow_redirects'] = false;
            $this->_client = $this->createPublicEndpointClient($uri, $config);
        }

        return parent::request($method, $uri, $options);
    }

}

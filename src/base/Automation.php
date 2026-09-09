<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAutomationPayloadEvent;
use verbb\formie\errors\IntegrationException;
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
        $url = Formie::$plugin->getTemplates()->renderObjectTemplate($url, $submission);
        $url = trim((string)App::parseEnv($url));

        if (!$this->isPublicHttpEndpoint($url)) {
            throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
        }

        return $url;
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
            $this->_applyDnsPin($config, $endpointUrl);
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
            $this->_applyDnsPin($config, $uri);
            $this->_client = new Client($config);
        }

        return parent::request($method, $uri, $options);
    }

    protected function isPublicHttpEndpoint(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = trim((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $ips = $this->_resolveEndpointIps($host);

        if (!$ips) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!$this->_isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Force curl to connect using a validated public IP for this host:port.
     * Re-validates at pin time so a late DNS change cannot introduce a private IP.
     */
    private function _applyDnsPin(array &$config, string $url): void
    {
        $parts = parse_url($url);

        if (!is_array($parts)) {
            throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = trim((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
        }

        // Literal IP hosts are already the connect target; still require public.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!$this->_isPublicIp($host)) {
                throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
            }

            return;
        }

        $ips = $this->_resolveEndpointIps($host);

        if (!$ips) {
            throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
        }

        foreach ($ips as $ip) {
            if (!$this->_isPublicIp($ip)) {
                throw new IntegrationException(Craft::t('formie', 'Outbound integration URL must use a public HTTP or HTTPS endpoint.'));
            }
        }

        $ip = $ips[0];
        $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
        $resolve = $config['curl'][CURLOPT_RESOLVE] ?? [];

        if (!is_array($resolve)) {
            $resolve = [];
        }

        // CURLOPT_RESOLVE entry format: host:port:address
        $resolve[] = sprintf('%s:%d:%s', $host, $port, $ip);
        $config['curl'][CURLOPT_RESOLVE] = array_values(array_unique($resolve));
    }

    private function _resolveEndpointIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        if (function_exists('dns_get_record')) {
            foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique(array_filter($ips, 'is_string')));
    }

    private function _isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

}

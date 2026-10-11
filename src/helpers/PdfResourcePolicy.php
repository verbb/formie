<?php
namespace verbb\formie\helpers;

use yii\helpers\IpHelper;

use Dompdf\Options;

class PdfResourcePolicy
{
    // Properties
    // =========================================================================

    private array $_trustedHosts;


    // Public Methods
    // =========================================================================

    public function __construct(array $trustedHosts = [])
    {
        $this->_trustedHosts = [];

        foreach ($trustedHosts as $host) {
            if (!is_string($host)) {
                continue;
            }
            $host = $this->_normalizeHost($host);

            if ($host !== '') {
                $this->_trustedHosts[$host] = true;
            }
        }
    }

    public function apply(Options $options): void
    {
        $allowedProtocols = $options->getAllowedProtocols();

        foreach (['http://', 'https://'] as $protocol) {
            if (!isset($allowedProtocols[$protocol]) || !is_array($allowedProtocols[$protocol])) {
                continue;
            }
            $rules = $allowedProtocols[$protocol]['rules'] ?? [];
            $rules = is_array($rules) ? $rules : [];
            $rules[] = [$this, 'validateRemoteUri'];
            $allowedProtocols[$protocol]['rules'] = $rules;
        }

        $options->setAllowedProtocols($allowedProtocols);

        // Redirect targets bypass Dompdf's protocol callbacks, so keep every
        // remote request on the destination that was validated above.
        $context = $options->getHttpContext();

        if (is_resource($context)) {
            stream_context_set_option($context, 'http', 'follow_location', false);
            stream_context_set_option($context, 'http', 'max_redirects', 0);
            stream_context_set_option($context, 'curl', 'max_redirects', 0);
        }
    }

    public function validateRemoteUri(string $uri): array
    {
        $parts = parse_url($uri);

        if (!is_array($parts)) {
            return [false, 'Remote PDF resources must use a valid public HTTP or HTTPS URL.'];
        }
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = $this->_normalizeHost((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return [false, 'Remote PDF resources must use a valid public HTTP or HTTPS URL.'];
        }

        if (isset($this->_trustedHosts[$host])) {
            return [true, null];
        }
        $ips = $this->resolveHostIps($host);

        if (!$ips) {
            return [false, 'Remote PDF resource host could not be resolved.'];
        }

        foreach ($ips as $ip) {
            if (!$this->isPublicIp($ip)) {
                return [false, 'Remote PDF resources must not resolve to a private or reserved network.'];
            }
        }

        return [true, null];
    }


    // Protected Methods
    // =========================================================================

    protected function resolveHostIps(string $host): array
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

    protected function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        foreach (['224.0.0.0/4', 'ff00::/8', '64:ff9b::/96', '2002::/16', '2001::/32'] as $range) {
            if (IpHelper::inRange($ip, $range)) {
                return false;
            }
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        return rtrim($host, '.');
    }
}

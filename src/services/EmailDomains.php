<?php
namespace verbb\formie\services;

use verbb\formie\events\ModifyEmailDomainsEvent;

use Craft;
use craft\base\Component;

class EmailDomains extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FREE_EMAIL_DOMAINS = 'modifyFreeEmailDomains';


    // Public Methods
    // =========================================================================

    public function extractDomainFromEmail(string $value): ?string
    {
        if (!str_contains($value, '@')) {
            return null;
        }

        $domain = explode('@', $value)[1] ?? '';

        return $this->normalizeDomain($domain);
    }

    public function normalizeDomain(string $domain): ?string
    {
        $domain = mb_strtolower(trim($domain));

        if (!$domain) {
            return null;
        }

        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46')) {
            $idnDomain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);

            if ($idnDomain !== false) {
                $domain = $idnDomain;
            }
        }

        return rtrim($domain, '.');
    }

    public function isFreeDomain(string $domain): bool
    {
        $normalizedDomain = $this->normalizeDomain($domain);

        if (!$normalizedDomain) {
            return false;
        }

        return isset($this->getFreeDomains()[$normalizedDomain]);
    }

    public function getFreeDomains(): array
    {
        $domainsPath = dirname(__DIR__) . '/data/free-email-domains.csv';
        $cacheKey = ['formie.freeEmailDomains', 'path' => $domainsPath];

        if ($domainsPath && is_file($domainsPath)) {
            $cacheKey['mtime'] = filemtime($domainsPath) ?: null;
            $cacheKey['size'] = filesize($domainsPath) ?: null;
        }

        $domains = Craft::$app->getCache()->getOrSet($cacheKey, function() use ($domainsPath) {
            $domains = [];

            if ($domainsPath && is_file($domainsPath)) {
                $contents = file_get_contents($domainsPath) ?: '';
                $lines = preg_split('/\R/', $contents) ?: [];

                foreach ($lines as $line) {
                    $line = trim($line);

                    if (!$line || str_starts_with($line, '#')) {
                        continue;
                    }

                    // Support either a single-column CSV or plain text list.
                    $parts = str_getcsv($line);
                    $domains[] = $parts[0] ?? '';
                }
            }
            
            $normalizedDomains = [];

            foreach ($domains as $domain) {
                $normalizedDomain = $this->normalizeDomain($domain);

                if ($normalizedDomain) {
                    $normalizedDomains[$normalizedDomain] = true;
                }
            }

            return array_keys($normalizedDomains);
        }, null);

        // Fire a 'modifyFreeEmailDomains' event after cache retrieval so listeners are always applied.
        $event = new ModifyEmailDomainsEvent([
            'domains' => $domains,
        ]);
        $this->trigger(self::EVENT_MODIFY_FREE_EMAIL_DOMAINS, $event);

        $normalizedDomains = [];

        foreach ($event->domains as $domain) {
            $normalizedDomain = $this->normalizeDomain($domain);

            if ($normalizedDomain) {
                $normalizedDomains[$normalizedDomain] = true;
            }
        }

        return $normalizedDomains;
    }
}

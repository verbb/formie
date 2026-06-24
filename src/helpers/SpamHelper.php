<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Email as EmailField;
use verbb\formie\models\Settings;

use Craft;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class SpamHelper
{
    // Static Methods
    // =========================================================================

    public static function checkSubmission(Submission $submission, ?string $spamKeywords = null): bool|array
    {
        /** @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $keywords = $spamKeywords ?? $settings->spamKeywords;

        return self::checkContent(
            self::buildSubmissionHaystack($submission),
            (string)($submission->ipAddress ?? self::_requestUserIp()),
            self::resolveKeywordLines($keywords, $submission),
        );
    }

    private static function _requestUserIp(): string
    {
        $request = Craft::$app->getRequest();

        if (!method_exists($request, 'getUserIP')) {
            return '';
        }

        return (string)($request->getUserIP() ?? '');
    }

    public static function checkGlobalEmailRules(Submission $submission): bool|array
    {
        /** @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!$settings->enableBlockedEmailDomains && !$settings->enableBlockFreeEmailDomains) {
            return false;
        }

        $emailDomains = Formie::$plugin->getEmailDomains();
        $allowlist = $settings->enableAllowedEmailDomains
            ? self::parseEmailAllowlist($settings->allowedEmailDomains)
            : ['domains' => [], 'emails' => []];

        foreach (self::collectSubmissionEmailAddresses($submission) as $email) {
            if ($settings->enableAllowedEmailDomains && self::emailMatchesAllowlist($email, $allowlist)) {
                continue;
            }

            $domain = $emailDomains->extractDomainFromEmail($email);

            if (!$domain) {
                continue;
            }

            if ($settings->enableBlockFreeEmailDomains && $emailDomains->isFreeDomain($domain)) {
                return [
                    'type' => 'freeEmailDomain',
                    'value' => $domain,
                ];
            }

            if ($settings->enableBlockedEmailDomains) {
                $blockedDomains = self::parseDomainList($settings->blockedEmailDomains);

                if (in_array($domain, $blockedDomains, true)) {
                    return [
                        'type' => 'blockedEmailDomain',
                        'value' => $domain,
                    ];
                }
            }
        }

        return false;
    }

    public static function resolveKeywordLines(?string $spamKeywords, ?Submission $submission = null): array
    {
        $lines = self::_getArrayFromMultiline($spamKeywords ?? '');
        $resolved = [];

        foreach ($lines as $line) {
            if ($submission && str_contains($line, '{')) {
                $parsed = References::parseContent($line, $submission);

                foreach (self::_getArrayFromMultiline($parsed) as $resolvedLine) {
                    $resolved[] = $resolvedLine;
                }

                continue;
            }

            $resolved[] = $line;
        }

        return array_values(array_filter($resolved));
    }

    public static function buildSubmissionHaystack(Submission $submission): string
    {
        return self::_buildHaystack($submission->getValuesAsString());
    }

    public static function parseDomainList(?string $domains): array
    {
        $emailDomains = Formie::$plugin->getEmailDomains();
        $normalized = [];

        foreach (self::_getArrayFromMultiline($domains ?? '') as $domain) {
            $domain = $emailDomains->normalizeDomain($domain);

            if ($domain) {
                $normalized[] = $domain;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function parseEmailAllowlist(?string $allowlist): array
    {
        $emailDomains = Formie::$plugin->getEmailDomains();
        $domains = [];
        $emails = [];

        foreach (self::_getArrayFromMultiline($allowlist ?? '') as $line) {
            if (str_contains($line, '@')) {
                $email = mb_strtolower(trim($line));

                if ($email !== '') {
                    $emails[] = $email;
                }

                continue;
            }

            $domain = $emailDomains->normalizeDomain($line);

            if ($domain) {
                $domains[] = $domain;
            }
        }

        return [
            'domains' => array_values(array_unique($domains)),
            'emails' => array_values(array_unique($emails)),
        ];
    }

    public static function emailMatchesAllowlist(string $email, array $allowlist): bool
    {
        $emailDomains = Formie::$plugin->getEmailDomains();
        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return false;
        }

        if (in_array($normalizedEmail, $allowlist['emails'] ?? [], true)) {
            return true;
        }

        $domain = $emailDomains->extractDomainFromEmail($normalizedEmail);

        if (!$domain) {
            return false;
        }

        return in_array($domain, $allowlist['domains'] ?? [], true);
    }

    public static function collectSubmissionEmailAddresses(Submission $submission): array
    {
        $form = $submission->getForm();
        $emails = [];

        if (!$form) {
            return $emails;
        }

        foreach ($form->getFields() as $field) {
            if (!$field instanceof EmailField) {
                continue;
            }

            $value = trim($submission->getFieldValueAsString($field->handle));

            if ($value !== '') {
                $emails[] = $value;
            }
        }

        return $emails;
    }

    public static function spamReasonFromMatch(array $match): string
    {
        if (($match['type'] ?? '') === 'ip') {
            return Craft::t('formie', 'Contains banned IP: “{c}”', ['c' => $match['value']]);
        }

        return Craft::t('formie', 'Contains banned keyword: “{c}”', ['c' => $match['value']]);
    }

    public static function spamReasonFromEmailMatch(array $match): string
    {
        if (($match['type'] ?? '') === 'freeEmailDomain') {
            return Craft::t('formie', 'Blocked free email domain: {domain}.', ['domain' => $match['value']]);
        }

        return Craft::t('formie', 'Blocked email domain: {domain}.', ['domain' => $match['value']]);
    }

    public static function checkMaximumLinks(Submission $submission, ?Settings $settings = null): bool|array
    {
        /** @var Settings $settings */
        $settings ??= Formie::$plugin->getSettings();

        if (!$settings->enableMaximumLinks) {
            return false;
        }

        $maximumLinks = max(1, (int)$settings->maximumLinks);
        $linkCount = self::countLinks(self::buildSubmissionHaystack($submission));

        if ($linkCount > $maximumLinks) {
            return [
                'type' => 'maximumLinks',
                'value' => $linkCount,
                'limit' => $maximumLinks,
            ];
        }

        return false;
    }

    public static function checkSuspiciousText(Submission $submission, ?Settings $settings = null): bool|array
    {
        /** @var Settings $settings */
        $settings ??= Formie::$plugin->getSettings();

        if (!$settings->enableSuspiciousTextDetection) {
            return false;
        }

        $allowedTerms = self::parseAllowedTerms($settings->suspiciousTextAllowedTerms);
        $form = $submission->getForm();

        if (!$form) {
            return false;
        }

        foreach ($form->getFields() as $field) {
            $value = $submission->getFieldValueAsString($field->handle);

            if ($value === '') {
                continue;
            }

            $analysis = SuspiciousTextHelper::analyze($value, $allowedTerms);

            if ($analysis['is_suspicious'] ?? false) {
                return [
                    'type' => 'suspiciousText',
                    'value' => $field->handle,
                ];
            }
        }

        return false;
    }

    public static function countLinks(string $content): int
    {
        if ($content === '') {
            return 0;
        }

        preg_match_all(
            '~(?:https?://|www\.)[^\s<>"{}|\\^`\[\]]+~iu',
            $content,
            $matches,
        );

        return count($matches[0] ?? []);
    }

    public static function parseAllowedTerms(?string $terms): array
    {
        return self::_getArrayFromMultiline($terms ?? '');
    }

    public static function spamReasonFromMaximumLinks(array $match): string
    {
        return Craft::t('formie', 'Submission contains {count} links, which exceeds the limit of {limit}.', [
            'count' => (int)($match['value'] ?? 0),
            'limit' => (int)($match['limit'] ?? 0),
        ]);
    }

    public static function spamReasonFromSuspiciousText(array $match): string
    {
        return Craft::t('formie', 'Submission contains suspicious text in field “{field}”.', [
            'field' => (string)($match['value'] ?? ''),
        ]);
    }

    public static function checkContent(string $content, ?string $userIp = null, ?array $lines = null): bool|array
    {
        if ($userIp === null) {
            $request = Craft::$app->getRequest();
            $userIp = method_exists($request, 'getUserIP')
                ? (string)($request->getUserIP() ?? '')
                : '';
        }

        $evaluator = self::_getEvaluator();

        if ($lines === null) {
            /** @var Settings $settings */
            $settings = Formie::$plugin->getSettings();
            $lines = self::resolveKeywordLines($settings->spamKeywords);
        }

        foreach ($lines as $line) {
            $expression = self::_parseLineToExpression($line);

            $result = $evaluator->evaluate($expression, [
                'content' => $content,
                'userIp' => $userIp,
            ]);

            if ($result) {
                return [
                    'type' => self::_getRuleType($line),
                    'value' => $line,
                ];
            }
        }

        return false;
    }

    private static function _getEvaluator(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();

        $expressionLanguage->register('formieContains', function($haystack, $needle) {
        }, function ($args, $haystack, $needle) {
            // Use regex to match whole words, not `str_contains`, and ensure case-sensitive
            return preg_match('/\b' . preg_quote($needle, '/') . '\b/', $haystack) === 1;
        });

        $expressionLanguage->register('ipMatches', function($userIp, $ruleIp) {
        }, function(array $variables, string $userIp, string $rules) {
            // Split the rule string on commas for multiple definitions
            // e.g. "192.168.0.1, 192.168.0.2, 192.168.0.0/24"
            $targets = array_map('trim', explode(',', $rules));

            foreach ($targets as $target) {
                // Identify the type (single, range, or CIDR)
                if (strpos($target, '-') !== false) {
                    // Range
                    if (self::_ipInRange($userIp, $target)) {
                        return true;
                    }
                } else if (strpos($target, '/') !== false) {
                    // CIDR
                    if (self::_ipInCidr($userIp, $target)) {
                        return true;
                    }
                } else {
                    // Single IP
                    if (self::_ipIsEqual($userIp, $target)) {
                        return true;
                    }
                }
            }

            // If none matched, return false
            return false;
        });

        return $expressionLanguage;
    }

    private static function _parseLineToExpression(string $line): string
    {
        // Detect if it's a [match: ...] line
        if (preg_match('/^\[match:\s*(.*?)\]$/i', $line, $m)) {
            // $m[1] is "spam AND bulk" or "spam OR junk", etc.
            return self::_convertMatchSyntax($m[1]);
        }

        // Detect if it's [ip: ...]
        if (preg_match('/^\[ip:\s*(.*?)\]$/i', $line, $m)) {
            $ip = trim($m[1]);
            // Return expression "ipMatches(userIp, 'someIp')"
            return sprintf("ipMatches(userIp, '%s')", addslashes($ip));
        }

        // If none of the above, treat as plain text
        return sprintf("formieContains(content, '%s')", addslashes($line));
    }

    private static function _convertMatchSyntax(string $expr): string
    {
        // Split on ( ) or AND/OR
        $pattern = '/\s*(\(|\)|\b(?:AND|OR)\b)\s*/i';
        $tokens = preg_split($pattern, $expr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $parts = [];
        foreach ($tokens as $tok) {
            $tokTrim = trim($tok);

            // If it's an operator or parentheses, we pass it as-is, in lower case for ExpressionLanguage
            if (preg_match('/^(AND|OR|\(|\))$/i', $tokTrim)) {
                $parts[] = strtolower($tokTrim);
                continue;
            }

            // Otherwise, it's a keyword, wrap in "formieContains(content, 'keyword')"
            $escaped = addslashes($tokTrim);
            $parts[] = "formieContains(content, '{$escaped}')";
        }

        // Rejoin with spaces. ExpressionLanguage can parse:
        // "( formieContains(...) or formieContains(...) ) and formieContains(...)"
        return implode(' ', $parts);
    }

    private static function _getRuleType(string $line): string
    {
        if (stripos($line, '[ip:') === 0) {
            return 'ip';
        }

        if (stripos($line, '[match:') === 0) {
            return 'text';
        }

        return 'text';
    }

    private static function _buildHaystack(array $values): string
    {
        $parts = [];

        foreach ($values as $value) {
            if (is_scalar($value)) {
                $parts[] = (string)$value;
                continue;
            }

            if (is_array($value)) {
                $parts[] = self::_buildHaystack($value);
            }
        }

        $haystack = trim(implode(' ', array_filter($parts)));

        if (strlen($haystack) > 65536) {
            return substr($haystack, 0, 65536);
        }

        return $haystack;
    }

    private static function _getArrayFromMultiline(?string $string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return array_values(array_filter($array));
    }

    private static function _ipIsEqual(string $userIp, string $targetIp): bool
    {
        return ($userIp === $targetIp);
    }

    private static function _ipInRange(string $userIp, string $range): bool
    {
        $userIpLong = ip2long($userIp);

        if ($userIpLong === false) {
            // If the user IP is invalid or not parsable, fail
            return false;
        }

        [$start, $end] = array_map('trim', explode('-', $range, 2));
        $startLong = ip2long($start);
        $endLong = ip2long($end);

        if ($startLong === false || $endLong === false) {
            return false;
        }

        // Ensure start <= end
        if ($startLong > $endLong) {
            [$startLong, $endLong] = [$endLong, $startLong];
        }

        return ($userIpLong >= $startLong && $userIpLong <= $endLong);
    }

    private static function _ipInCidr(string $userIp, string $cidr): bool
    {
        $userIpLong = ip2long($userIp);

        if ($userIpLong === false) {
            // If the user IP is invalid or not parsable, fail
            return false;
        }

        [$network, $maskBits] = explode('/', $cidr, 2);
        $mask = ~((1 << (32 - (int)$maskBits)) - 1);

        $networkLong = ip2long($network);

        if ($networkLong === false) {
            return false;
        }

        $net = $networkLong & $mask;
        $user = $userIpLong & $mask;

        return ($user === $net);
    }
}

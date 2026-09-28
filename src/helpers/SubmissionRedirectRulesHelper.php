<?php
namespace verbb\formie\helpers;

use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FormSettings;

class SubmissionRedirectRulesHelper
{
    // Public Methods
    // =========================================================================

    public static function getMatchedRule(Form $form, Submission $submission): ?array
    {
        $settings = $form->getSettings();

        if (!$settings instanceof FormSettings || !$settings->enableRedirectRules) {
            return null;
        }

        $rules = $settings->redirectRules ?? [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $conditionSettings = $rule['conditions'] ?? [];

            if (!$conditionSettings || !ConditionsHelper::getConditionalTestResult($conditionSettings, $submission)) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    public static function resolveMatchedRuleUrl(Form $form, Submission $submission, bool $includeQueryString = true): ?string
    {
        $matchedRule = self::getMatchedRule($form, $submission);

        if (!$matchedRule) {
            return null;
        }

        $url = self::resolveRuleUrl($matchedRule, $form, $submission);

        if ($url === '') {
            return null;
        }

        return CompletionRedirectPolicy::validate($includeQueryString ? UrlHelper::appendQueryParams($url, $form->getInstanceConfig()->query) : $url);
    }

    public static function resolveRuleUrl(array $rule, Form $form, Submission $submission): string
    {
        $redirectType = (string)($rule['redirectType'] ?? 'url');

        if ($redirectType === 'entry') {
            $entry = self::_getRuleEntry($rule);

            return $entry?->url ?? '';
        }

        $url = (string)($rule['redirectUrl'] ?? '');

        if ($url !== '') {
            $url = References::resolveUrl($url, $submission);
        }

        return is_string($url) ? $url : '';
    }


    // Private Methods
    // =========================================================================

    private static function _getRuleEntry(array $rule): ?Entry
    {
        $entryRef = $rule['redirectEntry'] ?? null;

        if (is_array($entryRef)) {
            if (isset($entryRef['id'])) {
                $entryId = (int)$entryRef['id'];
                $siteId = (int)($entryRef['siteId'] ?? 0) ?: '*';
            } else {
                $first = $entryRef[0] ?? null;
                $entryId = is_array($first) ? (int)($first['id'] ?? 0) : 0;
                $siteId = is_array($first) ? ((int)($first['siteId'] ?? 0) ?: '*') : '*';
            }
        } else {
            return null;
        }

        if (!$entryId) {
            return null;
        }

        return \Craft::$app->getEntries()->getEntryById($entryId, $siteId);
    }
}

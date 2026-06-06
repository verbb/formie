<?php
namespace verbb\formie\helpers;

use craft\elements\Entry;
use craft\helpers\UrlHelper;
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

    public static function getEffectiveSubmitAction(Form $form, ?Submission $submission = null): string
    {
        $settings = $form->getSettings();

        if (!$settings instanceof FormSettings) {
            return 'message';
        }

        $submission ??= $form->getCurrentSubmission();

        if ($submission instanceof Submission) {
            $matchedRule = self::getMatchedRule($form, $submission);
            $redirectType = (string)($matchedRule['redirectType'] ?? '');

            if ($matchedRule && in_array($redirectType, ['url', 'entry'], true)) {
                return $redirectType;
            }
        }

        return (string)$settings->submitAction;
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

        return self::_finalizeRedirectUrl($url, $includeQueryString);
    }

    public static function resolveRuleUrl(array $rule, Form $form, Submission $submission): string
    {
        $redirectType = (string)($rule['redirectType'] ?? 'url');

        if ($redirectType === 'entry') {
            $entry = self::_getRuleEntry($rule);

            return $entry?->url ?? '';
        }

        $url = (string)($rule['submitActionUrl'] ?? '');

        if ($url !== '') {
            $url = References::parseContent($url, $submission);
        }

        return is_string($url) ? $url : '';
    }


    // Private Methods
    // =========================================================================

    private static function _finalizeRedirectUrl(string $url, bool $includeQueryString): string
    {
        $request = \Craft::$app->getRequest();

        if ($url && $request->getIsSiteRequest() && $includeQueryString) {
            $requestParams = $request->getQueryStringWithoutPath();
            $urlParams = explode('?', $url)[1] ?? '';
            $url = UrlHelper::url($url, $requestParams . '&' . $urlParams);
        }

        $url = mb_convert_encoding($url, 'UTF-8', 'ISO-8859-1');

        return \craft\helpers\StringHelper::sanitizeRedirectUrl($url);
    }

    private static function _getRuleEntry(array $rule): ?Entry
    {
        $entryRef = $rule['submitActionEntry'] ?? null;

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

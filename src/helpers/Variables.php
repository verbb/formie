<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Table;
use verbb\formie\events\RegisterTransformersEvent;
use verbb\formie\compatibility\variables\VariableSourceCompatibility;
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\variables\VariableSourceInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\Notification;
use verbb\formie\models\ReferenceExpression;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Json;
use craft\models\Site;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\IdentityInterface;

use DateTime;
use DateTimeZone;
use Throwable;

class Variables
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_VARIABLES = 'registerVariables';
    public const EVENT_REGISTER_TRANSFORMERS = 'registerTransformers';
    public const TARGET_CUSTOM = 'custom';
    public const CONTENT_ANY = 'any';
    public const CONTENT_SINGLE_LINE = 'singleLine';

    public const TYPE_TEXT = 'text';
    public const TYPE_EMAIL = 'email';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CALCULATIONS = 'calculations';
    public const TYPE_URL = 'url';
    public const TYPE_DATE = 'date';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';

    public const GROUP_FIELDS = 'fieldsVariables';
    public const GROUP_FORM = 'formVariables';
    public const GROUP_SUBMISSION = 'submissionVariables';
    public const GROUP_SYSTEM = 'systemVariables';
    public const GROUP_CURRENT_TIME = 'currentTimeVariables';
    public const GROUP_ENVIRONMENT = 'environmentVariables';
    public const GROUP_CURRENT_SITE = 'siteVariables';
    public const GROUP_CURRENT_USER = 'userVariables';
    public const GROUP_DISPATCH = 'dispatchVariables';
    public const GROUP_CUSTOM = 'customVariables';

    public const STATIC_FIELDS = self::GROUP_FIELDS;
    public const STATIC_FORM = 'staticFormVariables';
    public const STATIC_GENERAL = 'staticGeneralVariables';
    public const STATIC_SITE = 'staticSiteVariables';
    public const STATIC_DISPATCH = 'staticDispatchVariables';
    public const STATIC_CUSTOM = 'staticCustomVariables';

    public const ENVIRONMENT_VARIABLE_PREFIX = 'FORMIE_';

    private const RESERVED_VARIABLE_TARGETS = [
        'field',
        'form',
        'submission',
        'site',
        'user',
        'system',
        'env',
        'dispatch',
        'timestamp',
        'allFields',
        'allContentFields',
        'allVisibleFields',
        self::TARGET_CUSTOM,
    ];

    private static ?array $_registeredVariableSources = null;
    private static array $_customVariableResolutionCache = [];
    

    // Static Methods
    // =========================================================================

    /**
     * Returns the full variable config for the form builder: category config, labels, and order.
     * Consumed by the client to resolve variable picker options (static + form fields).
     * Includes labels/order for builder categories and for sub-groups (fieldsVariables, formVariables, etc.).
     * Form builder only shows token-based groups: Fields, Form, General, Users.
     * We do not expose "Email", "Number", "Plain Text", or "Calculations" as display categories;
     * those keys are only used to look up config (which static variables + field types to include).
     */
    public static function getFormBuilderVariableConfig(): array
    {
        $config = self::getCategoryConfig();

        $labels = [
            self::GROUP_FIELDS => Craft::t('formie', 'Fields'),
            self::GROUP_FORM => Craft::t('formie', 'Form'),
            self::GROUP_SUBMISSION => Craft::t('formie', 'Submission'),
            self::GROUP_SYSTEM => Craft::t('formie', 'System'),
            self::GROUP_CURRENT_TIME => Craft::t('formie', 'Current Time'),
            self::GROUP_ENVIRONMENT => Craft::t('formie', 'Environment'),
            self::GROUP_CURRENT_SITE => Craft::t('formie', 'Site'),
            self::GROUP_CURRENT_USER => Craft::t('formie', 'Users'),
            self::GROUP_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::GROUP_CUSTOM => Craft::t('formie', 'Custom'),
            self::STATIC_FORM => Craft::t('formie', 'Form'),
            self::STATIC_GENERAL => Craft::t('formie', 'General'),
            self::STATIC_SITE => Craft::t('formie', 'Site'),
            self::STATIC_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::STATIC_CUSTOM => Craft::t('formie', 'Custom'),
        ];

        $order = [
            self::GROUP_FIELDS,
            self::STATIC_FORM,
            self::STATIC_DISPATCH,
            self::STATIC_GENERAL,
            self::STATIC_CUSTOM,
            self::STATIC_SITE,
            self::GROUP_FORM,
            self::GROUP_SUBMISSION,
            self::GROUP_SYSTEM,
            self::GROUP_CURRENT_TIME,
            self::GROUP_ENVIRONMENT,
            self::GROUP_CURRENT_SITE,
            self::GROUP_CURRENT_USER,
        ];

        return [
            'variableCategoriesConfig' => $config,
            'variableCategoryLabels' => $labels,
            'variableCategoryOrder' => $order,
        ];
    }

    /**
     * @return VariableSourceInterface[]
     */
    public static function getRegisteredVariableSources(): array
    {
        if (self::$_registeredVariableSources !== null) {
            return self::$_registeredVariableSources;
        }

        $event = new RegisterVariablesEvent([
            'sources' => [],
        ]);
        Event::trigger(self::class, self::EVENT_REGISTER_VARIABLES, $event);

        self::$_registeredVariableSources = self::_sanitizeRegisteredVariableSources($event->sources);

        return self::$_registeredVariableSources;
    }

    public static function clearRegisteredVariableSourcesCache(): void
    {
        self::$_registeredVariableSources = null;
        self::$_customVariableResolutionCache = [];
    }

    public static function findRegisteredVariableSource(string $handle): ?VariableSourceInterface
    {
        $handle = strtolower(trim($handle));

        if ($handle === '') {
            return null;
        }

        foreach (self::getRegisteredVariableSources() as $source) {
            if ($source->getHandle() === $handle) {
                return $source;
            }
        }

        return null;
    }

    public static function isReservedVariableTarget(string $target): bool
    {
        $target = strtolower(trim($target));

        return $target !== '' && in_array($target, self::RESERVED_VARIABLE_TARGETS, true);
    }

    public static function resolveRegisteredVariableSourceByHandle(Submission $submission, string $handle): mixed
    {
        $source = self::findRegisteredVariableSource($handle);

        if (!$source) {
            return null;
        }

        $cacheKey = ($submission->uid ?? 'new') . ':' . $source->getHandle();

        if (!array_key_exists($cacheKey, self::$_customVariableResolutionCache)) {
            self::$_customVariableResolutionCache[$cacheKey] = $source->resolveValue($submission);
        }

        return self::$_customVariableResolutionCache[$cacheKey];
    }

    /**
     * Returns variable picker configuration used by variableConfig:
     * - staticGroups: grouped static variable catalogs
     * - groupAliases: macro groups (STATIC_*) expanded client-side
     * - transformerRegistry: available v1 transforms by value type
     */
    public static function getCategoryConfig(): array
    {
        return [
            'groupAliases' => [
                self::STATIC_FORM => [self::GROUP_FORM, self::GROUP_SUBMISSION, self::GROUP_DISPATCH],
                self::STATIC_DISPATCH => [self::GROUP_DISPATCH],
                self::STATIC_GENERAL => [self::GROUP_SYSTEM, self::GROUP_ENVIRONMENT, self::GROUP_CURRENT_TIME, self::GROUP_CUSTOM],
                self::STATIC_SITE => [self::GROUP_CURRENT_SITE, self::GROUP_CURRENT_USER],
                self::STATIC_CUSTOM => [self::GROUP_CUSTOM],
            ],
            'staticGroups' => array_merge(
                self::_getStaticVariableGroups(),
                self::_getCustomVariableGroups(),
            ),
            'transformerRegistry' => self::_getTransformerRegistry(),
        ];
    }

    /**
     * Returns the merged list of variable definitions (with headings) for migrations/legacy use.
     */
    public static function getVariables(): array
    {
        return array_merge(
            self::_getFormVariableDefinitions(),
            self::_getSubmissionVariableDefinitions(),
            self::_getSystemVariableDefinitions(),
            self::_getCurrentTimeVariableDefinitions(),
            self::_getSiteVariableDefinitions(),
            self::_getEnvironmentVariableDefinitions(),
            self::_getUserVariableDefinitions()
        );
    }

    /**
     * Returns the merged variables array (globals + field values) for a submission.
     * Uses the same cache as getParsedValue, so repeated calls for the same submission are cheap.
     * Use this when resolving many reference tokens (e.g. integration field mappings) to avoid
     * rebuilding context and re-parsing all fields on every References::parseValue() call.
     *
     * @param bool $includeSummary Whether to include allFields / allContentFields / allVisibleFields.
     *        Summary variables are expensive and intentionally opt-in.
     * @param bool $parseEnvValues Whether string variable values should resolve env aliases before interpolation.
     */
    public static function getVariablesForSubmission(Submission $submission, ?Notification $notification = null, bool $includeSummary = false, bool $parseEnvValues = true): array
    {
        $form = $submission->form;
        $notification = $notification ?? new Notification();
        $cacheKey = self::_getSubmissionRenderCacheKey($submission);
        $renderCache = Formie::$plugin->getRenderCache();

        if (!$renderCache->getGlobalVariables($cacheKey)) {
            $currentUser = self::_getCurrentUser($submission);
            $userId = $currentUser->id ?? '';
            $userEmail = $currentUser->email ?? '';
            $username = $currentUser->username ?? '';
            $userFullName = $currentUser->fullName ?? '';
            $userFirstName = $currentUser->firstName ?? '';
            $userLastName = $currentUser->lastName ?? '';
            $userIp = $submission->ipAddress ?? '';

            $site = self::_getSite($submission);
            $siteId = $site->id ?? '';
            $siteName = $site->name ?? '';
            $siteHandle = $site->handle ?? '';
            $siteLanguage = $site->language ?? '';

            if ($site) {
                Craft::$app->getSites()->setCurrentSite($site);
            }

            $craftMailSettings = App::mailSettings();
            $systemEmail = $craftMailSettings->fromEmail;
            $systemReplyTo = $craftMailSettings->replyToEmail;
            $systemName = $craftMailSettings->fromName;

            $timeZone = Craft::$app->getTimeZone();
            $now = new DateTime('now', new DateTimeZone($timeZone));
            $dateCreated = $submission->dateCreated ?? null;
            $formName = $form?->title ?? '';
            $formHandle = $form?->handle ?? '';
            $submissionTitle = $submission?->title ?? '';
            $submissionStatus = $submission ? ($submission->getStatus() ?? '') : '';

            $variables = [
                'formName' => $formName,
                'formHandle' => $formHandle,
                'submissionTitle' => $submissionTitle,
                'submissionUrl' => $submission?->getCpEditUrl() ?? '',
                'submissionId' => $submission->id ?? null,
                'submissionUid' => $submission->uid ?? null,
                'submissionDate' => $dateCreated?->format('Y-m-d H:i:s'),
                'submissionStatus' => $submissionStatus,
                'submissionSite' => $submission?->siteId ?? null,
                'systemEmail' => $systemEmail,
                'systemReplyTo' => $systemReplyTo,
                'systemName' => $systemName,
                'craft' => new CraftVariable(),
                'currentSite' => $site,
                'currentUser' => $currentUser,
                'siteName' => $siteName,
                'siteUrl' => $site->getBaseUrl(),
                'siteId' => $siteId,
                'siteHandle' => $siteHandle,
                'siteLanguage' => $siteLanguage,
                'timestamp' => $now->format('Y-m-d H:i:s'),
                'userIp' => $userIp,
                'userId' => $userId,
                'userEmail' => $userEmail,
                'username' => $username,
                'userFullName' => $userFullName,
                'userFirstName' => $userFirstName,
                'userLastName' => $userLastName,
            ];

            foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
                $variables[$globalSet->handle] = $globalSet;
            }

            foreach (self::_getPrefixedEnvironmentVariableKeys(self::ENVIRONMENT_VARIABLE_PREFIX) as $envKey) {
                $variables['env' . $envKey] = App::env($envKey);
            }

            $renderCache->setGlobalVariables($cacheKey, $variables);
        }

        if ($parseEnvValues) {
            $variables = $renderCache->getResolvedVariables($cacheKey);

            if ($variables === null) {
                $variables = $renderCache->getVariables($cacheKey);

                foreach ($variables as $key => $variable) {
                    if (is_string($variable)) {
                        $variables[$key] = App::parseEnv($variable);
                    }
                }

                $renderCache->setResolvedVariables($cacheKey, $variables);
            }
        } else {
            $variables = $renderCache->getVariables($cacheKey);
        }

        if ($includeSummary) {
            $summaryCacheKey = self::_getSummaryRenderCacheKey($submission, $notification);
            $summaryVariables = $renderCache->getSummaryVariables($summaryCacheKey);

            if ($summaryVariables === null) {
                $summaryVariables = self::_getSummaryVariables($submission, $notification);
                $renderCache->setSummaryVariables($summaryCacheKey, $summaryVariables);
            }

            $variables = array_merge($variables, $summaryVariables);
        }

        return self::_appendDispatchVariables($variables, $submission);
    }

    /**
     * Maps a reference expression to the variable key used in the resolution array.
     * Used by reference resolution and token expansion.
     */
    public static function getReferenceVariableKey(ReferenceExpression $expr): string
    {
        return self::_referenceToVariableKey($expr);
    }

    /**
     * Returns the field for a field reference (e.g. {field:abc} or {field:abc:firstName}), or null
     * if the reference is not a field reference or the field is not found.
     *
     * When you need both the field and the value, use getFieldAndValueForReference() once instead
     * of calling getFieldForReference() and References::parseValue() separately, to avoid parsing
     * the reference twice.
     */
    public static function getFieldForReference(string $refValue, Submission $submission): ?FieldInterface
    {
        $expr = References::parseReferenceExpression($refValue);

        if (!$expr->isValid || $expr->target !== 'field' || $expr->identifier === '') {
            return null;
        }

        return self::_getSubmissionFieldByReference($submission, $expr->identifier);
    }

    /**
     * Returns both the field (when the reference is a field reference) and the resolved value
     * with a single parse and single variables lookup. Use this when you need both to avoid
     * calling getFieldForReference() and References::parseValue() separately.
     */
    public static function getFieldAndValueForReference(string $refValue, Submission $submission, ?array $variables = null): array
    {
        $expr = References::parseReferenceExpression($refValue);
        $field = null;

        if ($expr->isValid && $expr->target === 'field' && $expr->identifier !== '') {
            $field = self::_getSubmissionFieldByReference($submission, $expr->identifier);
        }

        if (!$expr->isValid) {
            $value = $expr->default !== '' ? $expr->default : null;
            return ['field' => $field, 'value' => $value];
        }

        // When the field is found, use the submission as source of truth; otherwise resolve from variables.
        if ($field !== null) {
            $value = self::_resolveReferenceFieldValue($submission, $field, $expr->selector, $expr->transformerParams);
        } else {
            if ($variables === null) {
                $variables = self::getVariablesForSubmission($submission);
            }
            $key = self::getReferenceVariableKey($expr);
            $value = ArrayHelper::getValue($variables, $key);

            if ($value === null && $expr->target === self::TARGET_CUSTOM && $expr->identifier !== '') {
                $value = self::resolveRegisteredVariableSourceByHandle($submission, $expr->identifier);
            }

            if ($value === null) {
                $value = VariableSourceCompatibility::resolveLegacyToken($submission, $expr);
            }
        }

        if ($expr->transformerId !== '' && self::_referenceAllowsTransforms($expr)) {
            $value = self::applyVariableTransformer($value, $expr->transformerId, $expr->transformerParams);
        }

        if (($value === null || $value === '') && $expr->default !== '') {
            $value = $expr->default;
        }

        return ['field' => $field, 'value' => $value];
    }

    public static function applyVariableTransformer(mixed $value, string $transformerId, array $params = []): mixed
    {
        // Applies a registered v1 transformer to a resolved variable value.
        // Public so that reference parsing and other callers can transform values consistently.
           
        switch ($transformerId) {
            case 'round':
            case 'floor':
            case 'ceil': {
                if (!is_numeric($value)) {
                    return $value;
                }

                $number = (float)$value;

                return match ($transformerId) {
                    'round' => round($number),
                    'floor' => floor($number),
                    'ceil' => ceil($number),
                    default => $number,
                };
            }

            case 'format': {
                if (is_numeric($value)) {
                    $decimals = isset($params['decimals']) && is_numeric($params['decimals']) ? (int)$params['decimals'] : 0;
                    $decimalPoint = isset($params['decimalPoint']) ? (string)$params['decimalPoint'] : '.';
                    $thousandsSeparator = isset($params['thousandsSeparator']) ? (string)$params['thousandsSeparator'] : ',';
                    return number_format((float)$value, $decimals, $decimalPoint, $thousandsSeparator);
                }

                $preset = isset($params['preset']) ? trim((string)$params['preset']) : '';
                $pattern = '';

                if ($preset === 'custom') {
                    $pattern = isset($params['pattern']) ? trim((string)$params['pattern']) : '';
                } else {
                    $pattern = self::_resolveDateFormatPatternFromPreset($preset);
                }

                if ($pattern === '') {
                    return $value;
                }

                try {
                    return Craft::$app->getFormatter()->asDatetime($value, $pattern);
                } catch (Throwable) {
                    return $value;
                }
            }

            case 'lower':
            case 'upper':
            case 'title':
            case 'capitalize': {
                $text = self::_stringifyVariableValue($value);

                if ($transformerId === 'lower') {
                    return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
                }

                if ($transformerId === 'upper') {
                    return function_exists('mb_strtoupper') ? mb_strtoupper($text) : strtoupper($text);
                }

                if ($transformerId === 'title') {
                    return self::_toTitleCase($text);
                }

                if ($transformerId === 'capitalize') {
                    if ($text === '') {
                        return '';
                    }

                    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
                        $first = mb_substr($text, 0, 1);
                        $rest = mb_substr($text, 1);
                        return mb_strtoupper($first) . $rest;
                    }

                    return ucfirst($text);
                }
            }

            case 'replace': {
                $search = isset($params['search']) ? (string)$params['search'] : '';
                $replace = isset($params['replace']) ? (string)$params['replace'] : '';

                if ($search === '') {
                    return $value;
                }

                return str_replace($search, $replace, self::_stringifyVariableValue($value));
            }

            case 'truncate': {
                $text = self::_stringifyVariableValue($value);
                $length = isset($params['length']) && is_numeric($params['length']) ? max(1, (int)$params['length']) : 50;
                $suffix = isset($params['suffix']) ? (string)$params['suffix'] : '...';

                $textLength = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

                if ($textLength <= $length) {
                    return $text;
                }

                $suffixLength = function_exists('mb_strlen') ? mb_strlen($suffix) : strlen($suffix);
                $take = max(0, $length - min($length, $suffixLength));
                $base = function_exists('mb_substr') ? mb_substr($text, 0, $take) : substr($text, 0, $take);

                return $base . $suffix;
            }

            case 'map': {
                $trueLabel = isset($params['trueLabel']) ? (string)$params['trueLabel'] : Craft::t('formie', 'Yes');
                $falseLabel = isset($params['falseLabel']) ? (string)$params['falseLabel'] : Craft::t('formie', 'No');
                return self::_toBoolean($value) ? $trueLabel : $falseLabel;
            }

            case 'join': {
                if (!is_array($value)) {
                    return $value;
                }

                $separator = isset($params['separator']) ? (string)$params['separator'] : ', ';

                return implode($separator, array_map(
                    static fn(mixed $item): string => self::_stringifyVariableValue($item),
                    $value,
                ));
            }

            case 'first': {
                if (!is_array($value)) {
                    return $value;
                }

                return $value[0] ?? null;
            }

            case 'last': {
                if (!is_array($value)) {
                    return $value;
                }

                return $value !== [] ? $value[array_key_last($value)] : null;
            }

            case 'count': {
                if (is_array($value)) {
                    return count($value);
                }

                return is_countable($value) ? count($value) : 0;
            }
        }

        return $value;
    }


    // Private Methods
    // =========================================================================

    private static function _getFormVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Form'), 'heading' => true],
            ['label' => Craft::t('formie', 'All Form Fields'), 'value' => '{allFields}', 'group' => 'selector', 'outputMode' => self::CONTENT_ANY, 'allowTransforms' => false],
            ['label' => Craft::t('formie', 'All Non Empty Fields'), 'value' => '{allContentFields}', 'group' => 'selector', 'outputMode' => self::CONTENT_ANY, 'allowTransforms' => false],
            ['label' => Craft::t('formie', 'All Visible Fields'), 'value' => '{allVisibleFields}', 'group' => 'selector', 'outputMode' => self::CONTENT_ANY, 'allowTransforms' => false],
            [
                'label' => Craft::t('formie', 'Form'),
                'children' => [
                    ['label' => Craft::t('formie', 'Form Name'), 'value' => '{form:name}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Form Handle'), 'value' => '{form:handle}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                ],
            ],
        ];
    }

    private static function _getSubmissionVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Submission'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'Submission'),
                'children' => [
                    ['label' => Craft::t('formie', 'Submission Title'), 'value' => '{submission:title}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Submission ID'), 'value' => '{submission:id}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Submission UID'), 'value' => '{submission:uid}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Submission URL'), 'value' => '{submission:url}', 'group' => 'selector', 'outputMode' => 'singleLine', 'compatibleWith' => ['url']],
                    [
                        'label' => Craft::t('formie', 'Submission Date'),
                        'value' => '{submission:date}',
                        'group' => 'selector',
                        'outputMode' => 'singleLine',
                        'transformValueTypes' => [self::TYPE_DATE],
                    ],
                    ['label' => Craft::t('formie', 'Submission Status'), 'value' => '{submission:status}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                ],
            ],
        ];
    }

    private static function _getSystemVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'System'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'System'),
                'children' => [
                    ['label' => Craft::t('formie', 'System Name'), 'value' => '{system:name}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'System Email'), 'value' => '{system:email}', 'group' => 'selector', 'outputMode' => 'singleLine', 'compatibleWith' => ['plainText', 'email']],
                    ['label' => Craft::t('formie', 'System Reply-To'), 'value' => '{system:replyTo}', 'group' => 'selector', 'outputMode' => 'singleLine', 'compatibleWith' => ['plainText', 'email']],
                ],
            ],
        ];
    }

    private static function _getCurrentTimeVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Current Time'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'Current Date/Time'),
                'value' => '{timestamp}',
                'group' => 'format',
                'outputMode' => 'singleLine',
                'transformValueTypes' => [self::TYPE_DATE],
            ],
        ];
    }

    private static function _getSiteVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Site'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'Current Site'),
                'children' => [
                    ['label' => Craft::t('formie', 'Site Name'), 'value' => '{site:name}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Site Handle'), 'value' => '{site:handle}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'Site URL'), 'value' => '{site:url}', 'group' => 'selector', 'outputMode' => 'singleLine', 'compatibleWith' => ['url']],
                    ['label' => Craft::t('formie', 'Site Language'), 'value' => '{site:language}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                ],
            ],
        ];
    }

    private static function _getEnvironmentVariableDefinitions(): array
    {
        $envKeys = self::_getPrefixedEnvironmentVariableKeys(self::ENVIRONMENT_VARIABLE_PREFIX);

        if ($envKeys === []) {
            return [];
        }

        $children = [];
        foreach ($envKeys as $envKey) {
            $children[] = [
                'label' => '$' . $envKey,
                'value' => '{env:' . $envKey . '}',
                'group' => 'selector',
                'outputMode' => 'singleLine',
                'compatibleWith' => ['plainText', 'email', 'number', 'calculations', 'url'],
            ];
        }

        return [
            ['label' => Craft::t('formie', 'Environment'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'Environment'),
                'children' => $children,
            ],
        ];
    }

    private static function _getUserVariableDefinitions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Users'), 'heading' => true],
            [
                'label' => Craft::t('formie', 'Current User'),
                'children' => [
                    ['label' => Craft::t('formie', 'User IP Address'), 'value' => '{user:ip}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'User ID'), 'value' => '{user:id}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'User Email'), 'value' => '{user:email}', 'group' => 'selector', 'outputMode' => 'singleLine', 'compatibleWith' => ['plainText', 'email']],
                    ['label' => Craft::t('formie', 'Username'), 'value' => '{user:username}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'User Full Name'), 'value' => '{user:fullName}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'User First Name'), 'value' => '{user:firstName}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                    ['label' => Craft::t('formie', 'User Last Name'), 'value' => '{user:lastName}', 'group' => 'selector', 'outputMode' => 'singleLine'],
                ],
            ],
        ];
    }

    private static function _getStaticVariableGroups(): array
    {
        return [
            self::GROUP_FORM => [
                self::_pickerSource(Craft::t('formie', 'All Form Fields'), '{allFields}', [], self::CONTENT_ANY, 'selector', false),
                self::_pickerSource(Craft::t('formie', 'All Non Empty Fields'), '{allContentFields}', [], self::CONTENT_ANY, 'selector', false),
                self::_pickerSource(Craft::t('formie', 'All Visible Fields'), '{allVisibleFields}', [], self::CONTENT_ANY, 'selector', false),
                self::_pickerGroup(Craft::t('formie', 'Form'), [
                    self::_pickerSource(Craft::t('formie', 'Form Name'), '{form:name}'),
                    self::_pickerSource(Craft::t('formie', 'Form Handle'), '{form:handle}'),
                ]),
            ],
            self::GROUP_SUBMISSION => [
                self::_pickerGroup(Craft::t('formie', 'Submission'), [
                    self::_pickerSource(Craft::t('formie', 'Submission Title'), '{submission:title}'),
                    self::_pickerSource(Craft::t('formie', 'Submission ID'), '{submission:id}'),
                    self::_pickerSource(Craft::t('formie', 'Submission UID'), '{submission:uid}'),
                    self::_pickerSource(Craft::t('formie', 'Submission URL'), '{submission:url}', [self::TYPE_URL]),
                    self::_pickerSource(Craft::t('formie', 'Submission Date'), '{submission:date}', [self::TYPE_DATE]),
                    self::_pickerSource(Craft::t('formie', 'Submission Status'), '{submission:status}'),
                ]),
            ],
            self::GROUP_SYSTEM => [
                self::_pickerGroup(Craft::t('formie', 'System'), [
                    self::_pickerSource(Craft::t('formie', 'System Name'), '{system:name}'),
                    self::_pickerSource(Craft::t('formie', 'System Email'), '{system:email}', [self::TYPE_TEXT, self::TYPE_EMAIL]),
                    self::_pickerSource(Craft::t('formie', 'System Reply-To'), '{system:replyTo}', [self::TYPE_TEXT, self::TYPE_EMAIL]),
                ]),
            ],
            self::GROUP_CURRENT_TIME => [
                self::_pickerSource(Craft::t('formie', 'Current Date/Time'), '{timestamp}', [self::TYPE_DATE], self::CONTENT_SINGLE_LINE, 'format'),
            ],
            self::GROUP_ENVIRONMENT => self::_getEnvironmentVariableSources(),
            self::GROUP_CURRENT_SITE => [
                self::_pickerGroup(Craft::t('formie', 'Current Site'), [
                    self::_pickerSource(Craft::t('formie', 'Site Name'), '{site:name}'),
                    self::_pickerSource(Craft::t('formie', 'Site Handle'), '{site:handle}'),
                    self::_pickerSource(Craft::t('formie', 'Site URL'), '{site:url}', [self::TYPE_URL]),
                    self::_pickerSource(Craft::t('formie', 'Site Language'), '{site:language}'),
                ]),
            ],
            self::GROUP_CURRENT_USER => [
                self::_pickerGroup(Craft::t('formie', 'Current User'), [
                    self::_pickerSource(Craft::t('formie', 'User IP Address'), '{user:ip}'),
                    self::_pickerSource(Craft::t('formie', 'User ID'), '{user:id}'),
                    self::_pickerSource(Craft::t('formie', 'User Email'), '{user:email}', [self::TYPE_TEXT, self::TYPE_EMAIL]),
                    self::_pickerSource(Craft::t('formie', 'Username'), '{user:username}'),
                    self::_pickerSource(Craft::t('formie', 'User Full Name'), '{user:fullName}'),
                    self::_pickerSource(Craft::t('formie', 'User First Name'), '{user:firstName}'),
                    self::_pickerSource(Craft::t('formie', 'User Last Name'), '{user:lastName}'),
                ]),
            ],
        ];
    }

    private static function _getEnvironmentVariableSources(): array
    {
        $envKeys = self::_getPrefixedEnvironmentVariableKeys(self::ENVIRONMENT_VARIABLE_PREFIX);

        if ($envKeys === []) {
            return [];
        }

        $children = [];

        foreach ($envKeys as $envKey) {
            $children[] = self::_pickerSource(
                '$' . $envKey,
                '{env:' . $envKey . '}',
                [self::TYPE_TEXT, self::TYPE_EMAIL, self::TYPE_NUMBER, self::TYPE_CALCULATIONS, self::TYPE_URL],
                self::CONTENT_SINGLE_LINE,
                'selector',
            );
        }

        return [
            self::_pickerGroup(Craft::t('formie', 'Environment'), $children),
        ];
    }

    private static function _pickerSource(string $label, string $value, array $types = [self::TYPE_TEXT], string $content = self::CONTENT_SINGLE_LINE, ?string $group = 'selector', ?bool $allowTransforms = null): array
    {
        $entry = [
            'label' => $label,
            'value' => $value,
            'content' => $content,
            'types' => array_values(array_unique(array_filter(array_map('strval', $types)))),
        ];

        if ($group !== null && $group !== '') {
            $entry['group'] = $group;
        }

        if ($allowTransforms !== null) {
            $entry['allowTransforms'] = $allowTransforms;
        }

        return $entry;
    }

    private static function _pickerGroup(string $label, array $children, string $content = self::CONTENT_SINGLE_LINE): array
    {
        return [
            'label' => $label,
            'content' => $content,
            'children' => array_values($children),
        ];
    }

    private static function _referenceAllowsTransforms(ReferenceExpression $expr): bool
    {
        return !in_array($expr->target, ['allFields', 'allContentFields', 'allVisibleFields'], true);
    }

    private static function _getTransformerRegistry(): array
    {
        $transformerRegistry = [
            self::TYPE_NUMBER => [
                [
                    'id' => 'round',
                    'label' => Craft::t('formie', 'Round'),
                    'description' => Craft::t('formie', 'Round to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'floor',
                    'label' => Craft::t('formie', 'Floor'),
                    'description' => Craft::t('formie', 'Round down to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'ceil',
                    'label' => Craft::t('formie', 'Ceil'),
                    'description' => Craft::t('formie', 'Round up to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'format',
                    'label' => Craft::t('formie', 'Number Format'),
                    'description' => Craft::t('formie', 'Format numeric output with decimal precision and separators.'),
                    'params' => [
                        [
                            'name' => 'decimals',
                            'type' => 'number',
                            'label' => Craft::t('formie', 'Decimal Places'),
                            'required' => false,
                            'default' => 0,
                        ],
                        [
                            'name' => 'decimalPoint',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Decimal Separator'),
                            'required' => false,
                            'default' => '.',
                        ],
                        [
                            'name' => 'thousandsSeparator',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Thousands Separator'),
                            'required' => false,
                            'default' => ',',
                        ],
                    ],
                ],
            ],
            self::TYPE_TEXT => [
                [
                    'id' => 'lower',
                    'label' => Craft::t('formie', 'Lowercase'),
                    'description' => Craft::t('formie', 'Convert text to lowercase.'),
                    'params' => [],
                ],
                [
                    'id' => 'upper',
                    'label' => Craft::t('formie', 'Uppercase'),
                    'description' => Craft::t('formie', 'Convert text to uppercase.'),
                    'params' => [],
                ],
                [
                    'id' => 'title',
                    'label' => Craft::t('formie', 'Title Case'),
                    'description' => Craft::t('formie', 'Convert text to title case.'),
                    'params' => [],
                ],
                [
                    'id' => 'capitalize',
                    'label' => Craft::t('formie', 'Capitalize First Letter'),
                    'description' => Craft::t('formie', 'Capitalize only the first letter of the text.'),
                    'params' => [],
                ],
                [
                    'id' => 'replace',
                    'label' => Craft::t('formie', 'Replace'),
                    'description' => Craft::t('formie', 'Replace part of the text with another value.'),
                    'params' => [
                        [
                            'name' => 'search',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Search'),
                            'required' => true,
                        ],
                        [
                            'name' => 'replace',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Replace With'),
                            'required' => false,
                            'default' => '',
                        ],
                    ],
                ],
                [
                    'id' => 'truncate',
                    'label' => Craft::t('formie', 'Truncate'),
                    'description' => Craft::t('formie', 'Truncate text to a maximum length.'),
                    'params' => [
                        [
                            'name' => 'length',
                            'type' => 'number',
                            'label' => Craft::t('formie', 'Length'),
                            'required' => true,
                            'default' => 50,
                        ],
                        [
                            'name' => 'suffix',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Suffix'),
                            'required' => false,
                            'default' => '...',
                        ],
                    ],
                ],
            ],
            self::TYPE_DATE => [
                [
                    'id' => 'format',
                    'label' => Craft::t('formie', 'Date Format'),
                    'description' => Craft::t('formie', 'Format date/time output with a Twig date format string.'),
                    'params' => [
                        [
                            'name' => 'preset',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Format'),
                            'required' => true,
                            'default' => 'isoDate',
                            'options' => [
                                [
                                    'value' => 'datetimeUs12',
                                    'label' => Craft::t('formie', 'Date/Time (mm/dd/yyyy 12h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeEu12',
                                    'label' => Craft::t('formie', 'Date/Time (dd/mm/yyyy 12h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeEu24',
                                    'label' => Craft::t('formie', 'Date/Time (dd/mm/yyyy 24h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeIso24',
                                    'label' => Craft::t('formie', 'Date/Time (yyyy-mm-dd 24h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'dateUs',
                                    'label' => Craft::t('formie', 'Date (mm/dd/yyyy)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'dateEu',
                                    'label' => Craft::t('formie', 'Date (dd/mm/yyyy)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'isoDate',
                                    'label' => Craft::t('formie', 'Date (yyyy-mm-dd)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'dateLong',
                                    'label' => Craft::t('formie', 'Date (Month Day, Year)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'time12',
                                    'label' => Craft::t('formie', 'Time (12h)'),
                                    'group' => Craft::t('formie', 'Time'),
                                ],
                                [
                                    'value' => 'time24',
                                    'label' => Craft::t('formie', 'Time (24h)'),
                                    'group' => Craft::t('formie', 'Time'),
                                ],
                            ],
                        ],
                        [
                            'name' => 'pattern',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Custom Format Pattern'),
                            'required' => true,
                            'placeholder' => Craft::t('formie', 'e.g. Y-m-d H:i'),
                            'showWhen' => [
                                'param' => 'preset',
                                'equals' => 'custom',
                            ],
                        ],
                    ],
                ],
            ],
            'boolean' => [
                [
                    'id' => 'map',
                    'label' => Craft::t('formie', 'True/False Labels'),
                    'description' => Craft::t('formie', 'Map boolean values to custom labels.'),
                    'params' => [
                        [
                            'name' => 'trueLabel',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'True Label'),
                            'required' => false,
                            'default' => Craft::t('formie', 'Yes'),
                        ],
                        [
                            'name' => 'falseLabel',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'False Label'),
                            'required' => false,
                            'default' => Craft::t('formie', 'No'),
                        ],
                    ],
                ],
            ],
            self::TYPE_ARRAY => [
                [
                    'id' => 'join',
                    'label' => Craft::t('formie', 'Join'),
                    'description' => Craft::t('formie', 'Join multiple values into a list string.'),
                    'params' => [
                        [
                            'name' => 'separator',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Separator'),
                            'required' => false,
                            'default' => ', ',
                        ],
                    ],
                ],
                [
                    'id' => 'first',
                    'label' => Craft::t('formie', 'First'),
                    'description' => Craft::t('formie', 'Use the first value from a list.'),
                    'params' => [],
                ],
                [
                    'id' => 'last',
                    'label' => Craft::t('formie', 'Last'),
                    'description' => Craft::t('formie', 'Use the last value from a list.'),
                    'params' => [],
                ],
                [
                    'id' => 'count',
                    'label' => Craft::t('formie', 'Count'),
                    'description' => Craft::t('formie', 'Count the number of values in a list.'),
                    'params' => [],
                ],
            ],
        ];

        $event = new RegisterTransformersEvent([
            'transformerRegistry' => $transformerRegistry,
        ]);
        Event::trigger(self::class, self::EVENT_REGISTER_TRANSFORMERS, $event);

        return self::_sanitizeTransformerRegistry($event->transformerRegistry);
    }

    private static function _sanitizeTransformerRegistry(mixed $registry): array
    {
        if (!is_array($registry)) {
            return [];
        }

        $sanitized = [];

        foreach ($registry as $valueType => $definitions) {
            if (!is_string($valueType) || trim($valueType) === '' || !is_array($definitions)) {
                self::_logInvalidTransformerEntry('Ignoring invalid transformer registry group entry.', [
                    'valueType' => $valueType,
                ]);
                continue;
            }

            $group = [];
            $seenIds = [];

            foreach ($definitions as $definition) {
                $normalized = self::_sanitizeTransformerDefinition($definition);
                if ($normalized === null) {
                    continue;
                }

                if (isset($seenIds[$normalized['id']])) {
                    self::_logInvalidTransformerEntry('Ignoring duplicate transformer id within valueType group.', [
                        'valueType' => $valueType,
                        'id' => $normalized['id'],
                    ]);
                    continue;
                }

                $seenIds[$normalized['id']] = true;
                $group[] = $normalized;
            }

            if ($group !== []) {
                $sanitized[$valueType] = $group;
            }
        }

        return $sanitized;
    }

    private static function _sanitizeTransformerDefinition(mixed $definition): ?array
    {
        if (!is_array($definition)) {
            self::_logInvalidTransformerEntry('Ignoring invalid transformer definition (expected array).');
            return null;
        }

        $id = trim((string)($definition['id'] ?? ''));
        $label = trim((string)($definition['label'] ?? ''));
        $description = trim((string)($definition['description'] ?? ''));
        $params = $definition['params'] ?? [];
        $appliesTo = $definition['appliesTo'] ?? [];

        if ($id === '' || $label === '') {
            self::_logInvalidTransformerEntry('Ignoring invalid transformer definition (missing id/label).', [
                'id' => $id,
                'label' => $label,
            ]);
            return null;
        }

        if (!is_array($params)) {
            $params = [];
        }

        if (!is_array($appliesTo)) {
            $appliesTo = [];
        }

        $normalizedParams = [];

        foreach ($params as $param) {
            $normalized = self::_sanitizeTransformerParam($param);

            if ($normalized === null) {
                self::_logInvalidTransformerEntry('Ignoring invalid transformer param entry.', [
                    'transformerId' => $id,
                ]);

                continue;
            }

            $normalizedParams[] = $normalized;
        }

        return [
            'id' => $id,
            'label' => $label,
            'description' => $description,
            'params' => $normalizedParams,
            'appliesTo' => array_values(array_unique(array_filter(array_map('strval', $appliesTo)))),
        ];
    }

    private static function _sanitizeTransformerParam(mixed $param): ?array
    {
        if (!is_array($param)) {
            return null;
        }

        $name = trim((string)($param['name'] ?? ''));
        $type = trim((string)($param['type'] ?? 'string'));
        $label = trim((string)($param['label'] ?? ''));
        $required = (bool)($param['required'] ?? false);

        if ($name === '' || $label === '') {
            return null;
        }

        if (!in_array($type, ['string', 'number', 'boolean'], true)) {
            $type = 'string';
        }

        $normalized = [
            'name' => $name,
            'type' => $type,
            'label' => $label,
            'required' => $required,
        ];

        if (array_key_exists('default', $param)) {
            $normalized['default'] = $param['default'];
        }

        if (array_key_exists('placeholder', $param)) {
            $normalized['placeholder'] = (string)$param['placeholder'];
        }

        $options = self::_sanitizeTransformerParamOptions($param['options'] ?? null);

        if ($options !== []) {
            $normalized['options'] = $options;
        }

        $showWhen = self::_sanitizeTransformerParamShowWhen($param['showWhen'] ?? null);

        if ($showWhen !== null) {
            $normalized['showWhen'] = $showWhen;
        }

        return $normalized;
    }

    private static function _sanitizeTransformerParamOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $value = isset($option['value']) ? (string)$option['value'] : '';
            $label = isset($option['label']) ? (string)$option['label'] : '';

            if ($value === '' || $label === '') {
                continue;
            }

            $entry = [
                'value' => $value,
                'label' => $label,
            ];

            if (isset($option['group']) && trim((string)$option['group']) !== '') {
                $entry['group'] = (string)$option['group'];
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    private static function _sanitizeTransformerParamShowWhen(mixed $showWhen): ?array
    {
        if (!is_array($showWhen)) {
            return null;
        }

        $param = isset($showWhen['param']) ? trim((string)$showWhen['param']) : '';
        $equals = isset($showWhen['equals']) ? (string)$showWhen['equals'] : '';

        if ($param === '') {
            return null;
        }

        return [
            'param' => $param,
            'equals' => $equals,
        ];
    }

    private static function _logInvalidTransformerEntry(string $message, array $context = []): void
    {
        $contextSuffix = $context === [] ? '' : ' ' . Json::encode($context);

        Craft::warning($message . $contextSuffix, __METHOD__);
    }

    private static function _getSubmissionRenderCacheKey(Submission $submission): string
    {
        $form = $submission->form;

        if ($submission->id) {
            return 'submission' . $submission->id;
        }

        if ($form?->id) {
            return 'form' . $form->id . ':submission:' . spl_object_id($submission);
        }

        return 'submission:' . spl_object_id($submission);
    }

    private static function _getSummaryRenderCacheKey(Submission $submission, Notification $notification): string
    {
        $notificationKey = $notification->id
            ? 'notification' . $notification->id
            : 'notification:' . spl_object_id($notification);
        $templateKey = $notification->templateId ? ':template' . $notification->templateId : ':template:default';

        return self::_getSubmissionRenderCacheKey($submission) . ':' . $notificationKey . $templateKey;
    }

    private static function _getSubmissionFieldByReference(Submission $submission, string $reference): ?FieldInterface
    {
        if ($reference === '') {
            return null;
        }

        $renderCache = Formie::$plugin->getRenderCache();
        $cacheKey = self::_getSubmissionRenderCacheKey($submission);

        if (!$renderCache->hasFieldReferenceIndex($cacheKey)) {
            $fieldsByReference = [];

            foreach ($submission->getFields() as $field) {
                $fieldReference = trim((string)($field->reference ?? ''));

                if ($fieldReference === '') {
                    continue;
                }

                $fieldsByReference[$fieldReference] = $field;
            }

            $renderCache->setFieldReferenceIndex($cacheKey, $fieldsByReference);
        }

        return $renderCache->getFieldByReference($cacheKey, $reference);
    }

    private static function _getPrefixedEnvironmentVariableKeys(string $prefix): array
    {
        $keys = [];
        $sources = [$_ENV ?? [], $_SERVER ?? []];

        foreach ($sources as $source) {
            foreach (array_keys($source) as $key) {
                if (!is_string($key) || !str_starts_with($key, $prefix)) {
                    continue;
                }

                if (!preg_match('/^[A-Z0-9_]+$/', $key)) {
                    continue;
                }

                $keys[$key] = true;
            }
        }

        $allEnv = getenv();

        if (is_array($allEnv)) {
            foreach (array_keys($allEnv) as $key) {
                if (!is_string($key) || !str_starts_with($key, $prefix)) {
                    continue;
                }

                if (!preg_match('/^[A-Z0-9_]+$/', $key)) {
                    continue;
                }

                $keys[$key] = true;
            }
        }

        $envKeys = array_keys($keys);
        sort($envKeys);

        return $envKeys;
    }

    private static function _getSummaryVariables(Submission $submission, Notification $notification): array
    {
        $allFields = [];
        $allContentFields = [];
        $allVisibleFields = [];

        // Build expensive summary variables used by multi-line content references.
        foreach ($submission->getFields() as $field) {
            if (!$field->includeInEmailFieldSummaries || $field->isConditionallyHidden($submission)) {
                continue;
            }

            $value = $submission->getFieldValue($field->valueKey());

            $allFields[] = $field;

            if (!$field->isValueEmpty($value, $submission)) {
                $allContentFields[] = $field;
            }

            if (!$field->getIsHidden()) {
                $allVisibleFields[] = $field;
            }
        }

        return [
            'allFields' => self::_renderSummaryTemplate($notification, $submission, 'all-fields', $allFields),
            'allContentFields' => self::_renderSummaryTemplate($notification, $submission, 'all-content-fields', $allContentFields),
            'allVisibleFields' => self::_renderSummaryTemplate($notification, $submission, 'all-visible-fields', $allVisibleFields),
        ];
    }

    private static function _renderSummaryTemplate(Notification $notification, Submission $submission, string $template, array $fields): string
    {
        if (!$fields) {
            return '';
        }

        $html = $notification->renderTemplate($template, [
            'notification' => $notification,
            'submission' => $submission,
            'fields' => $fields,
        ]);

        return StringHelper::cleanString(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function _resolveDateFormatPatternFromPreset(string $preset): string
    {
        return match ($preset) {
            'datetimeUs12' => 'm/d/Y h:i A',
            'datetimeEu12' => 'd/m/Y h:i A',
            'datetimeEu24' => 'd/m/Y H:i',
            'datetimeIso24' => 'Y-m-d H:i',
            'dateUs' => 'm/d/Y',
            'dateEu' => 'd/m/Y',
            'isoDate' => 'Y-m-d',
            'dateLong' => 'F j, Y',
            'time12' => 'h:i A',
            'time24' => 'H:i',
            default => '',
        };
    }

    private static function _stringifyVariableValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        return '';
    }

    private static function _toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float)$value !== 0.0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === '' || in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }

            return true;
        }

        return (bool)$value;
    }

    private static function _toTitleCase(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (defined('MB_CASE_TITLE') && function_exists('mb_convert_case')) {
            return mb_convert_case($value, MB_CASE_TITLE);
        }

        return ucwords(strtolower($value));
    }

    private static function _referenceToVariableKey(ReferenceExpression $expr): string
    {
        if ($expr->target === 'field') {
            $path = $expr->identifier;

            if ($expr->selector !== '') {
                $path .= ':' . $expr->selector;
            }

            return 'field.' . str_replace(':', '.', $path);
        }

        if ($expr->target === 'dispatch') {
            $path = $expr->identifier;

            if ($expr->selector !== '') {
                $path .= '.' . str_replace(':', '.', $expr->selector);
            }

            return 'dispatch.' . $path;
        }

        if ($expr->target === 'timestamp') {
            return $expr->identifier === '' ? 'timestamp' : $expr->identifier;
        }

        return $expr->target . ucfirst($expr->identifier);
    }

    private static function _resolveReferenceFieldValue(
        Submission $submission,
        FieldInterface $field,
        string $selector = '',
        array $params = [],
    ): mixed {
        if ($field instanceof RepeatableParentFieldInterface) {
            return RepeaterReferenceHelper::resolve($submission, $field, $selector, $params);
        }

        if ($field instanceof Table) {
            return TableReferenceHelper::resolve($submission, $field, $selector, $params);
        }

        $fieldHandle = $field->handle;

        if ($selector === '') {
            return $submission->getFieldValue($fieldHandle);
        }

        $path = str_replace(':', '.', $selector);

        return $submission->getFieldValue($fieldHandle . '.' . $path);
    }

    private static function _getCurrentUser(?Submission $submission = null): bool|User|IdentityInterface|null
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser && Craft::$app->getRequest()->getIsSiteRequest()) {
            return $currentUser;
        }

        if ($submission && $submission->getUser()) {
            return $submission->getUser();
        }

        return null;
    }

    private static function _getSite(?Submission $submission): ?Site
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        if ($currentSite) {
            return $currentSite;
        }

        $siteId = $submission->siteId ?? null;

        if ($siteId) {
            return Craft::$app->getSites()->getSiteById($siteId);
        }

        return Craft::$app->getSites()->getPrimarySite();
    }

    private static function _getCustomVariableGroups(): array
    {
        $sources = self::getRegisteredVariableSources();

        if ($sources === []) {
            return [];
        }

        $items = array_map(static fn(VariableSourceInterface $source) => $source->toPickerSource(), $sources);

        return [
            self::GROUP_CUSTOM => $items,
        ];
    }

    private static function _sanitizeRegisteredVariableSources(array $sources): array
    {
        $sanitized = [];
        $seen = [];

        foreach ($sources as $source) {
            if (!$source instanceof VariableSourceInterface) {
                Formie::warning('Ignoring invalid custom variable source registration entry.');
                continue;
            }

            $handle = strtolower(trim($source->getHandle()));
            $label = trim($source->getLabel());

            if (!self::_isValidCustomVariableHandle($handle) || $label === '') {
                Formie::warning('Ignoring invalid custom variable source "{handle}".', [
                    'handle' => $source->getHandle(),
                ]);
                continue;
            }

            if (isset($seen[$handle])) {
                Formie::warning('Ignoring duplicate custom variable source "{handle}".', [
                    'handle' => $handle,
                ]);
                continue;
            }

            $seen[$handle] = true;
            $sanitized[] = $source;
        }

        return $sanitized;
    }

    private static function _isValidCustomVariableHandle(string $value): bool
    {
        $value = strtolower(trim($value));

        return $value !== '' && (bool)preg_match('/^[a-z][a-z0-9_]*$/', $value);
    }

    private static function _appendDispatchVariables(array $variables, Submission $submission): array
    {
        $context = Formie::$plugin->getIntegrationDispatch()->loadContext($submission);
        $dispatch = [];

        foreach ($context->results as $handle => $result) {
            if (!is_array($result)) {
                continue;
            }

            $dispatch[$handle] = array_filter([
                'id' => $result['elementId'] ?? null,
                'url' => $result['url'] ?? null,
                'success' => $result['success'] ?? false,
                'type' => $result['type'] ?? null,
            ], fn($value) => $value !== null && $value !== '');
        }

        if ($dispatch) {
            $variables['dispatch'] = $dispatch;
        }

        return $variables;
    }
}

<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\Formie;
use verbb\formie\base\Captcha;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;

class Recaptcha extends Captcha
{
    // Constants
    // =========================================================================

    public const RECAPTCHA_TYPE_V2_CHECKBOX  = 'v2_checkbox';
    public const RECAPTCHA_TYPE_V2_INVISIBLE = 'v2_invisible';
    public const RECAPTCHA_TYPE_V3 = 'v3';
    public const RECAPTCHA_TYPE_ENTERPRISE = 'enterprise';
    public const ENTERPRISE_MODE_SCORE = 'score';
    public const ENTERPRISE_MODE_CHECKBOX = 'checkbox';
    public const ENTERPRISE_MODE_POLICY = 'policy';


    // Properties
    // =========================================================================

    public ?string $handle = 'recaptcha';
    public ?string $secretKey = null;
    public ?string $siteKey = null;
    public ?string $type = 'v3';
    public string $size = 'normal';
    public string $theme = 'light';
    public string $badge = 'bottomright';
    public string $language = 'en';
    public float $minScore = 0.5;
    public string $action = 'submit';
    public string $scriptLoadingMethod = 'asyncDefer';
    public ?string $enterpriseType = 'score';
    public ?string $projectId = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        if (array_key_exists('apiKey', $config)) {
            $config['secretKey'] = ArrayHelper::remove($config, 'apiKey');
        }

        parent::__construct($config);
    }

    public function getName(): string
    {
        return Craft::t('formie', 'reCAPTCHA');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'reCAPTCHA is a free service that protects your forms from spam and abuse. Find out more via [Google reCAPTCHA](https://www.google.com/recaptcha).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        $variables['languageOptions'] = $this->_getLanguageOptions();

        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/recaptcha/_plugin-settings', $variables);
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-captcha formie-recaptcha-placeholder',
            'data-recaptcha-placeholder' => true,
        ]);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$context->form) {
            return null;
        }

        $moduleId = match ($this->type) {
            self::RECAPTCHA_TYPE_ENTERPRISE => 'recaptcha-enterprise',
            self::RECAPTCHA_TYPE_V2_CHECKBOX => 'recaptcha-v2-checkbox',
            self::RECAPTCHA_TYPE_V2_INVISIBLE => 'recaptcha-v2-invisible',
            self::RECAPTCHA_TYPE_V3 => 'recaptcha-v3',
            default => null,
        };

        if (!$moduleId) {
            return null;
        }

        return new ClientModule([
            'id' => $moduleId,
            'config' => [
                'handle' => $this->handle,
                'placeholderSelector' => '[data-recaptcha-placeholder]',
                'siteKey' => App::parseEnv($this->siteKey),
                'formId' => $context->form->getRenderId(),
                'theme' => $this->theme,
                'size' => $this->size,
                'badge' => $this->badge,
                'language' => $this->_getMatchedLanguageId() ?? 'en',
                'submitMethod' => $context->form->settings->submitMethod ?? 'page-reload',
                'hasMultiplePages' => $context->form->hasMultiplePages() ?? false,
                'action' => $this->_getRecaptchaAction(),
                'loadingMethod' => $this->scriptLoadingMethod,
                'enterpriseType' => $this->_getEnterpriseMode(),
            ],
        ]);
    }

    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return [
            'formId' => $form->getRenderId(),
            'sessionKey' => 'siteKey',
            'value' => App::parseEnv($this->siteKey),
        ];
    }

    public function validateSubmission(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'g-recaptcha-response');

        // Protect against invalid data being sent. No need to log, likely malicious
        if (!$responseToken || !is_string($responseToken)) {
            $this->spamReason = 'Client-side token missing.';

            return false;
        }

        $client = Craft::createGuzzleClient([
            'headers' => [
                'Referer' => Craft::$app->getSites()->getPrimarySite()->getBaseUrl(),
            ],
        ]);

        $siteKey = App::parseEnv($this->siteKey);
        $secretKey = App::parseEnv($this->secretKey);
        $projectId = App::parseEnv($this->projectId);

        if ($this->type === self::RECAPTCHA_TYPE_ENTERPRISE) {
            $enterpriseMode = $this->_getEnterpriseMode();
            $response = $client->post('https://recaptchaenterprise.googleapis.com/v1/projects/' . $projectId . '/assessments?key=' . $secretKey, [
                'json' => [
                    'event' => [
                        'siteKey' => $siteKey,
                        'token' => $responseToken,
                        'userAgent' => Craft::$app->getRequest()->getUserAgent(),
                        'userIpAddress' => Craft::$app->getRequest()->getRemoteIP(),
                    ],
                ],
            ]);

            $result = Json::decode((string)$response->getBody(), true);

            Formie::info('ReCAPTCHA result {result}', [
                'result' => Json::encode($result),
            ]);

            $isValid = $result['tokenProperties']['valid'] ?? false;
            $reason = $result['tokenProperties']['invalidReason'] ?? 'UNKNOWN_INVALID_REASON';
            $actualAction = $result['tokenProperties']['action'] ?? null;
            $expectedAction = $this->_getRecaptchaAction();

            if (!$isValid) {
                $this->spamReason = 'Invalid token (' . $reason . ').';

                return false;
            }

            if ($enterpriseMode !== self::ENTERPRISE_MODE_CHECKBOX && $actualAction && $actualAction !== $expectedAction) {
                $this->spamReason = 'Token action "' . $actualAction . '" did not match expected action "' . $expectedAction . '".';

                return false;
            }

            $score = $result['riskAnalysis']['score'] ?? $result['score'] ?? null;
            $reasons = $result['riskAnalysis']['reasons'] ?? $result['reasons'] ?? [];

            if ($enterpriseMode === self::ENTERPRISE_MODE_SCORE && $score !== null) {
                $scoreRating = ($score >= $this->minScore);

                if (!$scoreRating) {
                    $reasonsString = $reasons ? (' Reasons: ' . implode(', ', $reasons) . '.') : '';

                    $this->spamReason = 'Score ' . $score . ' is below threshold ' . $this->minScore . $reasonsString . '.';
                }

                return $scoreRating;
            }

            return $isValid;
        }

        $response = $client->post('https://www.recaptcha.net/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $secretKey,
                'response' => $responseToken,
                'remoteip' => Craft::$app->getRequest()->getRemoteIP(),
            ],
        ]);

        $result = Json::decode((string)$response->getBody(), true);
        $success = $result['success'] ?? false;

        Formie::info('ReCAPTCHA result {result}', [
            'result' => Json::encode($result),
        ]);

        if ($success && isset($result['score'])) {
            $scoreRating = ($result['score'] >= $this->minScore);

            if (!$scoreRating) {
                $this->spamReason = 'Score ' . $result['score'] . ' is below threshold ' . $this->minScore . '.';
            }

            $success = $scoreRating;
        }

        if (!$success && !$this->spamReason) {
            $this->spamReason = Json::encode($result);

            Formie::error(Craft::t('formie', '{token}:{spamReason}', [
                'token' => $responseToken,
                'spamReason' => $this->spamReason,
            ]));
        }

        return $success;
    }

    public function hasValidSettings(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    public function allowedGqlSettings(): array
    {
        return [
            'siteKey' => $this->siteKey,
            'type' => $this->type,
            'size' => $this->size,
            'theme' => $this->theme,
            'badge' => $this->badge,
            'language' => $this->language,
            'scriptLoadingMethod' => $this->scriptLoadingMethod,
            'action' => $this->action,
            'enterpriseType' => $this->_getEnterpriseMode(),
            'projectId' => $this->projectId,
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        if (!$this->hasValidSettings()) {
            return [
                $this->getMissingSettingsWarningSchema('ReCAPTCHA', 'recaptcha'),
            ];
        }

        return parent::defineFormSettingsSchema($form);
    }


    // Private Methods
    // =========================================================================

    private function _getMatchedLanguageId()
    {
        if ($this->language && $this->language != 'auto') {
            return $this->language;
        }

        $currentLanguageId = Craft::$app->getLocale()->getLanguageID();

        // 700+ languages supported
        $allCraftLocales = Craft::$app->getI18n()->getAllLocales();
        $allCraftLanguageIds = ArrayHelper::getColumn($allCraftLocales, 'id');

        // ~70 languages supported
        $allRecaptchaLanguageIds = ArrayHelper::getColumn($this->_getLanguageOptions(), 'value');

        // 65 matched language IDs
        $matchedLanguageIds = array_intersect($allRecaptchaLanguageIds, $allCraftLanguageIds);

        // If our current request Language ID matches a reCAPTCHA language ID, use it
        if (in_array($currentLanguageId, $matchedLanguageIds, true)) {
            return $currentLanguageId;
        }

        // If our current language ID has a more generic match, use it
        if (str_contains($currentLanguageId, '-')) {
            $parts = explode('-', $currentLanguageId);
            $baseLanguageId = $parts['0'] ?? null;

            if (in_array($baseLanguageId, $matchedLanguageIds, true)) {
                return $baseLanguageId;
            }
        }

        return null;
    }

    private function _getLanguageOptions(): array
    {
        $languages = [
            'Auto' => 'auto',
            'Arabic' => 'ar',
            'Afrikaans' => 'af',
            'Amharic' => 'am',
            'Armenian' => 'hy',
            'Azerbaijani' => 'az',
            'Basque' => 'eu',
            'Bengali' => 'bn',
            'Bulgarian' => 'bg',
            'Catalan' => 'ca',
            'Chinese (Hong Kong)' => 'zh-HK',
            'Chinese (Simplified)' => 'zh-CN',
            'Chinese (Traditional)' => 'zh-TW',
            'Croatian' => 'hr',
            'Czech' => 'cs',
            'Danish' => 'da',
            'Dutch' => 'nl',
            'English (UK)' => 'en-GB',
            'English (US)' => 'en',
            'Estonian' => 'et',
            'Filipino' => 'fil',
            'Finnish' => 'fi',
            'French' => 'fr',
            'French (Canadian)' => 'fr-CA',
            'Galician' => 'gl',
            'Georgian' => 'ka',
            'German' => 'de',
            'German (Austria)' => 'de-AT',
            'German (Switzerland)' => 'de-CH',
            'Greek' => 'el',
            'Gujarati' => 'gu',
            'Hebrew' => 'iw',
            'Hindi' => 'hi',
            'Hungarian' => 'hu',
            'Icelandic' => 'is',
            'Indonesian' => 'id',
            'Italian' => 'it',
            'Japanese' => 'ja',
            'Kannada' => 'kn',
            'Korean' => 'ko',
            'Laothian' => 'lo',
            'Latvian' => 'lv',
            'Lithuanian' => 'lt',
            'Malay' => 'ms',
            'Malayalam' => 'ml',
            'Marathi' => 'mr',
            'Mongolian' => 'mn',
            'Norwegian' => 'no',
            'Persian' => 'fa',
            'Polish' => 'pl',
            'Portuguese' => 'pt',
            'Portuguese (Brazil)' => 'pt-BR',
            'Portuguese (Portugal)' => 'pt-PT',
            'Romanian' => 'ro',
            'Russian' => 'ru',
            'Serbian' => 'sr',
            'Sinhalese' => 'si',
            'Slovak' => 'sk',
            'Slovenian' => 'sl',
            'Spanish' => 'es',
            'Spanish (Latin America)' => 'es-419',
            'Swahili' => 'sw',
            'Swedish' => 'sv',
            'Tamil' => 'ta',
            'Telugu' => 'te',
            'Thai' => 'th',
            'Turkish' => 'tr',
            'Ukrainian' => 'uk',
            'Urdu' => 'ur',
            'Vietnamese' => 'vi',
            'Zulu' => 'zu',
        ];

        $languageOptions = [];

        foreach ($languages as $languageName => $languageCode) {
            $languageOptions[] = [
                'label' => Craft::t('formie', $languageName),
                'value' => $languageCode,
            ];
        }

        return $languageOptions;
    }

    private function _getEnterpriseMode(): string
    {
        $enterpriseMode = trim((string)$this->enterpriseType);

        // Older saved configs used "invisible" for the submit-triggered Enterprise
        // challenge flow. Keep normalizing that value to the current policy-based key.
        if ($enterpriseMode === 'invisible') {
            return self::ENTERPRISE_MODE_POLICY;
        }

        if (in_array($enterpriseMode, [
            self::ENTERPRISE_MODE_SCORE,
            self::ENTERPRISE_MODE_CHECKBOX,
            self::ENTERPRISE_MODE_POLICY,
        ], true)) {
            return $enterpriseMode;
        }

        return self::ENTERPRISE_MODE_SCORE;
    }

    private function _getRecaptchaAction(): string
    {
        $action = trim((string)$this->action);

        return $action !== '' ? $action : 'submit';
    }

}

<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
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
    public ?string $projectId = null;
    public string $scriptLoadingMethod = 'asyncDefer';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'reCAPTCHA');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'reCAPTCHA is a free service that protects your forms from spam and abuse. Find out more via [Google reCAPTCHA](https://www.google.com/recaptcha).');
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/recaptcha/_plugin-settings', [
            'integration' => $this,
            'languageOptions' => $this->_getLanguageOptions(),
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/recaptcha/_form-settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml(Form $form, $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-recaptcha-placeholder',
            'data-recaptcha-placeholder' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsVariables(Form $form, $page = null): ?array
    {
        $settings = [
            'siteKey' => App::parseEnv($this->siteKey),
            'formId' => $form->getFormId(),
            'theme' => $this->theme,
            'size' => $this->size,
            'badge' => $this->badge,
            'language' => $this->_getMatchedLanguageId() ?? 'en',
            'submitMethod' => $form->settings->submitMethod ?? 'page-reload',
            'hasMultiplePages' => $form->hasMultiplePages() ?? false,
            'loadingMethod' => $this->scriptLoadingMethod,
        ];

        if ($this->type === self::RECAPTCHA_TYPE_ENTERPRISE) {
            $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/captchas/recaptcha-enterprise.js', true);

            return [
                'src' => $src,
                'module' => 'FormieRecaptchaEnterprise',
                'settings' => $settings,
            ];
        }

        if ($this->type === self::RECAPTCHA_TYPE_V3) {
            $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/captchas/recaptcha-v3.js', true);

            return [
                'src' => $src,
                'module' => 'FormieRecaptchaV3',
                'settings' => $settings,
            ];
        }

        if ($this->type === self::RECAPTCHA_TYPE_V2_CHECKBOX) {
            $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/captchas/recaptcha-v2-checkbox.js', true);

            return [
                'src' => $src,
                'module' => 'FormieRecaptchaV2Checkbox',
                'settings' => $settings,
            ];
        }

        if ($this->type === self::RECAPTCHA_TYPE_V2_INVISIBLE) {
            $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/captchas/recaptcha-v2-invisible.js', true);

            return [
                'src' => $src,
                'module' => 'FormieRecaptchaV2Invisible',
                'settings' => $settings,
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateSubmission(Submission $submission): bool
    {
        $response = $this->getRequestParam('g-recaptcha-response');

        // Protect against invalid data being sent. No need to log, likely malicious
        if (!$response || !is_string($response)) {
            return false;
        }

        $client = Craft::createGuzzleClient();
        $siteKey = App::parseEnv($this->siteKey);
        $secretKey = App::parseEnv($this->secretKey);
        $projectId = App::parseEnv($this->projectId);

        if ($this->type === self::RECAPTCHA_TYPE_ENTERPRISE) {
            $response = $client->post('https://recaptchaenterprise.googleapis.com/v1beta1/projects/' . $projectId . '/assessments?key=' . $secretKey, [
                'json' => [
                    'event' => [
                        'siteKey' => $siteKey,
                        'token' => $response,
                    ],
                ],
            ]);

            $result = Json::decode((string)$response->getBody(), true);

            $reason = $result['tokenProperties']['invalidReason'] ?? false;

            if ($reason) {
                $this->spamReason = $reason;
            }

            if (isset($result['score'])) {
                return ($result['score'] >= $this->minScore);
            }

            return $result['tokenProperties']['valid'] ?? false;
        }

        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $secretKey,
                'response' => $response,
                'remoteip' => Craft::$app->getRequest()->getRemoteIP(),
            ],
        ]);

        $result = Json::decode((string)$response->getBody(), true);
        $success = $result['success'] ?? false;

        if ($success && isset($result['score'])) {
            $success = (bool)($result['score'] >= $this->minScore);
        }

        if (!$success) {
            $this->spamReason = Json::encode($result);
        }

        return $success;
    }

    public function hasValidSettings(): bool
    {
        return $this->siteKey && $this->secretKey;
    }


    // Private Methods
    // =========================================================================

    public function _getMatchedLanguageId()
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

}

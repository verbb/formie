<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\LanguageOptions;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;

class FriendlyCaptcha extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'friendlyCaptcha';
    public ?string $secretKey = null;
    public ?string $siteKey = null;
    public string $apiVersion = 'v1';
    public string $language = 'en';
    public string $startMode = 'none';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Friendly Captcha');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Friendly Captcha employs a fundamentally new approach to securely defend your websites and online services from spam and bots. Find out more via [Friendly Captcha](https://friendlycaptcha.com/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        $variables['languageOptions'] = $this->_getLanguageOptions();

        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/friendly-captcha/_plugin-settings', $variables);
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-captcha formie-friendly-captcha-placeholder',
            'data-friendly-captcha-placeholder' => true,
        ]);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$context->form) {
            return null;
        }

        $moduleId = $this->apiVersion === 'v2' ? 'friendly-captcha-v2' : 'friendly-captcha-v1';

        return new ClientModule([
            'id' => $moduleId,
            'config' => [
                'handle' => $this->handle,
                'placeholderSelector' => '[data-friendly-captcha-placeholder]',
                'siteKey' => App::parseEnv($this->siteKey),
                'formId' => $context->form->getRenderId(),
                'language' => $this->_getMatchedLanguageId() ?? 'en',
                'startMode' => $this->startMode ?? 'none',
                'apiVersion' => $this->apiVersion,
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
        if ($this->apiVersion === 'v2') {
            return $this->_validateV2($submission);
        }

        return $this->_validateV1($submission);
    }

    public function hasValidSettings(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    public function allowedGqlSettings(): array
    {
        $settings = [
            'siteKey' => $this->siteKey,
            'language' => $this->language,
        ];

        if ($this->apiVersion === 'v2') {
            $settings['apiVersion'] = $this->apiVersion;
        }

        return $settings;
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        if (!$this->hasValidSettings()) {
            return [
                $this->getMissingSettingsWarningSchema('Friendly Captcha', 'friendly-captcha'),
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
            'English' => 'en',
            'French' => 'fr',
            'German' => 'de',
            'Italian' => 'it',
            'Dutch' => 'nl',
            'Portuguese' => 'pt',
            'Spanish' => 'es',
            'Catalan' => 'ca',
            'Danish' => 'da',
            'Japanese' => 'ja',
            'Russian' => 'ru',
            'Swedish' => 'sv',
            'Greek' => 'el',
            'Ukrainian' => 'uk',
            'Bulgarian' => 'bg',
            'Czech' => 'cs',
            'Slovak' => 'sk',
            'Norwegian' => 'no',
            'Finnish' => 'fi',
            'Latvian' => 'lv',
            'Lithuanian' => 'lt',
            'Polish' => 'pl',
            'Estonian' => 'et',
            'Croatian' => 'hr',
            'Serbian' => 'sr',
            'Slovenian' => 'sl',
            'Hungarian' => 'hu',
            'Romanian' => 'ro',
            'Chinese (Simplified)' => 'zh',
            'Chinese (Traditional)' => 'zh_TW',
            'Vietnamese' => 'vi',
            'Hebrew' => 'he',
            'Thai' => 'th',
        ];

        return LanguageOptions::buildOptions($languages);
    }

    private function _validateV1(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'frc-captcha-solution');

        if (!$responseToken) {
            $this->spamReason = 'Missing Friendly Captcha token.';

            return false;
        }

        $response = $this->request('POST', 'https://api.friendlycaptcha.com/api/v1/siteverify', [
            'json' => [
                'secret' => App::parseEnv($this->secretKey),
                'sitekey' => App::parseEnv($this->siteKey),
                'solution' => $responseToken,
            ],
        ]);

        $success = $response['success'] ?? false;

        if (!$success) {
            $this->spamReason = Json::encode($response);
        }

        return $success;
    }

    private function _validateV2(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'frc-captcha-response');

        if (!$responseToken) {
            $this->spamReason = 'Missing Friendly Captcha response.';

            return false;
        }

        $response = $this->request('POST', 'https://global.frcapi.com/api/v2/captcha/siteverify', [
            'headers' => [
                'X-API-Key' => App::parseEnv($this->secretKey),
            ],
            'json' => [
                'sitekey' => App::parseEnv($this->siteKey),
                'response' => $responseToken,
            ],
        ]);

        $success = $response['success'] ?? false;

        if (!$success) {
            $errorDetail = $response['error']['detail'] ?? null;
            $errorCode = $response['error']['error_code'] ?? null;
            $this->spamReason = $errorDetail ?? $errorCode ?? Json::encode($response);
        }

        return $success;
    }
}

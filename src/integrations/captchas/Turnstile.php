<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;

class Turnstile extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'turnstile';
    public ?string $secretKey = null;
    public ?string $siteKey = null;
    public string $scriptLoadingMethod = 'asyncDefer';
    public string $theme = 'auto';
    public string $size = 'normal';
    public string $appearance = 'always';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Cloudflare Turnstile');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Cloudflare Turnstile is a free, privacy-first CAPTCHA alternative that protects your forms from spam and abuse. Find out more via [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/turnstile/_plugin-settings', $variables);
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'fui-captcha formie-turnstile-placeholder',
            'data-turnstile-placeholder' => true,
        ]);
    }

    public function getFrontEndJsVariables(Form $form, FieldLayoutPage $page = null): ?array
    {
        $settings = [
            'siteKey' => App::parseEnv($this->siteKey),
            'formId' => $form->getFormId(),
            'loadingMethod' => $this->scriptLoadingMethod,
            'theme' => $this->theme,
            'size' => $this->size,
            'appearance' => $this->appearance,
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/captchas/turnstile.js');

        return [
            'src' => $src,
            'module' => 'FormieTurnstile',
            'settings' => $settings,
        ];
    }

    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return [
            'formId' => $form->getFormId(),
            'sessionKey' => 'siteKey',
            'value' => App::parseEnv($this->siteKey),
        ];
    }

    public function validateSubmission(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'cf-turnstile-response');

        if (!$responseToken) {
            return false;
        }

        $response = $this->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'json' => [
                'secret' => App::parseEnv($this->secretKey),
                'response' => $responseToken,
                'remoteip' => Craft::$app->getRequest()->getRemoteIP(),
            ],
        ]);

        $success = $response['success'] ?? false;

        if (!$success) {
            $this->spamReason = Json::encode($response);
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
            'scriptLoadingMethod' => $this->scriptLoadingMethod,
            'theme' => $this->theme,
            'size' => $this->size,
            'appearance' => $this->appearance,
        ];
    }    
}

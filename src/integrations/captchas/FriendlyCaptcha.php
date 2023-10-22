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

class FriendlyCaptcha extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'friendlyCaptcha';
    public ?string $secretKey = null;
    public ?string $siteKey = null;


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

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/friendly-captcha/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/friendly-captcha/_form-settings', [
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
            'class' => 'formie-friendly-captcha-placeholder',
            'data-friendly-captcha-placeholder' => true,
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
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/captchas/friendly-captcha.js', true);

        return [
            'src' => $src,
            'module' => 'FormieFriendlyCaptcha',
            'settings' => $settings,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getGqlVariables(Form $form, $page = null): array
    {
        return [
            'formId' => $form->getFormId(),
            'sessionKey' => 'siteKey',
            'value' => App::parseEnv($this->siteKey),
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateSubmission(Submission $submission): bool
    {
        $response = $this->getRequestParam('frc-captcha-solution');

        if (!$response) {
            return false;
        }

        $client = Craft::createGuzzleClient();

        $response = $client->post('https://api.friendlycaptcha.com/api/v1/siteverify', [
            'json' => [
                'secret' => App::parseEnv($this->secretKey),
                'sitekey' => App::parseEnv($this->siteKey),
                'solution' => $response,
            ],
        ]);

        $result = Json::decode((string)$response->getBody(), true);
        $success = $result['success'] ?? false;

        if (!$success) {
            $this->spamReason = Json::encode($result);
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
        ];
    }    
}

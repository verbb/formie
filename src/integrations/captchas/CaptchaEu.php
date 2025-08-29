<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;

class CaptchaEu extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'captchaEu';
    public ?string $restKey = null;
    public ?string $publicKey = null;
    public ?string $endPoint = null;


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Captcha.eu');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Protect your business from bots and spam while ensuring 100% data privacy compliance. No puzzles, no tracking – just invisible security that works. Find out more via [captcha.eu](https://www.captcha.eu/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/captcha-eu/_plugin-settings', $variables);
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'fui-captcha formie-captcha-eu-placeholder',
            'data-captcha-eu-placeholder' => true,
        ]);
    }

    public function getFrontEndJsVariables(Form $form, $page = null): ?array
    {
        $settings = [
            'publicKey' => App::parseEnv($this->publicKey),
            'formId' => $form->getFormId(),
        ];

        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/captchas/captcha-eu.js');

        return [
            'src' => $src,
            'module' => 'FormieCaptchaEu',
            'settings' => $settings,
        ];
    }

    public function validateSubmission(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'captcha-eu-token');

        if (!$responseToken) {
            $this->spamReason = 'Missing Captcha.eu token.';

            return false;
        }

        try {
            $response = $this->request('POST', 'https://w19.captcha.at/validate', [
                'body' => $responseToken,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Rest-Key' => App::parseEnv($this->restKey),
                ],
            ]);

            if (!($response['success'] ?? false)) {
                $this->spamReason = 'Captcha.eu flagged this submission as spam. ' . ($response['detail'] ?? '');
                
                return false;
            }
        } catch (Throwable $e) {
            $this->spamReason = 'Captcha.eu error: ' . $e->getMessage();
            
            return false;
        }

        return true;
    }

}

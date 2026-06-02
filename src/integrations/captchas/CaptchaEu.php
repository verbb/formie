<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\FieldLayoutPage;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;

use Throwable;

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

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-captcha formie-captcha-eu-placeholder',
            'data-captcha-eu-placeholder' => true,
        ]);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$context->form) {
            return null;
        }

        return new ClientModule([
            'id' => 'captcha-eu',
            'config' => [
                'handle' => $this->handle,
                'placeholderSelector' => '[data-captcha-eu-placeholder]',
                'publicKey' => App::parseEnv($this->publicKey),
                'endPoint' => App::parseEnv($this->endPoint ?: 'https://www.captcha.eu'),
                'formId' => $context->form->getRenderId(),
            ],
        ]);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $responseToken = $this->getCaptchaValue($submission, 'captcha-eu-token');

        if (!$responseToken) {
            $this->spamReason = 'Missing Captcha.eu token.';

            return false;
        }

        try {
            $baseUrl = rtrim((string)App::parseEnv($this->endPoint ?: 'https://www.captcha.eu'), '/');
            $response = $this->request('POST', $baseUrl . '/validate', [
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
            return $this->handleValidationException($submission, $e);
        }

        return true;
    }

    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): ?array
    {
        return $this->getFrontEndJsVariables($form, $page);
    }
}

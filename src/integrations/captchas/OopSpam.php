<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;

class OopSpam extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'OopSpam';
    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'OOPSpam');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Privacy-friendly anti-spam solution to safeguard your customers. Find out more via [OOPSpam](https://oopspam.com/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/oop-spam/_plugin-settings', $variables);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $apiKey = Craft::parseEnv($this->apiKey);
        $ip = Craft::$app->getRequest()->getUserIP();
        $userAgent = Craft::$app->getRequest()->getUserAgent();
        $referrer = Craft::$app->getRequest()->getReferrer();

        $message = ArrayHelper::recursiveImplode($submission->getValuesAsString(), ' ');

        $payload = [
            'content' => $message,
            'ip' => $ip,
            'userAgent' => $userAgent,
            'referrer' => $referrer,
        ];

        try {
            $response = $this->request('POST', 'https://api.oopspam.com/v1/spamdetection', [
                'json' => $payload,
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json',
                ],
            ]);

            if (!($response['success'] ?? false)) {
                $this->spamReason = 'OOPSpam validation failed.';

                return false;
            }

            if ($response['isSpam'] ?? false) {
                $this->spamReason = 'OOPSpam flagged this submission as spam.';

                return false;
            }
        } catch (Throwable $e) {
            $this->spamReason = 'OOPSpam error: ' . $e->getMessage();

            return false;
        }

        return true;
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey;
    }

}

<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;

class CleanTalk extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'cleantalk';
    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'CleanTalk');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'No Captcha, no questions, no counting animals, no puzzles, no math. Fight spam! Find out more via [CleanTalk](https://cleantalk.org/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/cleantalk/_plugin-settings', $variables);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $apiKey = Craft::parseEnv($this->apiKey);
        $ip = Craft::$app->getRequest()->getUserIP();
        $userAgent = Craft::$app->getRequest()->getUserAgent();

        $message = ArrayHelper::recursiveImplode($submission->getValuesAsString(), ' ');

        $payload = [
            'method_name' => 'check_message',
            'auth_key' => $apiKey,
            'message' => $message,
            'sender_ip' => $ip,
            'agent' => $userAgent,
            'js_on' => 0, // Optional: JavaScript presence detection
        ];

        try {
            $response = $this->request('POST', 'https://moderate.cleantalk.org/api2.0', [
                'json' => $payload,
            ]);

            if (!($response['allow'] ?? false)) {
                $this->spamReason = $response['comment'] ?? 'CleanTalk flagged this submission as spam.';

                return false;
            }
        } catch (Throwable $e) {
            $this->spamReason = 'CleanTalk validation failed: ' . $e->getMessage();

            return false;
        }

        return true;
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey;
    }

}

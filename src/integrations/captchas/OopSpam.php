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

use Throwable;

class OopSpam extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'OopSpam';
    public ?string $apiKey = null;
    public int $spamThreshold = 3;


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'OOPSpam';
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
        $source = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;

        $message = ArrayHelper::recursiveImplode($submission->getValuesAsString(), ' ');

        $payload = array_filter([
            'content' => $message,
            'senderIP' => $ip,
            'userAgent' => $userAgent,
            'referrer' => $referrer,
            'source' => $source,
        ], static function($value) {
            return $value !== null && $value !== '';
        });

        try {
            $response = $this->request('POST', 'https://api.oopspam.com/v1/spamdetection', [
                'json' => $payload,
                'headers' => [
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            if (isset($response['Score'])) {
                $score = (int)$response['Score'];
                $details = $response['Details'] ?? [];

                if ($score >= $this->spamThreshold) {
                    $this->spamReason = 'OOPSpam flagged this submission as spam.';

                    if ($details) {
                        $this->spamReason .= ' ' . Json::encode($details);
                    }

                    return false;
                }

                return true;
            }

            // Fallback for any legacy/alternate response shapes.
            if (($response['success'] ?? true) === false) {
                $this->spamReason = 'OOPSpam validation failed.';

                if (isset($response['error'])) {
                    $this->spamReason .= ' ' . Json::encode($response['error']);
                }

                return false;
            }

            if ($response['isSpam'] ?? false) {
                $this->spamReason = 'OOPSpam flagged this submission as spam.';

                return false;
            }
        } catch (Throwable $e) {
            return $this->handleValidationException($submission, $e);
        }

        return true;
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey;
    }

}

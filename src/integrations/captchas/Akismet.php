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

class Akismet extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'akismet';
    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Akismet');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Akismet’s next generation machine learning filters out comment, form, and text spam with 99.99% accuracy, so you never have to worry about it again. Find out more via [Akismet](https://akismet.com/).');
    }

    public function getSettingsHtml(): ?string
    {
        $variables = $this->getSettingsHtmlVariables();
        
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/akismet/_plugin-settings', $variables);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $apiKey = Craft::parseEnv($this->apiKey);
        $siteUrl = Craft::$app->getSites()->getCurrentSite()->getBaseUrl();

        $client = $this->getClient();

        $data = [
            'blog' => $siteUrl,
            'user_ip' => Craft::$app->getRequest()->getUserIP(),
            'user_agent' => Craft::$app->getRequest()->getUserAgent(),
            'referrer' => Craft::$app->getRequest()->getReferrer(),
            'comment_type' => 'contact-form',
            'comment_content' => ArrayHelper::recursiveImplode($submission->getValuesAsString(), ' '),
        ];

        try {
            // Lack of JSON response
            $response = $client->post("https://$apiKey.rest.akismet.com/1.1/comment-check", [
                'form_params' => $data,
                'headers' => [
                    'User-Agent' => 'Formie | Akismet/1.0',
                ],
            ]);

            $body = (string)$response->getBody();

            if ($body === 'true') {
                $this->spamReason = 'Akismet flagged this submission as spam.';

                return false;
            }
        } catch (Throwable $e) {
            $this->spamReason = 'Akismet validation failed: ' . $e->getMessage();

            return false;
        }

        return true;
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey;
    }

}

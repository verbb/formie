<?php
namespace verbb\formie\integrations\messaging;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Messaging;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use GuzzleHttp\Client;

class Twilio extends Messaging
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Twilio');
    }


    // Properties
    // =========================================================================

    public ?string $accountSid = null;
    public ?string $authToken = null;
    public ?string $fromNumber = null;
    public ?string $toNumber = null;
    public ?string $message = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Twilio.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        return new IntegrationFormSettings([]);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $accountSid = App::parseEnv($this->accountSid);
            $from = App::parseEnv($this->fromNumber);
            $to = App::parseEnv($this->toNumber);
            $body = $this->_renderMessage($submission);

            $to = Variables::getParsedValue($to, $submission, $submission->getForm());

            $payload = [
                'From' => $from,
                'To' => $to,
                'Body' => $body,
            ];

            $response = $this->deliverPayload($submission, "Accounts/$accountSid/Messages.json", $payload, 'POST', 'form_params');

            if ($response === false) {
                return true;
            }

            if (isset($response['error_code']) && $response['error_code']) {
                $error = Craft::t('formie', 'Twilio returned an error: {message}', [
                    'message' => $response['error_message'] ?? 'Unknown error',
                ]);

                Integration::error($this, $error, true);
                
                return false;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            $accountSid = App::parseEnv($this->accountSid);

            $response = $this->request('GET', "Accounts/$accountSid.json");
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accountSid', 'authToken', 'fromNumber'], 'required'];

        // Validate the following when saving form settings
        $rules[] = [['toNumber', 'message'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $accountSid = App::parseEnv($this->accountSid);
        $authToken = App::parseEnv($this->authToken);

        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.twilio.com/2010-04-01/',
            'auth' => [$accountSid, $authToken],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        return $markdown;
    }
}
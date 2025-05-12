<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use GuzzleHttp\Client;

class Telegram extends Miscellaneous
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Telegram');
    }


    // Properties
    // =========================================================================

    public ?string $botToken = null;
    public ?string $chatId = null;
    public ?string $message = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Telegram.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['botToken'], 'required'];

        // Validate the following when saving form settings
        $rules[] = [['chatId', 'message'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        return new IntegrationFormSettings([]);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $chatId = App::parseEnv($this->chatId);
            $botToken = App::parseEnv($this->botToken);
            $message = $this->_renderMessage($submission);

            if (!$chatId || !$message) {
                Integration::error($this, Craft::t('formie', 'Missing Chat ID or message.'));

                return false;
            }

            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            $response = $this->deliverPayloadRequest($submission, 'sendMessage', $payload, 'POST', 'form_params');

            if ($response === false) {
                return true;
            }

            if ($response->getStatusCode() !== 200) {
                $body = (string)$response->getBody();

                Integration::error($this, Craft::t('formie', 'Telegram API error: {response}', [
                    'response' => $body,
                ]), true);

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
            $response = $this->request('GET', 'getMe');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $botToken = App::parseEnv($this->botToken);

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "https://api.telegram.org/bot{$botToken}/",
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        // Some extra work to get it to play with Slack's mrkdwn
        return str_replace(['*', '__'], ['_', '*'], $markdown);
    }
}
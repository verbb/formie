<?php
namespace verbb\formie\integrations\messaging;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Messaging;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use GuzzleHttp\Client;

class Discord extends Messaging
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Discord';
    }

    public static function supportsConnection(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public ?string $webhookUrl = null;
    public ?string $message = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Discord.');
    }
    
    public function fetchFormSettings(): IntegrationFormSettings
    {
        return new IntegrationFormSettings([]);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $webhookUrl = App::parseEnv($this->webhookUrl);
            $message = $this->_renderMessage($submission);

            $payload = [
                'content' => $message,
            ];

            $response = $this->deliverPayload($submission, $webhookUrl, $payload);

            if ($response === false) {
                return true;
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
            $webhookUrl = App::parseEnv($this->webhookUrl);

            $this->request('GET', $webhookUrl);
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

        // Validate the following when saving form settings
        $rules[] = [['webhookUrl', 'message'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'Webhook URL'),
            'instructions' => Craft::t('formie', 'Enter the {name} webhook URL that will be triggered when a submission is made.', ['name' => $this->displayName()]),
            'name' => 'webhookUrl',
            'required' => true,
        ]);
        $schema[] = SchemaHelper::richTextField([
            'label' => Craft::t('formie', 'Message'),
            'instructions' => Craft::t('formie', 'This text will be sent to {name}.', ['name' => $this->displayName()]),
            'name' => 'message',
            'required' => true,
        ]);

        return $schema;
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
<?php
namespace verbb\formie\integrations\webhooks;

use verbb\formie\base\Integration;
use verbb\formie\base\Webhook as BaseWebhook;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Webhook extends BaseWebhook
{
    // Properties
    // =========================================================================

    public $webhook;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Webhook');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhook'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $submissionContent = [];
            $submissionAttributes = $submission->getAttributes();


            $formAttributes = $submission->getForm()->getAttributes();

            // Trim the form settings a little
            $formAttributes['settings'] = $formAttributes['settings']->toArray();
            unset($formAttributes['settings']['integrations']);

            foreach ($submission->getForm()->getFields() as $field) {
                $value = $submission->getFieldValue($field->handle);
                $submissionContent[$field->handle] = $field->serializeValue($value, $submission);
            }

            $payload = [
                'submission' => array_merge($submissionAttributes, $submissionContent),
                'form' => $formAttributes,
            ];

            $response = $this->getClient()->request('POST', $this->webhook, $payload);
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->getClient()->request('POST', $this->webhook, ['ping' => 1]);
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient();
    }
}
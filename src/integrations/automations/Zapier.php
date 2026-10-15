<?php
namespace verbb\formie\integrations\automations;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Automation;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationResult;

use Craft;

use Throwable;

class Zapier extends Automation
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return 'Zapier';
    }


    // Properties
    // =========================================================================

    #[FormIntegrationSetting]
    #[Sensitive]
    public ?string $webhook = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Zapier.');
    }


    // Protected Methods
    // =========================================================================

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayload($submission, $this->getEndpointUrl($this->webhook, $submission), $payload);

            if ($response === false) {
                return $this->resultForPayload(true);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhook'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'Webhook URL'),
            'instructions' => Craft::t('formie', 'Enter the {name} webhook URL that will be triggered when a submission is made.', ['name' => $this->displayName()]),
            'name' => 'webhook',
            'required' => true,
        ]);

        $schema[] = SchemaHelper::integrationSendTestPayloadButtonField();

        return $schema;
    }
}

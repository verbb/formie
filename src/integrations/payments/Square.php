<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use GuzzleHttp\Client;

use Throwable;
use Exception;

class Square extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Square');
    }
    

    // Properties
    // =========================================================================

    public ?string $applicationId = null;
    public ?string $accessToken = null;
    public ?string $locationId = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->applicationId) && App::parseEnv($this->accessToken) && App::parseEnv($this->locationId);
    }

    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $settings = [
            'applicationId' => App::parseEnv($this->applicationId),
            'locationId' => App::parseEnv($this->locationId),
            'environment' => App::parseBooleanEnv($this->useSandbox) ? 'sandbox' : 'production',
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/square.js'),
            'module' => 'FormieSquare',
            'settings' => $settings,
        ];
    }

    public function processPayment(Submission $submission): bool
    {
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $fieldValue = $this->getPaymentFieldValue($submission);
            $squarePaymentId = $fieldValue['squarePaymentId'] ?? null;

            if (!$squarePaymentId || !is_string($squarePaymentId)) {
                throw new Exception("Missing `squarePaymentId` from payload: {$squarePaymentId}.");
            }

            if (!$amount) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            // Prepare Square API payload
            $idempotencyKey = StringHelper::UUID();
            $formattedAmount = (int)round($amount * 100); // Amount in the smallest currency unit

            $payload = [
                'source_id' => $squarePaymentId,
                'idempotency_key' => $idempotencyKey,
                'amount_money' => [
                    'amount' => $formattedAmount,
                    'currency' => $currency,
                ],
                'autocomplete' => true,
                'note' => "Formie Submission #{$submission->id}",
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'payments', ['json' => $event->payload]);
            $data = $response['payment'] ?? null;

            if (!$data || ($data['status'] ?? '') !== 'COMPLETED') {
                throw new Exception('Payment not completed successfully.');
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $data['id'] ?? '';
            $payment->response = $response;

            Formie::$plugin->getPayments()->savePayment($payment);

            $result = true;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $this->addFieldError($submission, Craft::t('formie', $e->getMessage()));
            
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->reference = null;
            $payment->response = ['message' => $e->getMessage()];

            Formie::$plugin->getPayments()->savePayment($payment);

            return false;
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return true;
        }

        return $result;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'locations');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Payment Currency'),
                'help' => Craft::t('formie', 'Provide the currency to be used for the transaction.'),
                'name' => 'currency',
                'required' => true,
                'validation' => 'required',
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                    static::getCurrencyOptions()
                ),
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Payment Amount'),
                'help' => Craft::t('formie', 'Provide an amount for the transaction. This can be either a fixed value, or derived from a field.'),
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'amountType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                                    ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                                ],
                            ]),
                            SchemaHelper::numberField([
                                'name' => 'amountFixed',
                                'size' => 6,
                                'if' => '$get(amountType).value == ' . Payment::VALUE_TYPE_FIXED,
                            ]),
                            SchemaHelper::fieldSelectField([
                                'name' => 'amountVariable',
                                'fieldTypes' => [
                                    fields\Calculations::class,
                                    fields\Dropdown::class,
                                    fields\Hidden::class,
                                    fields\Number::class,
                                    fields\Radio::class,
                                    fields\SingleLineText::class,
                                ],
                                'if' => '$get(amountType).value == ' . Payment::VALUE_TYPE_DYNAMIC,
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['applicationId', 'accessToken', 'locationId'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $baseUri = $useSandbox ? 'https://connect.squareupsandbox.com/v2/' : 'https://connect.squareup.com/v2/';

        return Craft::createGuzzleClient([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->accessToken),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}

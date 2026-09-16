<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use Exception;
use Throwable;

use GuzzleHttp\Client;

class Square extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Square';
    }
    


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


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

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'square',
            'config' => [
                'applicationId' => App::parseEnv($this->applicationId),
                'locationId' => App::parseEnv($this->locationId),
                'environment' => App::parseBooleanEnv($this->useSandbox) ? 'sandbox' : 'production',
                'requiredInputSuffixes' => ['squarePaymentId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $paymentPayload = $this->getPaymentFieldPayload($submission);
            $squarePaymentId = $paymentPayload->string('squarePaymentId');

            if (!$squarePaymentId || !is_string($squarePaymentId)) {
                throw new Exception('Missing `squarePaymentId` from payload.');
            }

            if (!$amount) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            // Prepare Square API payload
            $formattedAmount = (int)round($amount * 100); // Amount in the smallest currency unit

            $payload = [
                'source_id' => $squarePaymentId,
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

            $operation = 'square:' . $this->handle . ':' . $field->id;
            $response = (new DeliveryAttempt((int)$submission->id, $operation, (string)$submission->uid))->execute(
                $event->payload,
                fn(string $key) => $this->request('POST', 'payments', ['json' => array_merge($event->payload, ['idempotency_key' => $key])]),
                23 * 3600,
                fn(array $result) => $result['payment']['id'] ?? '',
                fn(string $id) => $this->request('GET', 'payments/' . rawurlencode($id)),
            );
            $data = $response['payment'] ?? null;

            if (!$data || ($data['status'] ?? '') !== 'COMPLETED') {
                throw new Exception('Payment not completed successfully.');
            }

            $payment = Formie::$plugin->getPayments()->getPaymentByReference($data['id']) ?? new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $data['id'] ?? '';
            $payment->response = $response;

            if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                throw new \verbb\formie\errors\DeliveryOutcomeUnknownException('Unable to save the accepted payment.');
            }

            $result = true;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $this->addFieldError($submission, $e->getMessage());
            
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $uncertain = $e instanceof \verbb\formie\errors\DeliveryOutcomeUnknownException;
            $payment->status = $uncertain ? PaymentModel::STATUS_PROCESSING : PaymentModel::STATUS_FAILED;
            $payment->reference = null;
            $payment->response = ['message' => $e->getMessage()];

            Formie::$plugin->getPayments()->savePayment($payment);

            return $uncertain
                ? PaymentDecision::pending($e->getMessage(), $this->handle)
                : PaymentDecision::failed($e->getMessage(), $this->handle);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return PaymentDecision::succeeded($this->handle);
        }

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
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

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::comboboxField([
                'label' => Craft::t('formie', 'Payment Currency'),
                'instructions' => Craft::t('formie', 'Provide the currency to be used for the transaction.'),
                'name' => 'currency',
                'required' => true,
                'placeholder' => Craft::t('formie', 'Select an option'),
                'options' => static::getCurrencyOptions(),
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Payment Amount'),
                'instructions' => Craft::t('formie', 'Provide an amount for the transaction. This can be either a fixed value, or derived from a field.'),
                'required' => true,
                'children' => [
                    SchemaHelper::selectField([
                        'name' => 'amountType',
                        'required' => true,
                        'options' => [
                            ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                            ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                        ],
                    ]),
                    SchemaHelper::numberField([
                        'name' => 'amountFixed',
                        'required' => true,
                        'size' => 6,
                        'if' => 'amountType == "' . Payment::VALUE_TYPE_FIXED . '"',
                    ]),
                    SchemaHelper::fieldSelectField([
                        'name' => 'amountVariable',
                        'referenceContext' => 'client',
                        'includeSelectors' => false,
                        'topLevelOnly' => true,
                        'required' => true,
                        'fieldTypes' => [
                            fields\Calculations::class,
                            fields\Dropdown::class,
                            fields\Hidden::class,
                            fields\Number::class,
                            fields\Radio::class,
                            fields\SingleLineText::class,
                        ],
                        'if' => 'amountType == "' . Payment::VALUE_TYPE_DYNAMIC . '"',
                    ]),
                ],
            ]),
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

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
        ];

        return $defaults;
    }

}

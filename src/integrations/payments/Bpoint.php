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

class Bpoint extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'BPOINT');
    }
    

    // Properties
    // =========================================================================

    public ?string $username = null;
    public ?string $password = null;
    public ?string $merchantNumber = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->username) && App::parseEnv($this->password) && App::parseEnv($this->merchantNumber);
    }

    public function processPayment(Submission $submission): bool
    {
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        $amount = $this->getAmount($submission);
        $currency = 'AUD';
        $field = $this->getField();
        $fieldValue = $this->getPaymentFieldValue($submission);
        $cardToken = $fieldValue['bpointToken'] ?? null;

        try {
            if (!$cardToken || !$amount || !$currency) {
                throw new Exception(Craft::t('formie', 'Missing required payment data.'));
            }

            $payload = [
                'CRN' => $submission->id,
                'MerchantNumber' => App::parseEnv($this->merchantNumber),
                'CardNumber' => $cardToken,
                'Amount' => number_format($amount, 2, '.', ''),
                'Action' => 0, // 0 = payment
                'Currency' => strtoupper($currency),
                'TxnType' => 0,
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'txns', [
                'json' => $event->payload,
            ]);

            $status = $response['ResponseCode'] ?? null;

            if ($status !== '00') {
                throw new Exception('Transaction declined: ' . ($response['ResponseText'] ?? 'Unknown error'));
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $response['ReceiptNumber'] ?? '';
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
            $response = $this->request('GET', '/');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function defineGeneralSchema(): array
    {
        return [
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

        $rules[] = [['username', 'password', 'merchantNumber'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $username = App::parseEnv($this->username);
        $password = App::parseEnv($this->password);
        $merchant = App::parseEnv($this->merchantNumber);

        return Craft::createGuzzleClient([
            'base_uri' => 'https://www.bpoint.com.au/webapi/v2/',
            'auth' => [$username . '|' . $merchant, $password],
        ]);
    }
}

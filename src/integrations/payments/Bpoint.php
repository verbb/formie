<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\ModifySubFieldsEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\Component;
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
    public const EVENT_MODIFY_PAYMENT_SUBFIELDS = 'modifyPaymentSubfields';


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

    public function processPayment(Submission $submission): PaymentDecision
    {
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        $amount = $this->getAmount($submission);
        $currency = strtoupper((string)($this->getFieldSetting('currency') ?: 'AUD'));
        $field = $this->getField();
        $paymentPayload = $this->getPaymentFieldPayload($submission);
        $cardToken = $paymentPayload->string('bpointToken') ?? '';

        try {
            if (!$cardToken || !$amount || !$currency) {
                throw new Exception(Craft::t('formie', 'Missing required payment data.'));
            }

            $txnReq = [
                'Action' => 'payment',
                'Amount' => (int)round($amount * 100),
                'Currency' => $currency,
                'MerchantReference' => "Formie Submission #{$submission->id}",
                'Crn1' => (string)$submission->id,
            ];

            if (is_string($cardToken) && Json::isJsonObject($cardToken)) {
                if (!$this->_canProcessRawCardPayload()) {
                    throw new Exception(Craft::t('formie', 'BPOINT raw card payloads are disabled outside development. Use DVToken/AuthKey flow, or set `FORMIE_BPOINT_ALLOW_RAW_CARD_DATA=true` for controlled non-production testing.'));
                }

                $cardData = Json::decode($cardToken);

                $cardNumber = trim((string)($cardData['cardNumber'] ?? ''));
                $expiryDate = $this->_normalizeExpiryDate((string)($cardData['expiryDate'] ?? ''));
                $cvn = trim((string)($cardData['cvn'] ?? $cardData['securityCode'] ?? ''));

                if (!$cardNumber || !$expiryDate || !$cvn) {
                    throw new Exception(Craft::t('formie', 'Invalid BPOINT card details.'));
                }

                $txnReq['CardDetails'] = [
                    'CardHolderName' => trim((string)($cardData['cardholderName'] ?? '')),
                    'CardNumber' => $cardNumber,
                    'ExpiryDate' => $expiryDate,
                    'Cvn' => $cvn,
                ];
            } else {
                // Support legacy behavior where `bpointToken` contains a DVToken string.
                $txnReq['DVTokenData'] = [
                    'DVToken' => trim((string)$cardToken),
                    'UpdateDVTokenExpiryDate' => false,
                ];
            }

            $payload = [
                'TxnReq' => $txnReq,
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

            $apiResponse = $response['APIResponse'] ?? [];
            $txnResponse = $response['TxnResp'] ?? [];
            $apiResponseCode = (string)($apiResponse['ResponseCode'] ?? '');
            $txnResponseCode = (string)($txnResponse['ResponseCode'] ?? '');
            $bankResponseCode = (string)($txnResponse['BankResponseCode'] ?? '');
            $responseCode = $txnResponseCode ?: $apiResponseCode;
            $isApproved = $responseCode === '0' || $bankResponseCode === '00';

            if (!$isApproved) {
                throw new Exception('Transaction declined: ' . ($txnResponse['ResponseText'] ?? $apiResponse['ResponseText'] ?? 'Unknown error'));
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $txnResponse['ReceiptNumber'] ?? $txnResponse['TxnNumber'] ?? '';
            $payment->response = $response;

            Formie::$plugin->getPayments()->savePayment($payment);

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

            return PaymentDecision::failed($e->getMessage(), $this->handle);
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
            $response = $this->request('GET', '/');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'bpoint',
            'config' => [
                'requiredInputSuffixes' => ['bpointToken'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::comboboxField([
                'label' => Craft::t('formie', 'Payment Currency'),
                'instructions' => Craft::t('formie', 'Provide the currency to be used for the transaction.'),
                'name' => 'currency',
                'required' => true,
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                    static::getCurrencyOptions()
                ),
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

    public function getPaymentSubFields($field): array
    {
        $subFields = [];
        $rowConfigs = [
            [
                [
                    'type' => fields\SingleLineText::class,
                    'name' => Craft::t('formie', 'Cardholder Name'),
                    'handle' => 'cardName',
                    'required' => true,
                    'inputAttributes' => [
                        ['label' => 'data-bpoint-card', 'value' => 'cardholder-name'],
                        ['label' => 'name', 'value' => false],
                        ['label' => 'autocomplete', 'value' => 'cc-name'],
                    ],
                ],
            ],
            [
                [
                    'type' => fields\SingleLineText::class,
                    'name' => Craft::t('formie', 'Card Number'),
                    'handle' => 'cardNumber',
                    'required' => true,
                    'placeholder' => '•••• •••• •••• ••••',
                    'inputAttributes' => [
                        ['label' => 'data-bpoint-card', 'value' => 'card-number'],
                        ['label' => 'name', 'value' => false],
                        ['label' => 'autocomplete', 'value' => 'cc-number'],
                    ],
                ],
                [
                    'type' => fields\SingleLineText::class,
                    'name' => Craft::t('formie', 'Expiry'),
                    'handle' => 'cardExpiry',
                    'required' => true,
                    'placeholder' => 'MM/YY',
                    'inputAttributes' => [
                        ['label' => 'data-bpoint-card', 'value' => 'expiry-date'],
                        ['label' => 'name', 'value' => false],
                        ['label' => 'autocomplete', 'value' => 'cc-exp'],
                    ],
                ],
                [
                    'type' => fields\SingleLineText::class,
                    'name' => Craft::t('formie', 'CVC'),
                    'handle' => 'cardCvc',
                    'required' => true,
                    'placeholder' => '•••',
                    'inputAttributes' => [
                        ['label' => 'data-bpoint-card', 'value' => 'security-code'],
                        ['label' => 'name', 'value' => false],
                        ['label' => 'autocomplete', 'value' => 'cc-csc'],
                    ],
                ],
            ],
        ];

        foreach ($rowConfigs as $key => $rowConfig) {
            foreach ($rowConfig as $config) {
                $subField = Component::createComponent($config, FieldInterface::class);
                $subFields[$key][] = $subField->withParentField($field);
            }
        }

        $event = new ModifySubFieldsEvent([
            'fields' => $subFields,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_PAYMENT_SUBFIELDS, $event);

        return $event->fields;
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

    protected function definePaymentFieldSettingsDefaults(): array
    {
        return [
            'currency' => 'AUD',
            'amountType' => self::VALUE_TYPE_FIXED,
        ];
    }


    // Private Methods
    // =========================================================================

    private function _normalizeExpiryDate(string $expiryDate): string
    {
        $sanitized = preg_replace('/\D+/', '', $expiryDate) ?? '';

        if (strlen($sanitized) < 4) {
            return '';
        }

        return substr($sanitized, 0, 4);
    }

    private function _canProcessRawCardPayload(): bool
    {
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            return true;
        }

        return App::parseBooleanEnv(App::env('FORMIE_BPOINT_ALLOW_RAW_CARD_DATA'));
    }
}

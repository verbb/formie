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

use GuzzleHttp\Client;

use Throwable;
use Exception;

class Eway extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    public const EVENT_MODIFY_PAYMENT_SUBFIELDS = 'modifyPaymentSubfields';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Eway');
    }

    // public static function supportsConnection(): bool
    // {
    //     return false;
    // }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiPassword =  null;
    public ?string $clientSideEncryptionKey =  null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->apiKey) && App::parseEnv($this->apiPassword) && App::parseEnv($this->clientSideEncryptionKey);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'eway',
            'config' => [
                'cseKey' => App::parseEnv($this->clientSideEncryptionKey),
                'requiredInputSuffixes' => ['ewayTokenData'],
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
            $cardData = $paymentPayload->array('ewayTokenData');

            if (!$cardData || !is_array($cardData)) {
                throw new Exception('Invalid card details payload.');
            }

            $cardNumber = trim((string)($cardData['cardNumber'] ?? ''));
            $securityCode = trim((string)($cardData['securityCode'] ?? ''));
            [$expiryMonth, $expiryYear] = $this->_normalizeExpiry((string)($cardData['expiryDate'] ?? ''));

            if ($cardNumber === '' || $securityCode === '' || $expiryMonth === '' || $expiryYear === '') {
                throw new Exception('Invalid card details. Please verify card number, expiry, and CVC.');
            }

            $payload = [
                'Customer' => [
                    'CardDetails' => [
                        'Name' => $cardData['cardholderName'] ?? '',
                        'Number' => $cardNumber,
                        'ExpiryMonth' => $expiryMonth,
                        'ExpiryYear' => $expiryYear,
                        'CVN' => $securityCode,
                    ],
                ],
                'Payment' => [
                    'TotalAmount' => $amount * 100, // in cents
                    'CurrencyCode' => strtoupper($currency),
                ],
                'Method' => 'ProcessPayment',
                'TransactionType' => 'Purchase',
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'Transaction', ['json' => $event->payload]);

            $transactionStatus = $response['TransactionStatus'] ?? false;

            if (!$transactionStatus) {
                throw new Exception($this->_extractGatewayErrorMessage($response));
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $response['TransactionID'] ?? '';
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
            $payment->code = $this->_extractGatewayErrorCode($response);
            $payment->message = $e->getMessage();
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
            $response = $this->request('POST', 'AccessCodes');
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
                    'label' => Craft::t('formie', 'Cardholder Name'),
                    'handle' => 'cardName',
                    'required' => true,
                    'inputAttributes' => [
                        [
                            'label' => 'data-eway-card',
                            'value' => 'cardholder-name',
                        ],
                        [
                            'label' => 'name',
                            'value' => false,
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'cc-name',
                        ],
                    ],
                ],
            ],
            [
                [
                    'type' => fields\SingleLineText::class,
                    'label' => Craft::t('formie', 'Card Number'),
                    'handle' => 'cardNumber',
                    'required' => true,
                    'placeholder' => '•••• •••• •••• ••••',
                    'inputAttributes' => [
                        [
                            'label' => 'data-eway-card',
                            'value' => 'card-number',
                        ],
                        [
                            'label' => 'name',
                            'value' => false,
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'cc-number',
                        ],
                    ],
                ],
                [
                    'type' => fields\SingleLineText::class,
                    'label' => Craft::t('formie', 'Expiry'),
                    'handle' => 'cardExpiry',
                    'required' => true,
                    'placeholder' => 'MMYY',
                    'inputAttributes' => [
                        [
                            'label' => 'data-eway-card',
                            'value' => 'expiry-date',
                        ],
                        [
                            'label' => 'name',
                            'value' => false,
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'cc-exp',
                        ],
                    ],
                ],
                [
                    'type' => fields\SingleLineText::class,
                    'label' => Craft::t('formie', 'CVC'),
                    'handle' => 'cardCvc',
                    'required' => true,
                    'placeholder' => '•••',
                    'inputAttributes' => [
                        [
                            'label' => 'data-eway-card',
                            'value' => 'security-code',
                        ],
                        [
                            'label' => 'name',
                            'value' => false,
                        ],
                        [
                            'label' => 'autocomplete',
                            'value' => 'cc-csc',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($rowConfigs as $key => $rowConfig) {
            foreach ($rowConfig as $config) {
                $subField = Component::createComponent($config, FieldInterface::class);

                // Ensure we set the parent field instance to handle the nested nature of subfields
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

        $rules[] = [['apiKey', 'apiPassword', 'clientSideEncryptionKey'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $baseUri = $useSandbox ? 'https://api.sandbox.ewaypayments.com/' : 'https://api.ewaypayments.com/';

        return Craft::createGuzzleClient([
            'base_uri' => $baseUri,
            'auth' => [App::parseEnv($this->apiKey), App::parseEnv($this->apiPassword)],
        ]);
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
        ];

        return $defaults;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeExpiry(string $expiryDate): array
    {
        $digits = preg_replace('/\D+/', '', $expiryDate) ?? '';

        if (strlen($digits) < 4) {
            return ['', ''];
        }

        $digits = substr($digits, 0, 4);

        return [substr($digits, 0, 2), substr($digits, 2, 2)];
    }

    private function _extractGatewayErrorCode(?array $response): ?string
    {
        if (!$response) {
            return null;
        }

        $errors = trim((string)($response['Errors'] ?? ''));

        if ($errors === '') {
            return null;
        }

        $first = trim(explode(',', $errors)[0] ?? '');

        return $first !== '' ? $first : null;
    }

    private function _extractGatewayErrorMessage(?array $response): string
    {
        if (!$response) {
            return 'Unknown error';
        }

        $responseMessage = trim((string)($response['ResponseMessage'] ?? ''));
        if ($responseMessage !== '') {
            return $responseMessage;
        }

        $errorCode = $this->_extractGatewayErrorCode($response);
        if (!$errorCode) {
            return 'Unknown error';
        }

        $map = [
            'V6110' => 'Invalid card number (V6110).',
        ];

        return $map[$errorCode] ?? ("Gateway error ({$errorCode}).");
    }
}

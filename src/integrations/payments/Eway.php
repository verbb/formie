<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
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

class Eway extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    public const EVENT_MODIFY_FRONT_END_SUBFIELDS = 'modifyFrontEndSubfields';


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

    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $settings = [
            'cseKey' => App::parseEnv($this->clientSideEncryptionKey),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/eway.js'),
            'module' => 'FormieEway',
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
            $cardData = $fieldValue['ewayTokenData'] ?? '';

            if (is_string($cardData) && Json::isJsonObject($cardData)) {
                $cardData = Json::decode($cardData);
            }

            if (!$cardData || !is_array($cardData)) {
                throw new Exception("Invalid card details: {$cardData}.");
            }

            $expiryDate = $cardData['expiryDate'] ?? '';
            $expiryMonth = trim(explode('/', $expiryDate)[0] ?? '');
            $expiryYear = trim(explode('/', $expiryDate)[1] ?? '');

            $payload = [
                'Customer' => [
                    'CardDetails' => [
                        'Name' => $cardData['cardholderName'] ?? '',
                        'Number' => $cardData['cardNumber'] ?? '',
                        'ExpiryMonth' => $expiryMonth,
                        'ExpiryYear' => $expiryYear,
                        'CVN' => $cardData['securityCode'] ?? '',
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
                $errors = $response['ResponseMessage'] ?? 'Unknown error';

                throw new Exception($errors);
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
            $response = $this->request('POST', 'AccessCodes');
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

    public function getFrontEndSubfields($field, $context): array
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
                    'name' => Craft::t('formie', 'Card Number'),
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
                    'name' => Craft::t('formie', 'Expiry'),
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
                    'name' => Craft::t('formie', 'CVC'),
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
                $subField = Component::createComponent($config, FormFieldInterface::class);

                // Ensure we set the parent field instance to handle the nested nature of subfields
                $subField->setParentField($field);

                $subFields[$key][] = $subField;
            }
        }

        $event = new ModifyFrontEndSubfieldsEvent([
            'field' => $this,
            'rows' => $subFields,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_FRONT_END_SUBFIELDS, $event);

        return $event->rows;
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
}

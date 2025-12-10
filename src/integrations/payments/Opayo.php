<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use Throwable;
use Exception;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

class Opayo extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';

    // https://stripe.com/docs/currencies#zero-decimal
    private const ZERO_DECIMAL_CURRENCIES = ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Opayo');
    }
    
    public static function toOpayoAmount(float $amount, string $currency): float
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES)) {
            return $amount;
        }

        return ceil($amount * 100);
    }

    public static function fromOpayoAmount(float $amount, string $currency): float
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES)) {
            return $amount;
        }

        return $amount * 0.01;
    }
    

    // Properties
    // =========================================================================

    public ?string $vendorName = null;
    public ?string $integrationKey = null;
    public ?string $integrationPassword = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function supportsCallbacks(): bool
    {
        return true;
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->vendorName) && App::parseEnv($this->integrationKey) && App::parseEnv($this->integrationPassword);
    }

    public function getReturnUrl(): string
    {
        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return UrlHelper::actionUrl('formie/payment-webhooks/process-callback', ['handle' => $this->handle]);
        }

        return UrlHelper::siteUrl('formie/payment-webhooks/process-callback', ['handle' => $this->handle]);
    }

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $settings = [
            'handle' => $this->handle,
            'useSandbox' => App::parseBooleanEnv($this->useSandbox),
            'currency' => $this->getFieldSetting('currency'),
            'amountType' => $this->getFieldSetting('amountType'),
            'amountFixed' => $this->getFieldSetting('amountFixed'),
            'amountVariable' => $this->getFieldSetting('amountVariable'),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/opayo.js'),
            'module' => 'FormieOpayo',
            'settings' => $settings,
        ];
    }

    public function getAmount(Submission $submission): float
    {
        // Ensure the amount is converted to Stripe for zero-decimal currencies
        return self::toOpayoAmount(parent::getAmount($submission), $this->getCurrency($submission));
    }

    public function getCurrency(Submission $submission): ?string
    {
        return (string)$this->getFieldSetting('currency');
    }

    public function processPayment(Submission $submission): bool
    {
        $payload = [];
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }        

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getCurrency($submission);

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $fieldValue = $this->getPaymentFieldValue($submission);
            $opayoTokenId = $fieldValue['opayoTokenId'] ?? null;
            $opayoSessionKey = $fieldValue['opayoSessionKey'] ?? null;
            $opayo3DSComplete = $fieldValue['opayo3DSComplete'] ?? null;

            // Check if we've returned from a 3DS challenge. We've already captured the payment, and recorded the successful payment.
            if ($opayo3DSComplete) {
                // Verify that we indeed have a verified payment - just in case people are trying to send through _any_ value
                if (Formie::$plugin->getPayments()->getPaymentByReference($opayo3DSComplete)) {
                    // We can return true here to allow the form to continue with the submission process
                    return true;
                } else {
                    throw new Exception('Unable to find payment by "' . $opayo3DSComplete . '".');
                }
            }

            if (!$opayoTokenId || !is_string($opayoTokenId)) {
                throw new Exception("Missing `opayoTokenId` from payload: {$opayoTokenId}.");
            }

            if (!$opayoSessionKey || !is_string($opayoSessionKey)) {
                throw new Exception("Missing `opayoSessionKey` from payload: {$opayoSessionKey}.");
            }

            if (!$amount) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            // Generate the payload data
            $payload = $this->_getPayload($opayoSessionKey, $opayoTokenId, $submission, $amount, $currency);

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $payload = $event->payload;

            // Trigger the Opato payment to be captured
            $response = $this->request('POST', 'transactions', ['json' => $payload]);

            $status = $response['status'] ?? null;
            $statusDetail = $response['statusDetail'] ?? null;

            // Was this a 3DS challenge? We need to redirect the user
            $acsUrl = $response['acsUrl'] ?? null;

            if ($acsUrl) {
                $payment = new PaymentModel();
                $payment->integrationId = $this->id;
                $payment->submissionId = $submission->id;
                $payment->fieldId = $field->id;
                $payment->amount = self::fromOpayoAmount($amount, $currency);
                $payment->currency = $currency;
                $payment->reference = $response['transactionId'] ?? '';
                $payment->response = $response;
                $payment->status = PaymentModel::STATUS_PENDING;

                Formie::$plugin->getPayments()->savePayment($payment);

                $threeDSSessionData = [
                    'submissionId' => $submission->id,
                    'fieldId' => $field->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'reference' => $response['transactionId'] ?? '',
                ];

                // Store the data we need for 3DS against the form, which is added is the Ajax response
                $submission->getForm()->addSubmitData([
                    'event' => 'FormiePaymentOpayo3DS',
                    'data' => [
                        'acsUrl' => $acsUrl,
                        'creq' => $response['cReq'] ?? '',
                        'returnUrl' => $this->getReturnUrl(),
                        'threeDSSessionData' => base64_encode(Json::encode($threeDSSessionData)),
                    ],
                ]);

                // Add an error to the form to ensure it doesn't proceed, and the 3DS popup is shown
                $this->addFieldError($submission, Craft::t('formie', 'This payment requires 3D Secure authentication. Please follow the instructions on-screen to continue.'));

                return false;
            }

            if ($status !== 'Ok') {
                throw new Exception(StringHelper::titleize($status) . ': ' . $statusDetail);
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromOpayoAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->reference = $response['transactionId'] ?? '';
            $payment->response = $response;
            $payment->status = PaymentModel::STATUS_SUCCESS;

            Formie::$plugin->getPayments()->savePayment($payment);

            $result = true;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”. Payload: “{payload}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
                'payload' => Json::encode($payload),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = (strlen($e->getMessage()) > 30) ? substr($e->getMessage(), 0, 30) . '...' : '';
            $this->addFieldError($submission, Craft::t('formie', 'A payment error has occurred “{message}”.', ['message' => $message]));
            
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromOpayoAmount($amount, $currency);
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

    public function processCallback(): Response
    {
        $request = Craft::$app->getRequest();
        $callbackResponse = Craft::$app->getResponse();
        $callbackResponse->format = Response::FORMAT_RAW;

        // Check to see if we're requesting a merchant session key - the first step
        if ($request->getParam('merchantSessionKey')) {
            $callbackResponse->format = Response::FORMAT_JSON;

            try {
                $response = $this->request('POST', 'merchant-session-keys', [
                    'json' => ['vendorName' => App::parseEnv($this->vendorName)],
                ]);

                $callbackResponse->data = [
                    'merchantSessionKey' => $response['merchantSessionKey'] ?? null,
                ];
            } catch (Throwable $e) {
                $callbackResponse->data = [
                    'error' => $e->getMessage(),
                ];
            }

            return $callbackResponse;
        }
        
        $response = [];
        $responseData = [];

        $cres = $request->getParam('cres');
        $data = $request->getParam('threeDSSessionData');

        if (!$cres || !$data) {
            Integration::error($this, 'Callback not signed or signing secret not set.');
            $callbackResponse->data = 'ok';

            return $callbackResponse;
        }

        // Get the data sent to Opayo
        $data = Json::decode(base64_decode($data));
        $submissionId = $data['submissionId'] ?? null;
        $fieldId = $data['fieldId'] ?? null;
        $amount = $data['amount'] ?? null;
        $currency = $data['currency'] ?? null;
        $transactionId = $data['reference'] ?? null;

        try {
            // Process the 3DS challenge
            $response = $this->request('POST', "transactions/$transactionId/3d-secure-challenge", [
                'json' => [
                    'threeDSSessionData' => $transactionId,
                    'cRes' => $cres,
                ],
            ]);

            $status = $response['status'] ?? null;
            $statusDetail = $response['statusDetail'] ?? null;

            if ($status !== 'Ok') {
                throw new Exception(StringHelper::titleize($status) . ': ' . $statusDetail);
            }

            // Record the payment
            $payment = Formie::$plugin->getPayments()->getPaymentByReference($transactionId);

            if ($payment) {
                $payment->status = PaymentModel::STATUS_SUCCESS;
                $payment->reference = $transactionId;
                $payment->response = $response;

                Formie::$plugin->getPayments()->savePayment($payment);
            } else {
                throw new Exception('Unable to find payment by "' . $transactionId . '".');
            }

            $responseData['success'] = true;
            $responseData['transactionId'] = $transactionId;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}. Payload: “{payload}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
                'payload' => Json::encode($data ?? []),
            ]));

            $shouldShowError = true;

            // There's a scenario we need to ignore with Opayo, where we get the response `{"description":"Operation not allowed for this transaction","code":1017}`
            // but the transaction has actually gone through successfully.
            if ($e instanceof RequestException && $e->getResponse()) {
                $rawResponse = $e->getResponse();
                $messageText = (string)$rawResponse->getBody()->getContents();
                $response = Json::decode($messageText);
                $code = $response['code'] ?? null;

                if ($code == '1017') {
                    $shouldShowError = false;

                    // Record the payment
                    $payment = Formie::$plugin->getPayments()->getPaymentByReference($transactionId);

                    if ($payment) {
                        $payment->status = PaymentModel::STATUS_SUCCESS;
                        $payment->reference = $transactionId;
                        $payment->response = $response;

                        Formie::$plugin->getPayments()->savePayment($payment);
                    }

                    $responseData['success'] = true;
                    $responseData['transactionId'] = $transactionId;
                }
            }

            if ($shouldShowError) {
                Integration::apiError($this, $e, $this->throwApiError);

                $error = ['message' => $e->getMessage()];

                $payment = new PaymentModel();
                $payment->response = $error;

                // Try and update the existing pending payment to failed, and merge content
                if ($transactionId) {
                    if ($payment = Formie::$plugin->getPayments()->getPaymentByReference($transactionId)) {
                        if (is_array($payment->response)) {
                            $payment->response['message'] = $e->getMessage();
                        }
                    }
                }
                
                $payment->integrationId = $this->id;
                $payment->submissionId = $submissionId;
                $payment->fieldId = $fieldId;
                $payment->amount = self::fromOpayoAmount($amount, $currency);
                $payment->currency = $currency;
                $payment->status = PaymentModel::STATUS_FAILED;
                $payment->reference = $transactionId;

                Formie::$plugin->getPayments()->savePayment($payment);

                $responseData['error'] = $error;
            }
        }

        // Send back some JS to trigger the iframe to close, and the submission to submit
        $callbackResponse->data = '<script>window.parent.postMessage({ message: "FormiePaymentOpayo3DSResponse", value: ' . Json::encode($responseData) . ' }, "*");</script>';

        return $callbackResponse;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('POST', 'merchant-session-keys', [
                'json' => ['vendorName' => App::parseEnv($this->vendorName)],
            ]);
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

    public function defineSettingsSchema(): array
    {
        return [
            [
                '$formkit' => 'staticTable',
                'label' => Craft::t('formie', 'Billing Details'),
                'help' => Craft::t('formie', 'Whether to send billing details alongside the payment.'),
                'name' => 'billingDetails',
                'columns' => [
                    'heading' => [
                        'type' => 'heading',
                        'heading' => Craft::t('formie', 'Billing Info'),
                        'class' => 'heading-cell thin',
                    ],
                    'value' => [
                        'type' => 'fieldSelect',
                        'label' => Craft::t('formie', 'Field'),
                        'class' => 'select-cell',
                    ],
                ],
                'rows' => [
                    'billingName' => [
                        'heading' => Craft::t('formie', 'Billing Name'),
                        'value' => '',
                    ],
                    'billingEmail' => [
                        'heading' => Craft::t('formie', 'Billing Email'),
                        'value' => '',
                    ],
                    'billingAddress' => [
                        'heading' => Craft::t('formie', 'Billing Address'),
                        'value' => '',
                    ],
                ],
            ],
        ];
    }

    public function getFrontEndSubFields($field, $context): array
    {
        $subFields = [];

        $rowConfigs = [
            [
                [
                    'type' => SingleLineText::class,
                    'label' => Craft::t('formie', 'Cardholder Name'),
                    'handle' => 'cardName',
                    'required' => true,
                    'inputAttributes' => [
                        [
                            'label' => 'data-opayo-card',
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
                    'type' => SingleLineText::class,
                    'label' => Craft::t('formie', 'Card Number'),
                    'handle' => 'cardNumber',
                    'required' => true,
                    'placeholder' => '•••• •••• •••• ••••',
                    'inputAttributes' => [
                        [
                            'label' => 'data-opayo-card',
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
                    'type' => SingleLineText::class,
                    'label' => Craft::t('formie', 'Expiry'),
                    'handle' => 'cardExpiry',
                    'required' => true,
                    'placeholder' => 'MMYY',
                    'inputAttributes' => [
                        [
                            'label' => 'data-opayo-card',
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
                    'type' => SingleLineText::class,
                    'label' => Craft::t('formie', 'CVC'),
                    'handle' => 'cardCvc',
                    'required' => true,
                    'placeholder' => '•••',
                    'inputAttributes' => [
                        [
                            'label' => 'data-opayo-card',
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
                $subField->setParentField($field);

                $subFields[$key][] = $subField;
            }
        }

        return $subFields;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['vendorName', 'integrationKey', 'integrationPassword'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $url = $useSandbox ? 'https://pi-test.sagepay.com/' : 'https://pi-live.sagepay.com/';

        return Craft::createGuzzleClient([
            'base_uri' => $url . 'api/v1/',
            'auth' => [App::parseEnv($this->integrationKey), App::parseEnv($this->integrationPassword)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getPayload(string $opayoSessionKey, string $opayoTokenId, Submission $submission, int $amount, string $currency): array
    {
        $payload = [
            'transactionType' => 'Payment',
            'paymentMethod' => [
                'card' => [
                    'merchantSessionKey' => $opayoSessionKey,
                    'cardIdentifier' => $opayoTokenId,
                ],
            ],
            'vendorTxCode' => App::parseEnv($this->vendorName) . '-' . $submission->id . '-' . StringHelper::randomString(12),
            'amount' => $amount,
            'currency' => $currency,
            'description' => $submission->id,
            'apply3DSecure' => 'UseMSPSetting',
            'strongCustomerAuthentication' => $this->_getRequestDetail(),

            // Set defaults, required by API
            'customerEMail' => 'customer@example.com',
            'customerFirstName' => 'Customer',
            'customerLastName' => 'Name',
            'billingAddress' => [
                'address1' => '407 St. John Street',
                'city' => 'London',
                'postalCode' => 'EC1V 4AB',
                'country' => 'GB',
            ],
        ];

        $billingName = $this->getFieldSetting('billingDetails.billingName');
        $billingAddress = $this->getFieldSetting('billingDetails.billingAddress');
        $billingEmail = $this->getFieldSetting('billingDetails.billingEmail');

        // Just in case we're picking the string version of the Address field value (due to Vue restrictions)
        // ensure that we refer to the actual Address field model value as we need the "bits".
        $billingName = str_replace('.__toString', '', $billingName);
        $billingAddress = str_replace('.__toString', '', $billingAddress);

        if ($billingEmail) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_STRING;

            $email = $this->getMappedFieldValue($billingEmail, $submission, $integrationField);
            
            // Only set if we have a valid email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $payload['customerEMail'] = $email;
            }
        }

        if ($billingName) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_ARRAY;

            $fullName = $this->getMappedFieldValue($billingName, $submission, $integrationField);

            if ($fullName && is_array($fullName)) {
                if ($firstName = ArrayHelper::remove($fullName, 'firstName')) {
                    $payload['customerFirstName'] = $firstName;
                }

                if ($lastName = ArrayHelper::remove($fullName, 'lastName')) {
                    $payload['customerLastName'] = $lastName;
                }
            }
        }

        if ($billingAddress) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_ARRAY;

            $address = $this->getMappedFieldValue($billingAddress, $submission, $integrationField);

            if ($address && is_array($address)) {
                $payload['billingAddress']['address1'] = trim((string)ArrayHelper::remove($address, 'address1'));
                $payload['billingAddress']['city'] = trim((string)ArrayHelper::remove($address, 'city'));
                $payload['billingAddress']['postalCode'] = trim((string)ArrayHelper::remove($address, 'zip'));
                $payload['billingAddress']['state'] = trim((string)ArrayHelper::remove($address, 'state'));
                $payload['billingAddress']['country'] = trim((string)ArrayHelper::remove($address, 'country'));
            }
        }

        // Testing only
        // $payload['billingAddress']['address1'] = '88';
        // $payload['billingAddress']['postalCode'] = '412';

        // All values need to be handled a little bit...
        $payload['billingAddress']['address1'] = trim(substr($payload['billingAddress']['address1'], 0, 20));
        $payload['billingAddress']['city'] = trim(substr($payload['billingAddress']['city'], 0, 20));
        $payload['billingAddress']['postalCode'] = trim(substr($payload['billingAddress']['postalCode'], 0, 8));

        // If mapping the country, we need to convert from full-text to abbreviation
        if ($payload['billingAddress']['country'] && strlen($payload['billingAddress']['country']) > 3) {
            $countryRepository = new CountryRepository();

            foreach ($countryRepository->getAll() as $country) {
                if ($country->getName() === $payload['billingAddress']['country']) {
                    $payload['billingAddress']['country'] = $country->getCountryCode();
                }
            }
        }

        // If mapping the state, we need to convert from full-text to abbreviation
        if ($payload['billingAddress']['state'] && strlen($payload['billingAddress']['state']) > 3) {
            $subdivisionRepository = new SubdivisionRepository();
            $states = $subdivisionRepository->getAll([$payload['billingAddress']['country']]);

            foreach ($states as $state) {
                if ($state->getName() === $payload['billingAddress']['state']) {
                    $payload['billingAddress']['state'] = $state->getCode();
                }
            }
        }

        // State is only required for US addresses, and will likely throw errors for other countries
        // https://www.opayo.co.uk/support/error-codes/3130-%C2%A0-billingstate-value-too-long
        if ($payload['billingAddress']['country'] !== 'US') {
            unset($payload['billingAddress']['state']);
        }

        return $payload;
    }

    private function _getRequestDetail(): array
    {
        return [
            'website' => Craft::$app->getRequest()->getOrigin(),
            'notificationURL' => $this->getReturnUrl(),
            'browserIP' => Craft::$app->getRequest()->getUserIP(),
            'browserAcceptHeader' => Craft::$app->getRequest()->getHeaders()->get('accept'),
            'browserJavascriptEnabled' => false,
            'browserJavaEnabled' => false,
            'browserLanguage' => Craft::$app->language,
            'browserColorDepth' => '16',
            'browserScreenHeight' => '768',
            'browserScreenWidth' => '1200',
            'browserTZ' => '+300',
            'browserUserAgent' => Craft::$app->getRequest()->getUserAgent(),
            'challengeWindowSize' => 'Small',
            'threeDSRequestorChallengeInd' => '02',
            'requestSCAExemption' => false,
            'transType' => 'GoodsAndServicePurchase',
            'threeDSRequestorDecReqInd' => 'N',
        ];
    }
}

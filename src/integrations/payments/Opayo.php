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
use verbb\formie\fields\values\AddressFieldValue;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\TooManyRequestsHttpException;

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
    private const MERCHANT_SESSION_RATE_LIMIT = 20;
    private const MERCHANT_SESSION_RATE_WINDOW_SECONDS = 60;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Opayo');
    }

    public function requiresAjaxSubmission(): bool
    {
        return true;
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

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'opayo',
            'config' => [
                'handle' => $this->handle,
                'useSandbox' => App::parseBooleanEnv($this->useSandbox),
                'currency' => $this->getFieldSetting('currency'),
                'amountType' => $this->getFieldSetting('amountType'),
                'amountFixed' => $this->getFieldSetting('amountFixed'),
                'amountVariable' => $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable')),
                'sessionToken' => PaymentAccess::issueProviderSessionToken('opayo', (int)$this->id, (string)$this->handle),
                'requiredInputSuffixes' => ['opayoTokenId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return ['opayoSessionKey', 'opayo3DSComplete'];
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

    public function processPayment(Submission $submission): PaymentDecision
    {
        $payload = [];
        $response = null;
        $result = false;
        $paymentReference = null;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }        

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getCurrency($submission);

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $paymentPayload = $this->getPaymentFieldPayload($submission);
            $opayoTokenId = $paymentPayload->string('opayoTokenId');
            $opayoSessionKey = $paymentPayload->string('opayoSessionKey');
            $opayo3DSComplete = $paymentPayload->string('opayo3DSComplete');

            // Check if we've returned from a 3DS challenge. We've already captured the payment, and recorded the successful payment.
            if ($opayo3DSComplete) {
                // Verify that we indeed have a verified payment - just in case people are trying to send through _any_ value
                if (Formie::$plugin->getPayments()->getPaymentByReference($opayo3DSComplete)) {
                    // We can return true here to allow the form to continue with the submission process
                    return PaymentDecision::succeeded($this->handle, $opayo3DSComplete);
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
                $paymentReference = $payment->reference;

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
                    'event' => 'formie:payment:opayo:challenge',
                    'data' => [
                        'acsUrl' => $acsUrl,
                        'creq' => $response['cReq'] ?? '',
                        'returnUrl' => $this->getReturnUrl(),
                        'threeDSSessionData' => base64_encode(Json::encode($threeDSSessionData)),
                    ],
                ]);

                return PaymentDecision::requiresAction(
                    $payment->reference,
                    PaymentAction::challengeEvent('formie:payment:opayo:challenge', $acsUrl)
                        ->forProvider($this->handle)
                        ->withMessage(Craft::t('formie', 'This payment requires 3D Secure authentication. Please follow the instructions on-screen to continue.'))
                        ->withPayload([
                            'acsUrl' => $acsUrl,
                            'creq' => $response['cReq'] ?? '',
                            'returnUrl' => $this->getReturnUrl(),
                            'threeDSSessionData' => base64_encode(Json::encode($threeDSSessionData)),
                        ])
                        ->resumeMode(PaymentAction::RESUME_MODE_CALLBACK, $this->getReturnUrl())
                );
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
            $paymentReference = $payment->reference;

            Formie::$plugin->getPayments()->savePayment($payment);

            $result = true;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”. Payload: “{payload}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
                'payload' => Json::encode($payload),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = $this->getFriendlyPaymentErrorMessage($e);
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

            return PaymentDecision::failed($e->getMessage(), $this->handle, $paymentReference);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return PaymentDecision::succeeded($this->handle);
        }

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
    }

    public function processCallback(): Response
    {
        $request = Craft::$app->getRequest();
        $callbackResponse = Craft::$app->getResponse();
        $callbackResponse->format = Response::FORMAT_RAW;

        // Check to see if we're requesting a merchant session key - the first step
        if ($request->getParam('merchantSessionKey')) {
            $callbackResponse->format = Response::FORMAT_JSON;
            $sessionToken = (string)$request->getParam('sessionToken');

            $this->_requireValidMerchantSessionToken($sessionToken);
            $this->_enforceMerchantSessionRateLimit($sessionToken);

            try {
                $response = $this->request('POST', 'merchant-session-keys', [
                    'json' => ['vendorName' => App::parseEnv($this->vendorName)],
                ]);

                $callbackResponse->data = [
                    'merchantSessionKey' => $response['merchantSessionKey'] ?? null,
                ];
            } catch (Throwable $e) {
                $callbackResponse->data = [
                    'error' => Craft::t('formie', 'Unable to initialize payment session.'),
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
                'message' => Integration::getExceptionLogMessage($e),
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
        $callbackResponse->data = '<script>window.parent.postMessage({ message: "formie:payment:opayo:challenge:response", value: ' . Json::encode($responseData) . ' }, "*");</script>';

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

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::staticTableField([
                'label' => Craft::t('formie', 'Billing Details'),
                'instructions' => Craft::t('formie', 'Whether to send billing details alongside the payment.'),
                'name' => 'billingDetails',
                'columns' => [
                    'heading' => [
                        'type' => 'heading',
                        'heading' => Craft::t('formie', 'Billing Info'),
                    ],
                    'value' => [
                        'type' => 'fieldSelect',
                        'label' => Craft::t('formie', 'Field'),
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
            ]),
        ];
    }

    public function getPaymentSubFields($field): array
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
                $subFields[$key][] = $subField->withParentField($field);
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
        $url = $useSandbox ? 'https://sandbox.opayo.eu.elavon.com/' : 'https://live.opayo.eu.elavon.com/';

        return Craft::createGuzzleClient([
            'base_uri' => $url . 'api/v1/',
            'auth' => [App::parseEnv($this->integrationKey), App::parseEnv($this->integrationPassword)],
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

        $billingName = $this->getPaymentBillingFieldKey('billingName');
        $billingAddress = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmail = $this->getPaymentBillingFieldKey('billingEmail');


        if ($billingEmail && ($email = $submission->getFieldValueAsString($billingEmail))) {
            // Only set if we have a valid email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $payload['customerEMail'] = $email;
            }
        }

        if ($billingName && ($fullName = $submission->getFieldValueAsArray($billingName))) {
            if ($fullName instanceof NameFieldValue) {
                $fullName = $fullName->toValueArray();
            }

            if (is_array($fullName)) {
                if ($firstName = ArrayHelper::remove($fullName, 'firstName')) {
                    $payload['customerFirstName'] = $firstName;
                }

                if ($lastName = ArrayHelper::remove($fullName, 'lastName')) {
                    $payload['customerLastName'] = $lastName;
                }
            }
        }

        if ($billingAddress && ($address = $submission->getFieldValueAsArray($billingAddress))) {
            if ($address instanceof AddressFieldValue) {
                $address = $address->toValueArray();
            }

            if (is_array($address)) {
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
        $billingState = trim((string)($payload['billingAddress']['state'] ?? ''));
        $billingCountry = trim((string)($payload['billingAddress']['country'] ?? ''));

        if ($billingState !== '' && strlen($billingState) > 3 && $billingCountry !== '') {
            $subdivisionRepository = new SubdivisionRepository();
            $states = $subdivisionRepository->getAll([$billingCountry]);

            foreach ($states as $state) {
                if ($state->getName() === $billingState) {
                    $payload['billingAddress']['state'] = $state->getCode();
                }
            }
        }

        // State is only required for US addresses, and will likely throw errors for other countries
        // https://www.opayo.co.uk/support/error-codes/3130-%C2%A0-billingstate-value-too-long
        if (($payload['billingAddress']['country'] ?? '') !== 'US') {
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

    private function _requireValidMerchantSessionToken(string $token): void
    {
        $payload = PaymentAccess::resolveProviderSessionToken($token, 'opayo');

        if (!$payload || (int)$payload['integrationId'] !== (int)$this->id || (string)$payload['integrationHandle'] !== (string)$this->handle) {
            throw new BadRequestHttpException('Invalid or expired payment session.');
        }
    }

    private function _enforceMerchantSessionRateLimit(string $token): void
    {
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $cacheKey = 'formie.opayo-merchant-session-rate.' . md5($token . '|' . $ipAddress);
        $mutexKey = 'formie.opayo-merchant-session-rate-lock.' . md5($token . '|' . $ipAddress);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + self::MERCHANT_SESSION_RATE_WINDOW_SECONDS,
                ];
            }

            if ((int)$entry['count'] >= self::MERCHANT_SESSION_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many payment session requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }
}

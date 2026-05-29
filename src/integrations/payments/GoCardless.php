<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Response;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

use Throwable;
use Exception;

class GoCardless extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'GoCardless');
    }
    

    // Properties
    // =========================================================================

    public ?string $accessToken = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->accessToken);
    }

    public function getReturnUrl(array $params = []): string
    {
        $endpoint = 'formie/payment-webhooks/status';

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            $url = UrlHelper::actionUrl($endpoint, $params);
        } else {
            $url = UrlHelper::siteUrl($endpoint, $params);
        }

        // For local development, we should use a proxy to ensure it works
        if (App::devMode()) {
            return "https://proxy.verbb.io?return=$url";
        }

        return $url;
    }

    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/go-cardless.js'),
            'module' => 'FormieGoCardless',
        ];
    }

    public function processPayment(Submission $submission): bool
    {
        $response = null;
        $result = false;
        $field = $this->getField();

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Create a payment right away so we can use it for redirect or fail, rather than multiple
        $payment = new PaymentModel();
        $payment->integrationId = $this->id;
        $payment->submissionId = $submission->id;
        $payment->fieldId = $field->id;
        $payment->amount = $amount;
        $payment->currency = $currency;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        try {
            $payment->status = PaymentModel::STATUS_REDIRECT;
            $payment->redirectUrl = Craft::$app->getRequest()->getReferrer();

            // Create the Formie payment record before starting the GoCardless redirect flow.
            Formie::$plugin->getPayments()->savePayment($payment);

            $sessionToken = StringHelper::UUID();

            $payload = [
                'session_token' => $sessionToken,
                'success_redirect_url' => $this->getReturnUrl([
                    'paymentUid' => $payment->uid,
                ]),
                'metadata' => [
                    'formiePaymentId' => (string)$payment->id,
                ],
            ];

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission);

            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            // Redirect via the front-end for a nicer UX than just a sudden redirect away.
            $response = $this->request('POST', 'redirect_flows', ['json' => ['redirect_flows' => $event->payload]]);
            $flow = $response['redirect_flows'] ?? [];

            // Persist session token and redirect flow id so we can complete the flow when the customer returns.
            $payment->response = [
                'sessionToken' => $sessionToken,
                'redirectFlow' => $flow,
            ];
            Formie::$plugin->getPayments()->savePayment($payment);

            $submission->getForm()->addSubmitData([
                'event' => 'FormiePaymentGoCardlessRedirect',
                'data' => [
                    'redirectUrl' => $flow['redirect_url'] ?? '',
                ],
            ]);

            // Add an error to the form to ensure it doesn't proceed and redirects
            $this->addFieldError($submission, Craft::t('formie', 'Please wait while you are redirected to GoCardless.'));

            // Allow events to say the response is invalid
            if (!$this->afterProcessPayment($submission, $result)) {
                return true;
            }

            return false;
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

            // Update the payment if one has already been made
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->response = ['message' => $e->getMessage()];

            Formie::$plugin->getPayments()->savePayment($payment);

            return false;
        }

        return true;
    }

    public function processWebhook(): Response
    {
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        try {
            $payload = Json::decode(Craft::$app->getRequest()->getRawBody());
            $events = $payload['events'] ?? [];

            foreach ($events as $event) {
                $resourceType = $event['resource_type'] ?? '';
                $resourceId = $event['links']['payment'] ?? null;

                // Only process payment events (ignore mandates, refunds, etc.)
                if ($resourceType !== 'payments' || !$resourceId) {
                    continue;
                }

                // Fetch payment info
                $gcPayment = $this->request('GET', "payments/{$resourceId}")['payments'] ?? [];

                // Get the Formie payment ID (saved in metadata in `processPayment`)
                $formiePaymentId = $gcPayment['metadata']['formiePaymentId'] ?? null;

                if (!$formiePaymentId) {
                    Integration::error($this, 'Missing `formiePaymentId` in GoCardless metadata.');
                    continue;
                }

                $payment = Formie::$plugin->getPayments()->getPaymentById((int)$formiePaymentId);

                if (!$payment) {
                    Integration::error($this, "No Formie payment found for ID: {$formiePaymentId}");
                    continue;
                }

                $this->_syncFormiePaymentFromGoCardlessPayment($payment, $gcPayment);
            }

            if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
                $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                    'webhookData' => $payload,
                ]));
            }

            $response->data = 'ok';
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
            $response->data = 'Webhook error: ' . $e->getMessage();
        }

        return $response;
    }

    public function getTransaction(PaymentModel $payment): void
    {
        if (!$payment->reference) {
            throw new Exception(Craft::t('formie', 'Missing GoCardless payment reference.'));
        }

        if (
            $payment->status === PaymentModel::STATUS_SUCCESS ||
            $payment->status === PaymentModel::STATUS_FAILED
        ) {
            return;
        }

        $gcPayment = $this->request('GET', "payments/{$payment->reference}")['payments'] ?? [];

        if (!$gcPayment) {
            throw new Exception(Craft::t('formie', 'Unable to load GoCardless payment.'));
        }

        $this->_syncFormiePaymentFromGoCardlessPayment($payment, $gcPayment);
    }

    public function getTransactionStatus(PaymentModel $payment): void
    {
        $submission = $payment->getSubmission();
        $request = Craft::$app->getRequest();

        // GoCardless payment already created — refresh from API and let the status page poll.
        if (is_string($payment->reference) && str_starts_with($payment->reference, 'PM')) {
            try {
                $this->getTransaction($payment);
            } catch (Throwable $e) {
                Integration::error($this, Craft::t('formie', 'Unable to refresh GoCardless payment: “{message}”.', [
                    'message' => $e->getMessage(),
                ]));
            }

            return;
        }

        $redirectFlowId = $request->getQueryParam('redirect_flow_id');
        $sessionToken = is_array($payment->response) ? ($payment->response['sessionToken'] ?? null) : null;

        if (!$submission) {
            Integration::error($this, 'GoCardless return URL missing submission for payment.');

            return;
        }

        $this->setField($payment->getField());

        if (!$redirectFlowId || !$sessionToken) {
            $this->_failGoCardlessReturn(
                $payment,
                $submission,
                Craft::t('formie', 'Your payment could not be confirmed. Please return to the form and try again.')
            );

            return;
        }

        $mandateId = null;

        try {
            $completedFlow = $this->_completeGoCardlessRedirectFlow($payment, $redirectFlowId, $sessionToken);
            $mandateId = $completedFlow['links']['mandate'] ?? null;

            if (!$mandateId) {
                throw new Exception(Craft::t('formie', 'GoCardless did not return a mandate for this payment.'));
            }

            $this->_persistMandateAfterRedirectFlowComplete($payment, $completedFlow, $mandateId);

            $gcPayment = $this->_createGoCardlessPaymentForMandate($payment, $submission, $mandateId);
            $this->_syncFormiePaymentFromGoCardlessPayment($payment, $gcPayment);
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'GoCardless payment error: “{message}” {file}:{line}. Context: “{context}”. Response: “{response}”.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'context' => Json::encode([
                    'paymentId' => $payment->id,
                    'paymentUid' => $payment->uid,
                    'submissionId' => $submission->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'redirectFlowId' => $redirectFlowId,
                    'mandateId' => $mandateId,
                    'statusCode' => $this->_getGoCardlessExceptionStatusCode($e),
                ]),
                'response' => $this->_getGoCardlessExceptionResponse($e),
            ]));

            $this->_failGoCardlessReturn(
                $payment,
                $submission,
                Craft::t('formie', 'We could not complete your Direct Debit payment. Please try again or contact support.')
            );
        }
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'customer_bank_accounts');
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
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Payment Description'),
                'help' => Craft::t('formie', 'Enter a description for this payment, to appear against the transaction in your GoCardless account, and on the customer bank statement where supported.'),
                'name' => 'paymentDescription',
                'variables' => 'plainTextVariables',
            ]),
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
                    'billingFirstName' => [
                        'heading' => Craft::t('formie', 'Billing First Name'),
                        'value' => '',
                    ],
                    'billingLastName' => [
                        'heading' => Craft::t('formie', 'Billing Last Name'),
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
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Metadata'),
                'help' => Craft::t('formie', 'Add any additional metadata to store against a transaction.'),
                'validation' => 'min:0',
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                ],
                'generateValue' => false,
                'columns' => [
                    [
                        'type' => 'label',
                        'label' => Craft::t('formie', 'Option'),
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'class' => 'singleline-cell textual',
                    ],
                ],
                'name' => 'metadata',
            ]),
        ];
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accessToken'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $baseUri = $useSandbox ? 'https://api-sandbox.gocardless.com/' : 'https://api.gocardless.com/';

        return Craft::createGuzzleClient([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->accessToken),
                'GoCardless-Version' => '2015-07-06',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }
    

    // Private Methods
    // =========================================================================

    private function _setPayloadDetails(array &$payload, Submission $submission): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription') ?? "Formie Submission #{$submission->id}";
        $metadata = $this->getFieldSetting('metadata', []);

        if ($paymentDescription) {
            $payload['description'] = Variables::getParsedValue($paymentDescription, $submission, $submission->getForm());
        }

        // Add a few other things about the customer from mapping (in field settings)
        $billingFirstName = $this->getFieldSetting('billingDetails.billingFirstName');
        $billingLastName = $this->getFieldSetting('billingDetails.billingLastName');
        $billingAddress = $this->getFieldSetting('billingDetails.billingAddress');
        $billingEmail = $this->getFieldSetting('billingDetails.billingEmail');

        if ($billingFirstName) {
            $payload['prefilled_customer']['given_name'] = $this->getMappedFieldValue($billingFirstName, $submission, new IntegrationField());
        }

        if ($billingLastName) {
            $payload['prefilled_customer']['family_name'] = $this->getMappedFieldValue($billingLastName, $submission, new IntegrationField());
        }

        if ($billingAddress) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_ARRAY;

            $address = $this->getMappedFieldValue($billingAddress, $submission, $integrationField);

            if ($address) {
                $payload['prefilled_customer']['address_line1'] = ArrayHelper::remove($address, 'address1');
                $payload['prefilled_customer']['address_line2'] = ArrayHelper::remove($address, 'address2');
                $payload['prefilled_customer']['address_line3'] = ArrayHelper::remove($address, 'address3');
                $payload['prefilled_customer']['city'] = ArrayHelper::remove($address, 'city');
                $payload['prefilled_customer']['postal_code'] = ArrayHelper::remove($address, 'zip');
                $payload['prefilled_customer']['region'] = ArrayHelper::remove($address, 'state');
                $payload['prefilled_customer']['country_code'] = ArrayHelper::remove($address, 'country');
            }
        }

        if ($billingEmail) {
            $payload['prefilled_customer']['email'] = $this->getMappedFieldValue($billingEmail, $submission, new IntegrationField());
        }

        // Note API limit of 4 total
        if ($metadata) {
            foreach ($metadata as $option) {
                $label = trim($option['label']);
                $value = trim($option['value']);

                if ($label && $value) {
                    $payload['metadata'][$label] = Variables::getParsedValue($value, $submission, $submission->getForm());
                }
            }
        }
    }

    private function _syncFormiePaymentFromGoCardlessPayment(PaymentModel $payment, array $gcPayment): void
    {
        $status = $gcPayment['status'] ?? '';

        $payment->reference = $gcPayment['id'] ?? $payment->reference;

        switch ($status) {
            case 'confirmed':
            case 'paid_out':
            case 'pending_submission':
            case 'submitted':
                $payment->status = PaymentModel::STATUS_SUCCESS;
                break;
            case 'failed':
            case 'cancelled':
            case 'charged_back':
            case 'customer_approval_denied':
                $payment->status = PaymentModel::STATUS_FAILED;
                break;
            default:
                $payment->status = PaymentModel::STATUS_PENDING;
                break;
        }

        $existing = is_array($payment->response) ? $payment->response : [];
        $preserved = [];

        foreach (['sessionToken', 'redirectFlow', 'completedRedirectFlow', 'mandateId'] as $key) {
            if (isset($existing[$key])) {
                $preserved[$key] = $existing[$key];
            }
        }

        $payment->response = array_merge($preserved, $gcPayment);

        Formie::$plugin->getPayments()->savePayment($payment);
    }

    private function _completeGoCardlessRedirectFlow(PaymentModel $payment, string $redirectFlowId, string $sessionToken): array
    {
        $existing = is_array($payment->response) ? $payment->response : [];

        if (!empty($existing['completedRedirectFlow']['links']['mandate'])) {
            return $existing['completedRedirectFlow'];
        }

        try {
            $apiResponse = $this->request('POST', "redirect_flows/{$redirectFlowId}/actions/complete", [
                'json' => [
                    'data' => [
                        'session_token' => $sessionToken,
                    ],
                ],
                'headers' => [
                    'Idempotency-Key' => substr($payment->uid . '-rf-complete', 0, 120),
                ],
            ]);
        } catch (Throwable $e) {
            if ($this->_goCardlessErrorIsRedirectFlowAlreadyCompleted($e)) {
                $mandateId = $existing['mandateId'] ?? null;

                if ($mandateId) {
                    return ['links' => ['mandate' => $mandateId]];
                }
            }

            throw $e;
        }

        $flow = $apiResponse['redirect_flows'] ?? $apiResponse['redirect_flow'] ?? [];

        if (empty($flow['links']['mandate'])) {
            throw new Exception(Craft::t('formie', 'GoCardless did not return a mandate for this payment.'));
        }

        return $flow;
    }

    private function _goCardlessErrorIsRedirectFlowAlreadyCompleted(Throwable $e): bool
    {
        if (!$e instanceof ClientException) {
            return false;
        }

        try {
            $body = Json::decode((string)$e->getResponse()->getBody());
            $errors = $body['error']['errors'] ?? [];

            foreach ($errors as $error) {
                if (($error['reason'] ?? '') === 'redirect_flow_already_completed') {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function _persistMandateAfterRedirectFlowComplete(PaymentModel $payment, array $completedFlow, string $mandateId): void
    {
        $prev = is_array($payment->response) ? $payment->response : [];
        $payment->response = array_merge($prev, [
            'completedRedirectFlow' => $completedFlow,
            'mandateId' => $mandateId,
        ]);
        $payment->status = PaymentModel::STATUS_PENDING;

        Formie::$plugin->getPayments()->savePayment($payment);
    }

    private function _createGoCardlessPaymentForMandate(PaymentModel $payment, Submission $submission, string $mandateId): array
    {
        $currency = (string)$payment->currency;
        $amountMinor = $this->_amountToMinorUnits((float)$payment->amount, $currency);

        if ($amountMinor < 1) {
            throw new Exception(Craft::t('formie', 'The payment amount is too small for GoCardless.'));
        }

        $payload = [
            'amount' => $amountMinor,
            'currency' => strtoupper($currency),
            'metadata' => [
                'formiePaymentId' => (string)$payment->id,
            ],
            'links' => [
                'mandate' => $mandateId,
            ],
        ];

        $apiResponse = $this->request('POST', 'payments', [
            'json' => ['payments' => $payload],
            'headers' => [
                'Idempotency-Key' => substr($payment->uid . '-pm-create', 0, 120),
            ],
        ]);

        $gcPayment = $apiResponse['payments'] ?? [];

        if (!$gcPayment || empty($gcPayment['id'])) {
            throw new Exception(Craft::t('formie', 'GoCardless did not return a payment resource.'));
        }

        return $gcPayment;
    }

    private function _failGoCardlessReturn(PaymentModel $payment, Submission $submission, string $message): void
    {
        $payment->status = PaymentModel::STATUS_FAILED;
        $payment->message = $message;

        Formie::$plugin->getPayments()->savePayment($payment);

        $form = $submission->getForm();
        Formie::$plugin->getService()->setError($form->id, $message);

        $url = $payment->redirectUrl ?: UrlHelper::siteUrl();

        Craft::$app->getResponse()->redirect($url)->send();
    }

    private function _getGoCardlessExceptionStatusCode(Throwable $e): ?int
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            return $e->getResponse()->getStatusCode();
        }

        return null;
    }

    private function _getGoCardlessExceptionResponse(Throwable $e): ?string
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            return (string)$e->getResponse()->getBody();
        }

        return null;
    }

    private function _amountToMinorUnits(float $amount, string $currencyCode): int
    {
        $currencyCode = strtoupper($currencyCode);

        try {
            $currencies = new ISOCurrencies();
            $currency = new Currency($currencyCode);
            $subunit = $currencies->subunitFor($currency);

            return (int)round($amount * (10 ** $subunit));
        } catch (Throwable) {
            return (int)round($amount * 100);
        }
    }
}

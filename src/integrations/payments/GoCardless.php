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
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\References;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

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

use Throwable;
use Exception;

class GoCardless extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'GoCardless');
    }
    

    // Properties
    // =========================================================================

    public ?string $accessToken = null;
    public ?string $webhookSecretKey = null;
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

        return Payment::applyPaymentWebhookProxy($url);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'go-cardless',
            'config' => [
                'requiredInputSuffixes' => [],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
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
            return PaymentDecision::notRequired();
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
                    'statusToken' => PaymentAccess::issueStatusToken($payment),
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
                'event' => 'formie:payment:go-cardless:redirect',
                'data' => [
                    'redirectUrl' => $flow['redirect_url'] ?? '',
                ],
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterProcessPayment($submission, $result)) {
                return PaymentDecision::succeeded($this->handle);
            }

            return PaymentDecision::requiresAction(
                $payment->reference,
                PaymentAction::redirectEvent('formie:payment:go-cardless:redirect', $flow['redirect_url'] ?? null)
                    ->forProvider($this->handle)
                    ->withMessage(Craft::t('formie', 'Please wait while you are redirected to GoCardless.'))
                    ->withPayload(['redirectUrl' => $flow['redirect_url'] ?? ''])
                    ->resumeMode(PaymentAction::RESUME_MODE_WEBHOOK, $this->getRedirectUri())
            );
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $userMessage = Craft::t('formie', 'Unable to process your payment right now. Please try again.');
            $this->addFieldError($submission, $userMessage);

            // Update the payment if one has already been made
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->response = ['message' => $userMessage];

            Formie::$plugin->getPayments()->savePayment($payment);

            return PaymentDecision::failed($userMessage, $this->handle, $payment->reference);
        }

        return PaymentDecision::succeeded($this->handle);
    }

    public function processWebhook(): Response
    {
        $rawBody = Craft::$app->getRequest()->getRawBody();
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;
        $secret = trim((string)App::parseEnv($this->webhookSecretKey));
        $signature = trim((string)(Craft::$app->getRequest()->getHeaders()->get('Webhook-Signature') ?? ''));

        if (!$secret || !$signature) {
            Integration::error($this, 'Webhook not signed or signing secret not set.');
            $response->data = 'success';

            return $response;
        }

        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Integration::error($this, 'Webhook signature check failed.');
            $response->data = 'success';

            return $response;
        }

        try {
            $payload = Json::decode($rawBody);
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

                // Update status based on GoCardless payment status
                $status = $gcPayment['status'] ?? '';

                switch ($status) {
                    case 'confirmed':
                        $payment->status = PaymentModel::STATUS_SUCCESS;
                        break;
                    case 'failed':
                    case 'cancelled':
                        $payment->status = PaymentModel::STATUS_FAILED;
                        break;
                    case 'pending_submission':
                    case 'submitted':
                    default:
                        $payment->status = PaymentModel::STATUS_PENDING;
                        break;
                }

                $this->_updatePaymentStatus($payment, $gcPayment);
            }

            if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
                $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                    'webhookData' => $payload,
                ]));
            }

            $response->data = 'success';
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
            $response->data = 'error';
        }

        return $response;
    }

    public function getTransaction(PaymentModel $payment): void
    {
        if (!$payment->reference) {
            throw new Exception('Missing GoCardless payment reference.');
        }

        if (
            $payment->status === PaymentModel::STATUS_SUCCESS ||
            $payment->status === PaymentModel::STATUS_FAILED
        ) {
            return;
        }

        $gcPayment = $this->request('GET', "payments/{$payment->reference}")['payments'] ?? [];

        if (!$gcPayment) {
            throw new Exception('Unable to resolve GoCardless payment.');
        }

        $this->_updatePaymentStatus($payment, $gcPayment);
    }

    public function getTransactionStatus(PaymentModel $payment): void
    {
        $submission = $payment->getSubmission();
        $request = Craft::$app->getRequest();

        // GoCardless payment already created — refresh from API and let the status page poll.
        if (in_array($payment->status, [PaymentModel::STATUS_SUCCESS, PaymentModel::STATUS_FAILED], true)) {
            return;
        }

        $this->getTransaction($payment);
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

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Payment Description'),
                'instructions' => Craft::t('formie', 'Enter a description for this payment, to appear against the transaction in your Mollie account, and on the payment receipt sent to the customer.'),
                'name' => 'paymentDescription',
                'variables' => 'plainTextVariables',
            ]),
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
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Metadata'),
                'instructions' => Craft::t('formie', 'Add any additional metadata to store against a transaction.'),
                'name' => 'metadata',
                'columns' => [
                    [
                        'name' => 'label',
                        'type' => 'label',
                        'label' => Craft::t('formie', 'Option'),
                    ],
                    [
                        'name' => 'value',
                        'type' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                    ],
                ],
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

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
        ];

        return $defaults;
    }
    

    // Private Methods
    // =========================================================================

    private function _updatePaymentStatus(PaymentModel $payment, array $gcPayment): void
    {
        $status = $gcPayment['status'] ?? '';

        switch ($status) {
            case 'confirmed':
                $payment->status = PaymentModel::STATUS_SUCCESS;
                break;
            case 'failed':
            case 'cancelled':
                $payment->status = PaymentModel::STATUS_FAILED;
                break;
            case 'pending_submission':
            case 'submitted':
            default:
                $payment->status = PaymentModel::STATUS_PENDING;
                break;
        }

        $payment->reference = $gcPayment['id'] ?? $payment->reference;
        $payment->response = $gcPayment;

        Formie::$plugin->getPayments()->savePayment($payment);
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    private function _setPayloadDetails(array &$payload, Submission $submission): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription') ?? "Formie Submission #{$submission->id}";
        $metadata = $this->getFieldSetting('metadata', []);

        if ($paymentDescription) {
            $payload['description'] = References::parseContent($paymentDescription, $submission);
        }

        // Add a few other things about the customer from mapping (in field settings)
        $billingFirstName = $this->getPaymentBillingFieldKey('billingFirstName');
        $billingLastName = $this->getPaymentBillingFieldKey('billingLastName');
        $billingAddress = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmail = $this->getPaymentBillingFieldKey('billingEmail');

        if ($billingFirstName && ($billingFirstNameValue = $submission->getFieldValueAsString($billingFirstName))) {
            $payload['prefilled_customer']['given_name'] = $billingFirstNameValue;
        }

        if ($billingLastName && ($billingLastNameValue = $submission->getFieldValueAsString($billingLastName))) {
            $payload['prefilled_customer']['family_name'] = $billingLastNameValue;
        }

        if ($billingAddress && ($address = $submission->getFieldValueAsArray($billingAddress)) && is_array($address)) {
            $payload['prefilled_customer']['address_line1'] = ArrayHelper::remove($address, 'address1');
            $payload['prefilled_customer']['address_line2'] = ArrayHelper::remove($address, 'address2');
            $payload['prefilled_customer']['address_line3'] = ArrayHelper::remove($address, 'address3');
            $payload['prefilled_customer']['city'] = ArrayHelper::remove($address, 'city');
            $payload['prefilled_customer']['postal_code'] = ArrayHelper::remove($address, 'zip');
            $payload['prefilled_customer']['region'] = ArrayHelper::remove($address, 'state');
            $payload['prefilled_customer']['country_code'] = ArrayHelper::remove($address, 'country');
        }

        if ($billingEmail && ($billingEmailValue = $submission->getFieldValueAsString($billingEmail))) {
            $payload['prefilled_customer']['email'] = $billingEmailValue;
        }

        // Note API limit of 4 total
        if ($metadata) {
            foreach ($metadata as $option) {
                $label = trim($option['label']);
                $value = trim($option['value']);

                if ($label && $value) {
                    $payload['metadata'][$label] = References::parseContent($value, $submission);
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
            $body = Json::decode($e->getResponse()->getBody()->getContents());
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

        $paymentDescription = $this->getFieldSetting('paymentDescription') ?? "Formie Submission #{$submission->id}";
        $referenceBase = Variables::getParsedValue($paymentDescription, $submission, $submission->getForm());
        $reference = strtoupper(substr(preg_replace('/[^A-Za-z0-9\-]/', '-', (string)$referenceBase), 0, 18));

        if ($reference === '' || $reference === '-') {
            $reference = 'F' . $submission->id;
            $reference = strtoupper(substr($reference, 0, 18));
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
            'reference' => $reference,
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
        Formie::$plugin->getService()->setError($form->getFlashNamespace(), $message);

        $url = $payment->redirectUrl ?: UrlHelper::siteUrl();

        Craft::$app->getResponse()->redirect($url)->send();
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

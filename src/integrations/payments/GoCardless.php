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
use verbb\formie\models\Subscription;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

use GuzzleHttp\Client;

use Throwable;
use Exception;

class GoCardless extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';

    private const API_VERSION = '2015-07-06';


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
        $field = $this->getField();
        $amount = $this->getAmount($submission);
        $currency = (string)$this->getFieldSetting('currency');

        $payment = new PaymentModel();
        $payment->integrationId = $this->id;
        $payment->submissionId = $submission->id;
        $payment->fieldId = $field->id;
        $payment->amount = $amount;
        $payment->currency = $currency;

        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        try {
            $payment->status = PaymentModel::STATUS_REDIRECT;
            $payment->redirectUrl = Craft::$app->getRequest()->getReferrer();

            Formie::$plugin->getPayments()->savePayment($payment);

            $returnUrl = $this->getReturnUrl([
                'statusToken' => PaymentAccess::issueStatusToken($payment),
            ]);

            $billingRequestPayload = $this->_buildBillingRequestPayload($payment, $submission);

            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $billingRequestPayload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'billing_requests', [
                'json' => ['billing_requests' => $event->payload],
            ]);
            $billingRequest = $response['billing_requests'] ?? [];

            if (empty($billingRequest['id'])) {
                throw new Exception(Craft::t('formie', 'GoCardless did not return a billing request.'));
            }

            $this->_collectBillingCustomerDetails((string)$billingRequest['id'], $submission);

            $flowResponse = $this->request('POST', 'billing_request_flows', [
                'json' => [
                    'billing_request_flows' => [
                        'redirect_uri' => $returnUrl,
                        'exit_uri' => $returnUrl,
                        'links' => [
                            'billing_request' => $billingRequest['id'],
                        ],
                    ],
                ],
            ]);
            $flow = $flowResponse['billing_request_flows'] ?? [];
            $authorisationUrl = (string)($flow['authorisation_url'] ?? '');

            if ($authorisationUrl === '') {
                throw new Exception(Craft::t('formie', 'GoCardless did not return an authorisation URL.'));
            }

            $payment->response = [
                'billingRequest' => $billingRequest,
                'billingRequestFlow' => $flow,
            ];
            Formie::$plugin->getPayments()->savePayment($payment);

            $submission->getForm()->addSubmitData([
                'event' => 'formie:payment:go-cardless:redirect',
                'data' => [
                    'redirectUrl' => $authorisationUrl,
                ],
            ]);

            if (!$this->afterProcessPayment($submission, false)) {
                return PaymentDecision::succeeded($this->handle);
            }

            return PaymentDecision::requiresAction(
                $payment->reference,
                PaymentAction::redirectEvent('formie:payment:go-cardless:redirect', $authorisationUrl)
                    ->forProvider($this->handle)
                    ->withMessage(Craft::t('formie', 'Please wait while you are redirected to GoCardless.'))
                    ->withPayload(['redirectUrl' => $authorisationUrl])
                    ->resumeMode(PaymentAction::RESUME_MODE_WEBHOOK, $this->getRedirectUri())
            );
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $userMessage = Craft::t('formie', 'Unable to process your payment right now. Please try again.');
            $this->addFieldError($submission, $userMessage);

            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->response = ['message' => $userMessage];

            Formie::$plugin->getPayments()->savePayment($payment);

            return PaymentDecision::failed($userMessage, $this->handle, $payment->reference);
        }
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
                $resourceType = (string)($event['resource_type'] ?? '');
                $action = (string)($event['action'] ?? '');

                if ($resourceType === 'payments') {
                    $this->_processPaymentWebhookEvent($event);
                    continue;
                }

                if ($resourceType === 'billing_requests') {
                    $this->_processBillingRequestWebhookEvent($event, $action);
                    continue;
                }

                if ($resourceType === 'subscriptions') {
                    $this->_processSubscriptionWebhookEvent($event, $action);
                }
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

        if (in_array($payment->status, [PaymentModel::STATUS_SUCCESS, PaymentModel::STATUS_FAILED], true)) {
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
        if (in_array($payment->status, [PaymentModel::STATUS_SUCCESS, PaymentModel::STATUS_FAILED], true)) {
            return;
        }

        if ($field = $payment->getField()) {
            $this->setField($field);
        }

        try {
            $this->_syncPaymentFromBillingRequestReturn($payment);

            $existing = is_array($payment->response) ? $payment->response : [];

            if (!empty($existing['gcSubscription']['id'])) {
                $this->_refreshGoCardlessSubscription($payment);
            } elseif ($payment->reference) {
                $this->getTransaction($payment);
            }
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Unable to refresh GoCardless payment: “{message}”.', [
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function cancelSubscription($reference, $params = []): ?array
    {
        try {
            $response = $this->request('POST', "subscriptions/{$reference}/actions/cancel")['subscriptions'] ?? [];

            if (!$response) {
                return null;
            }

            $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($reference);

            if ($subscription) {
                $subscription->subscriptionData = $response;
                $this->_setSubscriptionStatusData($subscription, $response);
                Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
            }

            return $response;
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }

        return null;
    }

    public function fetchConnection(): bool
    {
        try {
            $this->request('GET', 'billing_requests', [
                'query' => ['limit' => 1],
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Payment Type'),
                'instructions' => Craft::t('formie', 'Select the type of payment to use.'),
                'name' => 'type',
                'required' => true,
                'options' => [
                    ['label' => Craft::t('formie', 'Once-off'), 'value' => self::PAYMENT_TYPE_SINGLE],
                    ['label' => Craft::t('formie', 'Subscription'), 'value' => self::PAYMENT_TYPE_SUBSCRIPTION],
                ],
            ]),
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
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Subscription Frequency'),
                'instructions' => Craft::t('formie', 'Select how often this subscription should be billed.'),
                'if' => 'type == "subscription"',
                'children' => [
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'Bill every'),
                    ],
                    SchemaHelper::numberField([
                        'name' => 'frequencyValue',
                        'required' => true,
                    ]),
                    SchemaHelper::selectField([
                        'name' => 'frequencyType',
                        'required' => true,
                        'options' => [
                            ['label' => Craft::t('formie', 'Weeks'), 'value' => 'week'],
                            ['label' => Craft::t('formie', 'Months'), 'value' => 'month'],
                            ['label' => Craft::t('formie', 'Years'), 'value' => 'year'],
                        ],
                    ]),
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Subscription Description'),
                'instructions' => Craft::t('formie', 'Enter a description for the subscription. It appears against the GoCardless subscription and in customer communications.'),
                'name' => 'subscriptionDescription',
                'if' => 'type == "subscription"',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Payment Description'),
                'instructions' => Craft::t('formie', 'Enter a description for this payment. It appears against the GoCardless payment and in customer communications.'),
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
                'GoCardless-Version' => self::API_VERSION,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        return [
            'type' => self::PAYMENT_TYPE_SINGLE,
            'amountType' => self::VALUE_TYPE_FIXED,
            'frequencyValue' => 1,
            'frequencyType' => 'month',
        ];
    }


    // Private Methods
    // =========================================================================

    private function _buildBillingRequestPayload(PaymentModel $payment, Submission $submission): array
    {
        $currency = strtoupper((string)$payment->currency);
        $payload = [
            'mandate_request' => [
                'scheme' => $this->_resolveDirectDebitScheme($currency),
            ],
            'metadata' => $this->_buildMetadata($payment, $submission),
        ];

        $metadata = $this->getFieldSetting('metadata', []);

        if ($metadata) {
            foreach ($metadata as $option) {
                $label = trim((string)($option['label'] ?? ''));
                $value = trim((string)($option['value'] ?? ''));

                if ($label && $value) {
                    $payload['metadata'][$label] = References::parseContent($value, $submission);
                }
            }
        }

        return $payload;
    }

    private function _buildMetadata(PaymentModel $payment, Submission $submission): array
    {
        return [
            'formiePaymentId' => (string)$payment->id,
            'formiePaymentType' => (string)$this->getFieldSetting('type', self::PAYMENT_TYPE_SINGLE),
        ];
    }

    private function _resolveDirectDebitScheme(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => 'sepa_core',
            'AUD' => 'becs',
            'NZD' => 'becs_nz',
            'USD' => 'ach',
            'CAD' => 'pad',
            'SEK' => 'autogiro',
            'DKK' => 'betalingsservice',
            default => 'bacs',
        };
    }

    private function _collectBillingCustomerDetails(string $billingRequestId, Submission $submission): void
    {
        $customer = [];
        $billingDetail = [];

        $billingFirstName = $this->getPaymentBillingFieldKey('billingFirstName');
        $billingLastName = $this->getPaymentBillingFieldKey('billingLastName');
        $billingAddress = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmail = $this->getPaymentBillingFieldKey('billingEmail');

        if ($billingFirstName && ($value = $submission->getFieldValueAsString($billingFirstName))) {
            $customer['given_name'] = $value;
        }

        if ($billingLastName && ($value = $submission->getFieldValueAsString($billingLastName))) {
            $customer['family_name'] = $value;
        }

        if ($billingEmail && ($value = $submission->getFieldValueAsString($billingEmail))) {
            $customer['email'] = $value;
        }

        if ($billingAddress && ($address = $submission->getFieldValueAsArray($billingAddress)) && is_array($address)) {
            $billingDetail['address_line1'] = ArrayHelper::remove($address, 'address1');
            $billingDetail['address_line2'] = ArrayHelper::remove($address, 'address2');
            $billingDetail['city'] = ArrayHelper::remove($address, 'city');
            $billingDetail['postal_code'] = ArrayHelper::remove($address, 'zip');
            $billingDetail['region'] = ArrayHelper::remove($address, 'state');
            $billingDetail['country_code'] = ArrayHelper::remove($address, 'country');
        }

        if (!$customer && !$billingDetail) {
            return;
        }

        $payload = array_filter([
            'customer' => $customer ?: null,
            'customer_billing_detail' => $billingDetail ?: null,
        ]);

        if (!$payload) {
            return;
        }

        try {
            $this->request('POST', "billing_requests/{$billingRequestId}/actions/collect_customer_details", [
                'json' => ['data' => $payload],
            ]);
        } catch (Throwable $e) {
            Formie::warning('GoCardless customer prefill failed for billing request "{billingRequestId}": {message}', [
                'billingRequestId' => $billingRequestId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function _syncPaymentFromBillingRequestReturn(PaymentModel $payment): void
    {
        $billingRequestId = $this->_resolveBillingRequestId($payment);

        if (!$billingRequestId) {
            return;
        }

        $billingRequest = $this->_fetchBillingRequest($billingRequestId);

        if (!$billingRequest) {
            return;
        }

        $this->_persistBillingRequestState($payment, $billingRequest);

        if (($billingRequest['status'] ?? '') !== 'fulfilled') {
            return;
        }

        $submission = $payment->getSubmission();

        if (!$submission) {
            throw new Exception(Craft::t('formie', 'Unable to find the submission for this payment.'));
        }

        $existing = is_array($payment->response) ? $payment->response : [];

        if (!empty($existing['gcPayment']['id'])) {
            $this->_updatePaymentStatus($payment, $existing['gcPayment']);

            return;
        }

        if (!empty($existing['gcSubscription']['id'])) {
            $this->_refreshGoCardlessSubscription($payment);

            return;
        }

        $gcPaymentId = $this->_resolveBillingRequestPaymentId($billingRequest);

        if ($gcPaymentId) {
            $gcPayment = $this->request('GET', "payments/{$gcPaymentId}")['payments'] ?? [];
            $this->_updatePaymentStatus($payment, $gcPayment);

            return;
        }

        $mandateId = $this->_resolveBillingRequestMandateId($billingRequest);

        if (!$mandateId) {
            return;
        }

        if ($this->_isSubscriptionPayment($payment)) {
            $gcSubscription = $this->_createGoCardlessSubscriptionForMandate($payment, $submission, $mandateId);
            $this->_finalizeSubscriptionPayment($payment, $submission, $gcSubscription);

            return;
        }

        $gcPayment = $this->_createGoCardlessPaymentForMandate($payment, $submission, $mandateId);
        $this->_updatePaymentStatus($payment, $gcPayment);
    }

    private function _resolveBillingRequestId(PaymentModel $payment): ?string
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsWebRequest()) {
            $queryBillingRequestId = trim((string)$request->getParam('billing_request_id'));

            if ($queryBillingRequestId !== '') {
                return $queryBillingRequestId;
            }
        }

        $stored = is_array($payment->response) ? $payment->response : [];

        return $stored['billingRequest']['id']
            ?? $stored['billingRequestFlow']['links']['billing_request']
            ?? null;
    }

    private function _fetchBillingRequest(string $billingRequestId): array
    {
        return $this->request('GET', "billing_requests/{$billingRequestId}")['billing_requests'] ?? [];
    }

    private function _persistBillingRequestState(PaymentModel $payment, array $billingRequest): void
    {
        $existing = is_array($payment->response) ? $payment->response : [];
        $payment->response = array_merge($existing, [
            'billingRequest' => $billingRequest,
            'mandateId' => $this->_resolveBillingRequestMandateId($billingRequest),
        ]);

        if ($payment->status === PaymentModel::STATUS_REDIRECT && ($billingRequest['status'] ?? '') === 'fulfilled') {
            $payment->status = PaymentModel::STATUS_PENDING;
        }

        Formie::$plugin->getPayments()->savePayment($payment);
    }

    private function _resolveBillingRequestMandateId(array $billingRequest): ?string
    {
        $candidates = [
            $billingRequest['links']['mandate'] ?? null,
            $billingRequest['mandate_request']['links']['mandate'] ?? null,
            $billingRequest['links']['mandate_request_mandate'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function _resolveBillingRequestPaymentId(array $billingRequest): ?string
    {
        $candidates = [
            $billingRequest['links']['payment'] ?? null,
            $billingRequest['payment_request']['links']['payment'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function _processPaymentWebhookEvent(array $event): void
    {
        $resourceId = $event['links']['payment'] ?? null;

        if (!$resourceId) {
            return;
        }

        $gcPayment = $this->request('GET', "payments/{$resourceId}")['payments'] ?? [];
        $gcSubscriptionId = $gcPayment['links']['subscription'] ?? null;

        if ($gcSubscriptionId) {
            $this->_processSubscriptionPaymentWebhook($gcPayment, (string)$gcSubscriptionId);

            return;
        }

        $formiePaymentId = $gcPayment['metadata']['formiePaymentId'] ?? null;

        if (!$formiePaymentId) {
            Integration::error($this, 'Missing `formiePaymentId` in GoCardless metadata.');
            return;
        }

        $payment = Formie::$plugin->getPayments()->getPaymentById((int)$formiePaymentId);

        if (!$payment) {
            Integration::error($this, "No Formie payment found for ID: {$formiePaymentId}");
            return;
        }

        if ($field = $payment->getField()) {
            $this->setField($field);
        }

        $this->_updatePaymentStatus($payment, $gcPayment);
    }

    private function _processBillingRequestWebhookEvent(array $event, string $action): void
    {
        if (!in_array($action, ['fulfilled', 'created', 'confirmed'], true)) {
            return;
        }

        $billingRequestId = $event['links']['billing_request'] ?? null;

        if (!$billingRequestId) {
            return;
        }

        $billingRequest = $this->_fetchBillingRequest($billingRequestId);
        $formiePaymentId = $billingRequest['metadata']['formiePaymentId'] ?? null;

        if (!$formiePaymentId) {
            return;
        }

        $payment = Formie::$plugin->getPayments()->getPaymentById((int)$formiePaymentId);

        if (!$payment) {
            Integration::error($this, "No Formie payment found for billing request ID: {$formiePaymentId}");
            return;
        }

        if ($field = $payment->getField()) {
            $this->setField($field);
        }

        $this->_syncPaymentFromBillingRequestReturn($payment);
    }

    private function _updatePaymentStatus(PaymentModel $payment, array $gcPayment): void
    {
        if (!$gcPayment) {
            return;
        }

        $status = $gcPayment['status'] ?? '';

        switch ($status) {
            case 'confirmed':
            case 'paid_out':
                $payment->status = PaymentModel::STATUS_SUCCESS;
                break;
            case 'failed':
            case 'cancelled':
            case 'charged_back':
            case 'customer_approval_denied':
                $payment->status = PaymentModel::STATUS_FAILED;
                break;
            case 'pending_submission':
            case 'submitted':
            default:
                $payment->status = PaymentModel::STATUS_PENDING;
                break;
        }

        $existing = is_array($payment->response) ? $payment->response : [];
        $preserved = [];

        foreach (['billingRequest', 'billingRequestFlow', 'mandateId', 'gcSubscription'] as $key) {
            if (isset($existing[$key])) {
                $preserved[$key] = $existing[$key];
            }
        }

        $payment->reference = $gcPayment['id'] ?? $payment->reference;
        $payment->response = array_merge($preserved, [
            'gcPayment' => $gcPayment,
        ]);

        Formie::$plugin->getPayments()->savePayment($payment);
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    private function _createGoCardlessPaymentForMandate(PaymentModel $payment, Submission $submission, string $mandateId): array
    {
        $currency = (string)$payment->currency;
        $amountMinor = $this->_amountToMinorUnits((float)$payment->amount, $currency);

        if ($amountMinor < 1) {
            throw new Exception(Craft::t('formie', 'The payment amount is too small for GoCardless.'));
        }

        $paymentDescription = $this->getFieldSetting('paymentDescription') ?? "Formie Submission #{$submission->id}";
        $referenceBase = References::parseContent($paymentDescription, $submission);
        $reference = strtoupper(substr(preg_replace('/[^A-Za-z0-9\-]/', '-', (string)$referenceBase), 0, 18));

        if ($reference === '' || $reference === '-') {
            $reference = 'F' . $submission->id;
            $reference = strtoupper(substr($reference, 0, 18));
        }

        $payload = [
            'amount' => $amountMinor,
            'currency' => strtoupper($currency),
            'description' => (string)$referenceBase,
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

    private function _isSubscriptionPayment(PaymentModel $payment): bool
    {
        if ($this->getFieldSetting('type') === self::PAYMENT_TYPE_SUBSCRIPTION) {
            return true;
        }

        $stored = is_array($payment->response) ? $payment->response : [];

        return ($stored['billingRequest']['metadata']['formiePaymentType'] ?? null) === self::PAYMENT_TYPE_SUBSCRIPTION;
    }

    private function _resolveGoCardlessSubscriptionInterval(): array
    {
        $frequencyValue = max(1, (int)$this->getFieldSetting('frequencyValue', 1));
        $frequencyType = (string)$this->getFieldSetting('frequencyType', 'month');

        return match ($frequencyType) {
            'week' => ['interval_unit' => 'weekly', 'interval' => $frequencyValue],
            'month' => ['interval_unit' => 'monthly', 'interval' => $frequencyValue],
            'year' => ['interval_unit' => 'yearly', 'interval' => $frequencyValue],
            default => throw new Exception(Craft::t('formie', 'GoCardless subscriptions support weekly, monthly, or yearly billing intervals only.')),
        };
    }

    private function _createGoCardlessSubscriptionForMandate(PaymentModel $payment, Submission $submission, string $mandateId): array
    {
        $currency = strtoupper((string)$payment->currency);
        $amountMinor = $this->_amountToMinorUnits((float)$payment->amount, $currency);

        if ($amountMinor < 1) {
            throw new Exception(Craft::t('formie', 'The payment amount is too small for GoCardless.'));
        }

        $interval = $this->_resolveGoCardlessSubscriptionInterval();
        $description = $this->getFieldSetting('subscriptionDescription')
            ?? $this->getFieldSetting('paymentDescription')
            ?? ('Formie Subscription #' . $submission->id);
        $name = References::parseContent($description, $submission);

        $payload = [
            'amount' => $amountMinor,
            'currency' => $currency,
            'name' => (string)$name,
            'interval_unit' => $interval['interval_unit'],
            'interval' => $interval['interval'],
            'metadata' => [
                'formiePaymentId' => (string)$payment->id,
            ],
            'links' => [
                'mandate' => $mandateId,
            ],
        ];

        $apiResponse = $this->request('POST', 'subscriptions', [
            'json' => ['subscriptions' => $payload],
            'headers' => [
                'Idempotency-Key' => substr($payment->uid . '-sub-create', 0, 120),
            ],
        ]);

        $gcSubscription = $apiResponse['subscriptions'] ?? [];

        if (!$gcSubscription || empty($gcSubscription['id'])) {
            throw new Exception(Craft::t('formie', 'GoCardless did not return a subscription resource.'));
        }

        return $gcSubscription;
    }

    private function _finalizeSubscriptionPayment(PaymentModel $payment, Submission $submission, array $gcSubscription): void
    {
        $subscription = new Subscription();
        $subscription->integrationId = $this->id;
        $subscription->submissionId = $submission->id;
        $subscription->fieldId = $payment->fieldId;
        $subscription->reference = $gcSubscription['id'];
        $subscription->subscriptionData = $gcSubscription;
        $subscription->trialDays = 0;
        $this->_setSubscriptionStatusData($subscription, $gcSubscription);

        Formie::$plugin->getSubscriptions()->saveSubscription($subscription);

        $existing = is_array($payment->response) ? $payment->response : [];
        $preserved = [];

        foreach (['billingRequest', 'billingRequestFlow', 'mandateId'] as $key) {
            if (isset($existing[$key])) {
                $preserved[$key] = $existing[$key];
            }
        }

        $payment->subscriptionId = $subscription->id;
        $payment->reference = $gcSubscription['id'];
        $payment->status = match ($gcSubscription['status'] ?? '') {
            'active' => PaymentModel::STATUS_SUCCESS,
            'customer_approval_denied', 'cancelled' => PaymentModel::STATUS_FAILED,
            default => PaymentModel::STATUS_PENDING,
        };
        $payment->response = array_merge($preserved, [
            'gcSubscription' => $gcSubscription,
        ]);

        Formie::$plugin->getPayments()->savePayment($payment);
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    private function _refreshGoCardlessSubscription(PaymentModel $payment, ?array $gcSubscription = null): void
    {
        $existing = is_array($payment->response) ? $payment->response : [];
        $subscriptionReference = $payment->reference ?: ($existing['gcSubscription']['id'] ?? null);

        if (!$subscriptionReference) {
            return;
        }

        if (!$gcSubscription) {
            $gcSubscription = $this->request('GET', "subscriptions/{$subscriptionReference}")['subscriptions'] ?? [];
        }

        if (!$gcSubscription) {
            throw new Exception(Craft::t('formie', 'Unable to resolve GoCardless subscription.'));
        }

        $subscription = $payment->subscriptionId
            ? Formie::$plugin->getSubscriptions()->getSubscriptionById((int)$payment->subscriptionId)
            : Formie::$plugin->getSubscriptions()->getSubscriptionByReference((string)$subscriptionReference);

        if ($subscription) {
            $subscription->subscriptionData = $gcSubscription;
            $this->_setSubscriptionStatusData($subscription, $gcSubscription);
            Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
        }

        $preserved = [];

        foreach (['billingRequest', 'billingRequestFlow', 'mandateId'] as $key) {
            if (isset($existing[$key])) {
                $preserved[$key] = $existing[$key];
            }
        }

        $payment->reference = $gcSubscription['id'] ?? $payment->reference;
        $payment->status = match ($gcSubscription['status'] ?? '') {
            'active' => PaymentModel::STATUS_SUCCESS,
            'customer_approval_denied', 'cancelled' => PaymentModel::STATUS_FAILED,
            'finished' => PaymentModel::STATUS_SUCCESS,
            default => PaymentModel::STATUS_PENDING,
        };
        $payment->response = array_merge($preserved, [
            'gcSubscription' => $gcSubscription,
        ]);

        Formie::$plugin->getPayments()->savePayment($payment);
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    private function _setSubscriptionStatusData(Subscription $subscription, array $gcSubscription): void
    {
        $status = (string)($gcSubscription['status'] ?? '');

        $subscription->isCanceled = $status === 'cancelled';
        $subscription->isExpired = $status === 'finished';
        $subscription->hasStarted = in_array($status, ['active', 'finished', 'cancelled'], true);

        if ($subscription->isCanceled && !$subscription->dateCanceled) {
            $subscription->dateCanceled = DateTimeHelper::toDateTime(new \DateTime());
        }

        if ($subscription->isExpired && !$subscription->dateExpired) {
            $subscription->dateExpired = DateTimeHelper::toDateTime(new \DateTime());
        }

        $nextChargeDate = $gcSubscription['upcoming_payments'][0]['charge_date'] ?? null;

        if ($nextChargeDate) {
            $subscription->nextPaymentDate = DateTimeHelper::toDateTime($nextChargeDate);
        }
    }

    private function _processSubscriptionPaymentWebhook(array $gcPayment, string $gcSubscriptionId): void
    {
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($gcSubscriptionId);

        if (!$subscription) {
            return;
        }

        if ($field = $subscription->getField()) {
            $this->setField($field);
        }

        $gcSubscription = $this->request('GET', "subscriptions/{$gcSubscriptionId}")['subscriptions'] ?? [];

        if ($gcSubscription) {
            $subscription->subscriptionData = $gcSubscription;
            $this->_setSubscriptionStatusData($subscription, $gcSubscription);
            Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
        }

        $status = (string)($gcPayment['status'] ?? '');

        if (in_array($status, ['confirmed', 'paid_out'], true) && !empty($gcSubscription['upcoming_payments'][0]['charge_date'])) {
            Formie::$plugin->getSubscriptions()->receivePayment(
                $subscription,
                DateTimeHelper::toDateTime($gcSubscription['upcoming_payments'][0]['charge_date']),
            );
        }
    }

    private function _processSubscriptionWebhookEvent(array $event, string $action): void
    {
        if (!in_array($action, ['created', 'cancelled', 'finished', 'payment_created', 'amended'], true)) {
            return;
        }

        $resourceId = $event['links']['subscription'] ?? null;

        if (!$resourceId) {
            return;
        }

        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference((string)$resourceId);

        if (!$subscription) {
            return;
        }

        if ($field = $subscription->getField()) {
            $this->setField($field);
        }

        $gcSubscription = $this->request('GET', "subscriptions/{$resourceId}")['subscriptions'] ?? [];

        if (!$gcSubscription) {
            return;
        }

        $subscription->subscriptionData = $gcSubscription;
        $this->_setSubscriptionStatusData($subscription, $gcSubscription);
        Formie::$plugin->getSubscriptions()->saveSubscription($subscription);

        $formiePaymentId = $gcSubscription['metadata']['formiePaymentId'] ?? null;

        if (!$formiePaymentId) {
            return;
        }

        $payment = Formie::$plugin->getPayments()->getPaymentById((int)$formiePaymentId);

        if (!$payment) {
            return;
        }

        if ($field = $payment->getField()) {
            $this->setField($field);
        }

        $this->_refreshGoCardlessSubscription($payment, $gcSubscription);
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

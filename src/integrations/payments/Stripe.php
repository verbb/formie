<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\PaymentAmountHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\References;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;
use verbb\formie\models\Subscription;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;
use yii\web\NotFoundHttpException;

use NumberFormatter;
use Throwable;
use Exception;

use Stripe\StripeClient;
use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Stripe\Exception as StripeException;
use Stripe\Invoice as StripeInvoice;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook as StripeWebhook;

class Stripe extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_SUBSCRIPTION_PAYLOAD = 'modifySubscriptionPayload';
    public const EVENT_MODIFY_SUBSCRIPTION_SCHEDULE_PAYLOAD = 'modifySubscriptionSchedulePayload';
    public const EVENT_MODIFY_SINGLE_PAYLOAD = 'modifySinglePayload';
    public const EVENT_MODIFY_PLAN_PAYLOAD = 'modifyPlanPayload';
    public const EVENT_MODIFY_CUSTOMER_PAYLOAD = 'modifyCustomerPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';

    // https://stripe.com/docs/currencies#zero-decimal
    private const ZERO_DECIMAL_CURRENCIES = ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
    private const STRIPE_EVENT_PAYMENT_INTENT_PROCESSING = 'payment_intent.processing';
    private const STRIPE_PAYMENT_INTENT_STATUS_PROCESSING = 'processing';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Stripe');
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function supportsCallbacks(): bool
    {
        return true;
    }

    public function requiresAjaxSubmission(): bool
    {
        return true;
    }

    public static function toStripeAmount(float $amount, string $currency): int
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES)) {
            return (int)ceil($amount);
        }

        return (int)ceil($amount * 100);
    }

    public static function fromStripeAmount(float $amount, string $currency): float
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES)) {
            return $amount;
        }

        return $amount * 0.01;
    }


    // Properties
    // =========================================================================

    public ?string $publishableKey = null;
    public ?string $secretKey = null;
    public ?string $webhookSecretKey = null;
    public bool $hidePostalCode = false;
    public bool $hideIcon = false;

    private ?StripeClient $_stripe = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public static function getSiteCurrency(): ?string
    {
        if ($locale = Craft::$app->getFormattingLocale()->id) {
            if ($numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL)) {
                if ($currency = $numberFormatter->getSymbol(NumberFormatter::INTL_CURRENCY_SYMBOL)) {
                    return strtolower($currency);
                }
            }
        }

        return null;
    }

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return ['stripePaymentId', 'stripeSubscriptionId'];
    }

    public function getInitialPaymentInformation(): array
    {
        $currency = static::getSiteCurrency();
        $currencyType = $this->getFieldSetting('currencyType');
        $currencyFixed = $this->getFieldSetting('currencyFixed');
        $currencyVariable = $this->normalizeClientFieldReference($this->getFieldSetting('currencyVariable'));

        if ($currencyType === Payment::VALUE_TYPE_FIXED) {
            $currency = strtolower($currencyFixed);
        } else if ($currencyType === Payment::VALUE_TYPE_DYNAMIC) {
            $currency = $currencyVariable;
        }

        // Set a default amount for when using dynamic values. This is changed on the front-end when updated there.
        $amount = self::toStripeAmount(100, $currency);
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable'));

        if ($amountType === Payment::VALUE_TYPE_FIXED) {
            $amount = self::toStripeAmount((float)$amountFixed, $currency);
        } else if ($amountType === Payment::VALUE_TYPE_DYNAMIC) {
            $amount = $amountVariable;
        }

        return [
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);
        $billingDetails = $this->getFieldSetting('billingDetails', false);

        if (is_array($billingDetails)) {
            $normalizedBillingDetails = [];

            foreach (['billingName', 'billingEmail', 'billingAddress'] as $key) {
                $normalized = $this->normalizeClientFieldReference(
                    $this->normalizeFieldMappingValue($billingDetails[$key] ?? '')
                );

                if ($normalized) {
                    $normalizedBillingDetails[$key] = $normalized;
                }
            }

            $billingDetails = $normalizedBillingDetails;
        }

        $hidePostalCode = $this->getFieldSetting('hidePostalCode', false);
        $hideIcon = $this->getFieldSetting('hideIcon', false);
        $paymentType = $this->getFieldSetting('type', 'single');

        return new ClientModule([
            'id' => 'stripe',
            'config' => [
                'publishableKey' => App::parseEnv($this->publishableKey),
                'billingDetails' => $billingDetails,
                'hidePostalCode' => $hidePostalCode,
                'hideIcon' => $hideIcon,
                'paymentType' => $paymentType,
                'amountType' => $this->getFieldSetting('amountType'),
                'currencyType' => $this->getFieldSetting('currencyType'),
                'initialPaymentInformation' => $this->getInitialPaymentInformation(),
                'requiredInputSuffixes' => ['stripePaymentIntentId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->publishableKey) && App::parseEnv($this->secretKey);
    }

    public function getReturnUrl(Submission $submission): string
    {
        $url = 'formie/payment-webhooks/process-callback';
        $params = ['token' => $submission->uid, 'handle' => $this->handle];

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return UrlHelper::actionUrl($url, $params);
        }

        return UrlHelper::siteUrl($url, $params);
    }

    public function getAmount(Submission $submission): float
    {
        // Ensure the amount is converted to Stripe for zero-decimal currencies
        return self::toStripeAmount(parent::getAmount($submission), $this->getCurrency($submission));
    }

    public function getSubscriptionPaymentLimit(Submission $submission): ?int
    {
        $limitType = $this->getFieldSetting('subscriptionLimitType');

        if ($limitType === Payment::VALUE_TYPE_FIXED) {
            $value = $this->getFieldSetting('subscriptionLimitFixed');
        } elseif ($limitType === Payment::VALUE_TYPE_DYNAMIC) {
            $value = References::parseValue($this->getFieldSetting('subscriptionLimitVariable'), $submission);
        } else {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $limit = (int)$value;

        return $limit > 0 ? $limit : null;
    }

    public function getSubscriptionSetupFee(Submission $submission): ?int
    {
        $feeType = $this->getFieldSetting('subscriptionSetupFeeType');

        if ($feeType === Payment::VALUE_TYPE_FIXED) {
            $value = $this->getFieldSetting('subscriptionSetupFeeFixed');
        } elseif ($feeType === Payment::VALUE_TYPE_DYNAMIC) {
            $value = References::parseValue($this->getFieldSetting('subscriptionSetupFeeVariable'), $submission);
        } else {
            return null;
        }

        $amount = PaymentAmountHelper::parseAmount($value);

        if ($amount <= 0) {
            return null;
        }

        $currency = $this->getCurrency($submission);

        if (!$currency) {
            return null;
        }

        return self::toStripeAmount($amount, $currency);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $result = false;

        $type = $this->getFieldSetting('type');

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        if ($type === self::PAYMENT_TYPE_SINGLE) {
            $result = $this->processSinglePayment($submission);
        } else if ($type === self::PAYMENT_TYPE_SUBSCRIPTION) {
            $result = $this->processSubscriptionPayment($submission);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return PaymentDecision::succeeded($this->handle);
        }

        $field = $this->getField();

        if (!$field) {
            return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
        }

        $latestPayment = null;

        foreach (Formie::$plugin->getPayments()->getSubmissionPayments($submission) as $payment) {
            if ((int)$payment->fieldId === (int)$field->id) {
                $latestPayment = $payment;
            }
        }

        if ($latestPayment) {
            return match ((string)$latestPayment->status) {
                PaymentModel::STATUS_SUCCESS => PaymentDecision::succeeded($this->handle, $latestPayment->reference),
                PaymentModel::STATUS_REDIRECT => PaymentDecision::requiresAction(
                    $latestPayment->reference,
                    PaymentAction::redirectEvent('formie:payment:stripe:confirm')
                        ->forProvider($this->handle)
                        ->withMessage($latestPayment->message ?: Craft::t('formie', 'Additional payment confirmation is required to continue.'))
                        ->resumeMode(PaymentAction::RESUME_MODE_CALLBACK, $this->getReturnUrl($submission))
                ),
                PaymentModel::STATUS_PENDING, PaymentModel::STATUS_PROCESSING => PaymentDecision::requiresAction(
                    $latestPayment->reference,
                    PaymentAction::confirmEvent('formie:payment:stripe:confirm')
                        ->forProvider($this->handle)
                        ->withMessage($latestPayment->message ?: Craft::t('formie', 'Additional payment confirmation is required to continue.'))
                        ->resumeMode(PaymentAction::RESUME_MODE_CALLBACK, $this->getReturnUrl($submission))
                ),
                PaymentModel::STATUS_FAILED => PaymentDecision::failed($latestPayment->message, $this->handle, $latestPayment->reference),
                default => $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle),
            };
        }

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
    }

    public function processSubscriptionPayment(Submission $submission): bool
    {
        $response = [];
        $payload = [];

        $field = $this->getField();
        $paymentPayload = $this->getPaymentFieldPayload($submission);
        $subscriptionId = $paymentPayload->string('stripeSubscriptionId');
        $paymentIntentId = $paymentPayload->string('stripePaymentIntentId');

        try {
            if ($subscriptionId) {
                $stripeSubscription = $this->getStripe()->subscriptions->retrieve($subscriptionId);

                if ($stripeSubscription) {
                    $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription->id);

                    if ($subscription) {
                        $subscription->reference = $stripeSubscription->id;
                        $subscription->subscriptionData = $stripeSubscription->toArray();

                        $this->_setSubscriptionStatusData($subscription, $stripeSubscription);

                        Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
                    } else {
                        throw new Exception('Unable to find subscription by "' . $stripeSubscription->id . '".');
                    }
                } else {
                    throw new Exception('Unable to find Stripe subscription by "' . $subscriptionId . '".');
                }

                return true;
            }

            // Get or create the plan (product) first
            $plan = $this->_getOrCreatePlan($submission);

            if (!$plan) {
                throw new Exception('Unable to get or create plan.');
            }

            // Get the Stripe customer. We create a new one each transaction
            $customer = $this->_getCustomer($submission);

            if (!$customer) {
                throw new Exception('Unable to create customer.');
            }

            $payload = [
                'customer' => $customer['id'],
                'items' => [['plan' => $plan->reference]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
            ];

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission, 'subscription');
            $this->_applySubscriptionSetupFee($payload, $submission);

            $setupFeeType = $this->getFieldSetting('subscriptionSetupFeeType');

            if ($setupFeeType === Payment::VALUE_TYPE_FIXED && $this->getSubscriptionSetupFee($submission) === null) {
                throw new Exception(Craft::t('formie', 'Enter a valid subscription setup fee.'));
            }

            // Raise a `modifySubscriptionPayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_SUBSCRIPTION_PAYLOAD, $event);

            $paymentLimit = $this->getSubscriptionPaymentLimit($submission);
            $limitType = $this->getFieldSetting('subscriptionLimitType');

            if ($limitType === Payment::VALUE_TYPE_FIXED && $paymentLimit === null) {
                throw new Exception(Craft::t('formie', 'Enter a valid subscription payment limit.'));
            }

            if ($paymentLimit !== null) {
                $schedulePayload = $this->_buildSubscriptionSchedulePayload($event->payload, $plan->reference, $paymentLimit);

                $scheduleEvent = new ModifyPaymentPayloadEvent([
                    'integration' => $this,
                    'submission' => $submission,
                    'payload' => $schedulePayload,
                ]);
                $this->trigger(self::EVENT_MODIFY_SUBSCRIPTION_SCHEDULE_PAYLOAD, $scheduleEvent);

                $scheduleResponse = $this->getStripe()->subscriptionSchedules->create($scheduleEvent->payload, [
                    'idempotency_key' => $this->_getIdempotencyKey($submission, 'subscription-schedule-create'),
                ]);

                $response = $this->_resolveScheduleSubscription($scheduleResponse);
                $scheduleId = $scheduleResponse->id;
            } else {
                // Create the Stripe subscription
                $response = $this->getStripe()->subscriptions->create($event->payload, [
                    'idempotency_key' => $this->_getIdempotencyKey($submission, 'subscription-create'),
                ]);
                $scheduleId = null;
            }

            // Create and record our Formie subscription
            $subscription = new Subscription();
            $subscription->integrationId = $this->id;
            $subscription->submissionId = $submission->id;
            $subscription->fieldId = $field->id;
            $subscription->planId = $plan->id;
            $subscription->reference = $response->id;
            $subscription->subscriptionData = $response->toArray();

            if ($scheduleId) {
                $subscription->subscriptionData['formieScheduleId'] = $scheduleId;
                $subscription->subscriptionData['formiePaymentLimit'] = $paymentLimit;
            }

            if (($setupFee = $this->getSubscriptionSetupFee($submission)) !== null) {
                $subscription->subscriptionData['formieSetupFee'] = $setupFee;
            }

            $subscription->trialDays = 0;

            $this->_setSubscriptionStatusData($subscription, $response);

            Formie::$plugin->getSubscriptions()->saveSubscription($subscription);

            $this->_addStripeSubscriptionConfirmSubmitData($submission, $response);

            return false;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Subscription error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, Craft::t('formie', 'A payment error has occurred “{message}”.', ['message' => $message]));

            return false;
        }

        return true;
    }

    public function processSinglePayment(Submission $submission): bool
    {
        $response = [];
        $payload = [];

        $field = $this->getField();
        $paymentPayload = $this->getPaymentFieldPayload($submission);
        $paymentIntentId = $paymentPayload->string('stripePaymentIntentId');

        try {
            if ($paymentIntentId) {
                $paymentIntent = $this->getStripe()->paymentIntents->retrieve($paymentIntentId);

                if ($paymentIntent) {
                    $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntent->id);

                    if (!$payment) {
                        throw new Exception('Unable to find payment by "' . $paymentIntent->id . '".');
                    }

                    // A PaymentIntent can only belong to one submission. If a stale
                    // hidden input is replayed on a new submission, fail safely.
                    if ((int)$payment->submissionId !== (int)$submission->id) {
                        Integration::warning($this, Craft::t('formie', 'Rejected Stripe PaymentIntent "{intentId}" reuse for submission "{submissionUid}". Intent belongs to submission ID {existingSubmissionId}.', [
                            'intentId' => $paymentIntent->id,
                            'submissionUid' => (string)($submission->uid ?? $submission->id ?? 'unknown'),
                            'existingSubmissionId' => (string)$payment->submissionId,
                        ]));

                        $this->addFieldError($submission, Craft::t('formie', 'Your previous payment session is no longer valid. Please refresh the payment details and try again.'));

                        return false;
                    }

                    if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                        $payment->status = PaymentModel::STATUS_SUCCESS;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();

                        Formie::$plugin->getPayments()->savePayment($payment);
                    } else if (in_array($paymentIntent->status, [
                        PaymentIntent::STATUS_REQUIRES_ACTION,
                        PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
                    ], true)) {
                        $payment->status = PaymentModel::STATUS_PENDING;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();
                        $payment->message = $paymentIntent->last_payment_error?->message ?? Craft::t('formie', 'Payment confirmation is still required.');

                        Formie::$plugin->getPayments()->savePayment($payment);

                        if (!empty($paymentIntent->client_secret)) {
                            $submission->getForm()->addSubmitData([
                                'event' => 'formie:payment:stripe:confirm',
                                'data' => [
                                    'clientSecret' => $paymentIntent->client_secret,
                                    'paymentIntentId' => $paymentIntent->id,
                                    'returnUrl' => $this->getReturnUrl($submission),
                                ],
                            ]);

                            return false;
                        }

                        $payment->status = PaymentModel::STATUS_FAILED;
                        $payment->message = Craft::t('formie', 'Payment requires additional confirmation, but no client secret was returned.');
                        Formie::$plugin->getPayments()->savePayment($payment);
                        $this->addFieldError($submission, $payment->message);
                        return false;
                    } else if ($paymentIntent->status === PaymentIntent::STATUS_PROCESSING) {
                        $payment->status = PaymentModel::STATUS_PROCESSING;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();
                        $payment->message = Craft::t('formie', 'Payment is still processing. Please wait a moment and submit again.');

                        Formie::$plugin->getPayments()->savePayment($payment);

                        $this->addFieldError($submission, $payment->message);
                        return false;
                    } else {
                        $payment->status = PaymentModel::STATUS_FAILED;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();
                        $payment->message = $paymentIntent->last_payment_error?->message ?? Craft::t('formie', 'Unable to confirm payment intent "{status}".', [
                            'status' => $paymentIntent->status,
                        ]);

                        Formie::$plugin->getPayments()->savePayment($payment);

                        $this->addFieldError($submission, $payment->message);
                        return false;
                    }
                } else {
                    throw new Exception('Unable to find payment intent by "' . $paymentIntentId . '".');
                }

                return true;
            }

            // If this submission/field already has a pending intent, reuse it instead
            // of creating a new one (prevents duplicate create attempts on retries).
            $existingPendingPayment = $this->_getLatestPendingPaymentForField($submission, (int)$field->id);
            $existingClientSecret = $existingPendingPayment->response['client_secret'] ?? null;
            $existingPaymentIntentId = $existingPendingPayment->reference ?? null;

            if ($existingPendingPayment && $existingClientSecret && $existingPaymentIntentId) {
                Integration::info($this, Craft::t('formie', 'Reusing pending Stripe PaymentIntent "{intentId}" for submission "{submissionUid}" (field ID: {fieldId}).', [
                    'intentId' => $existingPaymentIntentId,
                    'submissionUid' => (string)($submission->uid ?? $submission->id ?? 'unknown'),
                    'fieldId' => (string)$field->id,
                ]));

                $submission->getForm()->addSubmitData([
                    'event' => 'formie:payment:stripe:confirm',
                    'data' => [
                        'clientSecret' => $existingClientSecret,
                        'paymentIntentId' => $existingPaymentIntentId,
                        'returnUrl' => $this->getReturnUrl($submission),
                    ],
                ]);

                return false;
            }

            $amount = 0;
            $currency = null;

            // Get the amount from the field, which handles dynamic fields
            $amount = $this->getAmount($submission);
            $currency = $this->getCurrency($submission);

            if (!$amount) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            $payload = [
                'amount' => $amount,
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
            ];

            // Get the Stripe customer. We create a new one each transaction
            if ($customer = $this->_getCustomer($submission)) {
                $payload['customer'] = $customer['id'];
            }

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission, 'single');

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_SINGLE_PAYLOAD, $event);

            // Create a Payment Intent for the transaction, which we'll confirm in JS. This will either capture it immediately, challenge with
            // 3DS verification, or redirect to an off-site payment method.
            $response = $this->getStripe()->paymentIntents->create($event->payload, [
                'idempotency_key' => $this->_getIdempotencyKey($submission, 'payment-intent-create', [
                    'amount' => $event->payload['amount'] ?? null,
                    'currency' => $event->payload['currency'] ?? null,
                    'fieldId' => $field->id,
                ]),
            ]);

            // Save a pending payment before we head back to the front-end
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromStripeAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_PENDING;
            $payment->reference = $response->id;
            $payment->response = $response->toArray();

            Formie::$plugin->getPayments()->savePayment($payment);

            // Tell the front-end to stop the submission and to confirm the Payment Intent.
            $submission->getForm()->addSubmitData([
                'event' => 'formie:payment:stripe:confirm',
                'data' => [
                    'clientSecret' => $response->client_secret,
                    'paymentIntentId' => $response->id,
                    'returnUrl' => $this->getReturnUrl($submission),
                ],
            ]);

            return false;
        } catch (StripeException\CardException $e) {
            $body = $e->getJsonBody();

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromStripeAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->reference = $body['error']['charge'] ?? null;
            $payment->code = $body['error']['code'] ?? null;
            $payment->message = $body['error']['message'] ?? null;
            $payment->response = $body;

            Formie::$plugin->getPayments()->savePayment($payment);

            $this->addFieldError($submission, $payment->message);

            return false;
        } catch (StripeException\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $message = $body['error']['message'] ?? $e->getMessage();

            if (str_contains(strtolower((string)$message), 'idempotent')) {
                $message = Craft::t('formie', 'Payment setup was already in progress. Please try submitting again.');
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromStripeAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->reference = null;
            $payment->code = $body['error']['code'] ?? $body['error']['type'] ?? $e->getStripeCode();
            $payment->message = $message;
            $payment->response = $body;

            Formie::$plugin->getPayments()->savePayment($payment);

            $this->addFieldError($submission, $payment->message);

            return false;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, Craft::t('formie', 'A payment error has occurred “{message}”.', ['message' => $message]));

            return false;
        }

        return true;
    }

    public function processCallback(): Response
    {
        $form = null;
        $origin = '/';

        try {
            $request = Craft::$app->getRequest();
            $genericPaymentError = Craft::t('formie', 'We were unable to verify your payment. Please try again or contact support.');

            $origin = StringHelper::sanitizeRedirectUrl((string)$request->getParam('origin'));
            $token = $request->getParam('token');
            $paymentIntentId = $request->getParam('payment_intent');

            if (!$token) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            $submission = Submission::find()->isIncomplete(true)->uid($token)->one();

            if (!$submission) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            $form = $submission->form;

            if (!$paymentIntentId) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntentId);

            if (!$payment) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            if ((int)$payment->submissionId !== (int)$submission->id) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            $paymentIntent = $this->getStripe()->paymentIntents->retrieve($paymentIntentId);

            if (!$paymentIntent) {
                throw new NotFoundHttpException($genericPaymentError);
            }

            if (!$this->_isProcessablePaymentIntentStatus($paymentIntent->status)) {
                $payment->status = PaymentModel::STATUS_FAILED;
                $payment->reference = $paymentIntentId;

                Formie::$plugin->getPayments()->savePayment($payment);

                throw new Exception($genericPaymentError);
            }

            // Complete the submission and lodge the payment
            $payment->status = $this->_getPaymentStatusFromPaymentIntentStatus($paymentIntent->status);
            $payment->reference = $paymentIntentId;

            Formie::$plugin->getPayments()->savePayment($payment);
            $replay = Formie::$plugin->getSubmissionProcessor()->executePaymentReplay($payment);

            if (!$replay->response?->success) {
                throw new Exception(Craft::t('formie', 'Unable to finalize submission after payment confirmation.'));
            }

            Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);
            Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            if ($form) {
                Formie::$plugin->getService()->setError($form->id, Craft::t('formie', 'We were unable to verify your payment. Please try again or contact support.'));
            }
        }

        // Check the form settings for what needs to be done, as we're returning from offssite
        if ($form && ($redirect = $form->getRedirectUrl())) {
            $origin = $redirect;
        }

        return Craft::$app->getResponse()->redirect($origin ?: '/');
    }

    public function processWebhook(): Response
    {
        $rawData = Craft::$app->getRequest()->getRawBody();
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $secret = App::parseEnv($this->webhookSecretKey);
        $stripeSignature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (!$secret || !$stripeSignature) {
            Integration::error($this, 'Webhook not signed or signing secret not set.');
            $response->setStatusCode(400);
            $response->data = 'error';

            return $response;
        }

        try {
            // Check the payload and signature
            StripeWebhook::constructEvent($rawData, $stripeSignature, $secret);
        } catch (Throwable $e) {
            Integration::error($this, 'Webhook signature check failed: ' . Integration::getExceptionLogMessage($e));
            $response->data = 'ok';

            return $response;
        }

        $data = Json::decodeIfJson($rawData);

        if ($data) {
            try {
                if ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_CREATED) {
                    $this->handleSubscriptionCreated($data);
                } else if ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_DELETED) {
                    $this->handleSubscriptionExpired($data);
                } else if ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_UPDATED) {
                    $this->handleSubscriptionUpdated($data);
                } else if ($data['type'] === StripeEvent::INVOICE_CREATED) {
                    $this->handleInvoiceCreated($data);
                } else if ($data['type'] === StripeEvent::INVOICE_PAYMENT_FAILED) {
                    $this->handleInvoiceFailed($data);
                } else if ($data['type'] === StripeEvent::INVOICE_PAYMENT_SUCCEEDED) {
                    $this->handleInvoiceSucceeded($data);
                } else if ($data['type'] === StripeEvent::PLAN_DELETED) {
                    $this->handlePlanDeleted($data);
                } else if ($data['type'] === StripeEvent::PLAN_UPDATED) {
                    $this->handlePlanUpdated($data);
                } else if ($data['type'] === StripeEvent::PAYMENT_INTENT_CANCELED) {
                    $this->handlePaymentIntent($data);
                } else if ($data['type'] === StripeEvent::PAYMENT_INTENT_PAYMENT_FAILED) {
                    $this->handlePaymentIntent($data);
                } else if ($data['type'] === self::STRIPE_EVENT_PAYMENT_INTENT_PROCESSING) {
                    $this->handlePaymentIntent($data);
                } else if ($data['type'] === StripeEvent::PAYMENT_INTENT_SUCCEEDED) {
                    $this->handlePaymentIntent($data);
                }
            } catch (Throwable $e) {
                Integration::apiError($this, $e, false);
            }

            if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
                $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                    'webhookData' => $data,
                ]));
            }
        } else {
            Integration::error($this, 'Could not decode JSON payload.');
        }

        $response->data = 'ok';

        return $response;
    }

    public function cancelSubscription($reference, $params = []): ?array
    {
        try {
            $stripeSubscription = $this->getStripe()->subscriptions->retrieve($reference);
            $cancelImmediately = $params['cancelImmediately'] ?? false;

            if ($cancelImmediately) {
                $response = $stripeSubscription->cancel();
            } else {
                $stripeSubscription->cancel_at_period_end = true;
                $response = $stripeSubscription->save();
            }

            $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($reference);

            if ($subscription) {
                $subscription->subscriptionData = $response->toArray();

                $this->_setSubscriptionStatusData($subscription);

                Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
            }

            return $response->toArray();
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }

        return null;
    }

    public function fetchConnection(): bool
    {
        try {
            $charges = $this->getStripe()->charges->all(['limit' => 1]);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            return false;
        }

        return true;
    }

    public function getStripe(): StripeClient
    {
        if ($this->_stripe) {
            return $this->_stripe;
        }

        \Stripe\Stripe::setAppInfo('Craft Formie', Formie::$plugin->getVersion(), 'https://verbb.io/craft-plugins/formie');

        return $this->_stripe = new StripeClient([
            'api_key' => App::parseEnv($this->secretKey),
            'stripe_version' => '2020-08-27',
        ]);
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
                'label' => Craft::t('formie', 'Payment Currency'),
                'instructions' => Craft::t('formie', 'Provide the currency to be used for the transaction. This can be either a fixed value, or derived from a field.'),
                'required' => true,
                'children' => [
                    SchemaHelper::selectField([
                        'name' => 'currencyType',
                        'required' => true,
                        'options' => [
                            ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                            ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                        ],
                    ]),
                    SchemaHelper::comboboxField([
                        'name' => 'currencyFixed',
                        'required' => true,
                        'if' => 'currencyType == "' . Payment::VALUE_TYPE_FIXED . '"',
                        'placeholder' => Craft::t('formie', 'Select an option'),
                        'options' => static::getCurrencyOptions(),
                    ]),
                    SchemaHelper::fieldSelectField([
                        'name' => 'currencyVariable',
                        'referenceContext' => 'client',
                        'required' => true,
                        'if' => 'currencyType == "' . Payment::VALUE_TYPE_DYNAMIC . '"',
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
                            ['label' => Craft::t('formie', 'Days'), 'value' => 'day'],
                            ['label' => Craft::t('formie', 'Weeks'), 'value' => 'week'],
                            ['label' => Craft::t('formie', 'Months'), 'value' => 'month'],
                            ['label' => Craft::t('formie', 'Years'), 'value' => 'year'],
                        ],
                    ]),
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Subscription Description'),
                'instructions' => Craft::t('formie', 'Enter a description for the subscription. This will only be shown in Stripe.'),
                'name' => 'planDescription',
                'if' => 'type == "subscription"',
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Setup Fee'),
                'instructions' => Craft::t('formie', 'Charge a one-time setup fee on the first subscription invoice, in addition to the recurring amount.'),
                'if' => 'type == "subscription"',
                'children' => [
                    SchemaHelper::selectField([
                        'name' => 'subscriptionSetupFeeType',
                        'options' => [
                            ['label' => Craft::t('formie', 'No setup fee'), 'value' => ''],
                            ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                            ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                        ],
                    ]),
                    SchemaHelper::numberField([
                        'name' => 'subscriptionSetupFeeFixed',
                        'required' => true,
                        'size' => 6,
                        'if' => 'subscriptionSetupFeeType == "' . Payment::VALUE_TYPE_FIXED . '"',
                    ]),
                    SchemaHelper::fieldSelectField([
                        'name' => 'subscriptionSetupFeeVariable',
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
                        'if' => 'subscriptionSetupFeeType == "' . Payment::VALUE_TYPE_DYNAMIC . '"',
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Setup Fee Description'),
                        'instructions' => Craft::t('formie', 'The line item description shown in Stripe for the setup fee. Defaults to “Setup fee”.'),
                        'name' => 'subscriptionSetupFeeDescription',
                        'if' => 'subscriptionSetupFeeType == "' . Payment::VALUE_TYPE_FIXED . '" || subscriptionSetupFeeType == "' . Payment::VALUE_TYPE_DYNAMIC . '"',
                    ]),
                ],
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Payment Limit'),
                'instructions' => Craft::t('formie', 'Limit how many subscription payments are collected before Stripe cancels the subscription automatically.'),
                'if' => 'type == "subscription"',
                'children' => [
                    SchemaHelper::selectField([
                        'name' => 'subscriptionLimitType',
                        'options' => [
                            ['label' => Craft::t('formie', 'No limit'), 'value' => ''],
                            ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                            ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                        ],
                    ]),
                    SchemaHelper::numberField([
                        'name' => 'subscriptionLimitFixed',
                        'required' => true,
                        'size' => 6,
                        'if' => 'subscriptionLimitType == "' . Payment::VALUE_TYPE_FIXED . '"',
                    ]),
                    SchemaHelper::fieldSelectField([
                        'name' => 'subscriptionLimitVariable',
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
                        'if' => 'subscriptionLimitType == "' . Payment::VALUE_TYPE_DYNAMIC . '"',
                    ]),
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Payment Receipt'),
                'instructions' => Craft::t('formie', 'Whether Stripe should email a receipt to the customer on successful payment.'),
                'name' => 'paymentReceipt',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Email Address'),
                'instructions' => Craft::t('formie', 'Enter the email the payment receipt should be delivered to.'),
                'name' => 'paymentReceiptEmail',
                'variables' => 'emailVariables',
                'if' => 'paymentReceipt',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Payment Description'),
                'instructions' => Craft::t('formie', 'Enter a description for this payment, to appear against the transaction in your Stripe account, and on the payment receipt sent to the customer.'),
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

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide ZIP / Postal Code'),
                'instructions' => Craft::t('formie', 'Whether to hide the zip/postal code field, shown alongside credit card number fields.'),
                'name' => 'hidePostalCode',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide Icon'),
                'instructions' => Craft::t('formie', 'Whether to hide the card icon, shown alongside credit card number fields.'),
                'name' => 'hideIcon',
            ]),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publishableKey', 'secretKey'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'type' => self::PAYMENT_TYPE_SINGLE,
            'amountType' => self::VALUE_TYPE_FIXED,
            'currencyType' => self::VALUE_TYPE_FIXED,
            'currencyFixed' => static::getDefaultCurrencyCode(),
            'frequencyType' => 'day',
            'frequencyValue' => 1,
        ];

        return $defaults;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldControl') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-control' => true,
                ])
                ->theme([
                    'class' => 'formie-field-control formie-stripe-elements-wrapper',
                ]);
        }

        if ($key === 'fieldInput') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-stripe-elements' => true,
                ])
                ->theme([
                    'class' => 'formie-stripe-elements',
                ]);
        }

        if ($key === 'stripePlaceholder') {
            return SlotTag::make('div')
                ->core([
                    'text' => '<div class="formie-loading"></div>' . Craft::t('formie', 'Loading payment options...'),
                    'data-formie-stripe-elements-placeholder' => true,
                ])
                ->theme([
                    'class' => 'formie-stripe-placeholder',
                ]);
        }

        return null;
    }

    protected function handleInvoiceCreated(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        $canBePaid = empty($stripeInvoice['paid']) && $stripeInvoice['billing'] === 'charge_automatically';

        if ($canBePaid) {
            $invoice = $this->getStripe()->invoices->retrieve($stripeInvoice['id']);
            $invoice->pay();
        }
    }

    protected function handleInvoiceSucceeded(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        // Sanity check
        if (!$stripeInvoice['paid']) {
            return;
        }

        $subscriptionReference = $stripeInvoice['subscription'];

        $counter = 0;
        $limit = 5;

        do {
            // Handle cases when Stripe sends us a webhook so soon that we haven't processed the subscription that triggered the webhook
            sleep(1);
            $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($subscriptionReference);
            $counter++;
        } while (!$subscription && $counter < $limit);

        if (!$subscription) {
            throw new Exception('Subscription with the reference “' . $subscriptionReference . '” not found when processing webhook ' . $data['id']);
        }

        $nextPaymentDate = DateTimeHelper::toDateTime($stripeSubscription['current_period_end']);

        Formie::$plugin->getSubscriptions()->receivePayment($subscription, $nextPaymentDate);
    }

    protected function handleInvoiceFailed(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        // Sanity check
        if ($stripeInvoice['paid']) {
            return;
        }

        $subscriptionReference = $stripeInvoice['subscription'] ?? null;

        if (!$subscriptionReference || !($subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($subscriptionReference))) {
            Integration::info($this, 'Subscription with the reference “' . $subscriptionReference . '” not found when processing webhook ' . $data['id']);

            return;
        }

        $stripeSubscription = $this->getStripe()->subscriptions->retrieve([
            'id' => $subscription->reference,
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $subscription->subscriptionData = $stripeSubscription->toArray();
        $this->_setSubscriptionStatusData($subscription);

        Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    }

    protected function handlePlanDeleted(array $data): void
    {
        $reference = $data['data']['object']['id'];

        if ($plan = Formie::$plugin->getPlans()->getPlanByReference($reference)) {
            Formie::$plugin->getPlans()->archivePlanById($plan->id);

            Integration::info($this, Craft::t('formie', 'Plan “{reference}” was archived because the corresponding plan was deleted on Stripe.', [
                'reference' => $reference,
            ]));
        }
    }

    protected function handlePlanUpdated(array $data): void
    {
        // Nothing for now
    }

    protected function handleSubscriptionCreated(array $data): void
    {
        // Nothing for now
    }

    protected function handleSubscriptionExpired(array $data): void
    {
        $stripeSubscription = $data['data']['object'];

        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription['id']);

        if (!$subscription) {
            Integration::info($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        Formie::$plugin->getSubscriptions()->expireSubscription($subscription);
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $stripeSubscription = $data['data']['object'];
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription['id']);

        if (!$subscription) {
            Integration::info($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        // See if we care about this subscription at all
        $subscription->subscriptionData = $data['data']['object'];

        $this->_setSubscriptionStatusData($subscription);

        if (empty($data['data']['object']['plan'])) {
            Integration::info($this, $subscription->reference . ' contains multiple plans, which is not supported. (event "' . $data['id'] . '")');
        } else {
            $planReference = $data['data']['object']['plan']['id'];
            $plan = Formie::$plugin->getPlans()->getPlanByReference($planReference);

            if ($plan) {
                $subscription->planId = $plan->id;
            } else {
                Integration::info($this, $subscription->reference . ' was switched to a plan on Stripe that does not exist on this Site. (event "' . $data['id'] . '")');
            }
        }

        Formie::$plugin->getSubscriptions()->updateSubscription($subscription);
    }

    protected function handlePaymentIntent(array $data): void
    {
        $paymentIntent = $data['data']['object'] ?? [];
        $paymentIntentId = $paymentIntent['id'] ?? null;
        $paymentIntentStatus = $paymentIntent['status'] ?? null;

        if ($paymentIntent && $paymentIntentId) {
            $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntentId);

            if ($payment) {
                $payment->status = $this->_getPaymentStatusFromPaymentIntentStatus($paymentIntentStatus);

                Formie::$plugin->getPayments()->savePayment($payment);
                Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
            }
        }
    }


    // Private Methods
    // =========================================================================

    private function _isProcessablePaymentIntentStatus(?string $status): bool
    {
        return in_array($status, [
            PaymentIntent::STATUS_SUCCEEDED,
            self::STRIPE_PAYMENT_INTENT_STATUS_PROCESSING,
        ], true);
    }

    private function _getPaymentStatusFromPaymentIntentStatus(?string $status): string
    {
        if ($status === PaymentIntent::STATUS_SUCCEEDED) {
            return PaymentModel::STATUS_SUCCESS;
        }

        if ($status === self::STRIPE_PAYMENT_INTENT_STATUS_PROCESSING) {
            return PaymentModel::STATUS_PROCESSING;
        }

        return PaymentModel::STATUS_FAILED;
    }

    private function _getOrCreatePlan(Submission $submission): mixed
    {
        $field = $this->getField();
        $frequencyValue = $this->getFieldSetting('frequencyValue');
        $frequencyType = $this->getFieldSetting('frequencyType');
        $planDescription = $this->getFieldSetting('planDescription', 'Formie: ' . $submission->getForm()->title);

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getCurrency($submission);

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'interval' => $frequencyType,
            'interval_count' => $frequencyValue,
            'product' => [
                'name' => $planDescription,
            ],
        ];

        // Create a unique ID for this form+field+payload. Only used internally, but prevents creating duplicate plans (which throws an error)
        $payload['id'] = ArrayHelper::recursiveImplode(array_merge(['formie', $submission->getForm()->handle, $field->handle], $payload), '_');
        $payload['id'] = str_replace([' ', ':'], ['_', ''], $payload['id']);

        // Generate a nice name for the price description based on the payload. Added after the ID is generated based on the payload
        $payload['nickname'] = implode(' ', [
            $submission->getForm()->title . ' form',
            self::fromStripeAmount($amount, $currency),
            $currency, 'x' . $frequencyValue,
            $frequencyType,
        ]);

        // Get or create
        $plan = $this->_getPlan($payload['id']);

        if (!$plan) {
            $plan = $this->_createPlan($payload);
        }

        return $plan;
    }

    private function _getPlan($planId): ?Plan
    {
        try {
            $data = $this->getStripe()->plans->retrieve($planId);

            $plan = Formie::$plugin->getPlans()->getPlanByReference($data['id']);

            if (!$plan) {
                $plan = new Plan();
            }

            $plan->integrationId = $this->id;
            $plan->name = $data['nickname'];
            $plan->handle = $data['nickname'];
            $plan->reference = $data['id'];
            $plan->enabled = true;
            $plan->planData = $data->toArray();
            $plan->isArchived = false;

            Formie::$plugin->getPlans()->savePlan($plan);

            return $plan;
        } catch (StripeException\ApiErrorException $e) {
            // Totally fine if there's an error here, just ignore
            return null;
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            return null;
        }
    }

    private function _createPlan($payload): ?Plan
    {
        try {
            // Raise a `modifyPlanPayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PLAN_PAYLOAD, $event);

            $data = $this->getStripe()->plans->create($event->payload);

            $plan = Formie::$plugin->getPlans()->getPlanByReference($data['id']);

            if (!$plan) {
                $plan = new Plan();
            }

            $plan->integrationId = $this->id;
            $plan->name = $data['nickname'];
            $plan->handle = $data['nickname'];
            $plan->reference = $data['id'];
            $plan->enabled = true;
            $plan->planData = $data->toArray();
            $plan->isArchived = false;

            Formie::$plugin->getPlans()->savePlan($plan);

            return $plan;
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            return null;
        }
    }

    private function _getCustomer(Submission $submission): ?Customer
    {
        // We always create a new customer. Maybe one day we'll figure out a way to handle this better
        $payload = [];

        // Add a few other things about the customer from mapping (in field settings)
        $billingNameField = $this->getPaymentBillingFieldKey('billingName');
        $billingAddressField = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmailField = $this->getPaymentBillingFieldKey('billingEmail');

        if ($billingNameField && ($billingName = $submission->getFieldValueAsString($billingNameField))) {
            $payload['name'] = $billingName;
        }

        if ($billingAddressField && ($billingAddress = $submission->getFieldValueAsArray($billingAddressField))) {
            $payload['address']['line1'] = ArrayHelper::remove($billingAddress, 'address1');
            $payload['address']['line2'] = ArrayHelper::remove($billingAddress, 'address2');
            $payload['address']['city'] = ArrayHelper::remove($billingAddress, 'city');
            $payload['address']['postal_code'] = ArrayHelper::remove($billingAddress, 'zip');
            $payload['address']['state'] = ArrayHelper::remove($billingAddress, 'state');
            $payload['address']['country'] = ArrayHelper::remove($billingAddress, 'country');
        }

        if ($billingEmailField && ($billingEmail = $submission->getFieldValueAsString($billingEmailField))) {
            $payload['email'] = $billingEmail;
        }

        // Raise a `modifyCustomerPayload` event
        $event = new ModifyPaymentPayloadEvent([
            'integration' => $this,
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_CUSTOMER_PAYLOAD, $event);

        // Return the Stripe customer
        try {
            return $this->getStripe()->customers->create($event->payload);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            return null;
        }
    }

    private function _buildSubscriptionSchedulePayload(array $subscriptionPayload, string $planReference, int $iterations): array
    {
        $phase = [
            'items' => [
                ['plan' => $planReference],
            ],
            'iterations' => $iterations,
        ];

        if (!empty($subscriptionPayload['add_invoice_items'])) {
            $phase['add_invoice_items'] = $subscriptionPayload['add_invoice_items'];
        }

        $schedulePayload = [
            'customer' => $subscriptionPayload['customer'],
            'start_date' => 'now',
            'end_behavior' => 'cancel',
            'phases' => [
                $phase,
            ],
            'default_settings' => [
                'collection_method' => 'charge_automatically',
            ],
            'expand' => ['subscription.latest_invoice.payment_intent', 'subscription.pending_setup_intent'],
        ];

        if (!empty($subscriptionPayload['metadata'])) {
            $schedulePayload['metadata'] = $subscriptionPayload['metadata'];
        }

        if (!empty($subscriptionPayload['description'])) {
            $schedulePayload['default_settings']['description'] = $subscriptionPayload['description'];
        }

        return $schedulePayload;
    }

    private function _applySubscriptionSetupFee(array &$payload, Submission $submission): void
    {
        $invoiceItem = $this->_buildSubscriptionSetupFeeInvoiceItem($submission);

        if (!$invoiceItem) {
            return;
        }

        $payload['add_invoice_items'] = [$invoiceItem];
    }

    private function _buildSubscriptionSetupFeeInvoiceItem(Submission $submission): ?array
    {
        $amount = $this->getSubscriptionSetupFee($submission);

        if ($amount === null || $amount <= 0) {
            return null;
        }

        $currency = strtolower((string)$this->getCurrency($submission));
        $description = trim((string)$this->getFieldSetting('subscriptionSetupFeeDescription'));

        if ($description === '') {
            $description = Craft::t('formie', 'Setup fee');
        } else {
            $description = References::parseContent($description, $submission);
        }

        return [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $description,
                ],
                'unit_amount' => $amount,
            ],
        ];
    }

    private function _resolveScheduleSubscription(object $scheduleResponse): StripeSubscription
    {
        $subscription = $scheduleResponse->subscription ?? null;

        if ($subscription instanceof StripeSubscription) {
            return $subscription;
        }

        if (is_string($subscription) && $subscription !== '') {
            return $this->getStripe()->subscriptions->retrieve($subscription, [
                'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
            ]);
        }

        throw new Exception('Unable to resolve subscription from Stripe schedule.');
    }

    private function _addStripeSubscriptionConfirmSubmitData(Submission $submission, StripeSubscription $stripeSubscription): void
    {
        if ($stripeSubscription->pending_setup_intent !== null) {
            $submission->getForm()->addSubmitData([
                'event' => 'formie:payment:stripe:confirm',
                'data' => [
                    'type' => 'setup',
                    'clientSecret' => $stripeSubscription->pending_setup_intent->client_secret,
                    'subscriptionId' => $stripeSubscription->id,
                    'returnUrl' => $this->getReturnUrl($submission),
                ],
            ]);

            return;
        }

        $clientSecret = $stripeSubscription->latest_invoice->payment_intent->client_secret ?? null;

        if (!$clientSecret) {
            throw new Exception(Craft::t('formie', 'Unable to resolve Stripe payment confirmation for this subscription.'));
        }

        $submission->getForm()->addSubmitData([
            'event' => 'formie:payment:stripe:confirm',
            'data' => [
                'type' => 'payment',
                'clientSecret' => $clientSecret,
                'subscriptionId' => $stripeSubscription->id,
                'returnUrl' => $this->getReturnUrl($submission),
            ],
        ]);
    }

    private function _setPayloadDetails(array &$payload, Submission $submission, string $type): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription');
        $metadata = $this->getFieldSetting('metadata', []);
        $paymentReceipt = $this->getFieldSetting('paymentReceipt', false);
        $paymentReceiptEmail = $this->getFieldSetting('paymentReceiptEmail');

        if ($paymentDescription) {
            $payload['description'] = References::parseContent($paymentDescription, $submission);
        }

        if ($paymentReceipt && $paymentReceiptEmail && $type === 'single') {
            $payload['receipt_email'] = References::parseContent($paymentReceiptEmail, $submission);
        }

        // Add in some metadata by default
        $payload['metadata']['submissionId'] = $submission->id;
        $payload['metadata']['fieldId'] = $field->id;
        $payload['metadata']['formHandle'] = $submission->getForm()->handle;

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

    private function _setSubscriptionStatusData(Subscription $subscription): void
    {
        $data = $subscription->subscriptionData;

        $canceledAt = $data['canceled_at'] ?? null;
        $endedAt = $data['ended_at'] ?? null;
        $status = $data['status'] ?? null;

        // Somebody didn't manage to provide/authenticate a payment method
        if ($status === 'incomplete_expired') {
            $subscription->isExpired = true;
            $subscription->dateExpired = $endedAt ? DateTimeHelper::toDateTime($endedAt) : null;
            $subscription->isCanceled = false;
            $subscription->dateCanceled = null;
            $subscription->nextPaymentDate = null;
        }

        // Definitely not suspended
        if ($status === 'active') {
            $subscription->isSuspended = false;
            $subscription->dateSuspended = null;
        }

        // Suspend this and make a guess at the suspension date
        if ($status === 'past_due') {
            $timeLastInvoiceCreated = $data['latest_invoice']['created'] ?? null;
            $dateSuspended = $timeLastInvoiceCreated ? DateTimeHelper::toDateTime($timeLastInvoiceCreated) : null;
            $subscription->dateSuspended = $subscription->isSuspended ? $subscription->dateSuspended : $dateSuspended;
            $subscription->isSuspended = true;
        }

        if ($status === 'canceled') {
            $subscription->isExpired = true;
            $subscription->dateExpired = $endedAt ? DateTimeHelper::toDateTime($endedAt) : null;
        }

        // Make sure we mark this as started, if appropriate
        $subscription->hasStarted = !in_array($status, ['incomplete', 'incomplete_expired']);

        // Update all the other tidbits
        $subscription->isCanceled = (bool)$canceledAt;
        $subscription->dateCanceled = $canceledAt ? DateTimeHelper::toDateTime($canceledAt) : null;
        $subscription->nextPaymentDate = DateTimeHelper::toDateTime($data['current_period_end']);
    }

    private function _getLatestPendingPaymentForField(Submission $submission, int $fieldId): ?PaymentModel
    {
        $latest = null;

        foreach (Formie::$plugin->getPayments()->getSubmissionPayments($submission) as $payment) {
            if ((int)$payment->fieldId !== $fieldId) {
                continue;
            }

            if (!in_array($payment->status, [PaymentModel::STATUS_PENDING, PaymentModel::STATUS_PROCESSING], true)) {
                continue;
            }

            $latest = $payment;
        }

        return $latest;
    }

    private function _getIdempotencyKey(Submission $submission, string $action, array $fingerprint = []): string
    {
        $fieldId = $this->getField()?->id ?? 'none';
        $submissionUid = (string)($submission->uid ?? $submission->id ?? 'none');
        $payloadHash = $fingerprint ? substr(hash('sha256', Json::encode($fingerprint)), 0, 16) : 'default';

        // Keep keys stable per submission/field/action so duplicate client retries
        // cannot create duplicate Stripe resources for the same attempt.
        return implode(':', [
            'formie',
            'stripe',
            $this->handle ?: 'integration',
            (string)$action,
            (string)$submissionUid,
            (string)$fieldId,
            $payloadHash,
        ]);
    }
}

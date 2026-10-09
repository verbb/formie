<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Number;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\PaymentAmountHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentMoney;
use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\models\payments\PaymentWebhookReceipt;
use verbb\formie\models\payments\SubscriptionSnapshot;
use verbb\formie\models\payments\VerifiedWebhook;
use verbb\formie\models\payments\VerifiedWebhookBatch;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Subscription;
use verbb\formie\models\SubscriptionPlan;
use verbb\formie\references\ReferenceContext;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Event;
use yii\web\BadRequestHttpException;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use NumberFormatter;
use Throwable;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe as StripeSdk;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook as StripeWebhook;

class Stripe extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Stripe';
    }

    public static function toStripeAmount(string|int|float $amount, string $currency): int
    {
        $money = PaymentMoney::fromDecimal((string)$amount, $currency);
        // Stripe retains two-decimal API units for these zero-decimal currencies.
        return in_array(strtoupper($currency), ['ISK', 'UGX'], true) ? PaymentMoney::fromMinor($money->minor . '00', $currency)->integer() : $money->integer();
    }

    public static function fromStripeAmount(string|int|float $amount, string $currency): string
    {
        if (in_array(strtoupper($currency), ['ISK', 'UGX'], true)) {
            if (!str_ends_with((string)$amount, '00') && (string)$amount !== '0') {
                throw new InvalidArgumentException('Invalid Stripe zero-decimal amount.');
            }
            $amount = (string)$amount === '0' ? '0' : substr((string)$amount, 0, -2);
        }
        return PaymentMoney::fromMinor((string)$amount, $currency)->decimal();
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


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_SUBSCRIPTION_PAYLOAD = 'modifySubscriptionPayload';
    public const EVENT_MODIFY_SUBSCRIPTION_SCHEDULE_PAYLOAD = 'modifySubscriptionSchedulePayload';
    public const EVENT_MODIFY_SINGLE_PAYLOAD = 'modifySinglePayload';
    public const EVENT_MODIFY_PLAN_PAYLOAD = 'modifyPlanPayload';
    public const EVENT_MODIFY_CUSTOMER_PAYLOAD = 'modifyCustomerPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';

    // https://stripe.com/docs/currencies#zero-decimal
    // Stripe represents ISK and UGX using two decimal places for compatibility.
    private const ZERO_DECIMAL_CURRENCIES = ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','VND','VUV','XAF','XOF','XPF'];
    private const STRIPE_EVENT_PAYMENT_INTENT_PROCESSING = 'payment_intent.processing';
    private const STRIPE_PAYMENT_INTENT_STATUS_PROCESSING = 'processing';


    // Properties
    // =========================================================================

    public ?string $publishableKey = null;
    #[Sensitive]
    public ?string $secretKey = null;
    #[Sensitive]
    public ?string $webhookSecretKey = null;
    public bool $hidePostalCode = false;
    public bool $hideIcon = false;

    private ?StripeClient $_stripe = null;
    private ?PaymentAction $_confirmationAction = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function getInitialPaymentInformation(): array
    {
        $currency = static::getSiteCurrency();
        $currencyType = $this->getFieldSetting('currencyType');
        $currencyFixed = $this->getFieldSetting('currencyFixed');
        $currencyVariable = $this->normalizeClientFieldReference($this->getFieldSetting('currencyVariable'));

        if ($currencyType === Payment::VALUE_TYPE_FIXED) {
            $currency = strtolower($currencyFixed);
        } elseif ($currencyType === Payment::VALUE_TYPE_DYNAMIC) {
            $currency = $currencyVariable;
        }

        // Set a default amount for when using dynamic values. This is changed on the front-end when updated there.
        $amount = self::toStripeAmount(100, $currency);
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable'));

        if ($amountType === Payment::VALUE_TYPE_FIXED) {
            $amount = self::toStripeAmount((string)$amountFixed, $currency);
        } elseif ($amountType === Payment::VALUE_TYPE_DYNAMIC) {
            $amount = $amountVariable;
        }

        return [
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule
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

        return new BrowserModule([
            'moduleId' => 'formie:stripe',
            'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED],
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
        $url = 'formie/payment-return/index';
        $payment = Formie::$plugin->getPayments()->prepareAttempt($this, $submission);
        $params = ['returnToken' => PaymentAccess::issueReturnToken($payment)];

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return UrlHelper::actionUrl($url, $params);
        }

        return UrlHelper::siteUrl($url, $params);
    }

    public function getAmount(Submission $submission): string|int|float
    {
        // Ensure the amount is converted to Stripe for zero-decimal currencies
        return self::toStripeAmount(parent::getAmount($submission), $this->getCurrency($submission));
    }

    public function getPaymentAmount(Submission $submission): string|int|float
    {
        return self::fromStripeAmount($this->getAmount($submission), (string)$this->getCurrency($submission));
    }

    public function getSubscriptionPaymentLimit(Submission $submission): ?int
    {
        $limitType = $this->getFieldSetting('subscriptionLimitType');

        if ($limitType === Payment::VALUE_TYPE_FIXED) {
            $value = $this->getFieldSetting('subscriptionLimitFixed');
        } elseif ($limitType === Payment::VALUE_TYPE_DYNAMIC) {
            $value = References::resolveValue($this->getFieldSetting('subscriptionLimitVariable'), ReferenceContext::forSubmission($submission))->requireValue();
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
            $value = References::resolveValue($this->getFieldSetting('subscriptionSetupFeeVariable'), ReferenceContext::forSubmission($submission))->requireValue();
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
                    $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription->id, $this->id);

                    if ($subscription && (int)$subscription->submissionId === (int)$submission->id
                        && (int)$subscription->integrationId === (int)$this->id && (int)$subscription->fieldId === (int)$field->id
                        && !$subscription->getState()->isTerminal()) {
                        $subscription = $this->_applySubscriptionSnapshot(
                            $subscription,
                            $stripeSubscription->toArray(),
                            'stripeReturn',
                        );
                    } else {
                        throw new Exception('Unable to find subscription by "' . $stripeSubscription->id . '".');
                    }
                } else {
                    throw new Exception('Unable to find Stripe subscription by "' . $subscriptionId . '".');
                }

                $this->_setSubscriptionConfirmationAction($submission, $stripeSubscription);
                return in_array($stripeSubscription->status, ['active', 'trialing'], true);
            }

            // Get or create the plan (product) first
            $plan = $this->_getOrCreatePlan($submission);

            if (!$plan) {
                throw new Exception('Unable to get or create plan.');
            }

            // Resolve the customer for this payment operation.
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

                $scheduleResponse = $this->_createResourceOnce($submission, 'subscriptionSchedules', 'subscription-schedule-create', $scheduleEvent->payload);

                $response = $this->_resolveScheduleSubscription($scheduleResponse);
                $scheduleId = $scheduleResponse->id;
            } else {
                // Create the Stripe subscription
                $response = $this->_createResourceOnce($submission, 'subscriptions', 'subscription-create', $event->payload);
                $scheduleId = null;
            }

            // Create and record our Formie subscription
            $subscription = Formie::$plugin->getPayments()->prepareSubscription($this, $submission);
            $subscription->integrationId = $this->id;
            $subscription->submissionId = $submission->id;
            $subscription->fieldId = $field->id;
            $subscription->planId = $plan->id;
            $subscription->reference = $response->id;
            $subscriptionData = $response->toArray();

            if ($scheduleId) {
                $subscriptionData['formieScheduleId'] = $scheduleId;
                $subscriptionData['formiePaymentLimit'] = $paymentLimit;
            }

            if (($setupFee = $this->getSubscriptionSetupFee($submission)) !== null) {
                $subscriptionData['formieSetupFee'] = $setupFee;
            }

            $subscription->trialDays = 0;
            Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
            $this->_applySubscriptionSnapshot($subscription, $subscriptionData, 'stripeCreate');

            $this->_setSubscriptionConfirmationAction($submission, $response);

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

            throw $e;
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
                    $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntent->id, $this->id);

                    if (!$payment) {
                        throw new Exception('Unable to find payment by "' . $paymentIntent->id . '".');
                    }

                    // A PaymentIntent can only belong to one submission. If a stale
                    // hidden input is replayed on a new submission, fail safely.
                    if ((int)$payment->submissionId !== (int)$submission->id || (int)$payment->fieldId !== (int)$field->id) {
                        Integration::warning($this, Craft::t('formie', 'Rejected Stripe PaymentIntent "{intentId}" reuse for submission "{submissionUid}". Intent belongs to submission ID {existingSubmissionId}.', [
                            'intentId' => $paymentIntent->id,
                            'submissionUid' => (string)($submission->uid ?? $submission->id ?? 'unknown'),
                            'existingSubmissionId' => (string)$payment->submissionId,
                        ]));

                        $this->addFieldError($submission, Craft::t('formie', 'Your previous payment session is no longer valid. Please refresh the payment details and try again.'));

                        throw new Exception('Stripe PaymentIntent ownership mismatch.');
                    }

                    if (!isset($paymentIntent->amount, $paymentIntent->currency)
                        || !PaymentMoney::fromDecimal(self::fromStripeAmount((string)$paymentIntent->amount, strtoupper($paymentIntent->currency)), strtoupper($paymentIntent->currency))
                            ->equals(PaymentMoney::fromDecimal($payment->amount, (string)$payment->currency))) {
                        throw new Exception('Stripe payment snapshot mismatch.');
                    }

                    if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                        $payment->status = PaymentModel::STATUS_SUCCEEDED;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();

                        Formie::$plugin->getPayments()->savePayment($payment);
                    } elseif (in_array($paymentIntent->status, [
                        PaymentIntent::STATUS_REQUIRES_ACTION,
                        PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
                    ], true)) {
                        $payment->status = PaymentModel::STATUS_PENDING;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();
                        $payment->message = $paymentIntent->last_payment_error?->message ?? Craft::t('formie', 'Payment confirmation is still required.');

                        Formie::$plugin->getPayments()->savePayment($payment);

                        if (!empty($paymentIntent->client_secret)) {
                            $this->_setConfirmationAction([
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
                    } elseif ($paymentIntent->status === PaymentIntent::STATUS_PROCESSING) {
                        $payment->status = PaymentModel::STATUS_PROCESSING;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();
                        $payment->message = Craft::t('formie', 'Payment is still processing. Please wait a moment and submit again.');

                        Formie::$plugin->getPayments()->savePayment($payment);

                        $this->addFieldError($submission, $payment->message);
                        return false;
                    } else {
                        $payment->status = $this->_getPaymentStatusFromPaymentIntentStatus($paymentIntent->status);
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

                $this->_setConfirmationAction([
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

            // Resolve the customer for this payment operation.
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
            $response = $this->_createResourceOnce($submission, 'paymentIntents', 'payment-intent-create', $event->payload);

            // Save a pending payment before we head back to the front-end
            $payment = Formie::$plugin->getPayments()->getPaymentByReference($response->id, $this->id) ?? Formie::$plugin->getPayments()->prepareAttempt($this, $submission);
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
            $this->_setConfirmationAction([
                'event' => 'formie:payment:stripe:confirm',
                'data' => [
                    'clientSecret' => $response->client_secret,
                    'paymentIntentId' => $response->id,
                    'returnUrl' => $this->getReturnUrl($submission),
                ],
            ]);

            return false;
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $payment = Formie::$plugin->getPayments()->prepareAttempt($this, $submission);
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
        } catch (ApiErrorException $e) {
            // The provider may have accepted a request before the response failed.
            // Resource receipts determine whether a later retry is safe.
            throw $e;
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

            throw $e;
        }

        return true;
    }

    public function getTransaction(PaymentModel $payment): void
    {
        if ((int)$payment->integrationId !== (int)$this->id) {
            throw new Exception('Payment provider mismatch.');
        }

        if ($payment->subscriptionId) {
            $subscription = $payment->getSubscription();

            if (!$subscription?->reference) {
                return;
            }
            $remote = $this->getStripe()->subscriptions->retrieve($subscription->reference);
            $subscription = $this->_applySubscriptionSnapshot($subscription, $remote->toArray(), 'stripeReconcile');
            $this->_syncInitialSubscriptionPayment($subscription);
            return;
        }

        if (!$payment->reference) {
            return;
        }
        $remote = $this->getStripe()->paymentIntents->retrieve($payment->reference);

        if ($remote->id !== $payment->reference
            || !PaymentMoney::fromDecimal(self::fromStripeAmount((string)$remote->amount, strtoupper($remote->currency)), strtoupper($remote->currency))->equals(PaymentMoney::fromDecimal($payment->amount, (string)$payment->currency))) {
            throw new Exception('Stripe payment snapshot mismatch.');
        }
        $payment->status = $this->_getPaymentStatusFromPaymentIntentStatus($remote->status);
        $payment->response = $remote->toArray();
        Formie::$plugin->getPayments()->savePayment($payment);
    }

    public function verifyWebhook(PaymentWebhookCommand $request): VerifiedWebhookBatch
    {
        $rawData = $request->body;
        $secret = App::parseEnv($this->webhookSecretKey);
        $stripeSignature = $request->header('Stripe-Signature');

        if (!$secret || !$stripeSignature) {
            throw new BadRequestHttpException('Webhook not signed or signing secret not set.');
        }

        try {
            StripeWebhook::constructEvent($rawData, $stripeSignature, $secret);
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Webhook signature check failed.', 0, $e);
        }

        $data = Json::decodeIfJson($rawData);

        if (!is_array($data) || empty($data['id']) || empty($data['type'])) {
            throw new BadRequestHttpException('Could not decode Stripe webhook payload.');
        }

        $environment = !empty($data['livemode']) ? 'live' : 'test';
        $resource = $data['data']['object'] ?? [];
        $accountIdentity = isset($data['account'])
            ? (string)$data['account']
            : $this->getPaymentAccountIdentity();

        return new VerifiedWebhookBatch(
            $accountIdentity ? $this->getWebhookAccountFingerprint($environment, $accountIdentity) : $this->getPaymentAccountFingerprint(),
            $environment,
            [new VerifiedWebhook(
                (string)$data['id'],
                (string)$data['type'],
                isset($resource['object']) ? (string)$resource['object'] : null,
                isset($resource['id']) ? (string)$resource['id'] : null,
                isset($data['created']) ? (new DateTimeImmutable())->setTimestamp((int)$data['created']) : null,
                $data,
            )],
            ['Stripe-Signature' => $stripeSignature],
        );
    }

    public function handleWebhook(PaymentWebhookReceipt $receipt): void
    {
        $data = $receipt->payload;

        if ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_CREATED) {
            $this->handleSubscriptionCreated($data);
        } elseif ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_DELETED) {
            $this->handleSubscriptionExpired($data);
        } elseif ($data['type'] === StripeEvent::CUSTOMER_SUBSCRIPTION_UPDATED) {
            $this->handleSubscriptionUpdated($data);
        } elseif ($data['type'] === StripeEvent::INVOICE_CREATED) {
            $this->handleInvoiceCreated($data);
        } elseif ($data['type'] === StripeEvent::INVOICE_PAYMENT_FAILED) {
            $this->handleInvoiceFailed($data);
        } elseif ($data['type'] === StripeEvent::INVOICE_PAYMENT_SUCCEEDED) {
            $this->handleInvoiceSucceeded($data);
        } elseif ($data['type'] === StripeEvent::PLAN_DELETED) {
            $this->handlePlanDeleted($data);
        } elseif ($data['type'] === StripeEvent::PLAN_UPDATED) {
            $this->handlePlanUpdated($data);
        } elseif (in_array($data['type'], [StripeEvent::PAYMENT_INTENT_CANCELED, StripeEvent::PAYMENT_INTENT_PAYMENT_FAILED, self::STRIPE_EVENT_PAYMENT_INTENT_PROCESSING, StripeEvent::PAYMENT_INTENT_SUCCEEDED], true)) {
            $this->handlePaymentIntent($data);
        }

        if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
            $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent(['webhookData' => $data]));
        }
    }

    public function cancelSubscription($reference, $params = []): ?array
    {
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($reference, $this->id);

        if (!$subscription) {
            return null;
        }

        $snapshot = $this->cancelSubscriptionSnapshot(
            $subscription,
            ($params['cancelImmediately'] ?? false) ? SubscriptionCancellationMode::IMMEDIATE : SubscriptionCancellationMode::AT_PERIOD_END,
        );

        if (!$snapshot) {
            return null;
        }

        Formie::$plugin->getSubscriptions()->applySnapshot($subscription, $snapshot, 'legacyCancellation');

        return $snapshot->rawData;
    }

    public function getSubscriptionCancellationModes(): array
    {
        return [SubscriptionCancellationMode::AT_PERIOD_END, SubscriptionCancellationMode::IMMEDIATE];
    }

    public function getDefaultSubscriptionCancellationMode(): SubscriptionCancellationMode
    {
        return SubscriptionCancellationMode::AT_PERIOD_END;
    }

    public function cancelSubscriptionSnapshot(Subscription $subscription, SubscriptionCancellationMode $mode): ?SubscriptionSnapshot
    {
        try {
            $stripeSubscription = $this->getStripe()->subscriptions->retrieve($subscription->reference);

            if ($mode === SubscriptionCancellationMode::IMMEDIATE) {
                $response = $stripeSubscription->cancel();
            } else {
                $stripeSubscription->cancel_at_period_end = true;
                $response = $stripeSubscription->save();
            }

            return $this->_subscriptionSnapshot($response->toArray(), cancellationMode: $mode);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }

        return null;
    }

    public function fetchConnection(): bool
    {
        try {
            $charges = $this->getStripe()->charges->all(['limit' => 1]);
            $account = $this->getStripe()->accounts->retrieve();
            Craft::$app->getCache()->set($this->_accountIdentityCacheKey(), (string)$account->id, 0);
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

        StripeSdk::setAppInfo('Craft Formie', Formie::$plugin->getVersion(), 'https://verbb.io/craft-plugins/formie');

        return $this->_stripe = new StripeClient([
            'api_key' => App::parseEnv($this->secretKey),
            'stripe_version' => '2020-08-27',
        ]);
    }

    public function getPaymentEnvironment(): string
    {
        return str_contains((string)App::parseEnv($this->secretKey), '_test_') ? 'test' : 'live';
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
                            Calculations::class,
                            Dropdown::class,
                            Hidden::class,
                            Number::class,
                            Radio::class,
                            SingleLineText::class,
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
                            Calculations::class,
                            Dropdown::class,
                            Hidden::class,
                            Number::class,
                            Radio::class,
                            SingleLineText::class,
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
                            Calculations::class,
                            Dropdown::class,
                            Hidden::class,
                            Number::class,
                            Radio::class,
                            SingleLineText::class,
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

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function requiresAjaxSubmission(): bool
    {
        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function executePayment(Submission $submission): PaymentDecision
    {
        $this->_confirmationAction = null;
        $result = false;

        $type = $this->getFieldSetting('type');


        if ($type === self::PAYMENT_TYPE_SINGLE) {
            $result = $this->processSinglePayment($submission);
        } elseif ($type === self::PAYMENT_TYPE_SUBSCRIPTION) {
            $result = $this->processSubscriptionPayment($submission);
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

        if ($type === self::PAYMENT_TYPE_SUBSCRIPTION && $latestPayment?->subscriptionId) {
            $subscription = $latestPayment->getSubscription();

            if ($subscription && in_array($subscription->getState(), [SubscriptionStatus::TRIALING, SubscriptionStatus::ACTIVE], true)) {
                return PaymentDecision::succeeded($this->handle, $subscription->reference);
            }

            if ($subscription?->getState()->isTerminal()) {
                return PaymentDecision::cancelled(null, $this->handle, $subscription->reference);
            }

            if ($subscription && empty($subscription->providerData['requiresAction'])) {
                return $subscription->status === 'unknown' ? PaymentDecision::unknown(null, $this->handle, $subscription->reference)
                    : PaymentDecision::pending(null, $this->handle, $subscription->reference);
            }
        }

        if ($this->_confirmationAction) {
            return PaymentDecision::requiresAction($latestPayment?->reference, $this->_confirmationAction);
        }

        if ($latestPayment) {
            return match ((string)$latestPayment->status) {
                PaymentModel::STATUS_SUCCEEDED => PaymentDecision::succeeded($this->handle, $latestPayment->reference),
                PaymentModel::STATUS_REQUIRES_ACTION => PaymentDecision::requiresAction(
                    $latestPayment->reference,
                    PaymentAction::redirect(
                        provider: $this->handle,
                        event: 'formie:payment:stripe:confirm',
                        message: $latestPayment->message ?: Craft::t('formie', 'Additional payment confirmation is required to continue.'),
                        resumeMode: PaymentResumeMode::RETURN,
                        resumeUrl: $this->getReturnUrl($submission),
                    )
                ),
                PaymentModel::STATUS_PROCESSING => PaymentDecision::pending($latestPayment->message, $this->handle, $latestPayment->reference),
                PaymentModel::STATUS_CANCELLED => PaymentDecision::cancelled($latestPayment->message, $this->handle, $latestPayment->reference),
                PaymentModel::STATUS_PENDING => PaymentDecision::requiresAction(
                    $latestPayment->reference,
                    PaymentAction::confirm(
                        provider: $this->handle,
                        event: 'formie:payment:stripe:confirm',
                        message: $latestPayment->message ?: Craft::t('formie', 'Additional payment confirmation is required to continue.'),
                        resumeMode: PaymentResumeMode::RETURN,
                        resumeUrl: $this->getReturnUrl($submission),
                    )
                ),
                PaymentModel::STATUS_FAILED => PaymentDecision::failed($latestPayment->message, $this->handle, $latestPayment->reference),
                default => PaymentDecision::unknown(null, $this->handle, $latestPayment->reference),
            };
        }

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
    }

    protected function getPaymentAccountIdentity(): ?string
    {
        // Connection verification resolves the stable account without a request per payment.
        return Craft::$app->getCache()->get($this->_accountIdentityCacheKey()) ?: null;
    }

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

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
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

        $subscriptionReference = $stripeInvoice['subscription'] ?? null;
        $subscription = $subscriptionReference ? Formie::$plugin->getSubscriptions()->getSubscriptionByReference($subscriptionReference, $this->id) : null;

        if (!$subscription || (int)$subscription->integrationId !== (int)$this->id) {
            return;
        }

        // Stripe's automatic collection owns charging. Invoice creation is only an observation.
    }

    protected function handleInvoiceSucceeded(array $data): void
    {
        $invoice = $data['data']['object'];

        if (empty($invoice['paid'])) {
            return;
        }
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference((string)($invoice['subscription'] ?? ''), $this->id);

        if (!$subscription) {
            $remote = $this->getStripe()->subscriptions->retrieve((string)($invoice['subscription'] ?? ''));
            $subscription = $this->_resolveWebhookSubscription($remote->toArray());
        }

        if (!$subscription) {
            return;
        }

        if ((int)$subscription->integrationId !== (int)$this->id) {
            return;
        }
        Formie::$plugin->getPayments()->recordRecurring(
            $subscription,
            $invoice['id'],
            self::fromStripeAmount((string)$invoice['amount_paid'], strtoupper($invoice['currency'])),
            strtoupper($invoice['currency']),
            PaymentModel::STATUS_SUCCEEDED,
            $invoice
        );
        $previousPeriod = $subscription->currentPeriodEndsAt;
        $remote = $this->getStripe()->subscriptions->retrieve($subscription->reference);
        $subscription = $this->_applySubscriptionSnapshot(
            $subscription,
            $remote->toArray(),
            'stripeInvoiceSucceeded',
            (string)($data['id'] ?? ''),
            isset($data['created']) ? (int)$data['created'] : null,
        );
        $date = DateTimeHelper::toDateTime($remote['current_period_end']);

        if ($date) {
            Formie::$plugin->getSubscriptions()->receivePayment($subscription, $date, $previousPeriod);
        }
        $this->_syncInitialSubscriptionPayment($subscription);
    }

    protected function handleInvoiceFailed(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        // Sanity check
        if ($stripeInvoice['paid']) {
            return;
        }

        $subscriptionReference = $stripeInvoice['subscription'] ?? null;

        if (!$subscriptionReference || !($subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($subscriptionReference, $this->id)) || (int)$subscription->integrationId !== (int)$this->id) {
            Integration::info($this, 'Subscription with the reference “' . $subscriptionReference . '” not found when processing webhook ' . $data['id']);

            return;
        }

        if (!empty($stripeInvoice['id']) && isset($stripeInvoice['amount_due'], $stripeInvoice['currency'])) {
            Formie::$plugin->getPayments()->recordRecurring(
                $subscription,
                $stripeInvoice['id'],
                self::fromStripeAmount((string)$stripeInvoice['amount_due'], strtoupper($stripeInvoice['currency'])),
                strtoupper($stripeInvoice['currency']),
                PaymentModel::STATUS_FAILED,
                $stripeInvoice
            );
        }
        $stripeSubscription = $this->getStripe()->subscriptions->retrieve($subscription->reference, [
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $this->_applySubscriptionSnapshot(
            $subscription,
            $stripeSubscription->toArray(),
            'stripeInvoiceFailed',
            (string)($data['id'] ?? ''),
            isset($data['created']) ? (int)$data['created'] : null,
        );
    }

    protected function handlePlanDeleted(array $data): void
    {
        $reference = $data['data']['object']['id'];

        if (($plan = Formie::$plugin->getPlans()->getPlanByReference($reference, $this->id, $this->getPaymentAccountFingerprint())) && (int)$plan->integrationId === (int)$this->id) {
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
        $this->handleSubscriptionUpdated($data);
    }

    protected function handleSubscriptionExpired(array $data): void
    {
        $stripeSubscription = $data['data']['object'];
        $stripeSubscription['status'] ??= 'canceled';

        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription['id'], $this->id);

        if (!$subscription || (int)$subscription->integrationId !== (int)$this->id) {
            Integration::info($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        $this->_applySubscriptionSnapshot(
            $subscription,
            $stripeSubscription,
            'stripeWebhook',
            (string)($data['id'] ?? ''),
            isset($data['created']) ? (int)$data['created'] : null,
        );
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $stripeSubscription = $data['data']['object'];
        $subscription = $this->_resolveWebhookSubscription($stripeSubscription);

        if (!$subscription || (int)$subscription->integrationId !== (int)$this->id) {
            Integration::info($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        // See if we care about this subscription at all
        $remote = $this->getStripe()->subscriptions->retrieve($subscription->reference);
        $subscription = $this->_applySubscriptionSnapshot(
            $subscription,
            $remote->toArray(),
            'stripeWebhook',
            (string)($data['id'] ?? ''),
            isset($data['created']) ? (int)$data['created'] : null,
        );

        if (empty($data['data']['object']['plan'])) {
            Integration::info($this, $subscription->reference . ' contains multiple plans, which is not supported. (event "' . $data['id'] . '")');
        } else {
            $planReference = $data['data']['object']['plan']['id'];
            $plan = Formie::$plugin->getPlans()->getPlanByReference($planReference, $this->id, $this->getPaymentAccountFingerprint());

            if ($plan && (int)$plan->integrationId === (int)$this->id) {
                $subscription->planId = $plan->id;
            } else {
                Integration::info($this, $subscription->reference . ' was switched to a plan on Stripe that does not exist on this Site. (event "' . $data['id'] . '")');
            }
        }

        Formie::$plugin->getSubscriptions()->updateSubscription($subscription);
        $this->_syncInitialSubscriptionPayment($subscription);
    }

    protected function handlePaymentIntent(array $data): void
    {
        $paymentIntent = $data['data']['object'] ?? [];
        $paymentIntentId = $paymentIntent['id'] ?? null;
        $paymentIntentStatus = $paymentIntent['status'] ?? null;

        if ($paymentIntent && $paymentIntentId) {
            $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntentId, $this->id);

            if (!$payment && !empty($paymentIntent['metadata']['formiePaymentUid'])) {
                $candidate = Formie::$plugin->getPayments()->getPaymentByUid($paymentIntent['metadata']['formiePaymentUid']);

                if ($candidate && $candidate->integrationId === $this->id && !$candidate->reference && ($candidate->scope['initial'] ?? false)) {
                    $payment = $candidate;
                    $payment->reference = $paymentIntentId;
                }
            }

            if ($payment && (int)$payment->integrationId === (int)$this->id) {
                if (!isset($paymentIntent['amount'], $paymentIntent['currency'])
                    || !PaymentMoney::fromDecimal(self::fromStripeAmount((string)$paymentIntent['amount'], strtoupper($paymentIntent['currency'])), strtoupper($paymentIntent['currency']))
                        ->equals(PaymentMoney::fromDecimal($payment->amount, $payment->currency))) {
                    throw new Exception('Stripe webhook amount or currency mismatch.');
                }
                $payment->response = $paymentIntent;

                // Stripe may deliver earlier processing/failure events after success.
                // Successful intents are terminal; retries may still resume pending workflow work.
                if ($payment->status !== PaymentModel::STATUS_SUCCEEDED) {
                    $payment->status = $this->_getPaymentStatusFromPaymentIntentStatus($paymentIntentStatus);

                    if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                        throw new Exception('Unable to record the payment intent status.');
                    }
                }
                Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($payment);
            }
        }
    }

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return ['stripePaymentId', 'stripeSubscriptionId'];
    }


    // Private Methods
    // =========================================================================

    private function _accountIdentityCacheKey(): string
    {
        return 'formie.stripe-account.' . hash_hmac('sha256', (string)App::parseEnv($this->secretKey), Formie::$plugin->getSettings()->getSecurityKey());
    }

    private function _isProcessablePaymentIntentStatus(?string $status): bool
    {
        return in_array($status, [
            PaymentIntent::STATUS_SUCCEEDED,
            self::STRIPE_PAYMENT_INTENT_STATUS_PROCESSING,
        ], true);
    }

    private function _getPaymentStatusFromPaymentIntentStatus(?string $status): string
    {
        return match ($status) {
            PaymentIntent::STATUS_SUCCEEDED => PaymentModel::STATUS_SUCCEEDED,
            PaymentIntent::STATUS_PROCESSING, 'requires_capture' => PaymentModel::STATUS_PROCESSING,
            PaymentIntent::STATUS_REQUIRES_ACTION, PaymentIntent::STATUS_REQUIRES_CONFIRMATION => PaymentModel::STATUS_PENDING,
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD => PaymentModel::STATUS_FAILED,
            PaymentIntent::STATUS_CANCELED => PaymentModel::STATUS_CANCELLED,
            default => PaymentModel::STATUS_UNKNOWN,
        };
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

    private function _getPlan($planId): ?SubscriptionPlan
    {
        try {
            $data = $this->getStripe()->plans->retrieve($planId);

            $plan = Formie::$plugin->getPlans()->getPlanByReference($data['id'], $this->id, $this->getPaymentAccountFingerprint());

            if (!$plan) {
                $plan = new SubscriptionPlan();
            }

            $plan->integrationId = $this->id;
            $plan->name = $data['nickname'];
            $plan->handle = $data['nickname'];
            $plan->reference = $data['id'];
            $plan->enabled = true;
            $plan->accountFingerprint = $this->getPaymentAccountFingerprint();
            $plan->amountMinor = (string)$data['amount'];
            $plan->currency = strtoupper((string)$data['currency']);
            $plan->interval = $data['interval'];
            $plan->intervalCount = (int)$data['interval_count'];
            $plan->providerStatus = !empty($data['active']) ? 'active' : 'inactive';
            $plan->planData = ['interval' => $plan->interval, 'interval_count' => $plan->intervalCount, 'amount' => $plan->amountMinor, 'currency' => $plan->currency];
            $plan->isArchived = false;

            Formie::$plugin->getPlans()->savePlan($plan);

            return $plan;
        } catch (ApiErrorException $e) {
            // Totally fine if there's an error here, just ignore
            return null;
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            return null;
        }
    }

    private function _createPlan($payload): ?SubscriptionPlan
    {
        try {
            // Raise a `modifyPlanPayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PLAN_PAYLOAD, $event);

            $data = $this->getStripe()->plans->create($event->payload);

            $plan = Formie::$plugin->getPlans()->getPlanByReference($data['id'], $this->id, $this->getPaymentAccountFingerprint());

            if (!$plan) {
                $plan = new SubscriptionPlan();
            }

            $plan->integrationId = $this->id;
            $plan->name = $data['nickname'];
            $plan->handle = $data['nickname'];
            $plan->reference = $data['id'];
            $plan->enabled = true;
            $plan->accountFingerprint = $this->getPaymentAccountFingerprint();
            $plan->amountMinor = (string)$data['amount'];
            $plan->currency = strtoupper((string)$data['currency']);
            $plan->interval = $data['interval'];
            $plan->intervalCount = (int)$data['interval_count'];
            $plan->providerStatus = !empty($data['active']) ? 'active' : 'inactive';
            $plan->planData = ['interval' => $plan->interval, 'interval_count' => $plan->intervalCount, 'amount' => $plan->amountMinor, 'currency' => $plan->currency];
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
        // Customer creation shares the same recovery guarantees as the payment.
        $payload = [];

        // Add a few other things about the customer from mapping (in field settings)
        $billingNameField = $this->getPaymentBillingFieldKey('billingName');
        $billingAddressField = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmailField = $this->getPaymentBillingFieldKey('billingEmail');

        if ($billingNameField && ($billingName = $submission->getFieldValueAsString($billingNameField))) {
            $payload['name'] = $billingName;
        }

        if ($billingAddressField && ($billingAddress = $submission->getFieldValueAsData($billingAddressField))) {
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
            return $this->_createResourceOnce($submission, 'customers', 'customer-create', $event->payload);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);

            throw $e;
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
            $description = Craft::t('formie', 'Setup Fee');
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

    private function _setConfirmationAction(array $data): void
    {
        $this->_confirmationAction = PaymentAction::confirm(
            provider: $this->handle,
            event: 'formie:payment:stripe:confirm',
            payload: $data['data'],
            resumeMode: PaymentResumeMode::RETURN,
            resumeUrl: $data['data']['returnUrl'] ?? null,
        );
    }

    private function _setSubscriptionConfirmationAction(Submission $submission, StripeSubscription $stripeSubscription): void
    {
        if ($stripeSubscription->pending_setup_intent !== null) {
            $this->_setConfirmationAction([
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
            // Active, terminal and asynchronously pending subscriptions may have no browser action.
            return;
        }

        $this->_setConfirmationAction([
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
            $receiptEmail = trim((string)References::parseContent($paymentReceiptEmail, $submission));

            if ($receiptEmail !== '') {
                $payload['receipt_email'] = $receiptEmail;
            }
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

    private function _resolveWebhookSubscription(array $remote): ?Subscription
    {
        $subscriptions = Formie::$plugin->getSubscriptions();
        $reference = (string)($remote['id'] ?? '');

        if ($existing = $subscriptions->getSubscriptionByReference($reference, $this->id)) {
            return $existing;
        }
        $uid = $remote['metadata']['formieSubscriptionUid'] ?? null;
        $candidate = $uid ? $subscriptions->getSubscriptionByUid($uid) : null;

        if (!$candidate || $candidate->integrationId !== $this->id || $candidate->reference || !($candidate->scope['initial'] ?? false)) {
            return null;
        }
        $candidate->reference = $reference;
        $subscriptions->saveSubscription($candidate);

        return $subscriptions->getSubscriptionById($candidate->id);
    }

    private function _syncInitialSubscriptionPayment(Subscription $subscription): void
    {
        if (!$submission = $subscription->getSubmission()) {
            return;
        }

        foreach (Formie::$plugin->getPayments()->getSubmissionPayments($submission) as $payment) {
            if ($payment->subscriptionId !== $subscription->id || !($payment->scope['initial'] ?? false)) {
                continue;
            }
            $payment->status = match ($subscription->getState()) {
                SubscriptionStatus::TRIALING, SubscriptionStatus::ACTIVE => PaymentModel::STATUS_SUCCEEDED,
                SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED => PaymentModel::STATUS_CANCELLED,
                SubscriptionStatus::FAILED => PaymentModel::STATUS_FAILED,
                SubscriptionStatus::UNKNOWN => PaymentModel::STATUS_UNKNOWN,
                default => PaymentModel::STATUS_PENDING,
            };
            $payment->reference ??= $subscription->reference;
            Formie::$plugin->getPayments()->savePayment($payment);
            Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($payment);
        }
    }

    private function _applySubscriptionSnapshot(
        Subscription $subscription,
        array $data,
        string $source,
        ?string $providerEventId = null,
        ?int $providerUpdatedAt = null,
    ): Subscription {
        return Formie::$plugin->getSubscriptions()->applySnapshot(
            $subscription,
            $this->_subscriptionSnapshot($data, $providerEventId, $providerUpdatedAt),
            $source,
        );
    }

    private function _subscriptionSnapshot(
        array $data,
        ?string $providerEventId = null,
        ?int $providerUpdatedAt = null,
        ?SubscriptionCancellationMode $cancellationMode = null,
    ): SubscriptionSnapshot {
        $providerStatus = (string)($data['status'] ?? '');
        $status = match ($providerStatus) {
            'trialing' => SubscriptionStatus::TRIALING,
            'active' => SubscriptionStatus::ACTIVE,
            'incomplete' => SubscriptionStatus::PENDING,
            'past_due', 'unpaid' => SubscriptionStatus::PAST_DUE,
            'paused' => SubscriptionStatus::PAUSED,
            'incomplete_expired' => SubscriptionStatus::FAILED,
            'canceled' => SubscriptionStatus::CANCELLED,
            default => SubscriptionStatus::UNKNOWN,
        };
        $scheduledCancellation = !empty($data['cancel_at_period_end']) || !empty($data['cancel_at']);
        $periodEnd = $this->_subscriptionDate($data['current_period_end'] ?? null);
        $observedAt = $providerUpdatedAt ?? time();
        $observedDate = (new DateTimeImmutable())->setTimestamp($observedAt);
        $resolvedCancellationMode = $cancellationMode
            ?? ($scheduledCancellation ? SubscriptionCancellationMode::AT_PERIOD_END : null)
            ?? ($status === SubscriptionStatus::CANCELLED ? SubscriptionCancellationMode::IMMEDIATE : null);

        return new SubscriptionSnapshot(
            status: $status,
            providerStatus: $providerStatus,
            reference: isset($data['id']) ? (string)$data['id'] : null,
            providerUpdatedAt: $providerUpdatedAt,
            providerEventId: $providerEventId ?: null,
            startedAt: $this->_subscriptionDate($data['start_date'] ?? null),
            trialStartsAt: $this->_subscriptionDate($data['trial_start'] ?? null),
            trialEndsAt: $this->_subscriptionDate($data['trial_end'] ?? null),
            currentPeriodStartsAt: $this->_subscriptionDate($data['current_period_start'] ?? null),
            currentPeriodEndsAt: $periodEnd,
            nextPaymentAt: $status->isTerminal() ? null : $this->_subscriptionDate($data['next_payment_attempt'] ?? null),
            pausedAt: $status === SubscriptionStatus::PAUSED ? new DateTimeImmutable() : null,
            cancelAt: $scheduledCancellation ? $this->_subscriptionDate($data['cancel_at'] ?? null) ?? $periodEnd : null,
            cancelledAt: $status === SubscriptionStatus::CANCELLED
                ? $this->_subscriptionDate($data['canceled_at'] ?? null) ?? $observedDate
                : null,
            endedAt: $status->isTerminal()
                ? $this->_subscriptionDate($data['ended_at'] ?? null) ?? $observedDate
                : null,
            cancellationMode: $resolvedCancellationMode,
            rawData: $data,
        );
    }

    private function _subscriptionDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (new DateTimeImmutable())->setTimestamp((int)$value);
        }

        try {
            return new DateTimeImmutable((string)$value);
        } catch (Throwable) {
            return null;
        }
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

    private function _createResourceOnce(Submission $submission, string $resource, string $action, array $payload): mixed
    {
        if ($this->id && $this->getField()?->id && in_array($resource, ['paymentIntents', 'subscriptions'], true)) {
            $payment = Formie::$plugin->getPayments()->prepareAttempt($this, $submission);
            $payload['metadata']['formiePaymentUid'] = $payment->uid;

            if ($resource === 'subscriptions') {
                $payload['metadata']['formieSubscriptionUid'] = Formie::$plugin->getPayments()->prepareSubscription($this, $submission)->uid;
            }
        }
        $service = $this->getStripe()->$resource;
        $identity = $this->_getIdempotencyKey($submission, $action);
        $providerKey = $action === 'payment-intent-create' ? $this->_getIdempotencyKey($submission, $action, [
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'fieldId' => $this->getField()?->id,
        ]) : $identity;

        return (new DeliveryAttempt((int)$submission->id, $resource, $identity, $providerKey))->execute(
            $payload,
            fn(string $key) => $service->create($payload, ['idempotency_key' => $key]),
            23 * 3600,
            fn($result) => $result->id,
            fn(string $id) => $service->retrieve($id, []),
        );
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

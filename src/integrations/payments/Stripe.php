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
use verbb\formie\events\SubmissionEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\Plan;
use verbb\formie\models\Subscription;
use verbb\formie\services\Submissions;

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
    public const EVENT_MODIFY_SINGLE_PAYLOAD = 'modifySinglePayload';
    public const EVENT_MODIFY_PLAN_PAYLOAD = 'modifyPlanPayload';
    public const EVENT_MODIFY_CUSTOMER_PAYLOAD = 'modifyCustomerPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';

    // https://stripe.com/docs/currencies#zero-decimal
    private const ZERO_DECIMAL_CURRENCIES = ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];


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

    public static function toStripeAmount(float $amount, string $currency): float
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES)) {
            return $amount;
        }

        return ceil($amount * 100);
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

    public function getInitialPaymentInformation(): array
    {
        $currency = static::getSiteCurrency();
        $currencyType = $this->getFieldSetting('currencyType');
        $currencyFixed = $this->getFieldSetting('currencyFixed');
        $currencyVariable = $this->getFieldSetting('currencyVariable');

        if ($currencyType === Payment::VALUE_TYPE_FIXED) {
            $currency = strtolower($currencyFixed);
        } else if ($currencyType === Payment::VALUE_TYPE_DYNAMIC) {
            $currency = $currencyVariable;
        }

        // Set a default amount for when using dynamic values. This is changed on the front-end when updated there.
        $amount = self::toStripeAmount(100, $currency);
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->getFieldSetting('amountVariable');

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

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $billingDetails = $this->getFieldSetting('billingDetails', false);
        $hidePostalCode = $this->getFieldSetting('hidePostalCode', false);
        $hideIcon = $this->getFieldSetting('hideIcon', false);
        $paymentType = $this->getFieldSetting('type', 'single');

        $settings = [
            'publishableKey' => App::parseEnv($this->publishableKey),
            'billingDetails' => $billingDetails,
            'hidePostalCode' => $hidePostalCode,
            'hideIcon' => $hideIcon,
            'paymentType' => $paymentType,

            // Set the default currency and amount for when the page loads, and then JS can take over if they're dynamic
            // This is due to needing to create a Payment Intent on page load.
            'initialPaymentInformation' => $this->getInitialPaymentInformation(),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/stripe.js'),
            'module' => 'FormieStripe',
            'settings' => $settings,
        ];
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

    public function processPayment(Submission $submission): bool
    {
        $result = false;

        $type = $this->getFieldSetting('type');

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        if ($type === self::PAYMENT_TYPE_SINGLE) {
            $result = $this->processSinglePayment($submission);
        } else if ($type === self::PAYMENT_TYPE_SUBSCRIPTION) {
            $result = $this->processSubscriptionPayment($submission);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return true;
        }

        return $result;
    }

    public function processSubscriptionPayment(Submission $submission): bool
    {
        $response = [];
        $payload = [];

        $field = $this->getField();
        $fieldValue = $this->getPaymentFieldValue($submission);
        $subscriptionId = $fieldValue['stripeSubscriptionId'] ?? null;
        $paymentIntentId = $fieldValue['stripePaymentIntentId'] ?? null;

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

            // Raise a `modifySubscriptionPayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_SUBSCRIPTION_PAYLOAD, $event);

            // Create the Stripe subscription
            $response = $this->getStripe()->subscriptions->create($event->payload);

            // Create and record our Formie subscription
            $subscription = new Subscription();
            $subscription->integrationId = $this->id;
            $subscription->submissionId = $submission->id;
            $subscription->fieldId = $field->id;
            $subscription->planId = $plan->id;
            $subscription->reference = $response->id;
            $subscription->subscriptionData = $response->toArray();
            $subscription->trialDays = 0;

            $this->_setSubscriptionStatusData($subscription, $response);

            Formie::$plugin->getSubscriptions()->saveSubscription($subscription);

            // Tell the front-end to stop the submission and to confirm the Payment Intent.
            if ($response->pending_setup_intent !== null) {
                $submission->getForm()->addSubmitData([
                    'event' => 'FormiePaymentStripeConfirm',
                    'data' => [
                        'type' => 'setup',
                        'clientSecret' => $response->pending_setup_intent->client_secret,
                        'subscriptionId' => $response->id,
                        'returnUrl' => $this->getReturnUrl($submission),
                    ],
                ]);
            } else {
                $submission->getForm()->addSubmitData([
                    'event' => 'FormiePaymentStripeConfirm',
                    'data' => [
                        'type' => 'payment',
                        'clientSecret' => $response->latest_invoice->payment_intent->client_secret,
                        'subscriptionId' => $response->id,
                        'returnUrl' => $this->getReturnUrl($submission),
                    ],
                ]);
            }

            // Add an error to the form to ensure it doesn't proceed, even though it's not an error here.
            $this->addFieldError($submission, '');

            return false;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Subscription error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = (strlen($e->getMessage()) > 30) ? substr($e->getMessage(), 0, 30) . '...' : '';
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
        $fieldValue = $this->getPaymentFieldValue($submission);
        $paymentIntentId = $fieldValue['stripePaymentIntentId'] ?? null;

        try {
            if ($paymentIntentId) {
                $paymentIntent = $this->getStripe()->paymentIntents->retrieve($paymentIntentId);

                if ($paymentIntent) {
                    if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                        $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntent->id);

                        if ($payment) {
                            $payment->status = PaymentModel::STATUS_SUCCESS;
                            $payment->reference = $paymentIntent->id;
                            $payment->response = $paymentIntent->toArray();

                            Formie::$plugin->getPayments()->savePayment($payment);
                        } else {
                            throw new Exception('Unable to find payment by "' . $paymentIntent->id . '".');
                        }
                    } else {
                        throw new Exception('Unable to confirm payment intent "' . $paymentIntent->status . '".');
                    }
                } else {
                    throw new Exception('Unable to find payment intent by "' . $paymentIntentId . '".');
                }

                return true;
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
            $response = $this->getStripe()->paymentIntents->create($event->payload);

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
                'event' => 'FormiePaymentStripeConfirm',
                'data' => [
                    'clientSecret' => $response->client_secret,
                    'paymentIntentId' => $response->id,
                    'returnUrl' => $this->getReturnUrl($submission),
                ],
            ]);

            // Add an error to the form to ensure it doesn't proceed, even though it's not an error here.
            $this->addFieldError($submission, '');

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

            $this->addFieldError($submission, Craft::t('formie', $payment->message));

            return false;
        } catch (StripeException\ApiErrorException $e) {
            $body = $e->getJsonBody();

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromStripeAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->reference = null;
            $payment->code = $body['error']['code'] ?? $body['error']['type'] ?? $e->getStripeCode();
            $payment->message = $body['error']['message'] ?? $e->getMessage();
            $payment->response = $body;

            Formie::$plugin->getPayments()->savePayment($payment);

            $this->addFieldError($submission, Craft::t('formie', $payment->message));

            return false;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = (strlen($e->getMessage()) > 30) ? substr($e->getMessage(), 0, 30) . '...' : '';
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

            $origin = $request->getParam('origin');
            $token = $request->getParam('token');
            $paymentIntentId = $request->getParam('payment_intent');

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }

            $submission = Submission::find()->isIncomplete(true)->uid($token)->one();

            if (!$submission) {
                throw new NotFoundHttpException('Submission not found');
            }

            $form = $submission->form;

            if (!$paymentIntentId) {
                throw new NotFoundHttpException('Payment Intent not found');
            }

            $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntentId);

            if (!$payment) {
                throw new NotFoundHttpException('Payment ' . $paymentIntentId . ' not found');
            }

            $paymentIntent = $this->getStripe()->paymentIntents->retrieve($paymentIntentId);

            if (!$paymentIntent) {
                throw new NotFoundHttpException('Payment Intent ' . $paymentIntentId . ' not found');
            }

            if ($paymentIntent->status !== PaymentIntent::STATUS_SUCCEEDED) {
                $payment->status = PaymentModel::STATUS_FAILED;
                $payment->reference = $paymentIntentId;

                Formie::$plugin->getPayments()->savePayment($payment);

                throw new Exception('Payment Intent ' . $paymentIntentId . ' ' . $paymentIntent->status);
            }

            // Complete the submission and lodge the payment
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $paymentIntentId;

            Formie::$plugin->getPayments()->savePayment($payment);

            Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);
            Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));

            // Delete the currently saved page
            $form->resetCurrentPage();
            $form->resetCurrentSubmission();

            $submission->isIncomplete = false;
            Craft::$app->getElements()->saveElement($submission, false);

            // Fire an 'afterSubmission' event
            $event = new SubmissionEvent([
                'submission' => $submission,
                'submitAction' => 'submit',
                'success' => true,
            ]);
            $this->trigger(Submissions::EVENT_AFTER_SUBMISSION, $event);

            if (!$submission->isIncomplete) {
                if ($event->success) {
                    // Send off some emails, if all good!
                    Formie::$plugin->getSubmissions()->sendNotifications($event->submission);

                    // Trigger any integrations
                    Formie::$plugin->getSubmissions()->triggerIntegrations($event->submission);
                } else if ($submission->isSpam && $settings->spamEmailNotifications) {
                    // Special-case for wanting to send emails for spam
                    Formie::$plugin->getSubmissions()->sendNotifications($event->submission);
                }
            }
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            if ($form) {
                Formie::$plugin->getService()->setError($form->id, $e->getMessage());
            }
        }

        // Check the form settings for what needs to be done, as we're returning from offssite
        if ($form && ($redirect = $form->getRedirectUrl())) {
            $origin = $redirect;
        }

        return Craft::$app->getResponse()->redirect($origin);
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
            $response->data = 'ok';

            return $response;
        }

        try {
            // Check the payload and signature
            StripeWebhook::constructEvent($rawData, $stripeSignature, $secret);
        } catch (Throwable $e) {
            Integration::error($this, 'Webhook signature check failed: ' . $e->getMessage());
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

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Payment Type'),
                'help' => Craft::t('formie', 'Select the type of payment to use.'),
                'name' => 'type',
                'validation' => 'required',
                'required' => true,
                'options' => [
                    ['label' => Craft::t('formie', 'Once-off'), 'value' => self::PAYMENT_TYPE_SINGLE],
                    ['label' => Craft::t('formie', 'Subscription'), 'value' => self::PAYMENT_TYPE_SUBSCRIPTION],
                ],
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
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Payment Currency'),
                'help' => Craft::t('formie', 'Provide the currency to be used for the transaction. This can be either a fixed value, or derived from a field.'),
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::selectField([
                                'name' => 'currencyType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Fixed Value'), 'value' => Payment::VALUE_TYPE_FIXED],
                                    ['label' => Craft::t('formie', 'Dynamic Value'), 'value' => Payment::VALUE_TYPE_DYNAMIC],
                                ],
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'currencyFixed',
                                'if' => '$get(currencyType).value == ' . Payment::VALUE_TYPE_FIXED,
                                'options' => array_merge(
                                    [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                                    static::getCurrencyOptions()
                                ),
                            ]),
                            SchemaHelper::fieldSelectField([
                                'name' => 'currencyVariable',
                                'if' => '$get(currencyType).value == ' . Payment::VALUE_TYPE_DYNAMIC,
                            ]),
                        ],
                    ],
                ],
            ],
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Subscription Frequency'),
                'help' => Craft::t('formie', 'Select how often this subscription should be billed.'),
                'if' => '$get(type).value == subscription',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'name' => 'frequencyValue',
                                'required' => true,
                                'validation' => 'required',
                                'sections-schema' => [
                                    'prefix' => [
                                        '$el' => 'span',
                                        'attrs' => ['class' => 'fui-prefix-text'],
                                        'children' => Craft::t('formie', 'Bill every'),
                                    ],
                                ],
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'frequencyType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Days'), 'value' => 'day'],
                                    ['label' => Craft::t('formie', 'Weeks'), 'value' => 'week'],
                                    ['label' => Craft::t('formie', 'Months'), 'value' => 'month'],
                                    ['label' => Craft::t('formie', 'Years'), 'value' => 'year'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Subscription Description'),
                'help' => Craft::t('formie', 'Enter a description for the subscription. This will only be shown in Stripe.'),
                'name' => 'planDescription',
                'if' => '$get(type).value == subscription',
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Payment Receipt'),
                'help' => Craft::t('formie', 'Whether Stripe should email a receipt to the customer on successful payment.'),
                'name' => 'paymentReceipt',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Email Address'),
                'help' => Craft::t('formie', 'Enter the email the payment receipt should be delivered to.'),
                'name' => 'paymentReceiptEmail',
                'variables' => 'emailVariables',
                'if' => '$get(paymentReceipt).value',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Payment Description'),
                'help' => Craft::t('formie', 'Enter a description for this payment, to appear against the transaction in your Stripe account, and on the payment receipt sent to the customer.'),
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
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Metadata'),
                'help' => Craft::t('formie', 'Add any additional metadata to store against a transaction.'),
                'generateValue' => false,
                'name' => 'metadata',
                'validation' => '',
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                ],
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
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide ZIP / Postal Code'),
                'help' => Craft::t('formie', 'Whether to hide the zip/postal code field, shown alongside credit card number fields.'),
                'name' => 'hidePostalCode',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide Icon'),
                'help' => Craft::t('formie', 'Whether to hide the card icon, shown alongside credit card number fields.'),
                'name' => 'hideIcon',
            ]),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        if ($key === 'fieldInputWrapper') {
            return new HtmlTag('div', [
                'class' => 'fui-input-wrapper fui-stripe-elements-wrapper',
            ]);
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('div', [
                'class' => 'fui-stripe-elements',
                'data-fui-stripe-elements' => true,
            ]);
        }

        if ($key === 'stripePlaceholder') {
            return new HtmlTag('div', [
                'class' => 'fui-stripe-placeholder',
                'text' => '<div class="fui-loading"></div>' . Craft::t('formie', 'Loading payment options...'),
                'data-fui-stripe-elements-placeholder' => true,
            ]);
        }

        return null;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publishableKey', 'secretKey'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
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
                if ($paymentIntentStatus !== PaymentIntent::STATUS_SUCCEEDED) {
                    $payment->status = PaymentModel::STATUS_FAILED;
                } else {
                    $payment->status = PaymentModel::STATUS_SUCCESS;
                }

                Formie::$plugin->getPayments()->savePayment($payment);
            }
        }
    }


    // Private Methods
    // =========================================================================

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
        $field = $this->getField();
        $fieldValue = $this->getPaymentFieldValue($submission);

        $payload = [];

        // Add a few other things about the customer from mapping (in field settings)
        $billingName = $this->getFieldSetting('billingDetails.billingName');
        $billingAddress = $this->getFieldSetting('billingDetails.billingAddress');
        $billingEmail = $this->getFieldSetting('billingDetails.billingEmail');

        // Just in case we're picking the string version of the Address field value (due to Vue restrictions)
        // ensure that we refer to the actual Address field model value as we need the "bits".
        $billingAddress = str_replace('.__toString', '', $billingAddress);

        if ($billingName) {
            $payload['name'] = $this->getMappedFieldValue($billingName, $submission, new IntegrationField());
        }

        if ($billingAddress) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_ARRAY;

            $address = $this->getMappedFieldValue($billingAddress, $submission, $integrationField);

            if ($address) {
                $payload['address']['line1'] = ArrayHelper::remove($address, 'address1');
                $payload['address']['line2'] = ArrayHelper::remove($address, 'address2');
                $payload['address']['city'] = ArrayHelper::remove($address, 'city');
                $payload['address']['postal_code'] = ArrayHelper::remove($address, 'zip');
                $payload['address']['state'] = ArrayHelper::remove($address, 'state');
                $payload['address']['country'] = ArrayHelper::remove($address, 'country');
            }
        }

        if ($billingEmail) {
            $payload['email'] = $this->getMappedFieldValue($billingEmail, $submission, new IntegrationField());
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

    private function _setPayloadDetails(array &$payload, Submission $submission, string $type): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription');
        $metadata = $this->getFieldSetting('metadata', []);
        $paymentReceipt = $this->getFieldSetting('paymentReceipt', false);
        $paymentReceiptEmail = $this->getFieldSetting('paymentReceiptEmail');

        if ($paymentDescription) {
            $payload['description'] = Variables::getParsedValue($paymentDescription, $submission, $submission->getForm());
        }

        if ($paymentReceipt && $paymentReceiptEmail && $type === 'single') {
            $payload['receipt_email'] = Variables::getParsedValue($paymentReceiptEmail, $submission, $submission->getForm());
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
                    $payload['metadata'][$label] = Variables::getParsedValue($value, $submission, $submission->getForm());
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
}

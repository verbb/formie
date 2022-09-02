<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields\formfields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\Plan;
use verbb\formie\models\Subscription;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response;

use yii\base\Event;

use Throwable;
use Exception;

use Stripe\StripeClient;
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

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Stripe');
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
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

    private ?StripeClient $_stripe = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with Stripe.');
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $billingDetails = $this->getFieldSetting('billingDetails', false);
        $hidePostalCode = $this->getFieldSetting('hidePostalCode', false);
        $hideIcon = $this->getFieldSetting('hideIcon', false);

        $settings = [
            'publishableKey' => App::parseEnv($this->publishableKey),
            'billingDetails' => $billingDetails,
            'hidePostalCode' => $hidePostalCode,
            'hideIcon' => $hideIcon,
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/payments/stripe.js', true),
            'module' => 'FormieStripe',
            'settings' => $settings,
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->publishableKey) && App::parseEnv($this->secretKey);
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publishableKey', 'secretKey'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getAmount($submission): float
    {
        // Ensure the amount is converted to Stripe for zero-decimal currencies
        return self::toStripeAmount(parent::getAmount($submission), $this->getCurrency($submission));
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function processSubscriptionPayment(Submission $submission): bool
    {
        $response = [];
        $payload = [];

        $field = $this->getField();
        $fieldValue = $submission->getFieldValue($field->handle);
        $subscriptionId = $fieldValue['stripeSubscriptionId'] ?? null; 

        try {
            // Are we come back from a 3DS verification? Update the payment, skip everything else (already done)
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
                        Integration::error($this, 'Unable to find subscription by "' . $stripeSubscription->id . '".');
                    }
                } else {
                    Integration::error($this, 'Unable to find Stripe subscription by "' . $subscriptionId . '".');
                }

                return true;
            }
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

            $submission->addError($field->handle, Craft::t('formie', $e->getMessage()));

            return false;
        }

        // Get or create the plan (product) first
        $plan = $this->_getOrCreatePlan($submission);

        if (!$plan) {
            Integration::error($this, 'Unable to get or create plan.');

            return false;
        }

        // Get the Stripe customer. We create a new one each transaction
        $customer = $this->_getCustomer($submission);

        if (!$customer) {
            Integration::error($this, 'Unable to create customer.');

            return false;
        }

        try {
            $payload = [
                'customer' => $customer['id'],
                'items' => [['plan' => $plan->reference]],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission);

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

            $invoice = $response->latest_invoice;

            // Is this paid, or needs further action (3DS)?
            if ($response->status === StripeSubscription::STATUS_INCOMPLETE && $invoice->status === StripeInvoice::STATUS_OPEN) {
                $paymentIntent = $invoice->payment_intent;
                
                if ($paymentIntent->status === PaymentIntent::STATUS_REQUIRES_ACTION) {
                    // Store the data we need for 3DS against the form, which is added is the Ajax response
                    $submission->getForm()->addFrontEndJsEvents([
                        'event' => 'FormiePaymentStripe3DS',
                        'data' => [
                            'subscription_id' => $response->id,
                            'payment_intent_id' => $paymentIntent->id,
                            'client_secret' => $paymentIntent->client_secret,
                        ],
                    ]);

                    // Add an error to the form to ensure it doesn't proceed, and the 3DS popup is shown
                    $submission->addError($field->handle, Craft::t('formie', 'This payment requires 3D Secure authentication. Please follow the instructions on-screen to continue.'));

                    return false;
                }
            }
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

            $submission->addError($field->handle, Craft::t('formie', $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function processSinglePayment(Submission $submission): bool
    {
        $response = [];
        $payload = [];

        $field = $this->getField();
        $fieldValue = $submission->getFieldValue($field->handle);
        $paymentMethodId = $fieldValue['stripePaymentId'] ?? null; 
        $paymentIntentId = $fieldValue['stripePaymentIntentId'] ?? null;

        $amount = 0;
        $currency = null;

        try {
            // Are we come back from a 3DS verification? Update the payment, skip everything else (already done)
            if ($paymentIntentId) {
                $paymentIntent = $this->getStripe()->paymentIntents->retrieve($paymentIntentId);

                if ($paymentIntent) {
                    $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentIntent->id);

                    if ($payment) {
                        $payment->status = PaymentModel::STATUS_SUCCESS;
                        $payment->reference = $paymentIntent->id;
                        $payment->response = $paymentIntent->toArray();

                        Formie::$plugin->getPayments()->savePayment($payment);
                    } else {
                        Integration::error($this, 'Unable to find payment by "' . $paymentIntent->id . '".');
                    }
                } else {
                    Integration::error($this, 'Unable to find payment intent by "' . $paymentIntentId . '".');
                }

                return true;
            }

            // Protect against invalid data being sent.
            if (!$paymentMethodId || !is_string($paymentMethodId)) {
                throw new Exception("Missing `stripePaymentId` from payload: {$paymentMethodId}.");
            }

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
                'confirmation_method' => 'manual',
                'confirm' => true,
                'payment_method' => $paymentMethodId,
            ];

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission);

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_SINGLE_PAYLOAD, $event);

            // Send the payment to Stripe
            $response = $this->getStripe()->paymentIntents->create($event->payload);

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = self::fromStripeAmount($amount, $currency);
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_PENDING;
            $payment->reference = $response->id;
            $payment->response = $response->toArray();

            if ($response->status === PaymentIntent::STATUS_SUCCEEDED) {
                $payment->status = PaymentModel::STATUS_SUCCESS;
            }

            Formie::$plugin->getPayments()->savePayment($payment);

            // Is this paid, or needs further action (3DS)?
            if ($response->status === PaymentIntent::STATUS_REQUIRES_ACTION) {
                // Store the data we need for 3DS against the form, which is added is the Ajax response
                $submission->getForm()->addFrontEndJsEvents([
                    'event' => 'FormiePaymentStripe3DS',
                    'data' => [
                        'id' => $response->id,
                        'client_secret' => $response->client_secret,
                    ],
                ]);

                // Add an error to the form to ensure it doesn't proceed, and the 3DS popup is shown
                $submission->addError($field->handle, Craft::t('formie', 'This payment requires 3D Secure authentication. Please follow the instructions on-screen to continue.'));

                return false;
            }
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

            $submission->addError($field->handle, Craft::t('formie', $payment->message));

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

            $submission->addError($field->handle, Craft::t('formie', $payment->message));

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

            $submission->addError($field->handle, Craft::t('formie', $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritDoc
     */
    public function cancelSubscription($reference, $params = [])
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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
                                    formfields\Calculations::class,
                                    formfields\Dropdown::class,
                                    formfields\Hidden::class,
                                    formfields\Number::class,
                                    formfields\Radio::class,
                                    formfields\SingleLineText::class,
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

    /**
     * @inheritDoc
     */
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
                'validation' => 'min:0',
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                ],
                'generateValue' => false,
                'columns' => [
                    [
                        'type' => 'label',
                        'label' => 'Option',
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'value',
                        'label' => 'Value',
                        'class' => 'singleline-cell textual',
                    ],
                ],
                'name' => 'metadata',
            ]),
        ];
    }


    /**
     * @inheritDoc
     */
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


    // Protected Methods
    // =========================================================================

    /**
     * Handle a created invoice.
     *
     * @param array $data
     * @throws StripeException\ApiErrorException
     */
    protected function handleInvoiceCreated(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        $canBePaid = empty($stripeInvoice['paid']) && $stripeInvoice['billing'] === 'charge_automatically';

        if ($canBePaid) {
            $invoice = $this->getStripe()->invoices->retrieve($stripeInvoice['id']);
            $invoice->pay();
        }
    }

    /**
     * Handle a successful invoice payment event.
     *
     * @param array $data
     * @throws Throwable if something went wrong when processing the invoice
     */
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

    /**
     * Handle a failed invoice by updating the subscription data for the subscription it failed.
     *
     * @param array $data
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function handleInvoiceFailed(array $data): void
    {
        $stripeInvoice = $data['data']['object'];

        // Sanity check
        if ($stripeInvoice['paid']) {
            return;
        }

        $subscriptionReference = $stripeInvoice['subscription'] ?? null;

        if (!$subscriptionReference || !($subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($subscriptionReference))) {
            Integration::log($this, 'Subscription with the reference “' . $subscriptionReference . '” not found when processing webhook ' . $data['id']);

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

    /**
     * Handle a deleted plan.
     *
     * @param array $data
     * @throws InvalidConfigException If plan not available
     */
    protected function handlePlanDeleted(array $data): void
    {
        $reference = $data['data']['object']['id'];

        if ($plan = Formie::$plugin->getPlans()->getPlanByReference($reference)) {
            Formie::$plugin->getPlans()->archivePlanById($plan->id);

            Integration::log($this, Craft::t('formie', 'Plan “{reference}” was archived because the corresponding plan was deleted on Stripe.', [
                'reference' => $reference,
            ]));
        }
    }

    /**
     * Handle a updated plan.
     *
     * @param array $data
     * @throws InvalidConfigException If plan not available
     */
    protected function handlePlanUpdated(array $data): void
    {
        // Nothing for now
    }

    /**
     * Handle a created subscription.
     *
     * @param array $data
     * @throws Throwable
     */
    protected function handleSubscriptionCreated(array $data): void
    {
        // Nothing for now
    }

    /**
     * Handle an expired subscription.
     *
     * @param array $data
     * @throws Throwable
     */
    protected function handleSubscriptionExpired(array $data): void
    {
        $stripeSubscription = $data['data']['object'];

        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription['id']);

        if (!$subscription) {
            Integration::log($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        Formie::$plugin->getSubscriptions()->expireSubscription($subscription);
    }

    /**
     * Handle an updated subscription.
     *
     * @param array $data
     * @throws Throwable
     */
    protected function handleSubscriptionUpdated(array $data): void
    {
        $stripeSubscription = $data['data']['object'];
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($stripeSubscription['id']);

        if (!$subscription) {
            Integration::log($this, 'Subscription with the reference “' . $stripeSubscription['id'] . '” not found when processing webhook ' . $data['id']);

            return;
        }

        // See if we care about this subscription at all
        $subscription->subscriptionData = $data['data']['object'];

        $this->_setSubscriptionStatusData($subscription);

        if (empty($data['data']['object']['plan'])) {
            Integration::log($this, $subscription->reference . ' contains multiple plans, which is not supported. (event "' . $data['id'] . '")');
        } else {
            $planReference = $data['data']['object']['plan']['id'];
            $plan = Formie::$plugin->getPlans()->getPlanByReference($planReference);

            if ($plan) {
                $subscription->planId = $plan->id;
            } else {
                Integration::log($this, $subscription->reference . ' was switched to a plan on Stripe that does not exist on this Site. (event "' . $data['id'] . '")');
            }
        }

        Formie::$plugin->getSubscriptions()->updateSubscription($subscription);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
        $payload['id'] = ArrayHelper::recursiveImplode('_', array_merge(['formie', $submission->getForm()->handle, $field->handle], $payload));

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

    /**
     * @inheritDoc
     */
    private function _getPlan($planId)
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
    
    /**
     * @inheritDoc
     */
    private function _createPlan($payload)
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

    /**
     * @inheritDoc
     */
    private function _getCustomer(Submission $submission)
    {
        // We always create a new customer. Maybe one day we'll figure out a way to handle this better
        $field = $this->getField();
        $fieldValue = $submission->getFieldValue($field->handle);
        $paymentMethodId = $fieldValue['stripePaymentId'] ?? null; 

        // Create base-level customer data with the payload from the front-end
        $payload = [
            'payment_method' => $paymentMethodId,
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ];

        // Add a few other things about the customer from mapping (in field settings)
        $billingName = $this->getFieldSetting('billingDetails.billingName');
        $billingAddress = $this->getFieldSetting('billingDetails.billingAddress');
        $billingEmail = $this->getFieldSetting('billingDetails.billingEmail');

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

    /**
     * @inheritDoc
     */
    private function _setPayloadDetails(array &$payload, Submission $submission): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription');
        $metadata = $this->getFieldSetting('metadata', []);
        $paymentReceipt = $this->getFieldSetting('paymentReceipt', false);
        $paymentReceiptEmail = $this->getFieldSetting('paymentReceiptEmail');

        if ($paymentDescription) {
            $payload['description'] = Variables::getParsedValue($paymentDescription, $submission, $submission->getForm());
        }

        if ($paymentReceipt && $paymentReceiptEmail) {
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
                    $payload['metadata'][$label] = $value;
                }
            }
        }
    }

    /**
     * Set the various status properties on a Subscription by the subscription data set on it.
     *
     * @param Subscription $subscription
     * @throws \Exception
     */
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

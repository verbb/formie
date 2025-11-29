<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
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
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use GuzzleHttp\Client;

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

            // Create the payment immediately so we can pass a reference to the Mollie payment
            Formie::$plugin->getPayments()->savePayment($payment);

            $payload = [
                'session_token' => StringHelper::UUID(),
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
                $action = $event['action'] ?? '';
                $resourceId = $event['links']['payment'] ?? null;
                $metadata = $event['metadata'] ?? [];

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

                $payment = Formie::$plugin->getPayments()->getPaymentById($formiePaymentId);

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

                $payment->reference = $gcPayment['id'];
                $payment->response = $gcPayment;

                Formie::$plugin->getPayments()->savePayment($payment);
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

    public function getTransactionStatus(PaymentModel $payment): void
    {
        $payment->status = PaymentModel::STATUS_PENDING;

        Formie::$plugin->getPayments()->savePayment($payment);

        // Redirect back to the form and submit
        $submission = $payment->getSubmission();

        if (!$submission) {
            return;
        }

        $submission->isIncomplete = false;
        Craft::$app->getElements()->saveElement($submission, false);

        // Fire any notifications/integrations
        Formie::$plugin->getSubmissions()->sendNotifications($submission);
        Formie::$plugin->getSubmissions()->triggerIntegrations($submission);

        $form = $submission->getForm();

        Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);
        $url = '';

        // Handle heading back to the form and either redirecting to the form's redirect or show a message
        if ($form->settings->submitAction == 'message' || $form->settings->submitAction == 'reload') {
            if ($form->settings->submitAction == 'message') {
                Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));
            }

            // When reloading the page, provide a `submission` variable to pick up on the finalise submission
            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            $url = $payment->redirectUrl;
        } else {
            $url = $form->getRedirectUrl(false, false);
        }

        Craft::$app->getResponse()->redirect($url)->send();
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
                'help' => Craft::t('formie', 'Enter a description for this payment, to appear against the transaction in your Mollie account, and on the payment receipt sent to the customer.'),
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
}

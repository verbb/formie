<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
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

class Mollie extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Mollie');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;


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
        return App::parseEnv($this->apiKey);
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
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/mollie.js'),
            'module' => 'FormieMollie',
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
                'amount' => [
                    'currency' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'redirectUrl' => $this->getReturnUrl([
                    'paymentUid' => $payment->uid,
                ]),
                'webhookUrl' => $this->getRedirectUri(),
                'metadata' => [
                    'formiePaymentId' => $payment->id,
                ],
            ];

            // Add in extra settings configured at the field level
            $this->_setPayloadDetails($payload, $submission);

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'payments', ['json' => $event->payload]);

            $paymentId = $response['id'] ?? null;
            $checkoutUrl = $response['_links']['checkout']['href'] ?? null;

            // Update the Formie payment with Mollie payment details
            $payment->reference = $paymentId;
            $payment->response = $response;

            Formie::$plugin->getPayments()->savePayment($payment);

            // Redirect via the front-end for a nicer UX than just a sudden redirect away.
            $submission->getForm()->addSubmitData([
                'event' => 'FormiePaymentMollieRedirect',
                'data' => [
                    'checkoutUrl' => $checkoutUrl,
                ],
            ]);

            // Add an error to the form to ensure it doesn't proceed and redirects
            $this->addFieldError($submission, Craft::t('formie', 'Please wait while you are redirected to complete payment.'));

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
        $rawData = Craft::$app->getRequest()->getRawBody();
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $paymentId = $request->getParam('id');

        if (!$paymentId) {
            Integration::error($this, 'Mollie webhook triggered with no payment ID.');
            $response->data = 'Missing payment ID.';

            return $response;
        }

        try {
            // Fetch latest payment info from Mollie
            $molliePayment = $this->request('GET', "payments/{$paymentId}");

            $metadata = $molliePayment['metadata'] ?? [];
            $formiePaymentId = $metadata['formiePaymentId'] ?? null;

            if (!$formiePaymentId) {
                Integration::error($this, "Mollie webhook missing Formie payment ID in metadata.");
                $response->data = 'Missing Formie payment ID.';

                return $response;
            }

            $payment = Formie::$plugin->getPayments()->getPaymentById($formiePaymentId);

            if (!$payment) {
                Integration::error($this, "Formie payment not found for ID {$formiePaymentId}.");
                $response->data = 'Payment not found.';

                return $response;
            }

            $this->_updateFormiePaymentStatus($payment, $molliePayment);

            // Trigger event hook if needed
            if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
                $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                    'webhookData' => $molliePayment,
                ]));
            }

            $response->data = 'ok';
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);

            $response->data = 'Error: ' . $e->getMessage();
        }

        return $response;
    }

    public function getTransaction(PaymentModel $payment): void
    {
        // This is called every 10s from a webhook status check, in case there's an issue receiving the webhook
        // from the provider (on local installs for instance). Manually check how the payment has gone and update.
        if (!$payment->reference) {
            throw new Exception('Missing Mollie payment reference.');
        }

        if (
            $payment->status === PaymentModel::STATUS_SUCCESS ||
            $payment->status === PaymentModel::STATUS_FAILED
        ) {
            return;
        }

        $molliePayment = $this->request('GET', "payments/{$payment->reference}");

        $this->_updateFormiePaymentStatus($payment, $molliePayment);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'payments');
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

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.mollie.com/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
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

    private function _updateFormiePaymentStatus(PaymentModel $payment, array $molliePayment): void
    {
        $payment->reference = $molliePayment['id'] ?? $payment->reference;
        $payment->response = $molliePayment;

        $status = $molliePayment['status'] ?? '';

        switch ($status) {
            case 'paid':
                $payment->status = PaymentModel::STATUS_SUCCESS;
                break;
            case 'failed':
            case 'expired':
            case 'canceled':
                $payment->status = PaymentModel::STATUS_FAILED;
                break;
            case 'pending':
            case 'open':
            default:
                $payment->status = PaymentModel::STATUS_PENDING;
                break;
        }

        Formie::$plugin->getPayments()->savePayment($payment);
    }
}

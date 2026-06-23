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
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
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
use GuzzleHttp\Exception\RequestException;

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

    public function requiresAjaxSubmission(): bool
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

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'mollie',
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
            $payment->redirectUrl = StringHelper::sanitizeRedirectUrl((string)Craft::$app->getRequest()->getReferrer());

            // Create the payment immediately so we can pass a reference to the Mollie payment
            Formie::$plugin->getPayments()->savePayment($payment);

            $payload = [
                'amount' => [
                    'currency' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'redirectUrl' => $this->getReturnUrl([
                    'statusToken' => PaymentAccess::issueStatusToken($payment),
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
                'event' => 'formie:payment:mollie:redirect',
                'data' => [
                    'checkoutUrl' => $checkoutUrl,
                ],
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterProcessPayment($submission, $result)) {
                return PaymentDecision::succeeded($this->handle);
            }

            return PaymentDecision::requiresAction(
                $payment->reference,
                PaymentAction::redirectEvent('formie:payment:mollie:redirect', $checkoutUrl)
                    ->forProvider($this->handle)
                    ->withMessage(Craft::t('formie', 'Please wait while you are redirected to complete payment.'))
                    ->withPayload(['checkoutUrl' => $checkoutUrl])
                    ->resumeMode(PaymentAction::RESUME_MODE_WEBHOOK, $this->getRedirectUri())
            );
        } catch (Throwable $e) {
            $gatewayErrorMessage = $this->_extractMollieErrorMessage($e, $response, $currency, $amount);

            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => $gatewayErrorMessage,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $this->addFieldError($submission, Craft::t('formie', $gatewayErrorMessage));

            // Update the payment if one has already been made
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->message = $gatewayErrorMessage;
            $payment->response = [
                'message' => $gatewayErrorMessage,
                'rawResponse' => $response,
            ];

            Formie::$plugin->getPayments()->savePayment($payment);

            return PaymentDecision::failed($gatewayErrorMessage, $this->handle, $payment->reference);
        }

        return PaymentDecision::succeeded($this->handle);
    }

    public function processWebhook(): Response
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $paymentId = $request->getParam('id');

        if (!$paymentId) {
            Integration::error($this, 'Mollie webhook triggered with no payment ID.');
            $response->data = 'error';

            return $response;
        }

        $payment = Formie::$plugin->getPayments()->getPaymentByReference($paymentId);

        if (!$payment || (int)$payment->integrationId !== (int)$this->id) {
            Integration::info($this, 'Mollie webhook ignored for unknown local payment reference.');
            $response->data = 'success';

            return $response;
        }

        try {
            // Fetch latest payment info from Mollie
            $molliePayment = $this->request('GET', "payments/{$paymentId}");

            $metadata = $molliePayment['metadata'] ?? [];
            $formiePaymentId = $metadata['formiePaymentId'] ?? null;

            if (!$formiePaymentId || (string)$formiePaymentId !== (string)$payment->id) {
                Integration::error($this, 'Mollie webhook metadata did not match the stored Formie payment.');
                $response->data = 'success';

                return $response;
            }

            $this->_updateFormiePaymentStatus($payment, $molliePayment);

            Integration::info($this, 'Webhook processed: Mollie payment ' . $paymentId . ', Formie payment id ' . $formiePaymentId . ', Mollie status "' . ($molliePayment['status'] ?? '') . '", Formie status "' . $payment->status . '".', false);

            // Trigger event hook if needed
            if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
                $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                    'webhookData' => $molliePayment,
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

    public function getTransactionStatus(PaymentModel $payment): void
    {
        try {
            $this->getTransaction($payment);
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Unable to refresh Mollie payment: “{message}”.', [
                'message' => $e->getMessage(),
            ]));
        }
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

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
        ];

        return $defaults;
    }
    

    // Private Methods
    // =========================================================================

    private function _setPayloadDetails(array &$payload, Submission $submission): void
    {
        $field = $this->getField();
        $paymentDescription = $this->getFieldSetting('paymentDescription') ?? "Formie Submission #{$submission->id}";
        $metadata = $this->getFieldSetting('metadata', []);

        if ($paymentDescription) {
            $payload['description'] = References::parseContent($paymentDescription, $submission);
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
                $payment->message = $this->_resolveMollieFailureMessage($molliePayment, $status);
                break;
            case 'pending':
            case 'open':
            default:
                $payment->status = PaymentModel::STATUS_PENDING;
                break;
        }

        Formie::$plugin->getPayments()->savePayment($payment);
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    private function _extractMollieErrorMessage(Throwable $e, mixed $response, mixed $currency, mixed $amount): string
    {
        $detail = '';

        if (is_array($response)) {
            $detail = trim((string)($response['detail'] ?? ''));
        }

        if ($detail === '' && $e instanceof RequestException && $e->getResponse()) {
            $body = (string)$e->getResponse()->getBody()->getContents();

            if (Json::isJsonObject($body)) {
                $decoded = Json::decode($body);
                $detail = trim((string)($decoded['detail'] ?? ''));
            }
        }

        if ($detail === '') {
            $detail = trim((string)$e->getMessage());
        }

        if ($detail === '') {
            $detail = 'Unknown Mollie error.';
        }

        if (str_contains(strtolower($detail), 'no suitable payment methods found')) {
            $currencyValue = strtoupper(trim((string)$currency));
            $amountValue = number_format((float)$amount, 2, '.', '');

            return Craft::t('formie', 'No suitable payment methods found in Mollie for {currency} {amount}. Check your Mollie profile payment methods and currency support.', [
                'currency' => $currencyValue ?: 'configured currency',
                'amount' => $amountValue,
            ]);
        }

        return $detail;
    }

    private function _resolveMollieFailureMessage(array $molliePayment, string $status): string
    {
        $details = $molliePayment['details'] ?? [];
        $failureMessage = is_array($details) ? trim((string)($details['failureMessage'] ?? '')) : '';

        if ($failureMessage !== '') {
            return $failureMessage;
        }

        return match ($status) {
            'canceled' => Craft::t('formie', 'Your payment was canceled. Please try again.'),
            'expired' => Craft::t('formie', 'Your payment expired. Please try again.'),
            default => Craft::t('formie', 'Your payment failed. Please try again.'),
        };
    }
}

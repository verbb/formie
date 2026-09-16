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
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\PaymentAttempt;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

use Craft;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use Exception;
use Throwable;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;

class Mollie extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Mollie';
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    public const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';


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
        return (bool)App::parseEnv($this->apiKey);
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
            'id' => 'mollie',
            'config' => [
                'requiredInputSuffixes' => [],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        $currency = $this->getFieldSetting('currency');

        return PaymentAttempt::run($this, $submission, $this->getAmount($submission), $currency, [
            'apiKey' => $this->apiKey,
        ], fn(PaymentModel $payment, PaymentAttempt $attempt) => $this->_processPayment($submission, $payment, $attempt));
    }

    public function processWebhook(): Response
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $paymentId = $request->getParam('id');

        if (!is_string($paymentId) || $paymentId === '') {
            Integration::error($this, 'Mollie webhook triggered with no payment ID.');
            $response->data = 'error';

            return $response;
        }

        $response->data = 'success';

        if (strlen($paymentId) > 255 || !preg_match('/^tr_[a-zA-Z0-9]+$/', $paymentId)) {
            return $response;
        }

        try {
            $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where([
                'reference' => $paymentId, 'integrationId' => $this->id,
            ])->one());

            if (!$row) {
                // Only the payment-specific URL supplied to Mollie can trigger
                // a lookup when the create response was lost locally.
                $localId = $request->getQueryParam('formiePaymentId');
                $token = $request->getQueryParam('recoveryToken');

                if (!is_string($localId) || !ctype_digit($localId) || !is_string($token) || strlen($token) !== 64) {
                    return $response;
                }

                $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where([
                    'id' => $localId, 'integrationId' => $this->id, 'reference' => null,
                    'status' => [PaymentModel::STATUS_PENDING, PaymentModel::STATUS_REDIRECT],
                ])->one());

                if (!$row || !hash_equals($this->_webhookRecoveryToken(new PaymentModel($row)), $token)) {
                    return $response;
                }
            }

            $payment = new PaymentModel($row);
            $formiePaymentId = $payment->id;
            $molliePayment = $this->request('GET', 'payments/' . rawurlencode($paymentId));

            if (($molliePayment['id'] ?? null) !== $paymentId) {
                throw new Exception('Mollie returned a different payment reference.');
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

            $response->setStatusCode(503);
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

    private function _processPayment(Submission $submission, PaymentModel $payment, PaymentAttempt $attempt): PaymentDecision
    {
        $result = false;
        $field = $this->getField();

        // Get the amount from the field, which handles dynamic fields
        $amount = $payment->amount;
        $currency = $this->getFieldSetting('currency');

        $payment->status = PaymentModel::STATUS_REDIRECT;
        $payment->redirectUrl = StringHelper::sanitizeRedirectUrl((string)Craft::$app->getRequest()->getReferrer());

        $attempt->save();

        $payload = [
            'amount' => [
                'currency' => $currency,
                'value' => $this->_formatAmount($amount, $currency),
            ],
            'redirectUrl' => $this->getReturnUrl([
                'statusToken' => PaymentAccess::issueStatusToken($payment),
            ]),
            'webhookUrl' => UrlHelper::urlWithParams($this->getRedirectUri(), [
                'formiePaymentId' => $payment->id,
                'recoveryToken' => $this->_webhookRecoveryToken($payment),
            ]),
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

        $event->payload['metadata']['formiePaymentId'] = $payment->id;
        $event->payload['metadata']['formiePaymentUid'] = $payment->uid;
        $event->payload['metadata']['submissionId'] = $payment->submissionId;
        $event->payload['metadata']['fieldId'] = $payment->fieldId;

        $response = $attempt->request($event->payload, function(string $key) use ($event): array {
            try {
                return $this->request('POST', 'payments', ['json' => $event->payload, 'headers' => ['Idempotency-Key' => $key]]);
            } catch (RequestException $e) {
                $status = $e->getResponse()?->getStatusCode();
                $error = Json::decodeIfJson((string)$e->getResponse()?->getBody());

                // Preserve an explicit API rejection as a receipt. Timeouts,
                // conflicts, rate limits and server failures remain unresolved.
                if (in_array($status, [400, 401, 403, 404, 405, 415, 422], true)
                    && is_array($error) && ($error['status'] ?? null) === $status
                    && is_string($error['detail'] ?? null) && empty($error['id'])) {
                    return ['formieRejected' => true, 'status' => $status, 'detail' => $error['detail']];
                }

                throw $e;
            }
        }, static fn(array $response) => $response['id'] ?? null);

        if (($response['formieRejected'] ?? false) === true) {
            $attempt->reject($this->_extractMollieErrorMessage(new Exception(), $response, $currency, $amount));
        }

        $paymentId = $response['id'] ?? null;
        $checkoutUrl = $response['_links']['checkout']['href'] ?? null;

        if (!$paymentId || !$checkoutUrl) {
            throw new Exception('Mollie did not return a checkout reference and URL.');
        }

        // Update the Formie payment with Mollie payment details
        $payment->reference = $paymentId;
        $payment->response = $response;

        $attempt->save();

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

    }

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

    private function _webhookRecoveryToken(PaymentModel $payment): string
    {
        return hash_hmac('sha256', 'mollie-webhook|' . $payment->integrationId . '|' . $payment->id . '|' . $payment->uid, Formie::$plugin->getSettings()->getSecurityKey());
    }

    private function _updateFormiePaymentStatus(PaymentModel $payment, array $molliePayment): void
    {
        $mutex = Craft::$app->getMutex();
        $lock = PaymentAttempt::lockName((int)$payment->submissionId, (int)$payment->integrationId, (int)$payment->fieldId);

        if (!$mutex->acquire($lock, 10)) {
            throw new Exception('Mollie payment is already being updated.');
        }

        try {
            $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id, 'integrationId' => $this->id])->one());

            if (!$row) {
                throw new Exception('Mollie payment no longer exists.');
            }

            $current = new PaymentModel($row);

            if (($current->reference && ($molliePayment['id'] ?? null) !== $current->reference)
                || empty($molliePayment['id'])
                || (string)($molliePayment['metadata']['formiePaymentId'] ?? '') !== (string)$current->id
                || ($molliePayment['amount']['currency'] ?? null) !== $current->currency
                || ($molliePayment['amount']['value'] ?? null) !== $this->_formatAmount($current->amount, (string)$current->currency)) {
                throw new Exception('Mollie payment ownership or amount could not be verified.');
            }

            if (!$current->reference) {
                if (($molliePayment['metadata']['formiePaymentUid'] ?? null) !== $current->uid
                    || !in_array($current->status, [PaymentModel::STATUS_PENDING, PaymentModel::STATUS_REDIRECT], true)
                    || !(new DeliveryAttempt((int)$current->submissionId, 'payment-purchase', (string)$current->uid))->getMetadata()) {
                    throw new Exception('This Mollie payment is not awaiting a creation result.');
                }

                PaymentAttempt::verifyAccount($this, $current, ['apiKey' => $this->apiKey]);
                $current->reference = $molliePayment['id'];
            }

            if ($current->status !== PaymentModel::STATUS_SUCCESS) {
                $status = $molliePayment['status'] ?? '';
                $current->response = $molliePayment;
                $current->status = match ($status) {
                    'paid' => PaymentModel::STATUS_SUCCESS,
                    'failed', 'expired', 'canceled' => PaymentModel::STATUS_FAILED,
                    default => PaymentModel::STATUS_PENDING,
                };
                $current->message = $current->status === PaymentModel::STATUS_FAILED ? $this->_resolveMollieFailureMessage($molliePayment, $status) : null;

                if (!Formie::$plugin->getPayments()->savePayment($current)) {
                    throw new Exception('Unable to save the verified Mollie payment.');
                }
            }

            $payment->reference = $current->reference;
            $payment->status = $current->status;
            $payment->message = $current->message;
            $payment->response = $current->response;
        } finally {
            $mutex->release($lock);
        }

        $result = Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);

        if ($result && !$result->response?->success) {
            throw new Exception('The payment is verified, but submission processing needs to be retried.');
        }
    }

    private function _formatAmount(float $amount, string $currency): string
    {
        return number_format($amount, (new ISOCurrencies())->subunitFor(new Currency($currency)), '.', '');
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

<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Number;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\PaymentAttempt;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentAction;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentMoney;
use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\models\payments\PaymentWebhookReceipt;
use verbb\formie\models\payments\VerifiedWebhook;
use verbb\formie\models\payments\VerifiedWebhookBatch;

use Craft;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

use Exception;
use Throwable;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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

    #[Sensitive]
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
        $endpoint = 'formie/payment-return/index';

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            $url = UrlHelper::actionUrl($endpoint, $params);
        } else {
            $url = UrlHelper::siteUrl($endpoint, $params);
        }

        return Payment::applyPaymentWebhookProxy($url);
    }

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new BrowserModule([
            'moduleId' => 'formie:mollie',
            'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED],
            'config' => [
                'requiredInputSuffixes' => [],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function verifyWebhook(PaymentWebhookCommand $request): VerifiedWebhookBatch
    {
        $paymentId = $request->param('id');

        if (!is_string($paymentId) || $paymentId === '') {
            throw new BadRequestHttpException('Mollie webhook triggered with no payment ID.');
        }

        if (strlen($paymentId) > 255 || !preg_match('/^tr_[a-zA-Z0-9]+$/', $paymentId)) {
            throw new ForbiddenHttpException('Invalid Mollie payment reference.');
        }

        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where([
            'reference' => $paymentId, 'integrationId' => $this->id,
        ])->one());

        if (!$row) {
            // Only the payment-specific URL supplied to Mollie can trigger a
            // lookup when the create response was lost locally.
            $localId = $request->queryParams['formiePaymentId'] ?? null;
            $token = $request->queryParams['recoveryToken'] ?? null;

            if (!is_string($localId) || !ctype_digit($localId) || !is_string($token) || strlen($token) !== 64) {
                throw new ForbiddenHttpException('Invalid Mollie recovery capability.');
            }

            $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where([
                'id' => $localId, 'integrationId' => $this->id, 'reference' => null,
                'status' => [PaymentModel::STATUS_UNKNOWN, PaymentModel::STATUS_PENDING, PaymentModel::STATUS_REQUIRES_ACTION],
            ])->one());

            if (!$row || !hash_equals($this->_webhookRecoveryToken(new PaymentModel($row)), $token)) {
                throw new ForbiddenHttpException('Invalid Mollie recovery capability.');
            }
        }

        $secret = (string)($request->queryParams['recoveryToken'] ?? '');

        if (!hash_equals($this->_webhookRecoveryToken(new PaymentModel($row)), $secret)) {
            throw new ForbiddenHttpException('Invalid webhook secret.');
        }
        $payment = new PaymentModel($row);
        $molliePayment = $this->request('GET', 'payments/' . rawurlencode($paymentId));

        if (($molliePayment['id'] ?? null) !== $paymentId) {
            throw new Exception('Mollie returned a different payment reference.');
        }

        $molliePayment['_formiePaymentId'] = $payment->id;
        $apiKey = (string)App::parseEnv($this->apiKey);
        $environment = str_starts_with($apiKey, 'test_') ? 'test' : 'live';
        $accountIdentity = hash('sha256', $apiKey);
        $fingerprint = Json::encode([
            'id' => $molliePayment['id'] ?? null,
            'status' => $molliePayment['status'] ?? null,
            'amount' => $molliePayment['amount'] ?? null,
            'currency' => $molliePayment['amount']['currency'] ?? null,
            'formiePaymentId' => $payment->id,
        ]);

        return new VerifiedWebhookBatch(
            $this->getWebhookAccountFingerprint($environment, $accountIdentity),
            $environment,
            [new VerifiedWebhook(
                $paymentId . ':' . ($molliePayment['status'] ?? 'unknown'),
                'payment.' . ($molliePayment['status'] ?? 'unknown'),
                'payment',
                $paymentId,
                null,
                $molliePayment,
                $fingerprint,
            )],
            ['Content-Type' => $request->header('Content-Type')],
        );
    }

    public function handleWebhook(PaymentWebhookReceipt $receipt): void
    {
        $molliePayment = $receipt->payload;
        $payment = Formie::$plugin->getPayments()->getPaymentById((int)($molliePayment['_formiePaymentId'] ?? 0));

        if (!$payment || (int)$payment->integrationId !== (int)$this->id || ($molliePayment['id'] ?? null) !== $receipt->resourceReference) {
            throw new Exception('Mollie webhook payment ownership mismatch.');
        }

        unset($molliePayment['_formiePaymentId']);
        $this->_updateFormiePaymentStatus($payment, $molliePayment);
        Integration::info($this, 'Webhook processed: Mollie payment ' . $receipt->resourceReference . ', Formie payment id ' . $payment->id . ', Mollie status "' . ($molliePayment['status'] ?? '') . '", Formie status "' . $payment->status . '".', false);

        if ($this->hasEventHandlers(self::EVENT_RECEIVE_WEBHOOK)) {
            $this->trigger(self::EVENT_RECEIVE_WEBHOOK, new PaymentReceiveWebhookEvent([
                'webhookData' => $molliePayment,
            ]));
        }
    }

    public function getWebhookAcknowledgement(): Response
    {
        $response = parent::getWebhookAcknowledgement();
        $response->data = 'success';

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
            $payment->status === PaymentModel::STATUS_SUCCEEDED ||
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

    protected function executePayment(Submission $submission): PaymentDecision
    {

        $currency = $this->getFieldSetting('currency');

        return PaymentAttempt::run($this, $submission, $this->getAmount($submission), $currency, [
            'apiKey' => $this->apiKey,
        ], fn(PaymentModel $payment, PaymentAttempt $attempt) => $this->_processPayment($submission, $payment, $attempt));
    }

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

        $payment->status = PaymentModel::STATUS_REQUIRES_ACTION;
        $payment->redirectUrl = StringHelper::sanitizeRedirectUrl((string)Craft::$app->getRequest()->getReferrer());

        $attempt->save();

        $payload = [
            'amount' => [
                'currency' => $currency,
                'value' => $this->_formatAmount($amount, $currency),
            ],
            'redirectUrl' => $this->getReturnUrl([
                'returnToken' => PaymentAccess::issueReturnToken($payment),
            ]),
            'webhookUrl' => UrlHelper::urlWithParams($this->getWebhookUrl(), [
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


        return PaymentDecision::requiresAction(
            $payment->reference,
            PaymentAction::redirect(
                provider: $this->handle,
                event: 'formie:payment:mollie:redirect',
                message: Craft::t('formie', 'Please wait while you are redirected to complete payment.'),
                url: $checkoutUrl,
                payload: ['checkoutUrl' => $checkoutUrl],
                resumeMode: PaymentResumeMode::RETURN,
                resumeUrl: $event->payload['redirectUrl'],
            )
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
                    || !in_array($current->status, [PaymentModel::STATUS_UNKNOWN, PaymentModel::STATUS_PENDING, PaymentModel::STATUS_REQUIRES_ACTION], true)
                    || !(new DeliveryAttempt((int)$current->submissionId, 'payment-purchase', (string)$current->uid))->getMetadata()) {
                    throw new Exception('This Mollie payment is not awaiting a creation result.');
                }

                PaymentAttempt::verifyAccount($this, $current, ['apiKey' => $this->apiKey]);
                $current->reference = $molliePayment['id'];
            }

            if ($current->status !== PaymentModel::STATUS_SUCCEEDED) {
                $status = $molliePayment['status'] ?? '';
                $current->response = $molliePayment;
                $current->status = match ($status) {
                    'paid' => PaymentModel::STATUS_SUCCEEDED,
                    'failed', 'expired' => PaymentModel::STATUS_FAILED,
                    'canceled' => PaymentModel::STATUS_CANCELLED,
                    'open', 'pending', 'authorized' => PaymentModel::STATUS_PENDING,
                    default => PaymentModel::STATUS_UNKNOWN,
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

        $result = Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($payment);

        if ($result && !$result->response?->success) {
            throw new Exception('The payment is verified, but submission processing needs to be retried.');
        }
    }

    private function _formatAmount(string|int|float $amount, string $currency): string
    {
        return PaymentMoney::fromDecimal((string)$amount, $currency)->decimal();
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
            $amountValue = (string)$amount;

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

<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\errors\DeliveryOutcomeUnknownException;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Plan;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use Exception;
use Throwable;

use GuzzleHttp\Client;

class PayWay extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Westpac PayWay';
    }

    public static function getCurrencyOptions(): array
    {
        $event = new ModifyPaymentCurrencyOptionsEvent([
            'currencies' => [
                ['label' => 'AUD', 'value' => 'AUD'],
            ],
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_CURRENCY_OPTIONS, $event);

        return $event->currencies;
    }
    


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Properties
    // =========================================================================

    public ?string $publishableKey = null;
    public ?string $secretKey = null;
    public ?string $merchantId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->publishableKey) && App::parseEnv($this->secretKey) && App::parseEnv($this->merchantId);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'payway',
            'config' => [
                'publishableKey' => App::parseEnv($this->publishableKey),
                'currency' => $this->getFieldSetting('currency'),
                'amountType' => $this->getFieldSetting('amountType'),
                'amountFixed' => $this->getFieldSetting('amountFixed'),
                'amountVariable' => $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable')),
                'requiredInputSuffixes' => ['paywayTokenId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.payway.' . hash('sha256', $submission->id . ':' . $this->getField()?->id);
        if (!$mutex->acquire($lock, 10)) {
            return PaymentDecision::pending('PayWay payment is already being processed.', $this->handle);
        }
        try {
            return $this->_processPayment($submission);
        } finally {
            $mutex->release($lock);
        }
    }

    public function getTransaction(PaymentModel $payment): void
    {
        if (!$payment->reference || in_array($payment->status, [PaymentModel::STATUS_SUCCESS, PaymentModel::STATUS_FAILED], true)) {
            return;
        }
        $submission = $payment->getSubmission();
        if (!$submission || $payment->integrationId !== $this->id) {
            throw new Exception('Invalid PayWay payment context.');
        }
        $response = $this->request('GET', 'transactions/' . rawurlencode($payment->reference));
        $this->_validateTransaction($response, $submission, (float)$payment->amount, (string)$payment->currency);
        if ((string)$response['transactionId'] !== $payment->reference) {
            throw new DeliveryOutcomeUnknownException('PayWay returned a different transaction.');
        }
        $payment->status = match (strtolower((string)($response['status'] ?? ''))) {
            'approved', 'approved*' => PaymentModel::STATUS_SUCCESS,
            'declined', 'voided' => PaymentModel::STATUS_FAILED,
            default => PaymentModel::STATUS_PENDING,
        };
        $payment->response = $response;
        if (!Formie::$plugin->getPayments()->savePayment($payment)) {
            throw new DeliveryOutcomeUnknownException('Unable to save the PayWay payment outcome.');
        }
        Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    }

    public function getTransactionStatus(PaymentModel $payment): void
    {
        $this->getTransaction($payment);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', '/');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.payway.com.au/rest/v1/',
            'auth' => [App::parseEnv($this->secretKey), ''],
        ]);
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
    


    // Protected Methods
    // =========================================================================

    protected function getIntegrationHandle(): string
    {
        return 'payway';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publishableKey', 'secretKey', 'merchantId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.payway.com.au/rest/v1/',
            'auth' => [App::parseEnv($this->secretKey), ''],
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

    private function _processPayment(Submission $submission): PaymentDecision
    {
        $response = null;
        $result = false;
        $status = null;
        $field = $this->getField();
        $payment = null;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Capture the authorized payment
        try {
            if (!$submission->id || !$submission->uid || !$field?->id || !$this->id) {
                throw new Exception('Save the submission and configure a payment field before payment.');
            }
            $paymentPayload = $this->getPaymentFieldPayload($submission);
            $paywayTokenId = $paymentPayload->string('paywayTokenId');

            if (!$paywayTokenId || !is_string($paywayTokenId)) {
                throw new Exception("Missing `paywayTokenId` from payload: {$paywayTokenId}.");
            }

            if ($amount <= 0) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            $requestCurrency = strtolower(trim((string)$currency));

            if ($requestCurrency !== 'aud') {
                throw new Exception('PayWay supports AUD currency only.');
            }

            $payload = [
                'singleUseTokenId' => $paywayTokenId,
                'principalAmount' => $amount,
                'currency' => $requestCurrency,
                'transactionType' => 'payment',
                'customerNumber' => $submission->id,
                'orderNumber' => $submission->id,
                'merchantId' => App::parseEnv($this->merchantId),
                'customerIpAddress' => Craft::$app->getRequest()->getIsConsoleRequest() ? null : Craft::$app->getRequest()->getUserIP(),
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $this->_validateTransaction(['transactionId' => 'request'] + $event->payload, $submission, (float)$amount, (string)$currency);

            // PayWay retains idempotency keys for 24 hours; leave a safety margin.
            $response = (new DeliveryAttempt((int)$submission->id, 'payway:' . $this->id . ':' . $field->id, (string)$submission->uid))->execute(
                ['payload' => $event->payload, 'account' => hash('sha256', (string)App::parseEnv($this->secretKey))],
                fn(string $key) => $this->request('POST', 'transactions', [
                    'form_params' => $event->payload,
                    'headers' => ['Idempotency-Key' => $key],
                ]),
                23 * 3600,
                fn(array $data) => (string)($data['transactionId'] ?? ''),
                fn(string $reference) => $this->request('GET', 'transactions/' . rawurlencode($reference)),
            );
            $this->_validateTransaction($response, $submission, (float)$amount, (string)$currency);
            $payment = Formie::$plugin->getPayments()->getPaymentByReference((string)$response['transactionId']);
            if ($payment && ($payment->submissionId !== $submission->id || $payment->fieldId !== $field->id || $payment->integrationId !== $this->id)) {
                throw new DeliveryOutcomeUnknownException('PayWay transaction belongs to another payment.');
            }

            $status = strtolower((string)($response['status'] ?? ''));

            if (!in_array($status, ['approved', 'approved*', 'declined', 'voided'], true)) {
                $status = 'pending';
            }

            $payment ??= new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->reference = $response['transactionId'] ?? '';
            $payment->response = $response;

            $payment->status = PaymentModel::STATUS_FAILED;
            if ($status === 'pending') {
                $payment->status = PaymentModel::STATUS_PENDING;
            }

            if ($status === 'approved' || $status === 'approved*') {
                $payment->status = PaymentModel::STATUS_SUCCESS;
            }

            if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                throw new DeliveryOutcomeUnknownException('Unable to save the PayWay payment outcome.');
            }

            $result = $status === 'approved' || $status === 'approved*';
        } catch (DeliveryOutcomeUnknownException $e) {
            return PaymentDecision::pending($e->getMessage(), $this->handle);
        } catch (Throwable $e) {
            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, Craft::t('formie', 'A payment error has occurred “{message}”.', ['message' => $message]));
            return PaymentDecision::failed($message, $this->handle);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return PaymentDecision::failed(null, $this->handle);
        }

        if ($status === 'pending') {
            return PaymentDecision::pending(null, $this->handle);
        }

        if (!$result) {
            $message = Craft::t('formie', 'The payment was not approved.');
            $this->addFieldError($submission, $message);
            return PaymentDecision::failed($message, $this->handle);
        }

        return PaymentDecision::succeeded($this->handle);
    }

    private function _validateTransaction(array $response, Submission $submission, float $amount, string $currency): void
    {
        if (empty($response['transactionId'])
            || strtolower((string)($response['currency'] ?? '')) !== strtolower($currency)
            || !isset($response['principalAmount']) || !is_numeric($response['principalAmount'])
            || number_format((float)$response['principalAmount'], 2, '.', '') !== number_format($amount, 2, '.', '')
            || (string)($response['orderNumber'] ?? '') !== (string)$submission->id
            || (string)($response['customerNumber'] ?? '') !== (string)$submission->id
            || ($response['transactionType'] ?? '') !== 'payment') {
            throw new DeliveryOutcomeUnknownException('Unable to verify the PayWay amount, currency or submission association.');
        }
    }

}

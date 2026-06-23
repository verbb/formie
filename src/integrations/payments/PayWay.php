<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\events\PaymentReceiveWebhookEvent;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
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

use GuzzleHttp\Client;

use Throwable;
use Exception;

class PayWay extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


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
        $response = null;
        $result = false;
        $status = null;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $paymentPayload = $this->getPaymentFieldPayload($submission);
            $paywayTokenId = $paymentPayload->string('paywayTokenId');

            if (!$paywayTokenId || !is_string($paywayTokenId)) {
                throw new Exception("Missing `paywayTokenId` from payload: {$paywayTokenId}.");
            }

            if (!$amount) {
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
                'customerIpAddress' => Craft::$app->getRequest()->getUserIP(),
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->request('POST', 'transactions', ['form_params' => $event->payload]);

            $status = strtolower((string)($response['status'] ?? ''));
            $responseText = $response['responseText'] ?? null;

            if ($status !== 'approved' && $status !== 'approved*' && $status !== 'pending') {
                throw new Exception(StringHelper::titleize($status) . ': ' . $responseText);
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->reference = $response['transactionId'] ?? '';
            $payment->response = $response;

            if ($status === 'pending') {
                $payment->status = PaymentModel::STATUS_PENDING;
            }

            if ($status === 'approved' || $status === 'approved*') {
                $payment->status = PaymentModel::STATUS_SUCCESS;
            }

            Formie::$plugin->getPayments()->savePayment($payment);

            $result = $status === 'approved' || $status === 'approved*';
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            // Provide a client-friendly error, rather than expose the full error
            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, Craft::t('formie', 'A payment error has occurred “{message}”.', ['message' => $message]));
            
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_FAILED;
            $payment->reference = null;
            $payment->response = ['message' => $e->getMessage()];

            Formie::$plugin->getPayments()->savePayment($payment);

            return PaymentDecision::failed($e->getMessage(), $this->handle);
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return PaymentDecision::succeeded($this->handle);
        }

        if ($status === 'pending') {
            return PaymentDecision::pending(null, $this->handle);
        }

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
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
}

<?php
namespace verbb\formie\integrations\payments;

use verbb\formie\Formie;
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
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Response;

use yii\base\Event;

use GuzzleHttp\Client;
use SimpleXMLElement;

use Throwable;
use Exception;

class Moneris extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Moneris');
    }
    

    // Properties
    // =========================================================================

    public ?string $storeId = null;
    public ?string $apiToken = null;
    public ?string $profileId = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->storeId) && App::parseEnv($this->apiToken);
    }

    public function getFieldHtmlVariables(): array
    {
        return [
            'endpointUrl' => $this->getBaseUrl() . 'HPPtoken/index.php',
            'profileId' => App::parseEnv($this->profileId),
        ];
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'moneris',
            'config' => [
                'endpointUrl' => $this->getBaseUrl() . 'HPPtoken/index.php',
                'requiredInputSuffixes' => ['monerisTokenId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $response = null;
        $result = false;

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
            $monerisTokenId = $paymentPayload->string('monerisTokenId') ?? '';

            if (!$monerisTokenId) {
                throw new Exception("Missing `monerisTokenId` from payload: {$monerisTokenId}.");
            }

            if (!$amount) {
                throw new Exception("Missing `amount` from payload: {$amount}.");
            }

            if (!$currency) {
                throw new Exception("Missing `currency` from payload: {$currency}.");
            }

            $orderId = 'submission-' . $submission->id . '-' . date("dmy-G:i:s");
            $formattedAmount = number_format($amount, 2, '.', '');
            $storeId = App::parseEnv($this->storeId);
            $apiToken = App::parseEnv($this->apiToken);
            $payload = [
                'xml' => <<<XML
                    <?xml version="1.0" encoding="UTF-8"?>
                    <request>
                        <store_id>{$storeId}</store_id>
                        <api_token>{$apiToken}</api_token>
                        <res_purchase_cc>
                            <order_id>{$orderId}</order_id>
                            <amount>{$formattedAmount}</amount>
                            <data_key>{$monerisTokenId}</data_key>
                            <crypt_type>7</crypt_type>
                        </res_purchase_cc>
                    </request>
                XML,
            ];

            // Raise a `modifySinglePayload` event
            $event = new ModifyPaymentPayloadEvent([
                'integration' => $this,
                'submission' => $submission,
                'payload' => $payload,
            ]);
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);

            $response = $this->getClient()->request('POST', 'gateway2/servlet/MpgRequest', [
                'body' => $event->payload['xml'],
            ]);

            $xml = new SimpleXMLElement((string)$response->getBody());
            $receipt = $xml->receipt ?? null;

            if (!$receipt) {
                throw new Exception('Missing receipt in Moneris response.');
            }

            // Handle Moneris-specific casing
            $responseCode = isset($receipt->ResponseCode) && is_numeric((string)$receipt->ResponseCode)
                ? (int)$receipt->ResponseCode
                : 999;

            $isComplete = strtolower((string)($receipt->Complete ?? 'false')) === 'true';
            $message = (string)($receipt->Message ?? 'Unknown');
            $transactionId = (string)($receipt->TransID ?? null);

            if ($responseCode >= 50 || !$isComplete) {
                throw new Exception("Transaction declined: {$message}");
            }

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->reference = $transactionId;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->response = Json::decode(Json::encode($receipt)); // Convert XML to array

            Formie::$plugin->getPayments()->savePayment($payment);

            $result = true;
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'Payment error: “{message}” {file}:{line}. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $this->addFieldError($submission, $e->getMessage());
            
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

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
    }

    public function fetchConnection(): bool
    {
        try {
        $storeId = App::parseEnv($this->storeId);
        $apiToken = App::parseEnv($this->apiToken);

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <request>
                <store_id>{$storeId}</store_id>
                <api_token>{$apiToken}</api_token>
                <res_get_expiring_data>
                    <month>12</month>
                    <year>2060</year>
                </res_get_expiring_data>
            </request>
        XML;

        $this->getClient()->request('POST', 'gateway2/servlet/MpgRequest', [
            'body' => $xml,
        ]);

        return true;
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
    

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['storeId', 'apiToken', 'profileId'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => $this->getBaseUrl(),
            'headers' => ['Content-Type' => 'text/xml'],
        ]);
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
        ];

        return $defaults;
    }

    protected function getBaseUrl(): string
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);

        return $useSandbox ? 'https://esqa.moneris.com/' : 'https://www3.moneris.com/';
    }
}

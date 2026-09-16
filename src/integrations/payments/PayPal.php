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

class PayPal extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'PayPal';
    }
    


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public bool|string $useSandbox = false;

    private ?string $_accessToken = null;
    private int $_accessTokenExpires = 0;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->clientId) && App::parseEnv($this->clientSecret);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);

        return new ClientModule([
            'id' => 'paypal',
            'config' => [
                'clientId' => App::parseEnv($this->clientId),
                'useSandbox' => App::parseBooleanEnv($this->useSandbox),
                'currency' => $this->getFieldSetting('currency'),
                'amountType' => $this->getFieldSetting('amountType'),
                'amountFixed' => $this->getFieldSetting('amountFixed'),
                'amountVariable' => $this->normalizeClientFieldReference($this->getFieldSetting('amountVariable')),
                'buttonLayout' => $this->getFieldSetting('buttonLayout', 'horizontal'),
                'buttonColor' => $this->getFieldSetting('buttonColor', 'gold'),
                'buttonShape' => $this->getFieldSetting('buttonShape', 'rect'),
                'buttonLabel' => $this->getFieldSetting('buttonLabel', 'paypal'),
                'buttonTagline' => $this->getFieldSetting('buttonTagline', 'false'),
                'buttonWidth' => $this->getFieldSetting('buttonWidth'),
                'buttonHeight' => $this->getFieldSetting('buttonHeight'),
                'requiredInputSuffixes' => ['paypalOrderId'],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie.paypal.' . hash('sha256', $submission->id . ':' . $this->getField()?->id);
        if (!$mutex->acquire($lock, 10)) {
            return PaymentDecision::pending('PayPal payment is already being processed.', $this->handle);
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
            throw new Exception('Invalid PayPal payment context.');
        }
        $capture = $this->_requestApi('GET', 'v2/payments/captures/' . rawurlencode($payment->reference));
        $this->_applyCapture($payment, $capture, $submission);
        if (!Formie::$plugin->getPayments()->savePayment($payment)) {
            throw new DeliveryOutcomeUnknownException('Unable to save the PayPal payment status.');
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
            $response = $this->request('POST', 'v1/oauth2/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);
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
            SchemaHelper::staticTableField([
                'label' => Craft::t('formie', 'Billing Details'),
                'instructions' => Craft::t('formie', 'Whether to send billing details alongside the payment.'),
                'name' => 'billingDetails',
                'columns' => [
                    'heading' => [
                        'type' => 'heading',
                        'heading' => Craft::t('formie', 'Billing Info'),
                    ],
                    'value' => [
                        'type' => 'fieldSelect',
                        'label' => Craft::t('formie', 'Field'),
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
            ]),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Label'),
                'instructions' => Craft::t('formie', 'Choose a label for the PayPal button.'),
                'name' => 'buttonLabel',
                'options' => [
                    ['label' => Craft::t('formie', 'PayPal'), 'value' => 'paypal'],
                    ['label' => Craft::t('formie', 'PayPal Checkout'), 'value' => 'checkout'],
                    ['label' => Craft::t('formie', 'Pay with PayPal'), 'value' => 'pay'],
                    ['label' => Craft::t('formie', 'PayPal Buy Now'), 'value' => 'buynow'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Color'),
                'instructions' => Craft::t('formie', 'Choose a color for the PayPal button.'),
                'name' => 'buttonColor',
                'options' => [
                    ['label' => Craft::t('formie', 'Gold'), 'value' => 'gold'],
                    ['label' => Craft::t('formie', 'Blue'), 'value' => 'blue'],
                    ['label' => Craft::t('formie', 'Silver'), 'value' => 'silver'],
                    ['label' => Craft::t('formie', 'White'), 'value' => 'white'],
                    ['label' => Craft::t('formie', 'Black'), 'value' => 'black'],
                ],
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Button Width'),
                'instructions' => Craft::t('formie', 'Set a width PayPal button in pixels, between 150px and 750px.'),
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'buttonWidth',
                        'min' => '150',
                        'max' => '750',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Button Height'),
                'instructions' => Craft::t('formie', 'Set a height PayPal button in pixels, between 25px to 55px.'),
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'buttonHeight',
                        'min' => '25',
                        'max' => '55',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Shape'),
                'instructions' => Craft::t('formie', 'Choose the shape of the PayPal button.'),
                'name' => 'buttonShape',
                'options' => [
                    ['label' => Craft::t('formie', 'Rectangular'), 'value' => 'rect'],
                    ['label' => Craft::t('formie', 'Pill'), 'value' => 'pill'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Layout'),
                'instructions' => Craft::t('formie', 'Choose the layout of the PayPal button.'),
                'name' => 'buttonLayout',
                'options' => [
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Button Tagline'),
                'instructions' => Craft::t('formie', 'Whether to show a tagline underneath buttons.'),
                'name' => 'buttonTagline',
            ]),
        ];
    }
    


    // Protected Methods
    // =========================================================================

    protected function getIntegrationHandle(): string
    {
        return 'paypal';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $options = [];

        // Disable SSL verification for local dev (devMode enabled) to save some heartache.
        if (App::devMode()) {
            $options['verify'] = false;
        }

        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $clientId = App::parseEnv($this->clientId);
        $clientSecret = App::parseEnv($this->clientSecret);
        $token = base64_encode($clientId . ':' . $clientSecret);
        $url = $useSandbox ? 'https://api.sandbox.paypal.com/' : 'https://api.paypal.com/';

        return Craft::createGuzzleClient(array_merge([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Basic ' . $token,
                // 'Content-Type'  => 'application/x-www-form-urlencoded',
                'Content-Type' => 'application/json',
            ],
        ], $options));
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        $defaults = [
            'amountType' => self::VALUE_TYPE_FIXED,
            'buttonLabel' => 'paypal',
            'buttonColor' => 'gold',
            'buttonLayout' => 'horizontal',
            'buttonShape' => 'rect',
            'buttonTagline' => 'false',
        ];

        return $defaults;
    }

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return ['paypalAuthId'];
    }


    // Private Methods
    // =========================================================================

    private function _requestApi(string $method, string $uri, array $options = []): array
    {
        if (!$this->_accessToken || $this->_accessTokenExpires <= time()) {
            $token = $this->request('POST', 'v1/oauth2/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
            $this->_accessToken = $token['access_token'] ?? null;
            $this->_accessTokenExpires = time() + max(0, (int)($token['expires_in'] ?? 0) - 30);
            if (!$this->_accessToken) {
                throw new Exception('Unable to authenticate with PayPal.');
            }
        }
        $options['headers']['Authorization'] = 'Bearer ' . $this->_accessToken;
        return $this->request($method, $uri, $options);
    }

    private function _accountIdentity(): string
    {
        return hash('sha256', (string)App::parseEnv($this->clientId) . ':' . (int)App::parseBooleanEnv($this->useSandbox));
    }

    private function _invoiceId(Submission $submission, int $fieldId): string
    {
        return 'formie-' . $submission->uid . '-' . $fieldId;
    }

    private function _paymentAmount(float $amount, string $currency): array
    {
        $digits = (new \Money\Currencies\ISOCurrencies())->subunitFor(new \Money\Currency($currency));
        return ['value' => number_format($amount, $digits, '.', ''), 'currency_code' => $currency];
    }

    private function _verifyAmount(array $actual, array $expected): void
    {
        $value = (string)($actual['value'] ?? '');
        if (($actual['currency_code'] ?? '') !== $expected['currency_code'] || !preg_match('/^\d+(?:\.\d+)?$/D', $value)) {
            throw new Exception('PayPal currency or amount does not match the submission.');
        }
        $parser = new \Money\Parser\DecimalMoneyParser(new \Money\Currencies\ISOCurrencies());
        $currency = new \Money\Currency($expected['currency_code']);
        if (!$parser->parse($value, $currency)->equals($parser->parse($expected['value'], $currency))) {
            throw new Exception('PayPal amount does not match the submission.');
        }
    }

    private function _paymentRecord(Submission $submission, int $fieldId, float $amount, string $currency): PaymentModel
    {
        foreach (Formie::$plugin->getPayments()->getSubmissionPayments($submission) as $payment) {
            if ($payment->integrationId === $this->id && $payment->fieldId === $fieldId) {
                return $payment;
            }
        }
        return new PaymentModel(['integrationId' => $this->id, 'submissionId' => $submission->id,
            'fieldId' => $fieldId, 'amount' => $amount, 'currency' => $currency]);
    }

    private function _applyCapture(PaymentModel $payment, array $capture, Submission $submission): void
    {
        try {
            $this->_verifyAmount($capture['amount'] ?? [], $this->_paymentAmount($payment->amount, $payment->currency));
            if (empty($capture['id']) || ($capture['invoice_id'] ?? '') !== $this->_invoiceId($submission, (int)$payment->fieldId)
                || ($payment->reference && $payment->reference !== $capture['id'])) {
                throw new Exception('PayPal capture does not match the submission.');
            }
        } catch (Throwable $e) {
            throw new DeliveryOutcomeUnknownException('Unable to verify the PayPal capture. Check the payment before retrying.', 0, $e);
        }
        $payment->reference = $capture['id'];
        $payment->response = $capture;
        $payment->status = match ($capture['status'] ?? '') {
            'COMPLETED' => PaymentModel::STATUS_SUCCESS,
            'DECLINED', 'FAILED', 'DENIED', 'REFUNDED', 'PARTIALLY_REFUNDED' => PaymentModel::STATUS_FAILED,
            default => PaymentModel::STATUS_PROCESSING,
        };
    }

    private function _extractAuthorizationId(array $authorizationResponse): ?string
    {
        $purchaseUnits = $authorizationResponse['purchase_units'] ?? [];

        if (!is_array($purchaseUnits) || !$purchaseUnits) {
            return null;
        }

        $payments = $purchaseUnits[0]['payments'] ?? [];
        if (!is_array($payments)) {
            return null;
        }

        $authorizations = $payments['authorizations'] ?? [];
        if (!is_array($authorizations) || !$authorizations) {
            return null;
        }

        $authId = trim((string)($authorizations[0]['id'] ?? ''));

        return $authId !== '' ? $authId : null;
    }

    private function _processPayment(Submission $submission): PaymentDecision
    {
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        $field = $this->getField();
        $amount = $this->getAmount($submission);
        $currency = strtoupper((string)$this->getFieldSetting('currency'));
        $payment = null;

        try {
            if (!$submission->id || !$submission->uid || !$field?->id || !$this->id || $amount <= 0) {
                throw new Exception('Save the submission and configure a valid payment amount before payment.');
            }
            $expected = $this->_paymentAmount($amount, $currency);
            $payload = $this->getPaymentFieldPayload($submission);
            $authId = trim($payload->string('paypalAuthId') ?? '');
            $orderId = trim($payload->string('paypalOrderId') ?? '');
            $account = $this->_accountIdentity();

            if ($orderId !== '') {
                $order = $this->_requestApi('GET', 'v2/checkout/orders/' . rawurlencode($orderId));
                $units = $order['purchase_units'] ?? [];
                if (count($units) !== 1) {
                    throw new Exception('Expected one PayPal purchase unit.');
                }
                $this->_verifyAmount($units[0]['amount'] ?? [], $expected);
                $orderAuthId = $this->_extractAuthorizationId($order);
                if (!$orderAuthId && $authId === '') {
                    $order = (new DeliveryAttempt((int)$submission->id, 'paypal.authorize:' . $field->id, (string)$submission->uid))->execute(
                        ['account' => $account, 'orderId' => $orderId, 'amount' => $expected],
                        fn(string $key) => $this->_requestApi('POST', 'v2/checkout/orders/' . rawurlencode($orderId) . '/authorize', [
                            'headers' => ['PayPal-Request-Id' => $key, 'Prefer' => 'return=representation'],
                        ]),
                        5 * 3600,
                        fn(array $result) => $result['id'] ?? '',
                        fn(string $id) => $this->_requestApi('GET', 'v2/checkout/orders/' . rawurlencode($id)),
                    );
                    $orderAuthId = $this->_extractAuthorizationId($order);
                }
                if (!$orderAuthId || ($authId !== '' && $authId !== $orderAuthId)) {
                    throw new Exception('PayPal authorization does not match the approved order.');
                }
                $authId = $orderAuthId;
            }
            if ($authId === '') {
                throw new Exception('Missing PayPal authorization data for payment.');
            }

            $authorization = $this->_requestApi('GET', 'v2/payments/authorizations/' . rawurlencode($authId));
            if (($authorization['id'] ?? '') !== $authId) {
                throw new Exception('Invalid PayPal authorization response.');
            }
            $this->_verifyAmount($authorization['amount'] ?? [], $expected);
            DeliveryAttempt::claimResource((int)$submission->id, 'paypal:' . $account, $authId, (int)$field->id);

            $body = ['amount' => $expected, 'invoice_id' => $this->_invoiceId($submission, (int)$field->id), 'final_capture' => true];
            $capture = (new DeliveryAttempt((int)$submission->id, 'paypal.capture:' . $field->id, (string)$submission->uid))->execute(
                ['account' => $account, 'authorizationId' => $authId, 'body' => $body],
                fn(string $key) => $this->_requestApi('POST', 'v2/payments/authorizations/' . rawurlencode($authId) . '/capture', [
                    'json' => $body,
                    'headers' => ['PayPal-Request-Id' => $key, 'Prefer' => 'return=representation'],
                ]),
                5 * 3600,
                fn(array $result) => $result['id'] ?? '',
                fn(string $id) => $this->_requestApi('GET', 'v2/payments/captures/' . rawurlencode($id)),
            );

            $payment = $this->_paymentRecord($submission, (int)$field->id, $amount, $currency);
            $this->_applyCapture($payment, $capture, $submission);
            if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                throw new DeliveryOutcomeUnknownException('Unable to save the accepted PayPal payment.');
            }
            if ($payment->status === PaymentModel::STATUS_SUCCESS) {
                $this->afterProcessPayment($submission, true);
                return PaymentDecision::succeeded($this->handle);
            }
            return $payment->status === PaymentModel::STATUS_FAILED
                ? PaymentDecision::failed('PayPal did not complete the payment.', $this->handle)
                : PaymentDecision::pending('PayPal is still processing the payment.', $this->handle, $payment->reference);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, $this->throwApiError);
            $message = $this->getFriendlyPaymentErrorMessage($e);
            $this->addFieldError($submission, $message);
            if ($e instanceof DeliveryOutcomeUnknownException) {
                if ($submission->id && $field?->id && $this->id) {
                    $payment ??= $this->_paymentRecord($submission, (int)$field->id, $amount, $currency);
                    $payment->status = PaymentModel::STATUS_PROCESSING;
                    $payment->message = 'PayPal payment outcome requires confirmation.';
                    if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                        throw $e;
                    }
                }
                return PaymentDecision::pending($message, $this->handle, $payment?->reference);
            }
            return PaymentDecision::failed($message, $this->handle);
        }
    }

}

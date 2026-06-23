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

class PayPal extends Payment
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'PayPal');
    }
    

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public bool|string $useSandbox = false;


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

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return ['paypalAuthId'];
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
            $authId = $paymentPayload->string('paypalAuthId') ?? '';
            $orderId = $paymentPayload->string('paypalOrderId') ?? '';

            if (!$authId) {
                if (!$orderId) {
                    throw new Exception('Missing PayPal authorization data for payment.');
                }

                $authorization = $this->request('POST', "v2/checkout/orders/{$orderId}/authorize");
                $authId = trim((string)$this->_extractAuthorizationId($authorization));

                if (!$authId) {
                    throw new Exception('Missing Authorization ID for payment.');
                }
            }

            $response = $this->request('POST', "v2/payments/authorizations/{$authId}/capture");

            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $response['id'] ?? '';
            $payment->response = $response;

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

        return $result ? PaymentDecision::succeeded($this->handle) : PaymentDecision::failed(null, $this->handle);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('POST', 'v1/oauth2/token', [
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


    // Private Methods
    // =========================================================================

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
}

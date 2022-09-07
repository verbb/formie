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
use verbb\formie\fields\formfields;
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

class PayPal extends Payment
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
        return Craft::t('formie', 'Provide payment capabilities for your forms with PayPal.');
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->clientId) && App::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml($field, $renderOptions): string
    {
        if (!$this->hasValidSettings()) {
            return '';
        }

        $this->setField($field);

        return Craft::$app->getView()->renderTemplate('formie/integrations/payments/paypal/_input', [
            'field' => $field,
            'renderOptions' => $renderOptions,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $settings = [
            'clientId' => App::parseEnv($this->clientId),
            'useSandbox' => App::parseBooleanEnv($this->useSandbox),
            'currency' => $this->getFieldSetting('currency'),
            'amountType' => $this->getFieldSetting('amountType'),
            'amountFixed' => $this->getFieldSetting('amountFixed'),
            'amountVariable' => $this->getFieldSetting('amountVariable'),
            'buttonLayout' => $this->getFieldSetting('buttonLayout', 'horizontal'),
            'buttonColor' => $this->getFieldSetting('buttonColor', 'gold'),
            'buttonShape' => $this->getFieldSetting('buttonShape', 'rect'),
            'buttonLabel' => $this->getFieldSetting('buttonLabel', 'paypal'),
            'buttonTagline' => $this->getFieldSetting('buttonTagline', 'false'),
            'buttonWidth' => $this->getFieldSetting('buttonWidth'),
            'buttonHeight' => $this->getFieldSetting('buttonHeight'),
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/payments/paypal.js', true),
            'module' => 'FormiePayPal',
            'settings' => $settings,
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function processPayment(Submission $submission): bool
    {
        $response = null;
        $result = false;

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Capture the authorized payment
        try {
            $field = $this->getField();
            $fieldValue = $submission->getFieldValue($field->handle);
            $authId = $fieldValue['paypalAuthId'] ?? null;

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
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e, $this->throwApiError);

            $submission->addError($field->handle, Craft::t('formie', $e->getMessage()));
            
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

            return false;
        }

        // Allow events to say the response is invalid
        if (!$this->afterProcessPayment($submission, $result)) {
            return true;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
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

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $options = [];

        // Disable SSL verification for local dev (devMode enabled) to save some heartache.
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $options['verify'] = false;
        }

        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $clientId = App::parseEnv($this->clientId);
        $clientSecret = App::parseEnv($this->clientSecret);
        $token = base64_encode($clientId . ':' . $clientSecret);
        $url = $useSandbox ? 'https://api.sandbox.paypal.com/' : 'https://api.paypal.com/';

        return $this->_client = Craft::createGuzzleClient(array_merge([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Basic ' . $token,
                // 'Content-Type'  => 'application/x-www-form-urlencoded',
                'Content-Type' => 'application/json',
            ],
        ], $options));
    }

    /**
     * @inheritDoc
     */
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
                                    formfields\Calculations::class,
                                    formfields\Dropdown::class,
                                    formfields\Hidden::class,
                                    formfields\Number::class,
                                    formfields\Radio::class,
                                    formfields\SingleLineText::class,
                                ],
                                'if' => '$get(amountType).value == ' . Payment::VALUE_TYPE_DYNAMIC,
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        return [
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
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Label'),
                'help' => Craft::t('formie', 'Choose a label for the PayPal button.'),
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
                'help' => Craft::t('formie', 'Choose a color for the PayPal button.'),
                'name' => 'buttonColor',
                'options' => [
                    ['label' => Craft::t('formie', 'Gold'), 'value' => 'gold'],
                    ['label' => Craft::t('formie', 'Blue'), 'value' => 'blue'],
                    ['label' => Craft::t('formie', 'Silver'), 'value' => 'silver'],
                    ['label' => Craft::t('formie', 'White'), 'value' => 'white'],
                    ['label' => Craft::t('formie', 'Black'), 'value' => 'black'],
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Button Width'),
                'help' => Craft::t('formie', 'Set a width PayPal button in pixels, between 150px and 750px.'),
                'name' => 'buttonWidth',
                'min' => '150',
                'max' => '750',
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Button Height'),
                'help' => Craft::t('formie', 'Set a height PayPal button in pixels, between 25px to 55px.'),
                'name' => 'buttonHeight',
                'min' => '25',
                'max' => '55',
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Shape'),
                'help' => Craft::t('formie', 'Choose the shape of the PayPal button.'),
                'name' => 'buttonShape',
                'options' => [
                    ['label' => Craft::t('formie', 'Rectangular'), 'value' => 'rect'],
                    ['label' => Craft::t('formie', 'Pill'), 'value' => 'pill'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Button Layout'),
                'help' => Craft::t('formie', 'Choose the layout of the PayPal button.'),
                'name' => 'buttonLayout',
                'options' => [
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Button Tagline'),
                'help' => Craft::t('formie', 'Whether to show a tagline underneath buttons.'),
                'name' => 'buttonTagline',
            ]),
        ];
    }
    

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getIntegrationHandle(): string
    {
        return 'paypal';
    }
}
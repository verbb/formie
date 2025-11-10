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

class Paddle extends Payment
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PAYLOAD = 'modifyPayload';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Paddle');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $clientSideToken = null;
    public bool|string $useSandbox = false;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Provide payment capabilities for your forms with {name}.', ['name' => static::displayName()]);
    }

    public function hasValidSettings(): bool
    {
        return App::parseEnv($this->apiKey) && App::parseEnv($this->clientSideToken);
    }

    public function getFrontEndJsVariables($field = null): ?array
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($field);

        $useSandbox = App::parseBooleanEnv($this->useSandbox);

        $settings = [
            'clientSideToken' => App::parseEnv($this->clientSideToken),
            'environment' => $useSandbox ? 'sandbox' : 'production',
        ];

        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/payments/paddle.js'),
            'module' => 'FormiePaddle',
            'settings' => $settings,
        ];
    }

    public function processPayment(Submission $submission): bool
    {
        $response = null;
        $result = false;
        $field = $this->getField();
        $fieldValue = $this->getPaymentFieldValue($submission);

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Check if we're initializing the payment
        $paddleCheckoutInit = $fieldValue['paddleCheckoutInit'] ?? false;
        $paddleCheckoutData = $fieldValue['paddleCheckoutData'] ?? [];

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return true;
        }

        if ($paddleCheckoutInit) {
            // Create a payment right away so we can use it for redirect or fail, rather than multiple
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_PENDING;

            // Save the payment now, to pass on to Paddle
            Formie::$plugin->getPayments()->savePayment($payment);

            try {
                $items = $this->_getOrCreateProducts($submission);
            } catch (Throwable $e) {
                $this->addFieldError($submission, Craft::t('formie', $e->getMessage()));

                return false;
            }

            $payload = [
                'items' => $items,
                'customData' => [
                    'formiePaymentUId' => $payment->id,
                ],
                'customer' => [
                    'email' => 'josh@engramdesign.com.au',
                    'address' => [
                        'countryCode' => 'AU',
                        'postalCode' => '3032',
                    ],
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

            $submission->getForm()->addSubmitData([
                'event' => 'FormiePaymentPaddleCheckout',
                'data' => $event->payload,
            ]);

            // Add an error to the form to ensure it doesn't proceed to completion
            $this->addFieldError($submission, Craft::t('formie', 'Please wait while payment data is initialized.'));

            // Allow events to say the response is invalid
            if (!$this->afterProcessPayment($submission, $result)) {
                return true;
            }

            return false;
        }

        if ($paddleCheckoutData) {
            if (is_string($paddleCheckoutData) && Json::isJsonObject($paddleCheckoutData)) {
                $paddleCheckoutData = Json::decode($paddleCheckoutData);
            }

            if (!$paddleCheckoutData || !is_array($paddleCheckoutData)) {
                throw new Exception("Invalid checkout data: {$paddleCheckoutData}.");
            }

            // If we have checkout data, that means everything has been successful
            $payment = new PaymentModel();
            $payment->integrationId = $this->id;
            $payment->submissionId = $submission->id;
            $payment->fieldId = $field->id;
            $payment->amount = $amount;
            $payment->currency = $currency;
            $payment->status = PaymentModel::STATUS_SUCCESS;
            $payment->reference = $paddleCheckoutData['id'] ?? '';
            $payment->response = $paddleCheckoutData;

            Formie::$plugin->getPayments()->savePayment($payment);

            return true;
        }

        // Otherwise, something has gone wrong...
        $this->addFieldError($submission, Craft::t('formie', 'Unable to process payment.'));
        
        $payment = new PaymentModel();
        $payment->integrationId = $this->id;
        $payment->submissionId = $submission->id;
        $payment->fieldId = $field->id;
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->status = PaymentModel::STATUS_FAILED;

        Formie::$plugin->getPayments()->savePayment($payment);

        return false;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'event-types');
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Product Description'),
                'help' => Craft::t('formie', 'Enter a description for the product as shown in checkout.'),
                'name' => 'orderDescription',
            ]),
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

        $rules[] = [['apiKey', 'clientSideToken'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $useSandbox = App::parseBooleanEnv($this->useSandbox);
        $baseUri = $useSandbox ? 'https://sandbox-api.paddle.com/' : 'https://api.paddle.com/';


        return Craft::createGuzzleClient([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _setPayloadDetails(array &$payload, Submission $submission): void
    {
        $field = $this->getField();
        $fieldValue = $this->getPaymentFieldValue($submission);

        // Add a few other things about the customer from mapping (in field settings)
        $billingName = $this->getFieldSetting('billingDetails.billingName');
        $billingAddress = $this->getFieldSetting('billingDetails.billingAddress');
        $billingEmail = $this->getFieldSetting('billingDetails.billingEmail');

        // Just in case we're picking the string version of the Address field value (due to Vue restrictions)
        // ensure that we refer to the actual Address field model value as we need the "bits".
        $billingAddress = str_replace('.__toString', '', $billingAddress);

        if ($billingName) {
            $payload['customer']['business']['name'] = $this->getMappedFieldValue($billingName, $submission, new IntegrationField());
        }

        if ($billingAddress) {
            $integrationField = new IntegrationField();
            $integrationField->type = IntegrationField::TYPE_ARRAY;

            $address = $this->getMappedFieldValue($billingAddress, $submission, $integrationField);

            if ($address) {
                $payload['customer']['address']['firstLine'] = ArrayHelper::remove($address, 'address1');
                $payload['customer']['address']['city'] = ArrayHelper::remove($address, 'city');
                $payload['customer']['address']['postalCode'] = ArrayHelper::remove($address, 'zip');
                $payload['customer']['address']['region'] = ArrayHelper::remove($address, 'state');
                $payload['customer']['address']['countryCode'] = ArrayHelper::remove($address, 'country');
            }
        }

        if ($billingEmail) {
            $payload['customer']['email'] = $this->getMappedFieldValue($billingEmail, $submission, new IntegrationField());
        }

        $metadata = $this->getFieldSetting('metadata', []);

        // Add in some metadata by default
        $payload['customData']['submissionId'] = $submission->id;
        $payload['customData']['fieldId'] = $field->id;
        $payload['customData']['formHandle'] = $submission->getForm()->handle;

        if ($metadata) {
            foreach ($metadata as $option) {
                $label = trim($option['label']);
                $value = trim($option['value']);

                if ($label && $value) {
                    $payload['customData'][$label] = Variables::getParsedValue($value, $submission, $submission->getForm());
                }
            }
        }

    }

    private function _getOrCreateProducts(Submission $submission): mixed
    {
        $field = $this->getField();
        $orderDescription = $this->getFieldSetting('orderDescription', 'Formie: ' . $submission->getForm()->title);

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
        ];

        // Create a unique ID for this form+field+payload. Only used internally, but prevents creating duplicate plans (which throws an error)
        $payload['id'] = ArrayHelper::recursiveImplode(array_merge(['formie', $submission->getForm()->handle, $field->handle], $payload), '_');
        $payload['id'] = str_replace([' ', ':'], ['_', ''], $payload['id']);

        // Generate a nice name for the price description based on the payload. Added after the ID is generated based on the payload
        $payload['nickname'] = implode(' ', [
            $submission->getForm()->title . ' form',
            $amount,
            $currency,
        ]);

        // Create the product - no means to query by a custom ID via Paddle yet
        $priceId = $this->_createProduct($payload);

        return [
            [
                'priceId' => $priceId,
                'quantity' => 1,
            ]
        ];
    }

    private function _createProduct(array $payload)
    {
        $productResponse = $this->request('POST', 'products', [
            'json' => [
                'name' => $payload['nickname'],
                'type' => 'custom',
                'tax_category' => 'standard',
                'custom_data' => [
                    'id' => $payload['id'],
                ],
            ],
        ]);

        $product = $productResponse['data'] ?? null;

        if (!$product || !isset($product['id'])) {
            throw new Exception('Failed to create Paddle product.');
        }

        $priceResponse = $this->request('POST', 'prices', [
            'json' => [
                'type' => 'custom',
                'product_id' => $product['id'],
                'description' => $payload['nickname'],
                'unit_price' => [
                    'amount' => (string)($payload['amount'] * 100),
                    'currency_code' => $payload['currency'],
                ],
            ],
        ]);

        $price = $priceResponse['data'] ?? null;

        if (!$price || !isset($price['id'])) {
            throw new Exception('Failed to create Paddle price.');
        }

        return $price['id'];
    }

}

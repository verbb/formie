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
use verbb\formie\helpers\References;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
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

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        $this->setField($context->field);
        $useSandbox = App::parseBooleanEnv($this->useSandbox);

        return new ClientModule([
            'id' => 'paddle',
            'config' => [
                'clientSideToken' => App::parseEnv($this->clientSideToken),
                'environment' => $useSandbox ? 'sandbox' : 'production',
                'requiredInputSuffixes' => [],
                'waitForValueMs' => 2500,
            ],
        ]);
    }

    public function processPayment(Submission $submission): PaymentDecision
    {
        $field = $this->getField();
        $paymentPayload = $this->getPaymentFieldPayload($submission);

        // Get the amount from the field, which handles dynamic fields
        $amount = $this->getAmount($submission);
        $currency = $this->getFieldSetting('currency');

        // Check if we're initializing the payment
        $paddleCheckoutData = $paymentPayload->array('paddleCheckoutData');
        $hasCheckoutData = !empty($paddleCheckoutData);

        // Allow events to cancel sending
        if (!$this->beforeProcessPayment($submission)) {
            return PaymentDecision::notRequired();
        }

        // If no checkout payload is present, initialize checkout.
        // Never re-initialize when checkout data exists, even if the init flag is stale.
        if (!$hasCheckoutData) {
            // Persist the pending payment before handing control to the browser
            // so the eventual checkout callback can resume against a concrete
            // Formie payment record instead of recreating state heuristically.
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
                $this->addFieldError($submission, $e->getMessage());

                return PaymentDecision::failed($e->getMessage(), $this->handle);
            }

            $payload = [
                'items' => $items,
                'customData' => [
                    'formiePaymentUId' => $payment->id,
                ],
                'customer' => [],
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
                'event' => 'formie:payment:paddle:initialize',
                'data' => $event->payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterProcessPayment($submission, false)) {
                return PaymentDecision::succeeded($this->handle);
            }

            return PaymentDecision::requiresAction(
                $payment->reference,
                PaymentAction::initializeEvent('formie:payment:paddle:initialize')
                    ->forProvider($this->handle)
                    ->withMessage(Craft::t('formie', 'Please wait while payment data is initialized.'))
                    ->withPayload($event->payload)
                    ->resumeMode(PaymentAction::RESUME_MODE_CLIENT)
            );
        }

        if ($hasCheckoutData) {
            if (!$paddleCheckoutData || !is_array($paddleCheckoutData)) {
                throw new Exception("Invalid checkout data: {$paddleCheckoutData}.");
            }

            // Returned checkout data is treated as the browser's proof that the
            // initialize step completed, so the workflow can continue without
            // re-opening checkout or regenerating products.
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

            return PaymentDecision::succeeded($this->handle, $payment->reference);
        }

        // Should not generally hit this, but keep a deterministic fallback.
        return PaymentDecision::failed(Craft::t('formie', 'Unable to process payment.'), $this->handle);
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Product Description'),
                'instructions' => Craft::t('formie', 'Enter a description for the product as shown in checkout.'),
                'name' => 'orderDescription',
            ]),
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

        // Add a few other things about the customer from mapping (in field settings)
        $billingName = $this->getPaymentBillingFieldKey('billingName');
        $billingAddress = $this->getPaymentBillingFieldKey('billingAddress');
        $billingEmail = $this->getPaymentBillingFieldKey('billingEmail');

        if ($billingName && ($billingNameValue = $submission->getFieldValueAsString($billingName))) {
            $payload['customer']['business']['name'] = $billingNameValue;
        }

        if ($billingAddress && ($address = $submission->getFieldValueAsArray($billingAddress)) && is_array($address)) {
            $payload['customer']['address']['firstLine'] = ArrayHelper::remove($address, 'address1');
            $payload['customer']['address']['city'] = ArrayHelper::remove($address, 'city');
            $payload['customer']['address']['postalCode'] = ArrayHelper::remove($address, 'zip');
            $payload['customer']['address']['region'] = ArrayHelper::remove($address, 'state');
            $payload['customer']['address']['countryCode'] = ArrayHelper::remove($address, 'country');
        }

        if ($billingEmail && ($billingEmailValue = $submission->getFieldValueAsString($billingEmail))) {
            $payload['customer']['email'] = $billingEmailValue;
        }

        $metadata = $this->getFieldSetting('metadata', []);

        // Always attach Formie identifiers so webhooks, support debugging, and
        // payment replays can map Paddle activity back to the originating field.
        $payload['customData']['submissionId'] = $submission->id;
        $payload['customData']['fieldId'] = $field->id;
        $payload['customData']['formHandle'] = $submission->getForm()->handle;

        if ($metadata) {
            foreach ($metadata as $option) {
                $label = trim($option['label']);
                $value = trim($option['value']);

                if ($label && $value) {
                    $payload['customData'][$label] = References::parseContent($value, $submission);
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
            $orderDescription,
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

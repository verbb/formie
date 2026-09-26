<?php
namespace verbb\formie\models\payments;

use Craft;

final class PaymentWebhookCommand
{
    // Static Methods
    // =========================================================================

    public static function fromRequest(int $integrationId): self
    {
        $request = Craft::$app->getRequest();
        // Capture before an adapter parses or verifies the payload.
        $headers = $request->getHeaders()->toArray();
        if (empty($headers['stripe-signature']) && isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $headers['stripe-signature'] = [$_SERVER['HTTP_STRIPE_SIGNATURE']];
        }
        return new self($integrationId, $request->getRawBody(), $headers);
    }


    // Public Methods
    // =========================================================================

    public function __construct(public readonly int $integrationId, public readonly string $body, public readonly array $headers)
    {
    }

    public function header(string $name): string
    {
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        $value = $headers[strtolower($name)] ?? '';
        return is_array($value) ? implode(',', $value) : (string)$value;
    }
}

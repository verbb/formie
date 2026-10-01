<?php
namespace verbb\formie\models\payments;

use Craft;

use yii\web\RequestEntityTooLargeHttpException;

final class PaymentWebhookCommand
{
    // Constants
    // =========================================================================

    public const MAX_BODY_BYTES = 1048576;


    // Static Methods
    // =========================================================================

    public static function fromRequest(int $integrationId): self
    {
        $request = Craft::$app->getRequest();
        $contentLength = (int)$request->getHeaders()->get('Content-Length', 0);

        if ($contentLength > self::MAX_BODY_BYTES) {
            throw new RequestEntityTooLargeHttpException('Payment webhook payload is too large.');
        }

        $body = $request->getRawBody();

        if (strlen($body) > self::MAX_BODY_BYTES) {
            throw new RequestEntityTooLargeHttpException('Payment webhook payload is too large.');
        }

        // Capture before an adapter parses or verifies the payload.
        $headers = $request->getHeaders()->toArray();

        if (empty($headers['stripe-signature']) && isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $headers['stripe-signature'] = [$_SERVER['HTTP_STRIPE_SIGNATURE']];
        }
        return new self($integrationId, $body, $headers, $request->getQueryParams(), $request->getBodyParams());
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly int $integrationId,
        public readonly string $body,
        public readonly array $headers,
        public readonly array $queryParams = [],
        public readonly array $bodyParams = [],
    ) {
    }

    public function header(string $name): string
    {
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        $value = $headers[strtolower($name)] ?? '';
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    public function param(string $name): mixed
    {
        return $this->bodyParams[$name] ?? $this->queryParams[$name] ?? null;
    }
}

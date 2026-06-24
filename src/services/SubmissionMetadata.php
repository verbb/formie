<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;

use yii\base\Component;

class SubmissionMetadata extends Component
{
    // Constants
    // =========================================================================

    public const VERSION = 1;


    // Public Methods
    // =========================================================================

    public function normalize(?array $metadata): array
    {
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $metadata['v'] = self::VERSION;

        if (!isset($metadata['request']) || !is_array($metadata['request'])) {
            $metadata['request'] = [];
        }

        if (!isset($metadata['custom']) || !is_array($metadata['custom'])) {
            $metadata['custom'] = [];
        }

        return $metadata;
    }

    public function captureForSubmission(Submission $submission, Form $form): void
    {
        $metadata = $this->normalize($submission->metadata);

        $pendingCustom = $form->pullPendingSubmissionMetadata();

        if ($pendingCustom) {
            $metadata['custom'] = ArrayHelper::merge($metadata['custom'], $pendingCustom);
        }

        if (Craft::$app->getRequest()->getIsSiteRequest() && !$metadata['request']) {
            $metadata['request'] = $this->captureRequestContext($submission);
        }

        $submission->metadata = $metadata;
    }

    public function captureRequestContext(?Submission $submission = null): array
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return [];
        }

        $referrer = $request->getReferrer();

        return [
            'referrer' => $referrer,
            'pageUri' => $referrer ?: $request->getAbsoluteUrl(),
            'userAgent' => $request->getUserAgent(),
            'ipAddress' => $request->getUserIP() ?: $submission?->ipAddress,
            'cookies' => $this->captureTrackingCookies(),
        ];
    }

    public function captureTrackingCookies(): array
    {
        $cookies = [];

        if (isset($_COOKIE['hubspotutk'])) {
            $cookies['hubspotutk'] = (string)$_COOKIE['hubspotutk'];
        }

        $pattern = '/^visitor_id[0-9]+(-hash)?$/';

        foreach ($_COOKIE as $key => $value) {
            if (preg_match($pattern, (string)$key)) {
                $cookies[(string)$key] = (string)$value;
            }
        }

        return $cookies;
    }

    public function buildIntegrationContext(?Submission $submission): array
    {
        if (!$submission) {
            return $this->captureRequestContext();
        }

        $requestMetadata = $this->normalize($submission->metadata)['request'] ?? [];

        if (!$requestMetadata) {
            return $this->captureRequestContext($submission);
        }

        $cookies = is_array($requestMetadata['cookies'] ?? null) ? $requestMetadata['cookies'] : [];

        return array_filter([
            'referrer' => $requestMetadata['referrer'] ?? null,
            'ipAddress' => $requestMetadata['ipAddress'] ?? $submission->ipAddress,
            'hubspotutk' => $cookies['hubspotutk'] ?? null,
            'pardot_tracking' => $this->extractPardotTracking($cookies),
        ], fn($value) => $value !== null && $value !== '');
    }

    public function extractPardotTracking(array $cookies): array
    {
        $trackingData = [];
        $pattern = '/^visitor_id[0-9]+(-hash)?$/';

        foreach ($cookies as $key => $value) {
            if (preg_match($pattern, (string)$key)) {
                $trackingData[(string)$key] = (string)$value;
            }
        }

        return $trackingData;
    }

    public function getValue(Submission $submission, string $path): mixed
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        return ArrayHelper::getValue($this->normalize($submission->metadata), $path);
    }
}

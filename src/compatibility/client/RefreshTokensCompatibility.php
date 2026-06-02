<?php
namespace verbb\formie\compatibility\client;

use Craft;
use craft\helpers\Html;
use craft\web\Request;

class RefreshTokensCompatibility
{
    // Public Methods
    // =========================================================================

    public static function resolveRequestedHandle(Request $request): string
    {
        $handle = trim((string)$request->getParam('handle', ''));

        if ($handle !== '') {
            return $handle;
        }

        $legacyHandle = trim((string)$request->getParam('form', ''));

        if ($legacyHandle !== '') {
            Craft::$app->getDeprecator()->log(__METHOD__ . ':form', 'Using `form` for Formie refresh token requests has been deprecated. Use `handle` instead.');
        }

        return $legacyHandle;
    }

    public static function applyLegacyPayload(array $payload): array
    {
        $csrf = $payload['csrf'] ?? null;

        if (is_array($csrf) && isset($csrf['param'], $csrf['token']) && !isset($csrf['input'])) {
            $payload['csrf']['input'] = Html::hiddenInput((string)$csrf['param'], (string)$csrf['token']);
        }

        return $payload;
    }
}

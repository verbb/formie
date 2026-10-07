<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\web\Request;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class CrossOriginRequestHelper
{
    // Static Methods
    // =========================================================================

    public static function applyHeaders(Request $request, Response $response, array|string|null $allowedMethods = null): ?string
    {
        self::requireAllowedOrigin($request);
        $headers = $response->getHeaders();
        $headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Craft-Token, Cache-Control, X-Requested-With, X-Formie-Profile, X-Formie-Session, X-CSRF-Token');
        $headers->set('Access-Control-Allow-Methods', self::_normalizeAllowedMethods($request, $allowedMethods));
        $headers->set('Vary', 'Origin');

        $allowedOrigin = self::resolveAllowedOrigin($request);

        if ($allowedOrigin) {
            $headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $allowedOrigin;
    }

    public static function resolveAllowedOrigin(Request $request): ?string
    {
        $origin = trim((string)$request->getOrigin());

        if ($origin === '') {
            return null;
        }

        if ($origin === $request->getHostInfo() || in_array($origin, Formie::$plugin->getSettings()->allowedOrigins, true)) {
            return $origin;
        }

        return null;
    }

    public static function requireAllowedOrigin(Request $request): void
    {
        if (trim((string)$request->getOrigin()) !== '' && self::resolveAllowedOrigin($request) === null) {
            throw new ForbiddenHttpException('This origin is not allowed to access Formie. Configure Formie allowedOrigins for this application.');
        }
    }

    public static function isFormieActionPath(Request $request): bool
    {
        $path = trim($request->getPathInfo(), '/');
        $actionTrigger = trim((string)Craft::$app->getConfig()->getGeneral()->actionTrigger, '/');

        if ($actionTrigger !== '' && str_starts_with($path, $actionTrigger . '/formie/')) {
            return true;
        }

        return str_starts_with($path, 'formie/');
    }

    private static function _normalizeAllowedMethods(Request $request, array|string|null $allowedMethods): string
    {
        if (is_array($allowedMethods)) {
            return implode(', ', $allowedMethods);
        }

        if (is_string($allowedMethods) && $allowedMethods !== '') {
            return $allowedMethods;
        }

        $requestedMethod = strtoupper((string)$request->getHeaders()->get('Access-Control-Request-Method', ''));

        if ($requestedMethod !== '') {
            return $requestedMethod . ', OPTIONS';
        }

        return 'GET, POST, OPTIONS';
    }
}

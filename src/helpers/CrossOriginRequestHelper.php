<?php
namespace verbb\formie\helpers;

use Craft;
use craft\web\Request;

use yii\web\Response;

class CrossOriginRequestHelper
{
    public static function applyHeaders(Request $request, Response $response, array|string|null $allowedMethods = null): ?string
    {
        $headers = $response->getHeaders();
        $headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Craft-Token, Cache-Control, X-Requested-With');
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
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $originHeader = trim((string)$request->getOrigin());

        if ($originHeader === '') {
            return null;
        }

        if (is_array($generalConfig->allowedGraphqlOrigins)) {
            $origins = array_filter(array_map('trim', explode(',', $originHeader)));

            foreach ($origins as $origin) {
                if (in_array($origin, $generalConfig->allowedGraphqlOrigins, true)) {
                    return $origin;
                }
            }

            return null;
        }

        if (self::_shouldAllowLocalDevOrigin($request, $originHeader)) {
            // In local dev, allow localhost starters against local Craft hosts
            // without requiring GraphQL-origin config edits for each dev-server port.
            return $originHeader;
        }

        return null;
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

    private static function _shouldAllowLocalDevOrigin(Request $request, string $originHeader): bool
    {
        $originHost = parse_url($originHeader, PHP_URL_HOST);
        $requestHost = parse_url((string)$request->getHostInfo(), PHP_URL_HOST);

        if (!is_string($originHost) || !is_string($requestHost)) {
            return false;
        }

        return self::_isLocalDevHost($originHost) && self::_isLocalDevHost($requestHost);
    }

    private static function _isLocalDevHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.test') || str_ends_with($host, '.ddev.site')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) {
                return true;
            }

            if (preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host) === 1) {
                return true;
            }
        }

        return false;
    }
}

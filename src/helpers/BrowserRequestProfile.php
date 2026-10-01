<?php
namespace verbb\formie\helpers;

use Craft;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/** Adapter-owned policy; the submission engine continues to receive explicit authority. */
class BrowserRequestProfile
{
    // Static Methods
    // =========================================================================

    public static function enter(bool $bootstrap = false): string
    {
        $request = Craft::$app->getRequest();
        CrossOriginRequestHelper::requireAllowedOrigin($request);

        if ($request->getIsOptions()) {
            return self::CROSS_ORIGIN;
        }
        $profile = (string)$request->getHeaders()->get('X-Formie-Profile', self::SAME_ORIGIN);

        if (!in_array($profile, [self::SAME_ORIGIN, self::CROSS_ORIGIN], true)) {
            throw new BadRequestHttpException('Public Formie endpoints require a browser request profile. Use administrative GraphQL mutations for trusted administrative requests.');
        }

        if ($profile === self::SAME_ORIGIN) {
            $origin = trim((string)$request->getOrigin());

            if ($origin !== '' && $origin !== $request->getHostInfo()) {
                throw new ForbiddenHttpException('Cross-origin Formie requests must declare the cross-origin-public profile.');
            }
            return $profile;
        }

        if (!$request->getOrigin()) {
            throw new ForbiddenHttpException('The cross-origin-public profile requires an allowed Origin.');
        }
        // Opaque credentials select an isolated guest session. Ambient Craft login
        // cookies and cached identities never supply authority to this profile.
        $token = (string)$request->getHeaders()->get('X-Formie-Session', '');
        $cache = Craft::$app->getCache();
        $sessionId = $token !== '' ? $cache->get(['formie-public-session', hash('sha256', $token)]) : false;

        if (!$sessionId) {
            if (!$bootstrap || $token !== '') {
                throw new ForbiddenHttpException('Formie public session is missing or expired. Reload the form.');
            }
            $token = Craft::$app->getSecurity()->generateRandomString(64);
            $sessionId = bin2hex(random_bytes(24));
            $cache->set(['formie-public-session', hash('sha256', $token)], $sessionId, 7200);
        }
        $session = Craft::$app->getSession();

        if ($session->getId() !== $sessionId) {
            $session->close();
            $session->setUseCookies(false);
            $session->setId($sessionId);
            $session->open();
        }
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getResponse()->getHeaders()->set('X-Formie-Session', $token);
        Craft::$app->getResponse()->getHeaders()->set('Access-Control-Expose-Headers', 'X-Formie-Session');
        return $profile;
    }


    public static function enterAdministrative(): void
    {
        $request = Craft::$app->getRequest();

        if ($request instanceof \craft\web\Request) {
            CrossOriginRequestHelper::requireAllowedOrigin($request);

            if ($request->getHeaders()->get('X-Formie-Profile', self::ADMINISTRATIVE) !== self::ADMINISTRATIVE) {
                throw new ForbiddenHttpException('Administrative mutations require the trusted-administrative profile.');
            }
        }

        // A public schema's create scope permits interactive visitor submission,
        // not administrative mutation of submissions by ID or handle.
        if (Craft::$app->getGql()->getActiveSchema()->isPublic) {
            throw new ForbiddenHttpException('Administrative mutations require an authenticated API schema.');
        }
    }


    // Constants
    // =========================================================================

    public const SAME_ORIGIN = 'same-origin-browser';
    public const CROSS_ORIGIN = 'cross-origin-public';
    public const ADMINISTRATIVE = 'trusted-administrative';
}

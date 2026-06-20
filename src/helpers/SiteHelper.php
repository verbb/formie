<?php
namespace verbb\formie\helpers;

use Craft;

use yii\web\BadRequestHttpException;

class SiteHelper
{
    // Public Methods
    // =========================================================================

    public static function resolveRequestSiteId(
        mixed $siteId = null,
        mixed $siteHandle = null,
        ?int $fallbackSiteId = null,
    ): int {
        if (is_numeric($siteId) && (int)$siteId > 0) {
            return (int)$siteId;
        }

        if (is_string($siteHandle) && trim($siteHandle) !== '') {
            $site = Craft::$app->getSites()->getSiteByHandle(trim($siteHandle));

            if (!$site) {
                throw new BadRequestHttpException('Invalid site handle.');
            }

            return (int)$site->id;
        }

        if ($fallbackSiteId !== null && $fallbackSiteId > 0) {
            return $fallbackSiteId;
        }

        return (int)Craft::$app->getSites()->getCurrentSite()->id;
    }

    public static function resolveSiteIdFromRequest(?int $fallbackSiteId = null): int
    {
        $request = Craft::$app->getRequest();

        $siteId = $request->getBodyParam('siteId', $request->getParam('siteId'));
        $siteHandle = $request->getBodyParam('siteHandle', $request->getParam('siteHandle'));

        return self::resolveRequestSiteId($siteId, $siteHandle, $fallbackSiteId);
    }

    public static function applyLocaleForSiteId(int $siteId): void
    {
        $site = Craft::$app->getSites()->getSiteById($siteId, true);

        if (!$site) {
            return;
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));
        Craft::$app->getSites()->setCurrentSite($site);
    }
}

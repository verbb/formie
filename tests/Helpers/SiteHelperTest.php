<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\models\Settings;

it('resolves site id from site handle', function (): void {
    $site = Craft::$app->getSites()->getCurrentSite();

    expect(SiteHelper::resolveRequestSiteId(null, $site->handle))
        ->toBe((int)$site->id);
});

it('prefers site id over site handle when both are provided', function (): void {
    $site = Craft::$app->getSites()->getCurrentSite();

    expect(SiteHelper::resolveRequestSiteId($site->id, 'another-handle'))
        ->toBe((int)$site->id);
});

it('throws for an invalid site handle', function (): void {
    SiteHelper::resolveRequestSiteId(null, 'not-a-real-site-handle-' . uniqid());
})->throws(yii\web\BadRequestHttpException::class);

it('applies the site locale for validation messaging', function (): void {
    $site = Craft::$app->getSites()->getCurrentSite();
    $originalLanguage = Craft::$app->language;

    try {
        SiteHelper::applyLocaleForSiteId((int)$site->id);

        expect(Craft::$app->language)->toBe($site->language)
            ->and(Craft::$app->getLocale()->id)->toBe($site->language)
            ->and(Craft::$app->getSites()->getCurrentSite()->id)->toBe((int)$site->id);
    } finally {
        Craft::$app->language = $originalLanguage;
    }
});

it('coerces disallowed submit methods from plugin settings', function (): void {
    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $original = $settings->allowedSubmitMethods;

    try {
        $settings->allowedSubmitMethods = Settings::ALLOWED_SUBMIT_METHODS_AJAX;

        $formSettings = new verbb\formie\models\FormSettings([
            'submitMethod' => 'page-reload',
        ]);

        $formSettings->validate(['submitMethod']);

        expect($formSettings->submitMethod)->toBe('ajax');
    } finally {
        $settings->allowedSubmitMethods = $original;
    }
});

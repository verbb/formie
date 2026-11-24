<?php

declare(strict_types=1);

use craft\helpers\UrlHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\Formie;
use verbb\formie\integrations\addressproviders\Google;

function headlessServerRenderEndpoints(Form $form, bool $headlessMode): array
{
    $general = Craft::$app->getConfig()->getGeneral();
    $original = $general->headlessMode;
    $general->headlessMode = $headlessMode;

    try {
        $payload = Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, ['renderCss' => false, 'renderJs' => false]);
    } finally {
        $general->headlessMode = $original;
    }

    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $payload['html']);
    $element = $document->getElementsByTagName('form')->item(0);
    $tabLinks = [];

    foreach ((new DOMXPath($document))->query('//*[@data-formie-tab-link]') as $link) {
        $tabLinks[] = $link->getAttribute('href');
    }

    return [
        'submit' => $element->getAttribute('data-formie-submit-endpoint'),
        'refresh-tokens' => $element->getAttribute('data-formie-refresh-tokens-endpoint'),
        'clear-submission' => $element->getAttribute('data-formie-clear-submission-endpoint'),
        'tabLinks' => $tabLinks,
    ];
}

function createHeadlessServerRenderForm(): Form
{
    $form = formie()->form(['title' => 'Headless server endpoints'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();
    $form->settings->displayPageTabs = true;
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return $form;
}

// Headless mode put server-rendered forms' submit, page, token refresh and clear endpoints on the control panel route,
// where Formie refuses public actions ("Public submissions require the site request contract.").
it('gives server-rendered forms site action endpoints in headless mode', function (): void {
    $form = createHeadlessServerRenderForm();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
            expect(Craft::$app->getUser()->getIsGuest())->toBeTrue()
                ->and($request->getIsCpRequest())->toBeFalse();

            $siteSetPage = UrlHelper::actionUrl('formie/server/submissions/set-page');
            $nonHeadless = headlessServerRenderEndpoints($form, false);
            $headless = headlessServerRenderEndpoints($form, true);

            expect($nonHeadless['submit'])->toBe(UrlHelper::actionUrl('formie/server/submissions/submit'))
                ->and($headless['submit'])->toBe($nonHeadless['submit'])
                ->and($headless['refresh-tokens'])->toBe($nonHeadless['refresh-tokens'])
                ->and($headless['clear-submission'])->toBe($nonHeadless['clear-submission'])
                ->and($headless['tabLinks'])->toHaveCount(2);

            foreach ($headless['tabLinks'] as $href) {
                expect(str_starts_with($href, $siteSetPage))->toBeTrue("{$href} is not on the site route {$siteSetPage}");
            }
        }, ['requestUri' => '/actions/formie/server/forms/render']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});

// The Google address provider's geocode proxy looks the form up the same way, so guests got "Unable to geocode location."
it('gives the Google address provider a site geocode endpoint in headless mode', function (): void {
    $provider = new Google(['name' => 'Headless geocode', 'handle' => 'headlessGeocode' . bin2hex(random_bytes(4)), 'enabled' => true, 'apiKey' => 'key']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($provider, false))->toBeTrue();
    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$subField) {
            if (($subField['handle'] ?? null) === 'autoComplete') {
                $subField['enabled'] = true;
                $subField['integrationHandle'] = $provider->handle;
            }
        }
    }

    unset($row, $subField);
    $form = formie()->form(['title' => 'Headless geocode'])->addressField('home', ['label' => 'Home', 'rows' => $rows])->create();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
            expect(Craft::$app->getUser()->getIsGuest())->toBeTrue()
                ->and($request->getIsCpRequest())->toBeFalse();

            $geocodeEndpoint = static function (bool $headlessMode) use ($form): ?string {
                $general = Craft::$app->getConfig()->getGeneral();
                $original = $general->headlessMode;
                $general->headlessMode = $headlessMode;

                try {
                    $html = Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, ['renderCss' => false, 'renderJs' => false])['html'];
                } finally {
                    $general->headlessMode = $original;
                }

                preg_match('/"geocodeEndpoint":"([^"]+)"/', html_entity_decode($html), $matches);

                return isset($matches[1]) ? json_decode('"' . $matches[1] . '"') : null;
            };

            $siteEndpoint = UrlHelper::actionUrl('formie/address/google-places-geocode');

            expect($geocodeEndpoint(false))->toBe($siteEndpoint)
                ->and($geocodeEndpoint(true))->toBe($siteEndpoint);
        }, ['requestUri' => '/actions/formie/server/forms/render']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
        Formie::$plugin->getIntegrations()->deleteIntegrationById($provider->id);
    }
});

// Opayo's session request checks the form for the visitor too, so guests got "Invalid payment session authority."
it('gives the Opayo payment module a site session endpoint in headless mode', function (): void {
    $integration = new \verbb\formie\integrations\payments\Opayo([
        'name' => 'Headless Opayo', 'handle' => 'headlessOpayo' . bin2hex(random_bytes(4)), 'enabled' => true,
        'vendorName' => 'fixture', 'integrationKey' => 'fixture-key', 'integrationPassword' => 'fixture-password',
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form(['title' => 'Headless Opayo'])
        ->paymentField('pay', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => \verbb\formie\integrations\payments\Opayo::class, 'providerSettings' => [$integration->handle => [
            'amountType' => 'fixed', 'amountFixed' => '10', 'currencyType' => 'fixed', 'currencyFixed' => 'GBP',
        ]]])
        ->create();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
            expect(Craft::$app->getUser()->getIsGuest())->toBeTrue()
                ->and($request->getIsCpRequest())->toBeFalse();

            $sessionEndpoint = static function (bool $headlessMode) use ($form): ?string {
                $general = Craft::$app->getConfig()->getGeneral();
                $original = $general->headlessMode;
                $general->headlessMode = $headlessMode;

                try {
                    $html = Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, ['renderCss' => false, 'renderJs' => false])['html'];
                } finally {
                    $general->headlessMode = $original;
                }

                preg_match('/"sessionEndpoint":"([^"]+)"/', html_entity_decode($html), $matches);

                return isset($matches[1]) ? json_decode('"' . $matches[1] . '"') : null;
            };

            $siteEndpoint = UrlHelper::actionUrl('formie/payment-sessions/initialize');

            expect($sessionEndpoint(false))->toBe($siteEndpoint)
                ->and($sessionEndpoint(true))->toBe($siteEndpoint);
        }, ['requestUri' => '/actions/formie/server/forms/render']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});

it('keeps control panel action endpoints for server-rendered forms in control panel requests', function (bool $headlessMode): void {
    $form = createHeadlessServerRenderForm();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $headlessMode): void {
            $request->setIsCpRequest(true);

            $endpoints = headlessServerRenderEndpoints($form, $headlessMode);

            expect($endpoints['submit'])->toBe(UrlHelper::actionUrl('formie/server/submissions/submit'))
                ->and($endpoints['refresh-tokens'])->toBe(UrlHelper::actionUrl('formie/server/forms/refresh-tokens'))
                ->and($endpoints['clear-submission'])->toBe(UrlHelper::actionUrl('formie/server/submissions/clear-submission'));

            foreach ($endpoints['tabLinks'] as $href) {
                expect(str_starts_with($href, UrlHelper::actionUrl('formie/server/submissions/set-page')))->toBeTrue();
            }
        });
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
})->with([true, false]);

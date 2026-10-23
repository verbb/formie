<?php

declare(strict_types=1);

use craft\elements\Entry;

dataset('redirect_tabs', ['same-tab', 'new-tab']);

beforeEach(function () {
    // Console fixtures generate HTTP entry URLs even with an HTTPS site base.
    $siteUrl = \craft\helpers\UrlHelper::urlWithScheme(Craft::$app->getSites()->getPrimarySite()->getBaseUrl(), 'http');
    \verbb\formie\Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://example.test', $siteUrl];
});

it('resolves url redirect targets and tab behavior contract from form settings', function (string $tab): void {
    $form = formie()
        ->form(['title' => 'URL Redirect Contract ' . $tab])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => 'https://example.test/redirect-url',
        'redirectTarget' => $tab,
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $clientConfig = $form->getCpEditConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect($form->getRedirectUrl())->toContain('example.test/redirect-url')
        ->and($settings['submitMethod'] ?? null)->toBe($form->settings->submitMethod)
        ->and($form->settings->submitAction)->toBe('url')
        ->and($form->settings->redirectTarget)->toBe($tab);
})->with('redirect_tabs');

it('rejects Twig when saving submit action URLs and never executes it', function (): void {
    $form = formie()
        ->form(['title' => 'URL Redirect Literal Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => 'https://example.test/redirect-{{7*7}}',
        'redirectTarget' => 'same-tab',
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeFalse()
        ->and($form->getFirstError('settings.redirectUrl'))->toContain('Enter a valid relative URL');

    expect($form->getRedirectUrl())->toBe('')
        ->and($form->getRedirectUrl())->not->toContain('redirect-49');
});

it('rejects unsafe authored overrides without changing the current redirect', function (string $url): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $form->setSettings(['completionBehavior' => 'redirect', 'redirectUrl' => '/safe']);

    expect(fn() => $form->setRedirectUrl($url))->toThrow(\Twig\Error\RuntimeError::class, 'redirectUrl');
    expect($form->settings->redirectUrl)->toBe('/safe')
        ->and($form->getCompletionRedirectOverride())->toBeNull();
})->with([
    'Twig expression' => ['https://example.test/redirect-{{7*7}}'],
    'encoded header injection' => ['https://evil.test/%0d%0aInjected'],
]);

it('resolves entry redirect targets and tab behavior contract from form settings', function (string $tab): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Entry Redirect Contract ' . $tab])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'entry',
        'redirectTarget' => $tab,
    ], false);
    $form->redirectEntryId = $entry->id;
    $form->redirectEntrySiteId = $entry->siteId;

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $clientConfig = $form->getCpEditConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect((string)$form->getRedirectUrl())->toContain('formie-seed-entry')
        ->and($settings['submitMethod'] ?? null)->toBe($form->settings->submitMethod)
        ->and($form->settings->submitAction)->toBe('entry')
        ->and($form->settings->redirectTarget)->toBe($tab);
})->with('redirect_tabs');

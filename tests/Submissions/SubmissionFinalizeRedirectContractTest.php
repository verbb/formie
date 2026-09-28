<?php

declare(strict_types=1);

use Craft;
use craft\elements\Entry;

dataset('redirect_tabs', ['same-tab', 'new-tab']);

beforeEach(function () { \verbb\formie\Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://example.test', 'http://formie-react-tests.ddev.site']; });

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

it('does not execute Twig in submit action URLs', function (): void {
    $form = formie()
        ->form(['title' => 'URL Redirect Literal Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => 'https://example.test/redirect-{{7*7}}',
        'redirectTarget' => 'same-tab',
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    expect($form->getRedirectUrl())->toBe('')
        ->and($form->getRedirectUrl())->not->toContain('redirect-49');
});

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

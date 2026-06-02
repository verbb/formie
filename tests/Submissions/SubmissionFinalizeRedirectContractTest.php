<?php

declare(strict_types=1);

use Craft;
use craft\elements\Entry;

dataset('redirect_tabs', ['same-tab', 'new-tab']);

it('resolves url redirect targets and tab behavior contract from form settings', function (string $tab): void {
    $form = formie()
        ->form(['title' => 'URL Redirect Contract ' . $tab])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'submitActionUrl' => 'https://example.test/redirect-url',
        'submitActionTab' => $tab,
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $clientConfig = $form->getClientConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect($form->getRedirectUrl())->toContain('example.test/redirect-url')
        ->and($settings['submitMethod'] ?? null)->toBe($form->settings->submitMethod)
        ->and($form->settings->submitAction)->toBe('url')
        ->and($form->settings->submitActionTab)->toBe($tab);
})->with('redirect_tabs');

it('does not execute Twig in submit action URLs', function (): void {
    $form = formie()
        ->form(['title' => 'URL Redirect Literal Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'submitActionUrl' => 'https://example.test/redirect-{{7*7}}',
        'submitActionTab' => 'same-tab',
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    expect($form->getRedirectUrl())->toContain('redirect-{{7*7}}')
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
        'submitActionTab' => $tab,
    ], false);
    $form->submitActionEntryId = $entry->id;
    $form->submitActionEntrySiteId = $entry->siteId;

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $clientConfig = $form->getClientConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect((string)$form->getRedirectUrl())->toContain('formie-seed-entry')
        ->and($settings['submitMethod'] ?? null)->toBe($form->settings->submitMethod)
        ->and($form->settings->submitAction)->toBe('entry')
        ->and($form->settings->submitActionTab)->toBe($tab);
})->with('redirect_tabs');

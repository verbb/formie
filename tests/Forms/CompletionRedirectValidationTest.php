<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\FormSettings;

it('rejects disallowed completion URLs when saving a form and accepts an approved correction', function (): void {
    Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://parnter.test'];
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->setAttributes([
        'completionBehavior' => 'redirect',
        'redirectUrl' => 'https://partner.test/thanks',
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeFalse()
        ->and($form->getFirstError('settings.redirectUrl'))->toContain('completionRedirectAllowedOrigins');

    Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://partner.test'];
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue()
        ->and(Form::find()->id($form->id)->one()->settings->redirectUrl)->toBe('https://partner.test/thanks');
});

it('checks authored completion URLs without evaluating submission references', function (string $url, bool $valid): void {
    Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://partner.test'];
    $settings = new FormSettings(['completionBehavior' => 'redirect', 'redirectUrl' => $url]);
    $storedUrl = $settings->redirectUrl;

    expect($settings->validate(['redirectUrl']))->toBe($valid)
        ->and($settings->redirectUrl)->toBe($storedUrl);
})->with([
    'relative path' => ['/thanks?ok=1#done', true],
    'relative URL' => ['thanks', true],
    'approved origin' => ['https://partner.test/thanks', true],
    'unapproved origin' => ['https://unapproved.test/thanks', false],
    'wrong port' => ['https://partner.test:8443/thanks', false],
    'lookalike origin' => ['https://partner.test.evil.test/thanks', false],
    'protocol relative' => ['//partner.test/thanks', false],
    'unsupported scheme' => ['javascript:alert(1)', false],
    'encoded controls' => ['/thanks%0d%0aInjected', false],
    'credentials' => ['https://user@partner.test/thanks', false],
    'Twig' => ['/thanks/{{ submission.id }}', false],
    'path reference' => ['/thanks/{submission:id}', true],
    'query reference' => ['https://partner.test/thanks?name={field:name|Some Name}', true],
    'unapproved static origin with reference' => ['https://unapproved.test/thanks?id={submission:id}', false],
    'whole reference' => ['{field:destination}', true],
    'site reference' => ['{site:url}/thanks', true],
    'dynamic host' => ['https://{field:host}/thanks', true],
    'empty override target' => ['', true],
]);

it('does not validate unused completion URL settings', function (string $behavior, string $source): void {
    $settings = new FormSettings([
        'completionBehavior' => $behavior,
        'completionRedirectSource' => $source,
        'redirectUrl' => 'https://unapproved.test/thanks',
    ]);

    expect($settings->validate(['redirectUrl']))->toBeTrue();
})->with([['message', 'url'], ['reload', 'url'], ['reset', 'url'], ['redirect', 'entry']]);

it('accepts the configured site origin without an explicit allowlist entry', function (): void {
    Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = [];
    $url = rtrim(Craft::$app->getSites()->getPrimarySite()->getBaseUrl(), '/') . '/thanks';
    $settings = new FormSettings(['completionBehavior' => 'redirect', 'redirectUrl' => $url]);

    expect($settings->validate(['redirectUrl']))->toBeTrue();
});

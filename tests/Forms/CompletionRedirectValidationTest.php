<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\FormSettings;

it('saves an external completion URL without developer configuration', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->setAttributes([
        'completionBehavior' => 'redirect',
        'redirectUrl' => 'https://buy.stripe.com/bJe4gz0cN0C85mA03S0Ny07',
    ], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue()
        ->and(Form::find()->id($form->id)->one()->settings->redirectUrl)->toBe('https://buy.stripe.com/bJe4gz0cN0C85mA03S0Ny07');
});

it('checks authored completion URLs without evaluating submission references', function (string $url, bool $valid): void {
    $settings = new FormSettings(['completionBehavior' => 'redirect', 'redirectUrl' => $url]);
    $storedUrl = $settings->redirectUrl;

    expect($settings->validate(['redirectUrl']))->toBe($valid)
        ->and($settings->redirectUrl)->toBe($storedUrl);
})->with([
    'relative path' => ['/thanks?ok=1#done', true],
    'relative URL' => ['thanks', true],
    'approved origin' => ['https://partner.test/thanks', true],
    'external origin' => ['https://unapproved.test/thanks', true],
    'explicit port' => ['https://partner.test:8443/thanks', true],
    'external subdomain' => ['https://partner.test.evil.test/thanks', true],
    'protocol relative' => ['//partner.test/thanks', false],
    'unsupported scheme' => ['javascript:alert(1)', false],
    'encoded controls' => ['/thanks%0d%0aInjected', false],
    'credentials' => ['https://user@partner.test/thanks', false],
    'Twig' => ['/thanks/{{ submission.id }}', false],
    'path reference' => ['/thanks/{submission:id}', true],
    'query reference' => ['https://partner.test/thanks?name={field:name|Some Name}', true],
    'external static origin with reference' => ['https://unapproved.test/thanks?id={submission:id}', true],
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
    $url = rtrim(Craft::$app->getSites()->getPrimarySite()->getBaseUrl(), '/') . '/thanks';
    $settings = new FormSettings(['completionBehavior' => 'redirect', 'redirectUrl' => $url]);

    expect($settings->validate(['redirectUrl']))->toBeTrue();
});

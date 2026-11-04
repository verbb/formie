<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\integrations\payments\Mollie;

it('renders the redirect payment input and instructions through a saved form', function(string $provider) {
    $integration = new $provider([
        'name' => 'Redirect payment template',
        'handle' => 'redirectTemplate' . bin2hex(random_bytes(6)),
        ...($provider === GoCardless::class ? ['accessToken' => 'local-test-token'] : ['apiKey' => 'local-test-key']),
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()->form(['title' => 'Redirect payment template'])
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => $provider,
        ])->create();
    $field = $form->getFieldByHandle('payment');
    $html = (string)$field->getPaymentHtml();

    expect($html)->toContain('type="hidden"')
        ->toContain('name="fields[payment]"')
        ->toContain('You will be redirected to')
        ->toContain($integration::displayName());

    $rendered = WebRequestTestHelper::withWebRequestContext(
        fn() => (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false]),
    );
    expect($rendered)->toContain('name="fields[payment]"')
        ->toContain('You will be redirected to');
})->with([GoCardless::class, Mollie::class]);

it('keeps an unconfigured GoCardless field empty', function() {
    $integration = new GoCardless();
    $form = formie()->form(['title' => 'Unconfigured GoCardless'])->paymentField('payment')->create();

    expect($integration->renderFieldHtml($form->getFieldByHandle('payment')))->toBe('');
});

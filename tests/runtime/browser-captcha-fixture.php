<?php
// Captchas with test keys, and one form for each, for tests/browser/client-captchas.spec.ts. The providers' scripts are
// faked in the browser and their verification endpoints in browser-captcha-routes.php.
$integrations = \verbb\formie\Formie::$plugin->getIntegrations();
$captchaSettings = [
    'recaptcha' => ['siteKey' => 'browser-site-key', 'secretKey' => 'browser-secret', 'projectId' => 'browser-project', 'type' => 'v3'],
    'hcaptcha' => ['siteKey' => 'browser-site-key', 'secretKey' => 'browser-secret'],
    'turnstile' => ['siteKey' => 'browser-site-key', 'secretKey' => 'browser-secret'],
    'captchaEu' => ['publicKey' => 'browser-public-key', 'restKey' => 'browser-secret'],
    'question' => ['questions' => [['question' => 'What colour is a clear sky?', 'answers' => 'blue, light blue']]],
];

// A new form takes on every captcha that's enabled when it's saved, so the forms come first, each with its own captcha.
foreach (array_keys($captchaSettings) as $handle) {
    \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Captcha ' . $handle, 'handle' => 'captcha' . ucfirst($handle)])
        ->settings(['submitMethod' => 'ajax'])
        ->integrations([$handle => ['enabled' => true]])
        ->singleLineTextField('visitorName', ['label' => 'Visitor name'])
        ->create();
}

foreach ($captchaSettings as $handle => $settings) {
    $captcha = $integrations->getCaptchaByHandle($handle);
    $captcha->setEnabled(true);
    Craft::configure($captcha, $settings);

    if (!$integrations->saveCaptcha($captcha)) {
        throw new RuntimeException('Cannot save the ' . $handle . ' captcha fixture.');
    }
}

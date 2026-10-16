<?php

use craft\elements\conditions\entries\EntryCondition;
use verbb\formie\elements\Form;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Payment;
use verbb\formie\fields\SingleLineText;
use verbb\formie\integrations\captchas\FriendlyCaptcha;
use verbb\formie\integrations\captchas\Hcaptcha;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\models\BrowserModuleContext;

it('builds captcha browser configuration using automatic locale matching', function(string $provider, string $locale, string $expected) {
    $previous = Craft::$app->language;
    Craft::$app->language = $locale;
    try {
        $captcha = new $provider(['language' => 'auto', 'siteKey' => 'test-key']);
        $module = $captcha->getBrowserModule(new BrowserModuleContext(['form' => new Form(['id' => 123])]));
        expect($module)->not->toBeNull()
            ->and($module->config['language'])->toBe($expected);
    } finally {
        Craft::$app->language = $previous;
    }
})->with([FriendlyCaptcha::class, Hcaptcha::class])->with([
    ['de', 'de'],
    ['de-DE', 'de'],
]);

it('creates the own-form payment card fields with their correct types', function(string $provider) {
    $integration = new $provider();
    $rows = $integration->getPaymentSubFields(new Payment(['handle' => 'payment']));
    $fields = array_merge(...array_values($rows));
    expect(array_map(fn($field) => $field->handle, $fields))->toBe(['cardName', 'cardNumber', 'cardExpiry', 'cardCvc']);
    foreach ($fields as $field) {
        expect($field)->toBeInstanceOf(SingleLineText::class);
    }
})->with([Opayo::class, Eway::class]);

it('clears empty element selection conditions and retains configured ones', function() {
    $field = new Entries();
    $field->setSelectionCondition(new EntryCondition());
    expect($field->getSelectionCondition())->toBeNull();

    $condition = new EntryCondition(null, ['conditionRules' => [
        ['class' => \craft\elements\conditions\IdConditionRule::class, 'operator' => '=', 'value' => '42'],
    ]]);
    $field->setSelectionCondition($condition);
    expect($field->getSelectionCondition())->toBe($condition);
});

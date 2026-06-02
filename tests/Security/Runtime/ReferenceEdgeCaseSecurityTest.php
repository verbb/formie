<?php

declare(strict_types=1);

use Craft;
use craft\web\Request;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\helpers\References;

it('requires exact token strings for parseValue and leaves malformed variants unresolved', function (): void {
    $form = formie()
        ->form(['title' => 'Reference Whitespace Security'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Security Tester'])
        ->save();

    $token = References::field((string)$field->reference);

    expect(References::parseValue("  {$token}  ", $submission))->toBeNull()
        ->and(References::parseValue("{$token}extra", $submission))->toBe("{$token}extra");
})->group('security');

it('stringifies multi-value referenced fields before interpolation', function (): void {
    $form = formie()
        ->form(['title' => 'Reference Array Security'])
        ->checkboxesField('topics', [
            'options' => [
                ['label' => 'One', 'value' => 'one'],
                ['label' => 'Two', 'value' => 'two'],
            ],
        ])
        ->create();

    $field = $form->getFieldByHandle('topics');
    $submission = formie()
        ->submission($form)
        ->with(['topics' => ['one', 'two']])
        ->save();

    $token = References::field((string)$field->reference);

    expect(References::parseContent("Topics={$token}", $submission))->toBe('Topics=one, two');
})->group('security');

it('collapses unknown reference targets to empty strings during content parsing', function (): void {
    $form = formie()
        ->form(['title' => 'Reference Unknown Target Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Security Tester'])
        ->save();

    expect(References::parseContent('Value={evil:payload}', $submission))->toBe('Value=');
})->group('security');

it('does not reflect arbitrary origins when graphql origins are enabled without an explicit allowlist', function (): void {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalAllowedOrigins = $generalConfig->allowedGraphqlOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://odd.example.com');

    try {
        $generalConfig->allowedGraphqlOrigins = null;

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))->toBeNull();
    } finally {
        $generalConfig->allowedGraphqlOrigins = $originalAllowedOrigins;
    }
})->group('security');

<?php

declare(strict_types=1);

use verbb\formie\helpers\References;

it('builds submission reference tokens', function (): void {
    expect(References::token('submission', 'uid'))->toBe('{submission:uid}');
});

it('builds bodyless summary reference tokens', function (): void {
    expect(References::token('allFields'))->toBe('{allFields}');
});

it('builds field reference tokens with selectors and metadata', function (): void {
    expect(References::field('a1b2c3', 'email'))->toBe('{field:a1b2c3:email}')
        ->and(References::token('field', 'a1b2c3', 'email', ['scope' => 'all']))->toBe('{field:a1b2c3:email;scope=all}');
});

it('builds reference tokens with inline defaults', function (): void {
    expect(References::token('submission', 'uid', null, [], 'pending'))
        ->toBe('{submission:uid|pending}');
});

it('builds craft.formie.ref tokens from the Formie variable API', function (): void {
    expect(Craft::$app->getView()->renderString(
        "{{ craft.formie.ref('submission', 'uid') }}",
    ))->toBe('{submission:uid}');
});

it('builds craft.formie.refField tokens from a form field handle', function (): void {
    $form = formie()
        ->form(['title' => 'Reference Token Twig'])
        ->singleLineTextField('email')
        ->create();

    $field = $form->getFieldByHandle('email');

    expect(Craft::$app->getView()->renderString(
        "{{ craft.formie.refField(form, 'email') }}",
        ['form' => $form],
    ))->toBe(References::field((string)$field->reference));
});

it('resolves reference tokens set via craft.formie.ref in submit action messages', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Action Reference Tokens'])
        ->create();

    $token = References::token('submission', 'uid');

    $form->setSettings([
        'submitActionMessage' => 'Thanks ' . $token,
    ]);

    $submission = formie()->submission($form)->save();

    expect($form->settings->getSubmitActionMessage($submission))
        ->toBe('Thanks ' . $submission->uid);
});

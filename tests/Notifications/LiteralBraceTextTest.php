<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceException;
use verbb\formie\references\ReferenceUsage;

function literalBraceEmail(array $config): array
{
    $form = formie()->form()->singleLineTextField('company')->create();
    $saved = formie()->submission($form)->with(['company' => 'S Group'])->save();

    return Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Literal braces',
        'handle' => 'literalBraces',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => 'Enquiry received.',
        ...$config,
    ]), Submission::find()->id($saved->id)->status(null)->one());
}

it('keeps brace text that cannot be a reference literal in a subject', function (): void {
    $result = literalBraceEmail(['subject' => '{(Anfrage)} {field:company} {"a":1} {123}']);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSubject())->toBe('{(Anfrage)} S Group {"a":1} {123}');
});

it('keeps brace text that cannot be a reference literal in the body', function (): void {
    $result = literalBraceEmail(['content' => 'Before {(Anfrage)} after']);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSymfonyEmail()->getTextBody())->toContain('Before {(Anfrage)} after');
});

it('keeps brace text with spaces literal in text settings', function (string $text): void {
    $result = literalBraceEmail(['subject' => 'Before ' . $text . ' after', 'fromName' => 'From ' . $text]);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSubject())->toBe('Before ' . $text . ' after')
        ->and($result['email']->getFrom())->toBe(['sender@example.test' => 'From ' . $text]);
})->with(['{foo bar}', '{Note: see below}', '{a, b and c}', '{100% sure}']);

it('keeps brace text with spaces literal in the body', function (): void {
    $result = literalBraceEmail(['content' => 'Before {foo bar} after']);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSymfonyEmail()->getTextBody())->toContain('Before {foo bar} after');
});

it('still rejects brace text in every address setting', function (string $setting, string $text): void {
    $result = literalBraceEmail([$setting => 'staff@example.test, ' . $text]);

    expect($result)->toHaveKey('error');
})->with(['to', 'cc', 'bcc', 'replyTo'])->with(['{foo bar}', '{other:thing}', '{Note: see below}']);

it('still rejects brace text with spaces as the sender', function (): void {
    expect(literalBraceEmail(['from' => '{foo bar}']))->toHaveKey('error');
});

it('still fails a broken reference to a known source in a subject', function (string $token): void {
    expect(literalBraceEmail(['subject' => 'Before ' . $token . ' after']))->toHaveKey('error');
})->with(['{field:foo bar}', '{field.foo bar}', '{user:full name}', '{submission:nope}', '{other:thing}']);

it('keeps a bare token that could be a field handle strict', function (): void {
    // A single word could name a field, so it isn't treated as brace text.
    expect(literalBraceEmail(['subject' => 'Before {notAField} after']))->toHaveKey('error');
});

it('keeps brace text strict in URLs and integration values', function (): void {
    $form = formie()->form()->singleLineTextField('company')->create();
    $submission = formie()->submission($form)->with(['company' => 'S Group'])->save();

    expect(fn() => References::resolveUrl('https://example.test/{foo bar}', $submission))->toThrow(ReferenceException::class)
        ->and(fn() => References::interpolateText('{foo bar}', ReferenceContext::forSubmission($submission, usage: ReferenceUsage::Integration)))->toThrow(ReferenceException::class);
});

it('still rejects brace text as a recipient', function (): void {
    $result = literalBraceEmail(['to' => '{(Anfrage)}']);

    expect($result)->toHaveKey('error')
        ->and($result['email']->getTo())->toBeEmpty();
});

it('still treats Twig tags as invalid in a subject', function (): void {
    expect(fn() => literalBraceEmail(['subject' => '{% if form %}A{% endif %}']))->not->toThrow(Throwable::class);
    expect(literalBraceEmail(['subject' => '{% if form %}A{% endif %}']))->toHaveKey('error');
});

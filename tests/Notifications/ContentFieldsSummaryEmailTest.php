<?php

use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;

function contentSummaryEmail(Submission $submission, string $content): string
{
    $result = Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Content summary',
        'handle' => 'contentSummary',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => $content,
    ]), Submission::find()->id($submission->id)->status(null)->one());

    expect($result)->not->toHaveKey('error');

    return $result['email']->getSymfonyEmail()->getHtmlBody();
}

// Rich text as the builder saves it, with each token as a variable tag in its own paragraph.
function contentSummaryDoc(array ...$paragraphs): string
{
    return Json::encode(['type' => 'doc', 'content' => array_map(static fn(array $content): array => ['type' => 'paragraph', 'content' => array_map(
        static fn(string $part): array => str_starts_with($part, '{') ? ['type' => 'variableTag', 'attrs' => ['label' => 'Tag', 'value' => $part]] : ['type' => 'text', 'text' => $part],
        $content,
    )], $paragraphs)]);
}

function contentSummaryEmptyParagraphs(string $html): int
{
    return preg_match_all('/<p\b[^>]*>(?:\s|&nbsp;|<span\b[^>]*>\s*<\/span>)*<\/p>/i', $html);
}

it('leaves no empty paragraph around a summary alone in its paragraph', function (string $content): void {
    $form = formie()->form()->singleLineTextField('firstName')->create();
    $submission = formie()->submission($form)->with(['firstName' => 'Ada'])->save();

    $html = contentSummaryEmail($submission, $content);

    expect($html)->toContain('Ada')
        ->and(contentSummaryEmptyParagraphs($html))->toBe(0);
})->with([
    'variable tag' => [contentSummaryDoc(['{allContentFields}'])],
    'variable tag between paragraphs' => [contentSummaryDoc(['Here is what they sent.'], ['{allFields}'], ['Thanks.'])],
    'stored HTML' => ['<p>{allVisibleFields}</p>'],
]);

it('leaves no empty paragraph around a whole Table reference alone in its paragraph', function (): void {
    $form = formie()->form()->tableField('dates', ['columns' => [
        'col1' => ['heading' => 'Sacrament', 'handle' => 'sacrament', 'type' => 'singleline'],
    ]])->create();
    $submission = formie()->submission($form)->with(['dates' => [['col1' => 'Baptism']]])->save();

    $html = contentSummaryEmail($submission, contentSummaryDoc([References::field((string)$form->getFieldByHandle('dates')->reference)]));

    expect($html)->toContain('Baptism')
        ->and(contentSummaryEmptyParagraphs($html))->toBe(0);
});

it('keeps the text around a summary that shares its paragraph', function (): void {
    $form = formie()->form()->singleLineTextField('firstName')->create();
    $submission = formie()->submission($form)->with(['firstName' => 'Ada'])->save();

    $html = contentSummaryEmail($submission, contentSummaryDoc(['Details: ', '{allContentFields}']));

    expect($html)->toContain('Details:')
        ->and($html)->toContain('Ada');
});

it('lists an unticked Agree field with its Unchecked Value in all non-empty fields', function (): void {
    $form = formie()->form()
        ->agreeField('newsletter', ['label' => 'Newsletter', 'uncheckedValue' => 'Declined'])
        ->singleLineTextField('firstName')
        ->create();
    $submission = formie()->submission($form)->with(['newsletter' => false, 'firstName' => 'Ada'])->save();

    $html = contentSummaryEmail($submission, contentSummaryDoc(['{allContentFields}']));

    expect($html)->toContain('Newsletter')
        ->and($html)->toContain('Declined');
});

it('lists a ticked Agree field with its Checked Value in all non-empty fields', function (): void {
    $form = formie()->form()
        ->agreeField('newsletter', ['label' => 'Newsletter', 'checkedValue' => 'Subscribed', 'uncheckedValue' => 'Declined'])
        ->create();
    $submission = formie()->submission($form)->with(['newsletter' => true])->save();

    $html = contentSummaryEmail($submission, contentSummaryDoc(['{allContentFields}']));

    expect($html)->toContain('Subscribed')
        ->and($html)->not->toContain('Declined');
});

it('still leaves empty fields out of all non-empty fields', function (): void {
    $form = formie()->form()
        ->singleLineTextField('firstName')
        ->singleLineTextField('lastName', ['label' => 'Family name'])
        ->create();
    $submission = formie()->submission($form)->with(['firstName' => 'Ada'])->save();

    $html = contentSummaryEmail($submission, contentSummaryDoc(['{allContentFields}']));

    expect($html)->toContain('Ada')
        ->and($html)->not->toContain('Family name');
});

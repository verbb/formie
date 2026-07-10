<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\helpers\References;
use verbb\formie\models\RichText;

it('includes cosmetic html and rich text fields in email summaries when enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Cosmetic Email Summaries'])
        ->htmlField('notice', [
            'label' => 'Notice',
            'htmlContent' => '<p><strong>Static notice</strong></p>',
            'includeInEmailFieldSummaries' => true,
        ])
        ->contentField('intro', [
            'label' => 'Intro',
            'content' => RichText::from('<p>Intro <strong>text</strong></p>'),
            'includeInEmailFieldSummaries' => true,
        ])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Tester',
    ])->save();

    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);
    $allContentFields = References::parseContent('{allContentFields}', $submission, ['includeSummary' => true]);

    expect($allFields)->toContain('<strong>Static notice</strong>')
        ->and($allFields)->toContain('Intro')
        ->and($allFields)->toContain('Intro <strong>text</strong>')
        ->and($allFields)->toContain('Tester')
        ->and($allContentFields)->toContain('Static notice')
        ->and($allContentFields)->toContain('Intro <strong>text</strong>');
})->group('notifications');

it('excludes cosmetic fields from email summaries when include setting is disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Cosmetic Email Summary Opt Out'])
        ->htmlField('notice', [
            'label' => 'Notice',
            'htmlContent' => '<p>Should not appear</p>',
            'includeInEmailFieldSummaries' => false,
        ])
        ->contentField('intro', [
            'label' => 'Intro',
            'content' => RichText::from('<p>Also hidden</p>'),
            'includeInEmailFieldSummaries' => false,
        ])
        ->create();

    $submission = formie()->submission($form)->save();
    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);

    expect($allFields)->not->toContain('Should not appear')
        ->and($allFields)->not->toContain('Also hidden');
})->group('notifications');

it('purifies cosmetic html field output in email summaries', function (): void {
    $form = formie()
        ->form(['title' => 'Cosmetic Email Summary Purify'])
        ->htmlField('notice', [
            'label' => 'Notice',
            'htmlContent' => MaliciousPayloads::storedXssProbe(),
            'includeInEmailFieldSummaries' => true,
            'purifyContent' => true,
        ])
        ->create();

    $submission = formie()->submission($form)->save();
    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);

    expect($allFields)->toContain('safe-text')
        ->and($allFields)->not->toContain('<script')
        ->and($allFields)->not->toContain('onerror=');
})->group('notifications');

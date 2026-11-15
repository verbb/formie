<?php

use verbb\formie\helpers\References;

afterEach(function() {
    Craft::$app->setTimeZone($this->originalTimeZone);
});

beforeEach(function() {
    $this->originalTimeZone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone('Australia/Melbourne');
});

it('formats the current time in the site time zone', function(string $token, string $pattern) {
    $form = formie()->form()->singleLineTextField('text')->create();
    $submission = formie()->submission($form)->with(['text' => 'Value'])->save();
    $tz = new DateTimeZone('Australia/Melbourne');
    $before = (new DateTimeImmutable('now', $tz))->format($pattern);
    $value = References::parseContent($token, $submission);
    $after = (new DateTimeImmutable('now', $tz))->format($pattern);

    expect($value)->toBeIn([$before, $after]);
})->with([
    ['{dateInt}', 'd/m/Y'],
    ['{dateUs}', 'm/d/Y'],
    ['{time12}', 'h:i a'],
    ['{time24}', 'H:i'],
    ['{timestamp;transform=format;preset=custom;pattern=H%3Ai}', 'H:i'],
]);

it('keeps the time of a submission date when formatting it', function() {
    $form = formie()->form()->singleLineTextField('text')->create();
    $submission = formie()->submission($form)->with(['text' => 'Value'])->save();
    $expected = $submission->dateCreated->setTimezone(new DateTimeZone('Australia/Melbourne'))->format('Y-m-d H:i');

    expect(References::parseContent('{submission:date;transform=format;preset=custom;pattern=Y-m-d%20H%3Ai}', $submission))->toBe($expected);
});

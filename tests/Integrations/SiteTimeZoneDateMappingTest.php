<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\models\IntegrationField;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceUsage;

// The test app runs in UTC, where reading a site-local string as UTC changes nothing, so these need a real offset.
beforeEach(function(): void {
    $this->previousTimeZone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone('Australia/Melbourne');
});

afterEach(function(): void {
    Craft::$app->setTimeZone($this->previousTimeZone);
});

function siteTimeZoneSubmission(): Submission
{
    $form = formie()->form(['title' => 'Site Time Zone Dates'])->dateField('appointment', ['displayType' => 'calendar', 'includeTime' => true])->create();
    $submission = formie()->submission($form)->with(['appointment' => ['date' => '2025-10-31', 'time' => '16:17']])->save();
    $submission->dateCreated = new DateTime('2025-10-09 10:27:05', new DateTimeZone('Australia/Melbourne'));

    return $submission;
}

function siteTimeZoneText(Submission $submission, string $template): string
{
    return References::interpolateText($template, ReferenceContext::forSubmission($submission, usage: ReferenceUsage::Text), ReferenceOutputContext::PlainText);
}

it('keeps the site time when formatting the submission date in a reference', function(): void {
    $submission = siteTimeZoneSubmission();

    expect(siteTimeZoneText($submission, '{submission:date}'))->toBe('2025-10-09 10:27:05')
        ->and(siteTimeZoneText($submission, '{submission:date;transform=format;preset=datetimeIso24}'))->toBe('2025-10-09 10:27');
});

it('keeps the entered time when formatting a Date field in a reference', function(): void {
    $submission = siteTimeZoneSubmission();
    $reference = $submission->getForm()->getFieldByHandle('appointment')->reference;

    expect(siteTimeZoneText($submission, '{field:' . $reference . '}'))->toBe('2025-10-31 16:17')
        ->and(siteTimeZoneText($submission, '{field:' . $reference . ';transform=format;preset=datetimeIso24}'))->toBe('2025-10-31 16:17');
});

it('keeps an explicit UTC offset when reading a date string', function(): void {
    $date = \verbb\formie\fields\values\DateFieldValue::toDateTime('2025-10-09T10:27:05+11:00');

    expect($date->getTimestamp())->toBe((new DateTime('2025-10-08 23:27:05', new DateTimeZone('UTC')))->getTimestamp());
});

it('sends the moment the submission was made to HubSpot date and datetime properties', function(string $type): void {
    $submission = siteTimeZoneSubmission();
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspotDates']);
    $destination = new IntegrationField(['handle' => 'closedate', 'type' => $type]);

    expect($integration->getFieldMappingValues($submission, ['closedate' => '{submission:date}'], [$destination]))
        ->toBe(['closedate' => '1759966025000']);
})->with([IntegrationField::TYPE_DATE, IntegrationField::TYPE_DATETIME]);

it('sends the site time to other date destinations', function(string $type, string|null $expected): void {
    $submission = siteTimeZoneSubmission();
    $integration = new Entry(['name' => 'Entry', 'handle' => 'entryDates']);
    $destination = new IntegrationField(['handle' => 'when', 'type' => $type]);
    $value = $integration->getFieldMappingValues($submission, ['when' => '{submission:date}'], [$destination])['when'];

    if ($expected === null) {
        expect($value)->toBeInstanceOf(DateTime::class)
            ->and($value->getTimestamp())->toBe(DateTimeHelper::toDateTime('2025-10-08T23:27:05+00:00')->getTimestamp());

        return;
    }

    expect($value)->toBe($expected);
})->with([
    'text' => [IntegrationField::TYPE_STRING, '2025-10-09 10:27:05'],
    'datetime' => [IntegrationField::TYPE_DATETIME, '2025-10-09 10:27:05'],
    'date class' => [IntegrationField::TYPE_DATECLASS, null],
]);

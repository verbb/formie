<?php

declare(strict_types=1);

use craft\db\Query;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\Formie;
use yii\base\Event;

beforeEach(function (): void {
    $this->referenceTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function (): void {
    $this->referenceTransaction->rollBack();
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
});

it('scans upload references without sorting large submission content', function (string $scope, bool $expected): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()
        ->fileUploadField('document', ['restrictFiles' => false])
        ->fileUploadField('otherDocument', ['restrictFiles' => false])
        ->multiLineTextField('details')
        ->create();
    $asset = UploadTestHelper::seedAsset('reference-scan-' . uniqid() . '.txt', 'reference');
    $submission = formie()->submission($form)->with([
        'document' => [$asset->id],
        'otherDocument' => $scope === 'other-field' ? [$asset->id] : [],
        'details' => 'Large' . str_repeat(' ', 827000) . 'submission content.',
    ])->save();

    $content = (new Query())->select('content')->from('{{%formie_submissions}}')->where(['id' => $submission->id])->scalar();
    expect(strlen((string)$content))->toBeGreaterThan(800000);

    // Inspect the prepared SQL used by the service, not a separately constructed query.
    $queries = [];
    $handler = static function ($event) use (&$queries): void {
        $queries[] = [$event->sender->query->orderBy, $event->sender->subQuery->orderBy];
    };
    Event::on(SubmissionQuery::class, SubmissionQuery::EVENT_AFTER_PREPARE, $handler);

    try {
        $referenced = Formie::$plugin->getFileUploads()->isReferenced(
            $scope === 'missing' ? -1 : (int)$asset->id,
            in_array($scope, ['submission', 'field', 'other-field'], true) ? (int)$submission->id : null,
            in_array($scope, ['field', 'other-field'], true) ? 'document' : null,
        );
    } finally {
        Event::off(SubmissionQuery::class, SubmissionQuery::EVENT_AFTER_PREPARE, $handler);
    }

    expect($referenced)->toBe($expected)->and($queries)->not->toBeEmpty();

    foreach ($queries as [$orderBy, $subqueryOrderBy]) {
        // Site preference ordering inside unique() is separate from sorting the content rows.
        expect($orderBy)->toBeEmpty()->and($subqueryOrderBy)->toBeEmpty();
    }
})->with([
    'matching reference' => ['matching', true],
    'missing reference' => ['missing', false],
    'excluded submission' => ['submission', false],
    'excluded field' => ['field', false],
    'another field retains the reference' => ['other-field', true],
]);

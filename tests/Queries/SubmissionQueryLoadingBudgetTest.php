<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\Formie;

function countSubmissionReadQueries(callable $read): int
{
    $logger = Craft::getLogger();
    $interval = $logger->flushInterval;
    $logging = Craft::$app->getDb()->enableLogging;
    $logger->flushInterval = 0;
    Craft::$app->getDb()->enableLogging = true;
    $start = count($logger->messages);
    try {
        $read();
        return count(array_filter(array_slice($logger->messages, $start),
            fn($message) => $message[2] === 'yii\db\Command::query' && $message[1] === \yii\log\Logger::LEVEL_TRACE));
    } finally {
        $logger->flushInterval = $interval;
        Craft::$app->getDb()->enableLogging = $logging;
    }
}

it('loads saved values without adding a query per submission', function (): void {
    $form = formie()->form()->singleLineTextField('name')->numberField('score')->create();
    $ids = [];
    for ($i = 0; $i < 20; $i++) {
        $ids[] = formie()->submission($form)->with(['name' => "Person {$i}", 'score' => $i])->save()->id;
    }
    $read = function (int $limit) use ($form, $ids): void {
        $rows = Submission::find()->formId($form->id)->orderBy('elements.id ASC')->limit($limit)->all();
        expect(array_map(fn($row) => $row->id, $rows))->toBe(array_slice($ids, 0, $limit));
        foreach ($rows as $i => $row) {
            expect($row->getFieldValue('name'))->toBe("Person {$i}")
                ->and($row->getFieldValueAsString('score'))->toBe((string)$i);
        }
    };
    // Prime schema/registry discovery equally. The measured work is row loading,
    // not the one-time installation/schema cost.
    $read(1);
    $one = countSubmissionReadQueries(fn() => $read(1));
    $twenty = countSubmissionReadQueries(fn() => $read(20));
    expect($one)->toBeGreaterThan(0)
        ->and($twenty)->toBeLessThanOrEqual($one + 2);
});

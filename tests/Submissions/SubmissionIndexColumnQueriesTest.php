<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Db;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;

const SUBMISSION_INDEX_QUERY_COLUMNS = ['userId', 'updatedBy', 'paymentStatus', 'sendNotification'];

/**
 * Loads submissions the way the element index does for the given columns, renders those columns and counts the queries.
 */
function renderSubmissionIndexColumns(array $ids, bool $prep): array
{
    $logger = Craft::getLogger();
    $interval = $logger->flushInterval;
    $logging = Craft::$app->getDb()->enableLogging;
    $logger->flushInterval = 0;
    Craft::$app->getDb()->enableLogging = true;
    $start = count($logger->messages);

    try {
        $query = Submission::find()->id($ids)->orderBy(['elements.id' => SORT_ASC]);

        if ($prep) {
            foreach (SUBMISSION_INDEX_QUERY_COLUMNS as $attribute) {
                (new ReflectionMethod(Submission::class, 'prepElementQueryForTableAttribute'))->invoke(null, $query, $attribute);
            }
        }

        $html = [];

        foreach ($query->all() as $submission) {
            foreach (SUBMISSION_INDEX_QUERY_COLUMNS as $attribute) {
                // Chips, and the SVG gradients of user avatar placeholders, get random DOM ids on every render.
                $html[$submission->id][$attribute] = preg_replace(['/\b(chip|action-edit|action-menu)-\d+/', '/\b[a-z]{10}-gradient-[a-z]{10}\b/'], ['$1', 'gradient'], $submission->getAttributeHtml($attribute));
            }
        }

        $queries = count(array_filter(array_slice($logger->messages, $start),
            fn($message) => $message[2] === 'yii\db\Command::query' && $message[1] === \yii\log\Logger::LEVEL_TRACE));

        return [$html, $queries];
    } finally {
        $logger->flushInterval = $interval;
        Craft::$app->getDb()->enableLogging = $logging;
    }
}

it('renders the user, payment and notification index columns without a query per row', function (): void {
    $withNotification = formie()->form(['title' => 'Index columns notified'])->singleLineTextField('name')->create();
    $withoutNotification = formie()->form(['title' => 'Index columns silent'])->singleLineTextField('name')->create();
    $notification = new Notification(['formId' => $withNotification->id, 'name' => 'Admin', 'handle' => 'admin' . uniqid(), 'to' => 'index@example.test', 'subject' => 'Index', 'enabled' => true]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    $users = [];

    for ($i = 1; $i <= 3; $i++) {
        $name = 'indexColumns' . $i . bin2hex(random_bytes(4));
        $users[$i] = new User(['username' => $name, 'email' => $name . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($users[$i]))->toBeTrue();
    }

    $ids = [];

    for ($i = 0; $i < 8; $i++) {
        $submission = formie()->submission($i % 4 === 3 ? $withoutNotification : $withNotification)->with(['name' => "Row {$i}"])->save();

        // One row in four has no user, to keep the empty cell covered.
        $userId = $i % 4 === 2 ? null : $users[$i % 3 + 1]->id;
        Db::update(Table::FORMIE_SUBMISSIONS, ['userId' => $userId, 'updatedById' => $users[($i + 1) % 3 + 1]->id], ['id' => $submission->id], updateTimestamp: false);
        $ids[] = $submission->id;
    }

    // The column shows the latest payment, so give one row two.
    foreach ([[$ids[0], 'pending', '2026-01-01 00:00:00'], [$ids[0], 'success', '2026-01-02 00:00:00'], [$ids[5], 'failed', '2026-01-01 00:00:00']] as [$submissionId, $status, $date]) {
        Db::insert(Table::FORMIE_PAYMENTS, ['submissionId' => $submissionId, 'status' => $status, 'amount' => '10', 'currency' => 'AUD', 'dateCreated' => $date, 'dateUpdated' => $date]);
    }

    $previousIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

    try {
        // Prime one-time schema and registry work equally for every measured read.
        renderSubmissionIndexColumns([$ids[0]], false);
        renderSubmissionIndexColumns([$ids[0]], true);

        [$expected] = renderSubmissionIndexColumns($ids, false);
        [$fewHtml, $few] = renderSubmissionIndexColumns(array_slice($ids, 0, 4), true);
        [$allHtml, $all] = renderSubmissionIndexColumns($ids, true);
    } finally {
        Craft::$app->getUser()->setIdentity($previousIdentity);
    }

    expect($allHtml)->toBe($expected)
        ->and($fewHtml)->toBe(array_slice($expected, 0, 4, true))
        ->and($expected[$ids[0]]['paymentStatus'])->toContain('Succeeded')
        ->and($expected[$ids[5]]['paymentStatus'])->toContain('Failed')
        ->and($expected[$ids[1]]['paymentStatus'])->toBe('')
        ->and($expected[$ids[2]]['userId'])->toBe('')
        ->and($expected[$ids[0]]['userId'])->not->toBe('')
        ->and($expected[$ids[0]]['updatedBy'])->not->toBe('')
        ->and($expected[$ids[0]]['sendNotification'])->toContain('Send')
        ->and($expected[$ids[3]]['sendNotification'])->toBe('')
        // Twice the rows, the same queries.
        ->and($all)->toBe($few);
});

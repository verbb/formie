<?php

declare(strict_types=1);

use craft\db\Query;
use craft\services\ProjectConfig;
use verbb\formie\Formie;
use verbb\formie\events\SubmissionStatusEvent;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260641_000000_align_default_submission_status_colors;
use verbb\formie\services\SubmissionStatuses;
use yii\base\Event;

it('aligns the default submission status color without dispatching legacy status events', function (): void {
    $status = Formie::$plugin->getSubmissionStatuses()->getStatusByHandle('new');
    expect($status)->not->toBeNull();

    $originalProjectConfig = Craft::$app->getProjectConfig();
    $deprecator = Craft::$app->getDeprecator();
    $legacyClass = 'verbb\\formie\\services\\Statuses';
    $eventName = SubmissionStatuses::EVENT_AFTER_SAVE_STATUS;
    $originalRecordColor = (new Query())
        ->select(['color'])
        ->from([Table::FORMIE_SUBMISSION_STATUSES])
        ->where(['uid' => $status->uid])
        ->scalar();
    $originalThrowExceptions = $deprecator->throwExceptions;
    $eventCount = 0;
    $writtenColor = null;
    $handler = static function() use (&$eventCount): void {
        $eventCount++;
    };
    $projectConfig = $this->getMockBuilder(ProjectConfig::class)
        ->onlyMethods(['get', 'set'])
        ->getMock();
    $projectConfig->method('get')->willReturnCallback(
        static fn(?string $path = null, bool $external = false): mixed => $path === SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY && $external ? [
            $status->uid => ['handle' => 'new', 'color' => 'green'],
        ] : null,
    );
    $projectConfig->method('set')->willReturnCallback(
        static function(string $path, mixed $value) use (&$writtenColor, $projectConfig, $status): bool {
            $writtenColor = $value;

            if (!$projectConfig->muteEvents) {
                Formie::$plugin->getSubmissionStatuses()->trigger(
                    SubmissionStatuses::EVENT_AFTER_SAVE_STATUS,
                    new SubmissionStatusEvent(['status' => $status]),
                );
            }

            return true;
        },
    );

    try {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->getDb()->createCommand()
            ->update(Table::FORMIE_SUBMISSION_STATUSES, ['color' => 'green'], ['uid' => $status->uid])
            ->execute();
        $status->color = 'green';

        Event::on($legacyClass, $eventName, $handler);
        $deprecator->throwExceptions = true;

        expect((new m260641_000000_align_default_submission_status_colors())->safeUp())->toBeTrue()
            ->and($writtenColor)->toBe('turquoise')
            ->and((new Query())
                ->select(['color'])
                ->from([Table::FORMIE_SUBMISSION_STATUSES])
                ->where(['uid' => $status->uid])
                ->scalar())->toBe('turquoise')
            ->and(Formie::$plugin->getSubmissionStatuses()->getStatusByHandle('new')?->color)->toBe('turquoise')
            ->and($eventCount)->toBe(0)
            ->and($projectConfig->muteEvents)->toBeFalse();
    } finally {
        $deprecator->throwExceptions = $originalThrowExceptions;
        Event::off($legacyClass, $eventName, $handler);
        Craft::$app->set('projectConfig', $originalProjectConfig);
        Craft::$app->getDb()->createCommand()
            ->update(Table::FORMIE_SUBMISSION_STATUSES, ['color' => $originalRecordColor], ['uid' => $status->uid])
            ->execute();
        Formie::$plugin->getSubmissionStatuses()->invalidateCaches();
    }
});

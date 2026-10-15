<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261016_000000_migrate_form_site_content;
use verbb\formie\models\Notification;

it('migrates deferred site titles and legacy override keys without losing translations on rerun', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multisite content contract; exercised by the default and upgrade lanes.');
    }
    $siteId = (int)Craft::$app->getSites()->getAllSiteIds()[1];
    $form = formie()->form(['title' => 'Original source title'])->singleLineTextField('message')->create();
    $field = $form->getFieldByHandle('message');
    $page = $form->getPages()[0];
    $notification = new Notification(['formId' => $form->id, 'name' => 'Receipt', 'handle' => 'receipt',
        'subject' => 'Source subject', 'to' => 'migration@example.test', 'enabled' => false]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    $notificationUid = (string)(new Query())->select('uid')->from(Table::FORMIE_NOTIFICATIONS)
        ->where(['id' => $notification->id])->scalar();
    expect($notificationUid)->not->toBeEmpty();

    // Write historical keys directly so runtime normalization cannot hide migration failures.
    Db::insert(Table::FORMIE_FORM_SITE_OVERRIDES, [
        'formId' => $form->id, 'siteId' => $siteId,
        'overrides' => [
            'title' => 'Titre local',
            'pages' => [(string)$page->id => ['label' => 'Page locale']],
            'fieldInstanceOverrides' => [$field->uid => ['required' => true]],
            'notifications' => [$notificationUid => ['subject' => 'Sujet local']],
        ],
    ]);
    Db::update('{{%elements_sites}}', ['title' => 'Stale title'], ['elementId' => $form->id, 'siteId' => $siteId]);
    $migration = new m261016_000000_migrate_form_site_content();
    $snapshot = null;

    foreach ([1, 2] as $run) {
        expect($migration->safeUp())->toBeTrue();
        $overrides = Json::decode((new Query())->select('overrides')->from(Table::FORMIE_FORM_SITE_OVERRIDES)
            ->where(['formId' => $form->id, 'siteId' => $siteId])->scalar());
        expect($overrides)->toEqual([
            'title' => 'Titre local',
            'pages' => [$page->uid => ['label' => 'Page locale']],
            'fieldInstanceOverrides' => [$field->reference => ['required' => true]],
            'notifications' => ['receipt' => ['subject' => 'Sujet local']],
        ]);
        $titles = (new Query())->select(['siteId', 'title'])->from('{{%elements_sites}}')
            ->where(['elementId' => $form->id])->orderBy('siteId')->all();
        expect(array_column($titles, 'title', 'siteId')[$siteId])->toBe('Titre local');
        expect(array_column($titles, 'title', 'siteId')[$form->sourceSiteId])->toBe('Original source title');

        if ($snapshot !== null) {
            expect($titles)->toBe($snapshot);
        }
        $snapshot = $titles;
    }
});

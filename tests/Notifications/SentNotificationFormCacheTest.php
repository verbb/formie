<?php

declare(strict_types=1);

use craft\elements\db\ElementQuery;
use craft\elements\User;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\elements\SentNotification;
use yii\base\Event;

it('looks up each form once when rendering a page of sent notifications', function (): void {
    $forms = [
        'first' => formie()->form(['title' => 'Sent cache first'])->singleLineTextField('message')->create(),
        'second' => formie()->form(['title' => 'Sent cache second'])->singleLineTextField('message')->create(),
        'trashed' => formie()->form(['title' => 'Sent cache trashed'])->singleLineTextField('message')->create(),
    ];
    $rowsPerForm = ['first' => 4, 'second' => 2, 'trashed' => 2];
    $ids = [];

    foreach ($rowsPerForm as $key => $count) {
        for ($i = 1; $i <= $count; $i++) {
            $notice = new SentNotification([
                'title' => "{$key} notice {$i}",
                'formId' => (string)$forms[$key]->id,
                'subject' => 'Subject',
                'to' => 'cache@example.test',
                'success' => true,
            ]);

            expect(Craft::$app->getElements()->saveElement($notice))->toBeTrue();
            $ids[] = $notice->id;
        }
    }

    // A trashed form keeps its sent notifications, which show no form, as before.
    expect(Craft::$app->getElements()->deleteElement($forms['trashed']))->toBeTrue();

    // Start from a cold request cache, as a fresh index request does.
    if (property_exists(SentNotification::class, '_formByIdCache')) {
        (new ReflectionProperty(SentNotification::class, '_formByIdCache'))->setValue(null, []);
    }

    $formIds = array_map(static fn($form) => (int)$form->id, $forms);
    $lookups = array_fill_keys(array_keys($forms), 0);
    $handler = static function(Event $event) use ($formIds, &$lookups): void {
        $id = $event->sender->id;
        $id = is_array($id) && count($id) === 1 ? reset($id) : $id;

        if (is_numeric($id) && ($key = array_search((int)$id, $formIds, true)) !== false) {
            $lookups[$key]++;
        }
    };

    $previousIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
    Event::on(FormQuery::class, ElementQuery::EVENT_AFTER_PREPARE, $handler);

    try {
        $rows = SentNotification::find()->id($ids)->orderBy(['elements.id' => SORT_ASC])->all();
        $rendered = [];

        foreach ($rows as $row) {
            $rendered[] = [$row->getAttributeHtml('form'), str_contains($row->getAttributeHtml('resend'), 'Resend')];
        }
    } finally {
        Event::off(FormQuery::class, ElementQuery::EVENT_AFTER_PREPARE, $handler);
        Craft::$app->getUser()->setIdentity($previousIdentity);
    }

    expect($rows)->toHaveCount(8)
        ->and($lookups)->toBe(['first' => 1, 'second' => 1, 'trashed' => 1])
        ->and($rendered)->toBe([
            ['Sent cache first', true],
            ['Sent cache first', true],
            ['Sent cache first', true],
            ['Sent cache first', true],
            ['Sent cache second', true],
            ['Sent cache second', true],
            ['-', true],
            ['-', true],
        ])
        ->and($rows[0]->getForm())->toBe($rows[3]->getForm())
        ->and($rows[0]->getForm()?->id)->toBe($forms['first']->id)
        ->and($rows[4]->getForm()?->id)->toBe($forms['second']->id)
        ->and($rows[6]->getForm())->toBeNull();
});

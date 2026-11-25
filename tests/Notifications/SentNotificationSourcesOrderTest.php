<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\elements\SentNotification;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

it('lists sent notification form sources by title within their form groups', function(): void {
    $suffix = bin2hex(random_bytes(4));
    $group = new FormGroup(['name' => 'Sources ' . $suffix, 'handle' => 'sources' . $suffix]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    // Created out of title order, so the default newest-first form query can't pass by accident.
    $groupedZulu = formie()->form(['title' => "Zulu {$suffix}", 'groupId' => $group->id])->singleLineTextField('message')->create();
    $groupedAlpha = formie()->form(['title' => "alpha {$suffix}", 'groupId' => $group->id])->singleLineTextField('message')->create();
    $ungroupedMike = formie()->form(['title' => "Mike {$suffix}"])->singleLineTextField('message')->create();
    $ungroupedBravo = formie()->form(['title' => "Bravo {$suffix}"])->singleLineTextField('message')->create();

    $previousIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

    try {
        $sources = (new ReflectionMethod(SentNotification::class, 'defineSources'))->invoke(null, 'index');
    } finally {
        Craft::$app->getUser()->setIdentity($previousIdentity);
    }

    $positions = [];

    foreach (array_values($sources) as $index => $source) {
        $positions[$source['key'] ?? 'heading:' . $source['heading']] = $index;
    }

    $groupHeading = $positions['heading:' . $group->name] ?? null;
    $ungroupedHeading = $positions['heading:Ungrouped'] ?? null;

    expect($groupHeading)->not->toBeNull()
        ->and($ungroupedHeading)->not->toBeNull()
        ->and($positions['form:' . $groupedAlpha->id])->toBeGreaterThan($groupHeading)
        ->and($positions['form:' . $groupedZulu->id])->toBeGreaterThan($positions['form:' . $groupedAlpha->id])
        ->and($positions['form:' . $groupedZulu->id])->toBeLessThan($ungroupedHeading)
        ->and($positions['form:' . $ungroupedBravo->id])->toBeGreaterThan($ungroupedHeading)
        ->and($positions['form:' . $ungroupedMike->id])->toBeGreaterThan($positions['form:' . $ungroupedBravo->id]);
});

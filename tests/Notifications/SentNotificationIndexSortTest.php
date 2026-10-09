<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\elements\SentNotification;

it('sorts sent notification index sources newest first by default', function (): void {
    formie()->form()->singleLineTextField('message')->create();

    $previousIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

    try {
        $sources = (new ReflectionMethod(SentNotification::class, 'defineSources'))->invoke(null, 'index');
        $indexSources = [];

        foreach ($sources as $source) {
            if (isset($source['key'])) {
                $indexSources[] = $source;
            }
        }

        expect($indexSources)->not->toBeEmpty();

        foreach ($indexSources as $source) {
            expect($source['defaultSort'] ?? null)->toBe(['dateCreated', 'desc']);
        }
    } finally {
        Craft::$app->getUser()->setIdentity($previousIdentity);
    }
});

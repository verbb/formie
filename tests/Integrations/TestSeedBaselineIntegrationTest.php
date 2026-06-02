<?php

declare(strict_types=1);

use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;

it('ensures baseline test seed fixtures are available', function (): void {
    $userGroup = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');
    $tagGroup = Craft::$app->getTags()->getTagGroupByHandle('formieTestTags');
    $categoryGroup = Craft::$app->getCategories()->getGroupByHandle('formieTestCategories');
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('formieTestUploads');

    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    $seedTag = Tag::find()->status(null)->title('Formie Seed Tag')->one();
    $seedCategory = Category::find()->status(null)->title('Formie Seed Category')->one();
    $seedEntry = Entry::find()->status(null)->slug('formie-seed-entry')->one();

    expect($userGroup)->not->toBeNull()
        ->and($tagGroup)->not->toBeNull()
        ->and($categoryGroup)->not->toBeNull()
        ->and($volume)->not->toBeNull()
        ->and($seedUser)->not->toBeNull()
        ->and($seedTag)->not->toBeNull()
        ->and($seedCategory)->not->toBeNull()
        ->and($seedEntry)->not->toBeNull();
});

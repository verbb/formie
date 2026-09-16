<?php

declare(strict_types=1);

use craft\helpers\Db;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;

it('enforces persisted unique values at their nested location', function (string $placement, string $value): void {
    $builder = formie()->form();
    if ($placement === 'root') {
        $builder->singleLineTextField('uniqueName', ['uniqueValue' => true]);
        $values = ['uniqueName' => $value];
    } else {
        $method = $placement === 'group' ? 'groupField' : 'repeaterField';
        $builder->$method('details', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'uniqueName', 'label' => 'Unique name', 'uniqueValue' => true]]]]]);
        $values = ['details' => $placement === 'group' ? ['uniqueName' => $value] : [['uniqueName' => $value]]];
    }
    $form = $builder->create();
    $first = formie()->submission($form)->with($values)->save();
    $loaded = Submission::find()->id($first->id)->one();
    expect($loaded->validate())->toBeTrue();
    $duplicate = new Submission(['title' => 'Duplicate']);
    $duplicate->setForm($form);
    $duplicate->setFieldValues($values);
    expect($duplicate->validate())->toBeFalse();
    expect($duplicate->getErrors())->not->toBeEmpty();
    Db::update('{{%formie_submissions}}', ['isIncomplete' => true], ['id' => $first->id]);
    expect($duplicate->validate())->toBeTrue();
    Db::update('{{%formie_submissions}}', ['isIncomplete' => false], ['id' => $first->id]);
    expect(Craft::$app->getElements()->deleteElement($first))->toBeTrue();
    expect($duplicate->validate())->toBeTrue();
    expect(Craft::$app->getElements()->saveElement($duplicate))->toBeTrue();
})->with(['root', 'group', 'repeater'])->with(['taken-name', '0']);

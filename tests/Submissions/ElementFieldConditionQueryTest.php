<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\elements\Submission;
use verbb\formie\elements\conditions\SubmissionCondition;
use verbb\formie\fields\conditions\ElementFieldConditionRule;

it('matches relation emptiness consistently in queries and loaded submissions', function (string $operator): void {
    $name = 'conditionRelation' . bin2hex(random_bytes(5));
    $deleted = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($deleted))->toBeTrue();
    $active = User::find()->admin(true)->one();
    $form = formie()->form()->usersField('people')->create();
    $ids = [];
    foreach (['filled' => [$active->id], 'empty' => [], 'null' => null, 'deleted' => [$deleted->id]] as $label => $value) {
        $ids[$label] = formie()->submission($form)->with(['people' => $value])->save()->id;
    }
    expect(Craft::$app->getElements()->deleteElement($deleted))->toBeTrue();
    $condition = new SubmissionCondition(Submission::class, ['sourceKey' => 'form:' . $form->id]);
    $rule = $condition->createConditionRule([
        'class' => ElementFieldConditionRule::class, 'fieldUid' => $form->getFieldByHandle('people')->uid, 'operator' => $operator,
    ]);
    $condition->setConditionRules([$rule]);
    $expected = $operator === 'empty' ? array_values(array_diff($ids, [$ids['filled']])) : [$ids['filled']];
    $matched = [];
    foreach (Submission::find()->id($ids)->orderBy('id')->all() as $submission) {
        if ($rule->matchElement($submission)) { $matched[] = $submission->id; }
    }
    expect($matched)->toBe($expected);
    $query = Submission::find()->id($ids)->orderBy('id');
    $condition->modifyQuery($query);
    expect($query->ids())->toBe($expected);
})->with(['empty', 'notempty']);

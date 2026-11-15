<?php

declare(strict_types=1);

use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ConditionsHelper;

function datePartConditions(string $reference): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => '{field:' . $reference . '}', 'condition' => '<', 'value' => '2008']]];
}

it('keeps answers that depend on a Date dropdown part in the same Repeater row', function() {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [
        ['type' => Date::class, 'handle' => 'born', 'label' => 'Born', 'displayType' => 'dropdowns', 'includeDate' => true],
        ['type' => SingleLineText::class, 'handle' => 'phone', 'label' => 'Phone'],
    ]]]])->create();
    $people = $form->getFieldByHandle('people');
    $phone = $people->getFieldByHandle('phone');
    $phone->enableConditions = true;
    $phone->conditions = datePartConditions((string)$people->getFieldByHandle('born')->getFieldByHandle('year')->reference);

    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValues(['people' => [
        ['born' => ['year' => '2000', 'month' => '1', 'day' => '1'], 'phone' => 'kept'],
        ['born' => ['year' => '2015', 'month' => '1', 'day' => '1'], 'phone' => 'cleared'],
    ]]);

    (new ConditionVisibility())->clear($submission);

    expect($submission->getFieldValue('people.0.phone'))->toBe('kept')
        ->and($submission->getFieldValue('people.1.phone'))->toBe('');
});

it('compares a Date dropdown part outside a Repeater', function() {
    $form = formie()->form()
        ->dateField('born', ['displayType' => 'dropdowns'])
        ->create();
    $year = $form->getFieldByHandle('born')->getFieldByHandle('year');
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValue('born', ['year' => '2000', 'month' => '1', 'day' => '1']);

    expect(ConditionsHelper::evaluate(datePartConditions((string)$year->reference), $submission)->value)->toBeTrue();

    $submission->setFieldValue('born', ['year' => '2015', 'month' => '1', 'day' => '1']);

    expect(ConditionsHelper::evaluate(datePartConditions((string)$year->reference), $submission)->value)->toBeFalse();
});

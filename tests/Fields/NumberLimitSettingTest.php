<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

it('validates number bounds only while the persisted limit setting is enabled', function (string $value, bool $limitedValid): void {
    $form = formie()->form()->numberField('quantity', ['limit' => true, 'min' => 5, 'max' => 10])->create();
    $make = function (Form $form) use ($value): Submission {
        $submission = new Submission(['title' => 'Number bounds']);
        $submission->setForm($form);
        $submission->setFieldValue('quantity', $value);
        return $submission;
    };
    $limited = $make($form);
    expect($limited->validate())->toBe($limitedValid);
    $form->getFieldByHandle('quantity')->limit = false;
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $reloaded = Form::find()->withoutCpIndexScope()->id($form->id)->siteId($form->siteId)->status(null)->one();
    expect($reloaded->getFieldByHandle('quantity')->limit)->toBeFalse();
    $unlimited = $make($reloaded);
    $numeric = is_numeric($value);
    expect($unlimited->validate())->toBe($numeric);
    if ($numeric) {
        expect(Craft::$app->getElements()->saveElement($unlimited))->toBeTrue();
        expect(Submission::find()->id($unlimited->id)->one()->getFieldValue('quantity'))->toBe($value);
    }
})->with(['below' => ['2', false], 'zero' => ['0', false], 'within' => ['7', true], 'above' => ['12', false], 'invalid' => ['not numeric', false]]);

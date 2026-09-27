<?php

use verbb\formie\conditions\ConditionGraph;
use verbb\formie\conditions\ConditionMigration;
use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Email;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SubmissionErrors;

function authorityCondition(string $source, string $effect = 'show', bool $legacy = true): array
{
    return ['version' => 1, 'showRule' => $effect, 'conditionRule' => 'all', 'conditions' => [['field' => $source, 'condition' => '=', 'value' => 'yes', 'legacyForward' => $legacy]]];
}

it('clears attacker values in each repeater row according to that row source', function () {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'allow', 'label' => 'Allow'],
        ['type' => Email::class, 'handle' => 'email', 'label' => 'Email', 'required' => true, 'enableConditions' => true, 'conditions' => authorityCondition('people.allow')],
    ]]]])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValues(['people' => [['allow' => 'no', 'email' => 'attacker@example.test'], ['allow' => 'yes', 'email' => 'retained@example.test']]]);
    (new ConditionVisibility())->clear($submission);
    expect($submission->getFieldValue('people.0.email'))->toBe('')
        ->and($submission->getFieldValue('people.1.email'))->toBe('retained@example.test')
        ->and($submission->validate())->toBeTrue(json_encode($submission->getErrors()));
});

it('clears children when a group is hidden without retaining posted child content', function () {
    $form = formie()->form()->singleLineTextField('allow')->groupField('contact', ['enableConditions' => true, 'conditions' => authorityCondition('allow'), 'rows' => [['fields' => [['type' => Email::class, 'handle' => 'email', 'label' => 'Email']]]]])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValues(['allow' => 'no', 'contact' => ['email' => 'attacker@example.test']]);
    (new ConditionVisibility())->clear($submission);
    expect($submission->getFieldValue('contact.email'))->toBe('');
});

it('orders legacy dependencies and rejects new forward dependencies and cycles', function () {
    $form = formie()->form()->singleLineTextField('first')->singleLineTextField('second')->create();
    $first = $form->getFieldByHandle('first');
    $second = $form->getFieldByHandle('second');
    $first->enableConditions = true;
    $first->conditions = authorityCondition('second');
    expect(array_map(fn($field) => $field->handle, (new ConditionGraph())->orderedFields($form)))->toBe(['second', 'first']);
    $first->conditions = authorityCondition('second', legacy: false);
    expect(fn() => (new ConditionGraph())->orderedFields($form))->toThrow(RuntimeException::class, 'preceding field');
    $first->conditions = authorityCondition('second');
    $second->enableConditions = true;
    $second->conditions = authorityCondition('first');
    expect(fn() => (new ConditionGraph())->orderedFields($form))->toThrow(RuntimeException::class, 'cycle');
    expect($form->validate())->toBeFalse();
});

it('preserves instance roots nested paths page summaries and safe text from all Yii key shapes', function () {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [['type' => Email::class, 'handle' => 'email', 'label' => 'Email']]]]])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $root = $form->getFieldByHandle('people');
    $submission->addError('field:people.0.email', '<b>Invalid email.</b>');
    $submission->addError('fields[people][1][email]', 'Second error.');
    $submission->addError('form', '<a href="https://example.test">Retry.</a>');
    $errors = SubmissionErrors::fromSubmission($submission);
    expect($errors->toClient())->toBe(['form' => ['Retry.'], 'fields' => [$root->id . '.0.email' => ['Invalid email.'], $root->id . '.1.email' => ['Second error.']]])
        ->and($errors->toLegacy())->toBe(['people.0.email' => ['Invalid email.'], 'people.1.email' => ['Second error.'], 'form' => ['Retry.']])
        ->and($errors->firstPageId())->toBe($form->getPages()[0]->id)
        ->and($errors->forPage($form->getPages()[0]->id))->toHaveCount(2);
});

it('migrates Formie 3 conditions idempotently and retains unknown rules for diagnosis', function () {
    $legacy = ['conditions' => ['conditionRule' => 'any', 'showRule' => 'hide', 'conditions' => [['field' => 'a', 'condition' => 'equals', 'value' => 'yes'], ['field' => 'b', 'condition' => 'unknown']]]];
    $migrated = ConditionMigration::migrate($legacy);
    expect($migrated['conditions']['version'])->toBe(1)
        ->and($migrated['conditions']['conditions'][0]['condition'])->toBe('=')
        ->and($migrated['conditions']['conditions'][0]['legacyForward'])->toBeTrue()
        ->and($migrated['conditions']['conditions'][1]['condition'])->toBe('unknown')
        ->and(ConditionMigration::migrate($migrated))->toBe($migrated);
});

it('blocks target tampering before screening and persistence while keeping the current page', function () {
    $form = formie()->form()->multiPage(3)->onPage(1)->singleLineTextField('first')->onPage(2)->singleLineTextField('second')->onPage(3)->singleLineTextField('third')->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $pages = $form->getPages();
    $seen = [];
    $observe = function ($event) use (&$seen) { $seen[] = $event->stage; };
    \yii\base\Event::on(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $response = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'navigation' => \verbb\formie\enums\NavigationIntent::TARGET, 'pageId' => $pages[0]->id, 'targetPageId' => $pages[2]->id]));
        expect($response->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::VALIDATION_FAILED)
            ->and($form->getCurrentPage()->id)->toBe($pages[0]->id)
            ->and($submission->getErrors('form'))->not->toBeEmpty()
            ->and($submission->id)->toBeNull()->and($seen)->toBe(['preflight', 'validate']);
    } finally {
        \yii\base\Event::off(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
});

it('does not invert invalid button conditions into permission and exports the button rule', function () {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $page = $form->getPages()[0];
    $page->getPageSettings()->enableNextButtonConditions = true;
    $page->getPageSettings()->nextButtonConditions = authorityCondition('missing', 'hide');
    $definition = $page->getClientRenderedDefinition($form, 0);
    expect($definition['actions']['primary']['condition']['rules'])->toHaveCount(1);
    $submission = new Submission(['title' => 'Conditions test']);
    $response = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));
    expect($response->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::VALIDATION_FAILED)->and($submission->id)->toBeNull();
});

it('distinguishes invalid notification configuration from false conditions without sending', function () {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValue('answer', 'no');
    $notification = new \verbb\formie\models\Notification(['enableConditions' => true, 'conditions' => authorityCondition('missing')]);
    $invalid = \verbb\formie\Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission);
    expect($invalid['status'])->toBe('rejected')->and($invalid['success'])->toBeFalse();
    $notification->conditions = authorityCondition('answer');
    $skipped = \verbb\formie\Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission);
    expect($skipped['status'])->toBe('skipped')->and($skipped['success'])->toBeTrue();
});

it('returns false for errors added after parent submission validation', function () {
    $form = formie()->form()->settings(['requireUser' => true])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    expect($submission->validate())->toBeFalse()->and($submission->getErrors('form'))->not->toBeEmpty();
});

it('upgrades stored conditions idempotently without rewriting the stable boundary shape', function () {
    $form = formie()->form()->singleLineTextField('source')->singleLineTextField('target', ['enableConditions' => true, 'conditions' => ['conditionRule' => 'all', 'showRule' => 'show', 'conditions' => [['field' => 'source', 'condition' => 'equals', 'value' => 'yes']]]])->create();
    $migration = new \verbb\formie\migrations\m260927_020000_condition_schema();
    ob_start();
    try {
        expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue();
    } finally { ob_end_clean(); }
    $reloaded = \verbb\formie\elements\Form::find()->id($form->id)->one();
    $settings = $reloaded->getFieldByHandle('target')->getConditions();
    expect($settings['version'])->toBe(1)->and($settings['conditions'][0]['condition'])->toBe('=')->and($settings['conditions'][0]['legacyForward'])->toBeTrue();
});

it('orders collection dependencies after hidden descendants and rejects collection cycles', function () {
    $form = formie()->form()->singleLineTextField('allow')->groupField('contact', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'answer', 'label' => 'Answer', 'enableConditions' => true, 'conditions' => authorityCondition('allow')],
    ]]]])->singleLineTextField('target', ['enableConditions' => true, 'conditions' => authorityCondition('contact')])->create();
    $order = array_map(fn($field) => $field->handle, (new ConditionGraph())->orderedFields($form));
    expect(array_search('answer', $order))->toBeLessThan(array_search('target', $order));
    $child = $form->getFieldByHandle('contact')->getFields()[0];
    $child->conditions = authorityCondition('target');
    expect(fn() => (new ConditionGraph())->orderedFields($form))->toThrow(RuntimeException::class, 'cycle');
});

it('keeps disabled effects distinct from visibility while clearing and excluding validation', function () {
    $form = formie()->form()->singleLineTextField('allow')->emailField('email', ['required' => true, 'enableConditions' => true, 'conditions' => authorityCondition('allow', 'enable')])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValues(['allow' => 'no', 'email' => 'invalid attacker value']);
    $submission->setScenario(\craft\base\Element::SCENARIO_LIVE);
    $field = $form->getFieldByHandle('email');
    expect(ConditionVisibility::hidden($field, $submission))->toBeFalse()->and(ConditionVisibility::disabled($field, $submission))->toBeTrue();
    (new ConditionVisibility())->clear($submission);
    expect($submission->getFieldValue('email'))->toBe('')->and($submission->validate())->toBeTrue();
    $submission->setFieldValue('allow', 'yes');
    expect(ConditionVisibility::disabled($field, $submission))->toBeFalse()->and($submission->validate())->toBeFalse();
});

it('normalizes stable condition context selectors through the shared reference runtime', function () {
    $form = formie()->form(['title' => 'Legacy context'])->create();
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $settings = authorityCondition('{submission:formName}');
    $settings['conditions'][0]['value'] = 'Legacy context';
    expect(\verbb\formie\helpers\ConditionsHelper::evaluate($settings, $submission)->value)->toBeTrue();
    $compiled = (new \verbb\formie\conditions\ConditionCompiler())->compile(\verbb\formie\conditions\ConditionSet::fromArray($settings), $form);
    expect($compiled['rules'][0]['source']['target'])->toBe('form')->and($compiled['rules'][0]['browserSafe'])->toBeFalse();
});

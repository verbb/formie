<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Recipients;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\References;

function recipientRuleOptions(): array
{
    return [
        ['label' => 'Choose', 'value' => ''],
        ['label' => 'Sales', 'value' => 'sales@example.test'],
        ['label' => 'Support', 'value' => 'support@example.test'],
    ];
}

function recipientRules(string $field, string $operator, mixed $value): array
{
    return ['showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['id' => 'new1', 'field' => $field, 'condition' => $operator, 'value' => $value]]];
}

function recipientRuleSubmission(Form $form, array $values): Submission
{
    $submission = new Submission(['title' => 'Recipient rule']);
    $submission->setForm($form);

    foreach ($values as $handle => $value) {
        $submission->setFieldValue($handle, $value);
    }

    return $submission;
}

function recipientRuleForm(array $conditions): Form
{
    $factory = formie()->form()
        ->recipientsField('department', ['displayType' => 'dropdown', 'options' => recipientRuleOptions()])
        ->recipientsField('teams', ['displayType' => 'checkboxes', 'options' => recipientRuleOptions()])
        ->dropdownField('plainChoice', ['options' => [['label' => 'Id one', 'value' => 'id:1'], ['label' => 'Other', 'value' => 'other']]]);

    foreach ($conditions as $handle => $rules) {
        $factory = $factory->singleLineTextField($handle, ['enableConditions' => true, 'conditions' => $rules]);
    }

    return $factory->create();
}

// Every expectation here is what the Formie 2 evaluator returned for the same saved rule and submitted value.
it('keeps the visibility a saved option index had before upgrading', function(string $handle, string $operator, string $value, array $submitted, bool $hidden): void {
    $form = recipientRuleForm(['note' => recipientRules('{field:' . $handle . '}', $operator, $value)]);
    $submission = recipientRuleSubmission($form, $submitted);

    expect($form->getFieldByHandle('note')->isConditionallyHidden($submission))->toBe($hidden);
})->with([
    'selected option' => ['department', '=', 'id:1', ['department' => 'sales@example.test'], false],
    'another option' => ['department', '=', 'id:2', ['department' => 'sales@example.test'], true],
    'selected option, negated' => ['department', '!=', 'id:1', ['department' => 'sales@example.test'], true],
    'another option, negated' => ['department', '!=', 'id:1', ['department' => 'support@example.test'], false],
    'placeholder with nothing selected' => ['department', '=', 'id:0', [], false],
    'option with nothing selected' => ['department', '=', 'id:1', [], true],
    'removed option' => ['department', '=', 'id:9', ['department' => 'sales@example.test'], true],
    'removed option, negated' => ['department', '!=', 'id:9', ['department' => 'sales@example.test'], false],
    'selected checkbox' => ['teams', 'contains', 'id:2', ['teams' => ['support@example.test']], false],
    'unselected checkbox' => ['teams', 'contains', 'id:1', ['teams' => ['support@example.test']], true],
    'unselected checkbox, negated' => ['teams', 'notContains', 'id:1', ['teams' => ['support@example.test']], false],
    'other field value that looks like an index' => ['plainChoice', '=', 'id:1', ['plainChoice' => 'id:1'], false],
    'other field value that differs' => ['plainChoice', '=', 'id:1', ['plainChoice' => 'other'], true],
]);

it('compares the address the builder saves for a choice', function(string $operator, string $selected, bool $hidden): void {
    $form = recipientRuleForm(['note' => recipientRules('{field:department}', $operator, 'sales@example.test')]);
    $submission = formie()->submission($form)->with(['department' => $selected])->save();

    expect($form->getFieldByHandle('note')->isConditionallyHidden($submission))->toBe($hidden);
})->with([
    ['=', 'sales@example.test', false],
    ['=', 'support@example.test', true],
    ['!=', 'support@example.test', false],
]);

it('sends the browser the token rendered for that option, never its address', function(string $stored): void {
    $form = recipientRuleForm(['note' => recipientRules('{field:department}', '=', $stored)]);
    $department = $form->getFieldByHandle('department');
    $token = $department->getFieldOptions()[1]['value'];

    expect($token)->toStartWith('recipient:v1:')
        ->and($form->getFieldByHandle('note')->getBrowserConditions()['rules'][0]['value'])->toBe($token)
        ->and($form->getFieldByHandle('note')->getBrowserConditionsJson())->not->toContain('sales@example.test');
})->with(['id:1', 'sales@example.test']);

it('still treats a signed token as the rule and rejects everything else', function(): void {
    $form = recipientRuleForm([]);
    $reference = $form->getFieldByHandle('department')->reference;
    $submission = formie()->submission($form)->with(['department' => 'sales@example.test'])->save();
    $matches = fn(mixed $value): bool => ConditionsHelper::getConditionalTestResult(recipientRules(References::field($reference), '=', $value), $submission);
    $tokens = array_column($form->getFieldByHandle('department')->getFieldOptions(), 'value');

    expect($matches($tokens[1]))->toBeTrue()
        ->and($matches($tokens[2]))->toBeFalse()
        ->and($matches('recipient:v1:' . str_repeat('0', 64)))->toBeFalse()
        ->and($matches('sales'))->toBeFalse();
});

it('does not let a submitted option index satisfy a rule', function(string $submitted): void {
    $form = recipientRuleForm(['note' => recipientRules('{field:department}', '=', 'id:1')]);
    $submission = recipientRuleSubmission($form, ['department' => $submitted]);

    expect($form->getFieldByHandle('note')->isConditionallyHidden($submission))->toBeTrue();
})->with(['id:1', 'id:2']);

it('keeps matching when the option is relabelled or the security key changes', function(): void {
    $form = recipientRuleForm(['note' => recipientRules('{field:department}', '=', 'id:1')]);
    $submission = formie()->submission($form)->with(['department' => 'sales@example.test'])->save();
    $field = $form->getFieldByHandle('note');
    $department = $form->getFieldByHandle('department');
    $general = Craft::$app->getConfig()->getGeneral();
    $key = $general->securityKey;

    try {
        $general->securityKey = 'a-rotated-security-key';

        expect($field->isConditionallyHidden($submission))->toBeFalse();
    } finally {
        $general->securityKey = $key;
    }

    $options = recipientRuleOptions();
    $options[1]['label'] = 'Sales team';
    $department->options = $options;
    $submission->setFieldValue('department', ['value' => 'sales@example.test', 'label' => 'Sales team']);

    expect($field->isConditionallyHidden($submission))->toBeFalse();
});

it('reads a saved option index on Recipients inside Groups and Repeaters', function(): void {
    $child = ['type' => Recipients::class, 'handle' => 'desk', 'label' => 'Desk', 'settings' => ['displayType' => 'dropdown', 'options' => recipientRuleOptions()]];
    $form = formie()->form()
        ->groupField('company', ['rows' => [['fields' => [$child]]]])
        ->repeaterField('offices', ['rows' => [['fields' => [$child]]]])
        ->create();
    $groupDesk = $form->getFieldByHandle('company')->getFieldByHandle('desk');
    $rowDesk = $form->getFieldByHandle('offices')->getFieldByHandle('desk');
    $submission = formie()->submission($form)->with([
        'company' => ['desk' => 'sales@example.test'],
        'offices' => [['desk' => 'support@example.test']],
    ])->save();
    $matches = fn(string $token, string $operator, string $value): bool => ConditionsHelper::getConditionalTestResult(recipientRules($token, $operator, $value), $submission);

    expect($matches(References::field($groupDesk->reference), '=', 'id:1'))->toBeTrue()
        ->and($matches(References::field($groupDesk->reference), '=', 'id:2'))->toBeFalse()
        ->and($matches('{field:' . $rowDesk->reference . ';scope=all}', 'contains', 'id:2'))->toBeTrue()
        ->and($matches('{field:' . $rowDesk->reference . ';scope=all}', 'contains', 'id:1'))->toBeFalse();
});

it('reads a saved option index in notification conditions', function(): void {
    $form = recipientRuleForm([]);
    $reference = $form->getFieldByHandle('department')->reference;
    $submission = formie()->submission($form)->with(['department' => 'support@example.test'])->save();

    $routed = ConditionsHelper::evaluateConditions([
        ['field' => References::field($reference), 'condition' => '=', 'value' => 'id:1', 'email' => 'first@example.test'],
        ['field' => References::field($reference), 'condition' => '=', 'value' => 'id:2', 'email' => 'second@example.test'],
    ], $submission, fn($result, $condition) => $result ? $condition['email'] : null);

    expect($routed)->toBe(['second@example.test']);
});

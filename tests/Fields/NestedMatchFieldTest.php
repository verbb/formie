<?php

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Email;
use verbb\formie\fields\Password;
use verbb\formie\helpers\References;

function nestedMatchForm(): Form
{
    return formie()->form()
        ->groupField('contact', ['rows' => [['fields' => [
            ['type' => Email::class, 'handle' => 'home', 'label' => 'Home'],
            ['type' => Email::class, 'handle' => 'homeCopy', 'label' => 'Home copy'],
        ]]]])
        ->repeaterField('people', ['rows' => [['fields' => [
            ['type' => Email::class, 'handle' => 'email', 'label' => 'Email'],
            ['type' => Email::class, 'handle' => 'emailCopy', 'label' => 'Email copy'],
        ]]]])
        ->emailField('confirmation', ['label' => 'Confirmation'])
        ->create();
}

function nestedMatchFieldAt(Form $form, string $path): \verbb\formie\base\Field
{
    $parts = explode('.', $path);
    $field = $form->getFieldByHandle(array_shift($parts));

    foreach ($parts as $part) {
        $field = $field->getFieldByHandle($part);
    }

    return $field;
}

function saveNestedMatchField(Form $form, string $path, string $stored): Form
{
    nestedMatchFieldAt($form, $path)->matchField = $stored;
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));
    \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->status(null)->one();
}

function nestedMatchReference(Form $form, string $path, array $metadata = []): string
{
    return References::field((string)nestedMatchFieldAt($form, $path)->reference, null, $metadata);
}

function nestedMatchErrors(Form $form, array $values): array
{
    $submission = new Submission(['title' => 'Nested match']);
    $submission->setForm($form);
    $submission->setFieldValues($values);
    $submission->validate();

    return array_filter($submission->getErrors(), static fn($key) => in_array(explode('.', (string)$key)[0], ['contact', 'people', 'confirmation', 'first', 'second'], true), ARRAY_FILTER_USE_KEY);
}

it('compares a top-level field with a target in a Group', function(?string $stored) {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, 'confirmation', $stored ?? nestedMatchReference($form, 'contact.home'));
    $field = $form->getFieldByHandle('confirmation');

    expect($field->getMatchField())->toBe('contact.home')
        ->and(nestedMatchErrors($form, ['contact' => ['home' => 'same@example.test'], 'confirmation' => 'same@example.test']))->toBe([])
        ->and(nestedMatchErrors($form, ['contact' => ['home' => 'first@example.test'], 'confirmation' => 'other@example.test']))->toBe(['confirmation' => ['Confirmation must match Home.']])
        ->and(nestedMatchErrors($form, []))->toBe([]);
})->with([
    'current reference' => [null],
    'Formie 3 path' => ['{field:contact.home}'],
    'Formie 2 path' => ['{field.contact.home}'],
]);

it('compares fields within the same Group', function() {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, 'contact.homeCopy', nestedMatchReference($form, 'contact.home'));

    expect(nestedMatchErrors($form, ['contact' => ['home' => 'same@example.test', 'homeCopy' => 'same@example.test']]))->toBe([])
        ->and(nestedMatchErrors($form, ['contact' => ['home' => 'first@example.test', 'homeCopy' => 'other@example.test']]))->toBe(['contact.homeCopy' => ['Home copy must match Home.']]);
});

it('gives the browser the Group path that rendered fields use', function() {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, 'confirmation', nestedMatchReference($form, 'contact.home'));
    $rules = $form->getFieldByHandle('confirmation')->browserValidationRules();
    $match = array_values(array_filter($rules, static fn($rule) => $rule['type'] === 'match'));

    expect($match)->toHaveCount(1)
        ->and($match[0]['fieldHandle'])->toBe(nestedMatchFieldAt($form, 'contact.home')->valueKey())
        ->and($match[0]['messages']['match'])->toContain('Home');
});

it('compares fields within the same Repeater row', function() {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, 'people.emailCopy', nestedMatchReference($form, 'people.email', ['scope' => 'first']));

    expect(nestedMatchErrors($form, ['people' => [
        ['email' => 'first@example.test', 'emailCopy' => 'first@example.test'],
        ['email' => 'second@example.test', 'emailCopy' => 'second@example.test'],
    ]]))->toBe([]);

    $errors = nestedMatchErrors($form, ['people' => [
        ['email' => 'first@example.test', 'emailCopy' => 'first@example.test'],
        ['email' => 'second@example.test', 'emailCopy' => 'first@example.test'],
    ]]);

    expect(array_keys($errors))->toBe(['people.1.emailCopy'])
        ->and($errors['people.1.emailCopy'])->toBe(['Email copy must match Email.']);
});

it('compares a field outside a Repeater with its first row', function() {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, 'confirmation', nestedMatchReference($form, 'people.email', ['scope' => 'first']));
    $people = [['email' => 'first@example.test'], ['email' => 'second@example.test']];

    expect(nestedMatchErrors($form, ['people' => $people, 'confirmation' => 'first@example.test']))->toBe([])
        ->and(nestedMatchErrors($form, ['people' => $people, 'confirmation' => 'second@example.test']))->toBe(['confirmation' => ['Confirmation must match Email.']])
        ->and(nestedMatchErrors($form, ['confirmation' => 'first@example.test']))->toBe(['confirmation' => ['Confirmation must match Email.']])
        ->and(nestedMatchErrors($form, []))->toBe([]);
});

it('leaves Repeater targets to the server in the browser', function(string $path, string $target) {
    $form = nestedMatchForm();
    $form = saveNestedMatchField($form, $path, nestedMatchReference($form, $target, ['scope' => 'first']));
    $types = array_column(nestedMatchFieldAt($form, $path)->browserValidationRules(), 'type');

    expect($types)->not->toContain('match');
})->with([
    'same row' => ['people.emailCopy', 'people.email'],
    'outside' => ['confirmation', 'people.email'],
]);

it('keeps the picker value for nested targets', function() {
    $form = nestedMatchForm();
    $rowToken = nestedMatchReference($form, 'people.email', ['scope' => 'first']);
    $form = saveNestedMatchField($form, 'confirmation', $rowToken);
    expect($form->getFieldByHandle('confirmation')->getFormBuilderConfig()['matchField'])->toBe($rowToken);

    $form = saveNestedMatchField($form, 'confirmation', '{field:contact.home}');
    expect($form->getFieldByHandle('confirmation')->getFormBuilderConfig()['matchField'])->toBe(nestedMatchReference($form, 'contact.home'));

    $form = saveNestedMatchField($form, 'confirmation', '{field:people.email}');
    expect($form->getFieldByHandle('confirmation')->getFormBuilderConfig()['matchField'])->toBe($rowToken);
});

it('still compares raw passwords in a Group', function() {
    $form = formie()->form()
        ->groupField('account', ['rows' => [['fields' => [['type' => Password::class, 'handle' => 'first', 'label' => 'First']]]]])
        ->addField(Password::class, 'second', ['label' => 'Second'])
        ->create();
    $form = saveNestedMatchField($form, 'second', nestedMatchReference($form, 'account.first'));

    expect(nestedMatchErrors($form, ['account' => ['first' => 'same-secret-1'], 'second' => 'same-secret-1']))->not->toHaveKey('second')
        ->and(nestedMatchErrors($form, ['account' => ['first' => 'same-secret-1'], 'second' => 'other-secret-2']))->toHaveKey('second');
});

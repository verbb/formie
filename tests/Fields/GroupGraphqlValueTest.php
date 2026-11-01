<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\ObjectType;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\fields\Group;
use verbb\formie\fields\Number;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\models\DynamicModel;

function groupGraphqlChildren(): array
{
    return [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'lastName', 'label' => 'Last name'],
        ['type' => Number::class, 'handle' => 'postcode', 'label' => 'Postcode'],
    ]]];
}

function groupGraphqlForm(): Form
{
    $form = formie()->form()->groupField('details', ['rows' => groupGraphqlChildren()])->create();
    $form = Form::find()->id($form->id)->one();
    formie()->submission($form)->with(['details' => ['lastName' => 'Smith', 'postcode' => '3072']])->save();

    return $form;
}

function groupGraphqlQuery(Form $form, string $selection): array
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Group field values',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        return $gql->executeQuery($schema, '{ formieSubmissions(form: "' . $form->handle . '") { ... on ' . $form->handle . '_Submission { ' . $selection . ' } } }');
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }
}

function groupGraphqlRow(Form $form, string $selection): array
{
    $result = groupGraphqlQuery($form, $selection);

    expect($result['errors'] ?? [])->toBe([]);

    return json_decode(json_encode($result['data']['formieSubmissions'][0]), true);
}

it('selects group subfields directly', function (): void {
    $row = groupGraphqlRow(groupGraphqlForm(), 'details { lastName postcode }');

    expect($row['details']['lastName'])->toBe('Smith')
        ->and((string)$row['details']['postcode'])->toBe('3072');
});

it('selects the group type name and honours aliases', function (): void {
    $row = groupGraphqlRow(groupGraphqlForm(), 'details { __typename surname: lastName }');

    expect($row['details']['__typename'])->toEndWith('_NestedField')
        ->and($row['details']['surname'])->toBe('Smith');
});

it('selects group subfields through an inline fragment', function (): void {
    $form = groupGraphqlForm();
    $fields = Formie::$plugin->getFields();
    $config = current(array_filter($fields->getAllFieldConfigsForForms([$form->id])[$form->id], fn(array $item): bool => $item['handle'] === 'details'));
    $typeName = $fields->getFieldConfigGqlTypeName($config, 'NestedField');
    $row = groupGraphqlRow($form, "details { ... on {$typeName} { lastName } }");

    expect($row['details']['lastName'])->toBe('Smith');
});

it('exposes a group as an object type from both field definitions', function (): void {
    $form = groupGraphqlForm();
    $field = $form->getFieldByHandle('details');
    $submission = Submission::find()->formId($form->id)->one();
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $gql->flushCaches();
    $gql->setActiveSchema(new GqlSchema(['scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read"]]));

    try {
        $config = Formie::$plugin->getForms()->getGqlSchemaSnapshot()->fieldConfigs((int)$form->id);
        $config = current(array_filter($config, fn(array $item): bool => $item['handle'] === 'details'));

        foreach ([$field->getContentGqlType(), Group::gqlContentTypeFromConfig($config)] as $definition) {
            expect($definition['type'])->toBeInstanceOf(ObjectType::class)
                ->and($definition['resolve']($submission)->lastName)->toBe('Smith')
                ->and($definition['resolve'](new DynamicModel(['details' => ['lastName' => 'Jones']]))->lastName)->toBe('Jones')
                ->and($definition['resolve'](new DynamicModel([]))->attributes())->toBe([]);
        }
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }
});

it('returns empty subfields for a group with no answer', function (): void {
    $form = formie()->form()
        ->singleLineTextField('note')
        ->groupField('details', ['rows' => groupGraphqlChildren()])
        ->create();
    $form = Form::find()->id($form->id)->one();
    formie()->submission($form)->with(['note' => 'Empty group'])->save();

    expect(groupGraphqlRow($form, 'details { lastName postcode }')['details'])
        ->toBe(['lastName' => '', 'postcode' => null]);
});

it('selects groups held in repeater rows and other groups', function (): void {
    $form = formie()->form()
        ->repeaterField('people', ['rows' => [['fields' => [
            ['type' => Group::class, 'handle' => 'details', 'label' => 'Details', 'rows' => groupGraphqlChildren()],
        ]]]])
        ->groupField('outer', ['rows' => [['fields' => [
            ['type' => Group::class, 'handle' => 'inner', 'label' => 'Inner', 'rows' => groupGraphqlChildren()],
        ]]]])
        ->create();
    $form = Form::find()->id($form->id)->one();
    formie()->submission($form)->with([
        'people' => [['details' => ['lastName' => 'Smith', 'postcode' => '3072']]],
        'outer' => ['inner' => ['lastName' => 'Jones', 'postcode' => '2000']],
    ])->save();

    $row = groupGraphqlRow($form, 'people { rows { details { lastName } } } outer { inner { lastName } }');

    expect($row['people']['rows'][0]['details']['lastName'])->toBe('Smith')
        ->and($row['outer']['inner']['lastName'])->toBe('Jones');
});

it('resolves dates held in a group', function (): void {
    $form = formie()->form()->groupField('details', ['rows' => [['fields' => [
        ['type' => Date::class, 'handle' => 'birthday', 'label' => 'Birthday', 'displayType' => 'datePicker'],
    ]]]])->create();
    $form = Form::find()->id($form->id)->one();
    formie()->submission($form)->with(['details' => ['birthday' => '2010-05-20']])->save();

    $row = groupGraphqlRow($form, 'details { birthday formatted: birthday @formatDateTime(format: "Y-m-d") }');

    expect($row['details'])->toBe(['birthday' => '2010-05-20T00:00:00+00:00', 'formatted' => '2010-05-20']);
});

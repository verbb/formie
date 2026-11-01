<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Date;
use verbb\formie\fields\Group;
use verbb\formie\fields\Name;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Table;
use verbb\formie\Formie;

function graphqlTypeNamesText(string $handle): array
{
    return ['type' => SingleLineText::class, 'handle' => $handle, 'label' => $handle];
}

function graphqlTypeNamesForm(): Form
{
    $columns = ['columns' => [['id' => 'c1', 'handle' => 'cell', 'heading' => 'Cell', 'type' => 'singleline']]];
    $form = formie()->form()
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addressField('home', ['rows' => (new Address())->getSubFields()])
        ->groupField('details', ['rows' => [['fields' => [
            graphqlTypeNamesText('lastName'),
            ['type' => Name::class, 'handle' => 'contact', 'label' => 'Contact', 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()],
            ['type' => Group::class, 'handle' => 'extra', 'label' => 'Extra', 'rows' => [['fields' => [graphqlTypeNamesText('first')]]]],
        ]]]])
        ->groupField('other', ['rows' => [['fields' => [
            ['type' => Group::class, 'handle' => 'extra', 'label' => 'Extra', 'rows' => [['fields' => [graphqlTypeNamesText('second')]]]],
        ]]]])
        ->repeaterField('people', ['rows' => [['fields' => [
            graphqlTypeNamesText('note'),
            ['type' => Table::class, 'handle' => 'cells', 'label' => 'Cells'] + $columns,
        ]]]])
        ->addField(Table::class, 'grid', $columns)
        ->dateField('stay', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE])
        ->create();

    return Form::find()->id($form->id)->one();
}

function withGraphqlTypeNamesSchema(Form $form, callable $callback): mixed
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Stable type names',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read", "formieSubmissions.{$form->uid}:create", "formieSubmissions.{$form->uid}:save"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        return $callback(fn(string $query, array $variables = []): array => json_decode(json_encode($gql->executeQuery($schema, $query, $variables)), true));
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }
}

function graphqlTypeNameOf(array $type): string
{
    while (!isset($type['name'])) {
        $type = $type['ofType'];
    }

    return $type['name'];
}

const GRAPHQL_TYPE_NAMES_REF = 'type { name ofType { name ofType { name } } }';

it('names saved field types after their form and handle', function (): void {
    $form = graphqlTypeNamesForm();
    $handle = $form->handle;

    withGraphqlTypeNamesSchema($form, function (callable $query) use ($handle): void {
        $mutation = $query('{ __type(name: "Mutation") { fields { name args { name ' . GRAPHQL_TYPE_NAMES_REF . ' } } } }');
        $fields = array_column($mutation['data']['__type']['fields'], null, 'name');
        $inputs = [];

        foreach ($fields["save_{$handle}_Submission"]['args'] as $argument) {
            $inputs[$argument['name']] = graphqlTypeNameOf($argument['type']);
        }

        expect($inputs)->toMatchArray([
            'person' => "{$handle}_person_FormieNameInput",
            'home' => "{$handle}_home_FormieAddressInput",
            'details' => "{$handle}_details_FormieGroupInput",
            'people' => "{$handle}_people_FormieRepeaterInput",
            'grid' => "{$handle}_grid_TableRowInput",
            'stay' => "{$handle}_stay_FormieDateRangeInput",
        ]);

        $submission = $query('{ __type(name: "' . $handle . '_Submission") { fields { name ' . GRAPHQL_TYPE_NAMES_REF . ' } } }');
        $outputs = [];

        foreach ($submission['data']['__type']['fields'] as $field) {
            $outputs[$field['name']] = graphqlTypeNameOf($field['type']);
        }

        expect($outputs)->toMatchArray([
            'details' => "{$handle}_details_FormieGroupField",
            'people' => "{$handle}_people_FormieRepeaterField",
            'grid' => "{$handle}_grid_TableRow",
            'stay' => "{$handle}_stay_FormieDateRange",
        ]);
    });
});

it('reports input type names the schema accepts', function (): void {
    $form = graphqlTypeNamesForm();

    withGraphqlTypeNamesSchema($form, function (callable $query) use ($form): void {
        $meta = $query('{ formieForm(handle: "' . $form->handle . '") { formFields { handle inputTypeName } } }');
        $reported = array_column($meta['data']['formieForm']['formFields'], 'inputTypeName', 'handle');
        $handle = $form->handle;

        expect($meta['errors'] ?? [])->toBe([])
            ->and($reported['person'])->toBe("{$handle}_person_FormieNameInput")
            ->and($reported['grid'])->toBe("[{$handle}_grid_TableRowInput]");

        foreach (['person', 'home', 'details', 'people', 'grid'] as $field) {
            $result = $query("mutation Check(\${$field}: {$reported[$field]}) { save_{$handle}_Submission({$field}: \${$field}, isIncomplete: true) { id } }");

            expect(array_column($result['errors'] ?? [], 'message'))->toBe([]);
        }
    });
});

it('saves and returns documented typed mutations', function (): void {
    $form = graphqlTypeNamesForm();
    $handle = $form->handle;
    $variables = [
        'person' => ['firstName' => 'Pat', 'lastName' => 'Smith'],
        'home' => ['address1' => '1 Main St', 'city' => 'Melbourne', 'zip' => '3000', 'country' => 'AU'],
        'details' => ['lastName' => 'Jones', 'contact' => ['firstName' => 'Sam'], 'extra' => ['first' => 'Nested']],
        'people' => ['rows' => [['note' => 'Row one']]],
        'grid' => [['cell' => 'Value']],
    ];

    withGraphqlTypeNamesSchema($form, function (callable $query) use ($handle, $variables): void {
        $result = $query(
            "mutation Save(\$person: {$handle}_person_FormieNameInput, \$home: {$handle}_home_FormieAddressInput, \$details: {$handle}_details_FormieGroupInput, \$people: {$handle}_people_FormieRepeaterInput, \$grid: [{$handle}_grid_TableRowInput]) {
                save_{$handle}_Submission(person: \$person, home: \$home, details: \$details, people: \$people, grid: \$grid) {
                    ... on {$handle}_Submission {
                        person { firstName lastName }
                        home { address1 city }
                        details { lastName contact { firstName } extra { first } }
                        people { rows { note } }
                        grid { cell }
                    }
                }
            }",
            $variables,
        );

        expect(array_column($result['errors'] ?? [], 'message'))->toBe([])
            ->and($result['data']["save_{$handle}_Submission"])->toBe([
                'person' => ['firstName' => 'Pat', 'lastName' => 'Smith'],
                'home' => ['address1' => '1 Main St', 'city' => 'Melbourne'],
                'details' => ['lastName' => 'Jones', 'contact' => ['firstName' => 'Sam'], 'extra' => ['first' => 'Nested']],
                'people' => ['rows' => [['note' => 'Row one']]],
                'grid' => [['cell' => 'Value']],
            ]);
    });
});

it('keeps same-named nested fields apart under different parents', function (): void {
    $form = graphqlTypeNamesForm();
    $handle = $form->handle;

    withGraphqlTypeNamesSchema($form, function (callable $query) use ($handle): void {
        $first = $query('{ __type(name: "' . $handle . '_details_extra_FormieGroupField") { fields { name } } }');
        $second = $query('{ __type(name: "' . $handle . '_other_extra_FormieGroupField") { fields { name } } }');
        $table = $query('{ __type(name: "' . $handle . '_people_cells_TableRowInput") { inputFields { name } } }');

        expect(array_column($first['data']['__type']['fields'], 'name'))->toBe(['first'])
            ->and(array_column($second['data']['__type']['fields'], 'name'))->toBe(['second'])
            ->and(array_column($table['data']['__type']['inputFields'], 'name'))->toBe(['cell']);
    });
});

it('matches hydrated field names to saved field names', function (): void {
    $form = graphqlTypeNamesForm();
    $fields = Formie::$plugin->getFields();
    $details = $form->getFieldByHandle('details');
    $contact = $details->getFieldByHandle('contact');

    expect($fields->getFieldGqlTypeName($details, 'FormieGroupInput'))->toBe("{$form->handle}_details_FormieGroupInput")
        ->and($fields->getFieldGqlTypeName($contact, 'FormieNameInput'))->toBe("{$form->handle}_details_contact_FormieNameInput");
});

it('keeps unique names for a field config with no form', function (): void {
    $name = Formie::$plugin->getFields()->getFieldConfigGqlTypeName(['id' => 12, 'type' => Repeater::class, 'handle' => 'rows'], 'FormieRepeaterInput');

    expect($name)->toBe('Formie_rows_verbb_formie_fields_Repeater_12_FormieRepeaterInput');
});

it('returns name and address values at top level and inside a group', function (): void {
    $form = graphqlTypeNamesForm();
    $handle = $form->handle;
    $person = ['firstName' => 'Pat', 'lastName' => 'Smith'];
    formie()->submission($form)->with([
        'person' => $person,
        'home' => ['address1' => '1 Main St', 'city' => 'Melbourne', 'zip' => '3000', 'country' => 'AU'],
        'details' => ['contact' => $person],
    ])->save();

    withGraphqlTypeNamesSchema($form, function (callable $query) use ($handle): void {
        $result = $query('{ formieSubmissions(form: "' . $handle . '") { ... on ' . $handle . '_Submission { person { fullName firstName lastName } home { address1 city zip country } details { contact { fullName lastName } } } } }');

        expect($result['errors'] ?? [])->toBe([])
            ->and($result['data']['formieSubmissions'][0])->toBe([
                'person' => ['fullName' => 'Pat Smith', 'firstName' => 'Pat', 'lastName' => 'Smith'],
                'home' => ['address1' => '1 Main St', 'city' => 'Melbourne', 'zip' => '3000', 'country' => 'AU'],
                'details' => ['contact' => ['fullName' => 'Pat Smith', 'lastName' => 'Smith']],
            ]);
    });
});

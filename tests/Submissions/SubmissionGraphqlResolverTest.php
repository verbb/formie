<?php

declare(strict_types=1);

use verbb\formie\gql\resolvers\SubmissionResolver;

it('preserves underscored form handles when inferring graphql inline fragment context', function (): void {
    expect(SubmissionResolver::formHandleFromFragmentType('contact_us_Submission'))
        ->toBe('contact_us')
        ->and(SubmissionResolver::formHandleFromFragmentType('contact_Submission'))->toBe('contact')
        ->and(SubmissionResolver::formHandleFromFragmentType('SubmissionInterface'))->toBeNull();
});

it('preserves explicit GraphQL form selection when resolving inline fragments', function (string $selection, array $fragmentIndexes, array $expectedIndexes): void {
    $forms = [];
    $ids = [];
    foreach (['First', 'Second', 'Unselected'] as $name) {
        $form = formie()->form()->singleLineTextField('message')->create();
        $forms[] = $form;
        $ids[] = (int)formie()->submission($form)->with(['message' => $name])->save()->id;
    }
    $gql = Craft::$app->getGql();
    $previous = null;
    try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) {}
    $schema = new \craft\models\GqlSchema(['name' => 'Submission fragment selection', 'uid' => \craft\helpers\StringHelper::UUID(),
        'scope' => ['sites.all:read', 'formieForms.all:read', 'formieSubmissions.all:read']]);
    $gql->flushCaches();
    $gql->setActiveSchema($schema);
    try {
        $arguments = match ($selection) {
            'both' => 'form: ' . json_encode([$forms[0]->handle, $forms[1]->handle]),
            'first' => 'form: ' . json_encode([$forms[0]->handle]),
            'none' => 'form: []',
            default => '',
        };
        $fragments = implode(' ', array_map(fn($index) => '... on ' . \verbb\formie\elements\Submission::gqlTypeNameByContext($forms[$index]) . ' { message }', $fragmentIndexes));
        $query = '{ formieSubmissions(' . ($arguments ? $arguments . ', ' : '') . 'id: ' . json_encode($ids) . ', orderBy: "id asc") { id ' . $fragments . ' } }';
        $result = $gql->executeQuery($schema, $query);
        expect($result['errors'] ?? [])->toBe([]);
        expect(array_map('intval', array_column($result['data']['formieSubmissions'], 'id')))->toBe(array_map(fn($index) => $ids[$index], $expectedIndexes));
    } finally { $gql->flushCaches(); $gql->setActiveSchema($previous); }
})->with([
    'explicit two forms' => ['both', [0, 1], [0, 1]],
    'reversed fragments' => ['both', [1, 0], [0, 1]],
    'explicit form with another fragment' => ['first', [1], [0]],
    'explicit empty scope' => ['none', [1], []],
    'inferred single context' => ['inferred', [0], [0]],
    'inferred multiple contexts' => ['inferred', [0, 1], [0, 1]],
]);

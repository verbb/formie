<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('compares and sorts saved decimal strings numerically without rounding', function (): void {
    $values = ['-1e1000', '-100000000000000000000000000002', '-100000000000000000000000000001', '-20', '-3', '-2.5', '-0.00001', '0', '1e-1000', '0.00001', '0.1234567890123456789012345678901', '0.1234567890123456789012345678902', '1.20', '3', '10.25', '12', '20', '100000000000000000000000000001', '100000000000000000000000000002', '1e1000'];
    $form = formie()->form()->numberField('quantity')->create();
    $ids = [];
    foreach (array_reverse($values) as $value) { $ids[$value] = formie()->submission($form)->with(['quantity' => $value])->save()->id; }
    $query = fn() => Submission::find()->formId($form->id)->status(null);
    $sort = $form->getFieldByHandle('quantity')->getSortOption()['orderBy'][0];
    $orderedIds = array_map(fn($value) => $ids[$value], $values);
    expect($query()->orderBy([$sort => SORT_ASC, 'elements.id' => SORT_ASC])->ids())->toBe($orderedIds);
    expect($query()->orderBy([$sort => SORT_DESC, 'elements.id' => SORT_ASC])->ids())->toBe(array_reverse($orderedIds));
    expect($query()->orderBy([$sort => SORT_ASC])->offset(7)->limit(4)->ids())->toBe(array_slice($orderedIds, 7, 4));
    $cases = [
        ['>= 12', array_slice($values, 15)],
        ['< -3', array_slice($values, 0, 4)],
        ['> 0.1234567890123456789012345678901', array_slice($values, 11)],
        ['> 100000000000000000000000000001', array_slice($values, 18)],
        [['and', '>= -2.5', '< 0.00001'], array_slice($values, 5, 4)],
        [['or', '< -3', '> 100000000000000000000000000001'], array_merge(array_slice($values, 0, 4), array_slice($values, 18))],
    ];
    foreach ($cases as [$criterion, $expected]) {
        expect($query()->quantity($criterion)->orderBy([$sort => SORT_ASC])->ids())->toBe(array_map(fn($value) => $ids[$value], $expected));
    }
    expect($query()->quantity('1.20')->ids())->toBe([$ids['1.20']]);
    expect($query()->quantity('1.2')->ids())->toBe([]);
    expect($query()->quantity('100000000000000000000000000001')->ids())->toBe([$ids['100000000000000000000000000001']]);
    expect($query()->id($ids['100000000000000000000000000001'])->one()->getFieldValue('quantity'))->toBe('100000000000000000000000000001');
});

it('keeps number query parameters and empty values isolated across fields and forms', function (): void {
    $forms = [formie()->form()->numberField('quantity')->numberField('other')->create(), formie()->form()->numberField('quantity')->numberField('other')->create()];
    $expected = [];
    $small = [];
    foreach ($forms as $form) {
        $expected[] = formie()->submission($form)->with(['quantity' => '12', 'other' => '3'])->save()->id;
        $small[] = formie()->submission($form)->with(['quantity' => '3', 'other' => '20'])->save()->id;
        formie()->submission($form)->with(['quantity' => null, 'other' => null])->save();
    }
    $query = Submission::find()->formId(array_map(fn($form) => $form->id, $forms))->quantity('>= 12')->other('< 12')->orderBy(['elements.id' => SORT_ASC]);
    expect($query->ids())->toBe($expected);
    $form = $forms[0];
    $invalid = formie()->submission($form)->with(['quantity' => '4'])->save();
    \craft\helpers\Db::update('{{%formie_submissions}}', ['content' => [$form->getFieldByHandle('quantity')->uid => '1einvalid']], ['id' => $invalid->id]);
    expect(Submission::find()->formId($form->id)->quantity('>= 0')->orderBy(['elements.id' => SORT_ASC])->ids())->toBe([$expected[0], $small[0]]);
});

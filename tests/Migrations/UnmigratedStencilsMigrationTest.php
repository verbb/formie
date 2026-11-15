<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\fields\Group;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261115_025000_unmigrated_stencils;
use verbb\formie\services\Stencils as StencilsService;

function unmigratedStencilId(): int
{
    $handle = 'unmigrated' . random_int(1000, 999999);

    Db::insert(Table::FORMIE_STENCILS, [
        'name' => 'Unmigrated',
        'handle' => $handle,
        'data' => Json::encode([
            'settings' => [],
            'notifications' => [],
            'pages' => [[
                'label' => 'Page 1',
                'rows' => [
                    ['fields' => [['type' => 'verbb\formie\fields\formfields\SingleLineText', 'handle' => 'plain', 'label' => 'Plain', 'settings' => []]]],
                    ['fields' => [[
                        'type' => 'verbb\formie\fields\formfields\Group',
                        'handle' => 'contact',
                        'label' => 'Contact',
                        'settings' => [],
                        'rows' => [['fields' => [['type' => 'verbb\formie\fields\formfields\SingleLineText', 'handle' => 'position', 'label' => 'Position', 'settings' => []]]]],
                    ]]],
                ],
            ]],
        ]),
    ]);

    return (int)(new Query())->select('id')->from(Table::FORMIE_STENCILS)->where(['handle' => $handle])->scalar();
}

it('repairs a stencil the Formie 2 upgrade left with fields of unknown types', function(): void {
    $id = unmigratedStencilId();

    try {
        (new m261115_025000_unmigrated_stencils())->safeUp();

        $form = new Form();
        (new StencilsService())->getStencilById($id)->data->populateToForm($form);
        $fields = $form->getFields();
        $group = $form->getFieldByHandle('contact');

        expect(array_map(static fn($field) => $field::class, $fields))->toBe([SingleLineText::class, Group::class])
            ->and($group->getFieldByHandle('position'))->toBeInstanceOf(SingleLineText::class);

        $stored = (new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $id])->scalar();
        (new m261115_025000_unmigrated_stencils())->safeUp();

        expect((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $id])->scalar())->toBe($stored);
    } finally {
        Db::delete(Table::FORMIE_STENCILS, ['id' => $id]);
    }
});

it('leaves stencils that are already current as they are', function(): void {
    $legacyId = unmigratedStencilId();
    $handle = 'current' . random_int(1000, 999999);
    $data = Json::encode(['settings' => ['submitMethod' => 'ajax'], 'notifications' => [], 'pages' => [['label' => 'Page 1', 'rows' => []]]]);
    Db::insert(Table::FORMIE_STENCILS, ['name' => 'Current', 'handle' => $handle, 'data' => $data]);
    $currentId = (int)(new Query())->select('id')->from(Table::FORMIE_STENCILS)->where(['handle' => $handle])->scalar();

    try {
        (new m261115_025000_unmigrated_stencils())->safeUp();

        expect((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $currentId])->scalar())->toBe($data)
            ->and((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $legacyId])->scalar())->not->toContain('formfields');
    } finally {
        Db::delete(Table::FORMIE_STENCILS, ['id' => [$legacyId, $currentId]]);
    }
});

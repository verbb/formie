<?php

declare(strict_types=1);

use craft\db\Command;
use craft\db\Query;
use verbb\formie\cache\FieldGqlCache;
use verbb\formie\cache\FieldLookupCache;
use verbb\formie\cache\FieldRegistryCache;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\LayoutSaveContext;
use verbb\formie\services\Fields;

class LayoutSaveRecordingCommand extends Command
{
    private static bool $_recording = false;
    private static array $_queries = [];

    public static function startRecording(): void
    {
        self::$_recording = true;
        self::$_queries = [];
    }

    public static function stopRecording(): array
    {
        self::$_recording = false;

        return self::$_queries;
    }

    public function execute()
    {
        try {
            return parent::execute();
        } finally {
            $this->_record();
        }
    }

    protected function queryInternal($method, $fetchMode = null)
    {
        try {
            return parent::queryInternal($method, $fetchMode);
        } finally {
            $this->_record();
        }
    }

    private function _record(): void
    {
        if (self::$_recording) {
            self::$_queries[] = (string)$this->getRawSql();
        }
    }
}

class LayoutSaveFieldLookupCacheSpy extends FieldLookupCache
{
    public int $resetCount = 0;

    public function reset(): void
    {
        $this->resetCount++;
        parent::reset();
    }
}

class LayoutSaveFieldRegistryCacheSpy extends FieldRegistryCache
{
    public int $resetCount = 0;

    public function reset(): void
    {
        $this->resetCount++;
        parent::reset();
    }
}

class LayoutSaveFieldGqlCacheSpy extends FieldGqlCache
{
    public int $resetCount = 0;

    public function reset(): void
    {
        $this->resetCount++;
        parent::reset();
    }
}

it('batches definition usage and cache invalidation across root and nested layouts', function (): void {
    $nestedRows = [[
        'fields' => [[
            'type' => verbb\formie\fields\SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $builder = formie()->form(['title' => 'Layout Save Batch']);
    for ($index = 1; $index <= 8; $index++) {
        $builder->singleLineTextField("field{$index}");
    }
    $form = $builder
        ->groupField('grouped', ['rows' => $nestedRows])
        ->repeaterField('repeated', ['rows' => $nestedRows])
        ->create();

    foreach ($form->getFieldsRecursively() as $field) {
        $field->label .= ' Updated';
    }

    $fields = Formie::$plugin->getFields();
    [$spies, $restoreCaches] = installLayoutSaveCacheSpies($fields);
    $db = Craft::$app->getDb();
    $commandClass = $db->commandClass;
    $queries = [];

    try {
        $db->commandClass = LayoutSaveRecordingCommand::class;
        LayoutSaveRecordingCommand::startRecording();

        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $queries = LayoutSaveRecordingCommand::stopRecording();
    } finally {
        LayoutSaveRecordingCommand::stopRecording();
        $db->commandClass = $commandClass;
        $restoreCaches();
    }

    $usageQueries = array_values(array_filter($queries, static function(string $sql): bool {
        $sql = strtolower($sql);

        return str_contains($sql, 'count(*)')
            && str_contains($sql, 'formie_form_fields')
            && str_contains($sql, 'fieldid')
            && str_contains($sql, 'as `usagecount`');
    }));
    $identityExistsQueries = array_values(array_filter($queries, static function(string $sql): bool {
        $sql = strtolower($sql);

        return str_contains($sql, 'select exists')
            && str_contains($sql, 'formie_form_fields');
    }));

    expect($usageQueries)->toHaveCount(2)
        ->and($identityExistsQueries)->toBe([])
        ->and($spies['lookup']->resetCount)->toBe(1)
        ->and($spies['registry']->resetCount)->toBe(1)
        ->and($spies['gql']->resetCount)->toBe(1);

    foreach ($form->getFieldsRecursively() as $field) {
        expect($field->usageCount)->toBe(1)
            ->and($field->isSynced)->toBeFalse();
    }
});

it('updates builder layouts containing existing address and repeater rows', function (): void {
    $nestedRows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'itemName',
            'label' => 'Item Name',
        ]],
    ]];
    $form = formie()
        ->form(['title' => 'Existing Nested Builder Rows'])
        ->addressField('address', ['rows' => (new Address())->getSubFields()])
        ->repeaterField('items', ['rows' => $nestedRows])
        ->create();
    $addressLayoutId = $form->getFieldByHandle('address')->nestedLayoutId;
    $repeaterLayoutId = $form->getFieldByHandle('items')->nestedLayoutId;
    $serializer = new FormSerializer();

    $form->getFormLayout()->setPages($serializer->hydrateBuilder(
        $form->getFormLayout()->getFormBuilderConfig(),
        $form,
    ));
    $form->layoutSaveContext = new LayoutSaveContext('builder');
    $form->layoutSaveContext->trusted = false;
    $form->layoutSaveContext->remaps = $serializer->remaps;

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue()
        ->and($form->getFieldByHandle('address')->nestedLayoutId)->toBe($addressLayoutId)
        ->and($form->getFieldByHandle('items')->nestedLayoutId)->toBe($repeaterLayoutId);
});

it('keeps direct field saves atomic with their nested layout', function (): void {
    $form = formie()
        ->form(['title' => 'Direct Parent Save'])
        ->groupField('grouped', ['rows' => [[
            'fields' => [[
                'type' => verbb\formie\fields\SingleLineText::class,
                'handle' => 'innerText',
                'label' => 'Original Child Label',
            ]],
        ]]])
        ->create();

    $group = $form->getFieldByHandle('grouped');
    $child = $group->getFieldByHandle('innerText');
    $childDefinitionId = $child->definitionId;
    $child->label = 'Changed Child Label';
    $group->handle = 'invalid handle';

    expect(Formie::$plugin->getFields()->saveField($group))->toBeFalse();

    $storedLabel = (new Query())
        ->select('label')
        ->from(Table::FORMIE_FIELDS)
        ->where(['id' => $childDefinitionId])
        ->scalar();

    expect($storedLabel)->toBe('Original Child Label');
});

it('keeps standalone field usage metadata authoritative', function (): void {
    $form = formie()
        ->form(['title' => 'Standalone Field Save'])
        ->singleLineTextField('standalone')
        ->create();
    $field = $form->getFieldByHandle('standalone');
    $field->label = 'Standalone Updated';

    expect(Formie::$plugin->getFields()->saveField($field))->toBeTrue()
        ->and($field->usageCount)->toBe(1)
        ->and($field->isSynced)->toBeFalse();

    $storedLabel = (new Query())
        ->select('label')
        ->from(Table::FORMIE_FIELDS)
        ->where(['id' => $field->definitionId])
        ->scalar();

    expect($storedLabel)->toBe('Standalone Updated');
});

it('does not persist layout changes when form validation fails', function (): void {
    $form = formie()
        ->form(['title' => 'Atomic Form Validation'])
        ->singleLineTextField('fullName', ['label' => 'Original Label'])
        ->create();
    $field = $form->getFieldByHandle('fullName');
    $field->label = 'Must Roll Back';
    $form->handle = 'invalid handle';

    expect(Craft::$app->getElements()->saveElement($form))->toBeFalse();

    $storedLabel = (new Query())
        ->select('label')
        ->from(Table::FORMIE_FIELDS)
        ->where(['id' => $field->definitionId])
        ->scalar();

    expect($storedLabel)->toBe('Original Label');
});

it('rejects duplicate handles in a new unsaved layout without database lookups', function (): void {
    $form = new Form([
        'title' => 'Duplicate New Handles',
        'handle' => 'duplicateNewHandles' . bin2hex(random_bytes(4)),
    ]);
    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'rows' => [[
                'fields' => [
                    ['type' => SingleLineText::class, 'handle' => 'duplicate', 'label' => 'First'],
                    ['type' => SingleLineText::class, 'handle' => 'duplicate', 'label' => 'Second'],
                ],
            ]],
        ]],
    ]));

    expect(Craft::$app->getElements()->saveElement($form))->toBeFalse()
        ->and($form->getErrors())->not->toBeEmpty();
});

it('validates the layout inside the save transaction when form validation is disabled', function (): void {
    $form = new Form([
        'title' => 'Skipped Form Validation',
        'handle' => 'skippedFormValidation' . bin2hex(random_bytes(4)),
    ]);
    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'rows' => [[
                'fields' => [
                    ['type' => SingleLineText::class, 'handle' => 'duplicate', 'label' => 'First'],
                    ['type' => SingleLineText::class, 'handle' => 'duplicate', 'label' => 'Second'],
                ],
            ]],
        ]],
    ]));

    expect(Craft::$app->getElements()->saveElement($form, false))->toBeFalse()
        ->and((new Query())->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->exists())->toBeFalse();
});

it('rejects references owned by another form through the bulk identity snapshot', function (): void {
    $source = formie()
        ->form(['title' => 'Reference Source'])
        ->singleLineTextField('source')
        ->create();
    $target = formie()
        ->form(['title' => 'Reference Target'])
        ->singleLineTextField('target')
        ->create();
    $sourceField = $source->getFieldByHandle('source');
    $targetField = $target->getFieldByHandle('target');
    $originalReference = $targetField->reference;
    $targetField->reference = $sourceField->reference;

    expect(Craft::$app->getElements()->saveElement($target))->toBeFalse();

    $storedReference = (new Query())
        ->select('reference')
        ->from(Table::FORMIE_FORM_FIELDS)
        ->where(['id' => $targetField->id])
        ->scalar();

    expect($storedReference)->toBe($originalReference);
});

function installLayoutSaveCacheSpies(Fields $fields): array
{
    $spies = [
        'lookup' => new LayoutSaveFieldLookupCacheSpy(),
        'registry' => new LayoutSaveFieldRegistryCacheSpy(),
        'gql' => new LayoutSaveFieldGqlCacheSpy(),
    ];
    $properties = [
        '_fieldLookupCache' => 'lookup',
        '_fieldRegistryCache' => 'registry',
        '_fieldGqlCache' => 'gql',
    ];
    $originals = [];

    foreach ($properties as $propertyName => $spyName) {
        $property = new ReflectionProperty($fields, $propertyName);
        $property->setAccessible(true);
        $originals[$propertyName] = $property->getValue($fields);
        $property->setValue($fields, $spies[$spyName]);
    }

    $restore = static function() use ($fields, $properties, $originals): void {
        foreach ($properties as $propertyName => $spyName) {
            $property = new ReflectionProperty($fields, $propertyName);
            $property->setAccessible(true);
            $property->setValue($fields, $originals[$propertyName]);
        }
    };

    return [$spies, $restore];
}

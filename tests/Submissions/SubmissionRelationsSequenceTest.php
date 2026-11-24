<?php

use craft\db\Connection;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\StringHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261124_200000_relations_sequence;

beforeEach(function(): void {
    $this->originalDb = Craft::$app->getDb();
    $this->sequenceDb = new Connection([
        // An explicit disposable PostgreSQL connection exercises serial sequences locally.
        'dsn' => getenv('TEST_MIGRATION_DSN') ?: $this->originalDb->dsn,
        'username' => getenv('TEST_MIGRATION_USER') ?: $this->originalDb->username,
        'password' => getenv('TEST_MIGRATION_PASSWORD') ?: $this->originalDb->password,
        'tablePrefix' => 'rel_' . bin2hex(random_bytes(4)) . '_',
        'commandClass' => craft\db\Command::class,
    ]);

    $migration = new m261124_200000_relations_sequence(['db' => $this->sequenceDb]);
    $migration->createTable(Table::FORMIE_RELATIONS, [
        'id' => $migration->primaryKey(),
        'type' => $migration->string(255)->notNull(),
        'sourceId' => $migration->integer()->notNull(),
        'sourceSiteId' => $migration->integer(),
        'targetId' => $migration->integer()->notNull(),
        'dateCreated' => $migration->dateTime()->notNull(),
        'dateUpdated' => $migration->dateTime()->notNull(),
        'uid' => $migration->uid(),
    ]);
    $migration->createIndex(null, Table::FORMIE_RELATIONS, ['sourceId', 'sourceSiteId', 'targetId'], true);
});

afterEach(function(): void {
    $db = $this->sequenceDb;

    try {
        if ($db->tableExists(Table::FORMIE_RELATIONS, true)) {
            $db->createCommand()->dropTable(Table::FORMIE_RELATIONS)->execute();
        }
    } finally {
        $db->close();
    }
});

function saveRelationsOn(Connection $db, Submission $submission): void
{
    $original = Craft::$app->getDb();
    Craft::$app->set('db', $db);

    try {
        Formie::$plugin->getRelations()->saveRelations($submission);
    } finally {
        Craft::$app->set('db', $original);
    }
}

function relationsSequenceSubmission(int $id, array $relations): Submission
{
    $form = new class extends Form {
        public array $testRelations = [];

        public function getRelationsFromRequest()
        {
            return $this->testRelations;
        }
    };
    $form->testRelations = $relations;

    $submission = new Submission();
    $submission->id = $id;
    (new ReflectionProperty(Submission::class, '_form'))->setValue($submission, $form);

    return $submission;
}

it('saves relations for every submission related to the same element', function(): void {
    $relation = ['type' => User::class, 'id' => 1, 'siteId' => 1];

    // Each later save collided with the first row when saving reset PostgreSQL's sequence to 1.
    foreach ([101, 102, 103] as $submissionId) {
        saveRelationsOn($this->sequenceDb, relationsSequenceSubmission($submissionId, [$relation]));
    }

    // Saving a submission again replaces its own relations without touching the others.
    saveRelationsOn($this->sequenceDb, relationsSequenceSubmission(102, [$relation]));

    $rows = (new Query())->from(Table::FORMIE_RELATIONS)->orderBy('targetId')->all($this->sequenceDb);
    expect(array_column($rows, 'targetId'))->toBe([101, 102, 103])
        ->and(count(array_unique(array_column($rows, 'id'))))->toBe(3);
});

it('repairs a relations sequence left below existing rows and can run again', function(): void {
    $db = $this->sequenceDb;

    foreach ([1, 2, 3] as $id) {
        $db->createCommand()->insert(Table::FORMIE_RELATIONS, [
            'id' => $id, 'type' => User::class, 'sourceId' => $id, 'sourceSiteId' => 1, 'targetId' => 200,
            'dateCreated' => '2024-01-01 00:00:00', 'dateUpdated' => '2024-01-01 00:00:00', 'uid' => StringHelper::UUID(),
        ])->execute();
    }

    // Simulate a site where earlier relation saves reset the sequence.
    $db->createCommand()->resetSequence(Table::FORMIE_RELATIONS, 1)->execute();

    $migration = new m261124_200000_relations_sequence(['db' => $db]);
    expect($migration->up())->toBeTrue()
        ->and($migration->up())->toBeTrue();

    saveRelationsOn($this->sequenceDb, relationsSequenceSubmission(201, [['type' => User::class, 'id' => 1, 'siteId' => 1]]));

    $ids = (new Query())->from(Table::FORMIE_RELATIONS)->where(['targetId' => 201])->column($db);
    expect(array_map('intval', $ids))->toBe([4]);
});

it('saves an element related more than once as a single relation', function(): void {
    $relation = ['type' => User::class, 'id' => 1, 'siteId' => 1];

    // A unique index covers the element, site and submission, so a repeated element failed the whole save.
    saveRelationsOn($this->sequenceDb, relationsSequenceSubmission(301, [$relation, $relation, ['type' => User::class, 'id' => 1, 'siteId' => 2]]));

    $rows = (new Query())->from(Table::FORMIE_RELATIONS)->where(['targetId' => 301])->orderBy('sourceSiteId')->all($this->sequenceDb);
    expect(array_map('intval', array_column($rows, 'sourceSiteId')))->toBe([1, 2]);
});

<?php
namespace verbb\formie\services;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\helpers\Db;

class Relations extends Component
{
    // Public Methods
    // =========================================================================

    public function getRelations(Submission $submission): array
    {
        $elements = [];

        $relations = (new Query())
            ->from([Table::FORMIE_RELATIONS])
            ->where(['targetId' => $submission->id])
            ->all();

        foreach ($relations as $relation) {
            $element = Craft::$app->getElements()->getElementById($relation['sourceId'], $relation['type'], $relation['sourceSiteId']);

            if ($element) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    public function getSubmissionRelations(ElementInterface $element): array
    {
        $elements = [];

        $relations = (new Query())
            ->from([Table::FORMIE_RELATIONS])
            ->where(['sourceId' => $element->id, 'sourceSiteId' => $element->siteId])
            ->all();

        foreach ($relations as $relation) {
            $element = Craft::$app->getElements()->getElementById($relation['targetId'], Submission::class);

            if ($element) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    public function saveRelations(Submission $submission): void
    {
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        $relations = $form->getRelationsFromRequest();

        if (!$relations) {
            return;
        }

        $db = Craft::$app->getDb();
        $values = [];

        // Keep relations fresh
        Db::delete(Table::FORMIE_RELATIONS, ['targetId' => $submission->id], [], $db);

        // Reset auto-increment
        $db->createCommand()->resetSequence(Table::FORMIE_RELATIONS, '1')->execute();

        foreach ($relations as $relation) {
            $values[] = [
                $relation['type'],
                $relation['id'],
                $relation['siteId'],
                $submission->id,
            ];
        }

        Db::batchInsert(Table::FORMIE_RELATIONS, ['type', 'sourceId', 'sourceSiteId', 'targetId'], $values, $db);
    }
}

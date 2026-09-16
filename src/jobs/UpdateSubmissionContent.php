<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Translation;
use craft\queue\BaseJob;

class UpdateSubmissionContent extends BaseJob
{
    // Properties
    // =========================================================================

    public ?int $formId = null;
    public array $previousGroupFieldUids = [];


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $form = Form::find()->withoutCpIndexScope()->id($this->formId)->site('*')->unique()->status(null)->one();

        if (!$form) {
            return;
        }

        // Only Groups can release their children. Repeater and composite values stay opaque.
        $destinations = [];
        $groupUids = $this->previousGroupFieldUids;

        foreach ($form->getFields() as $field) {
            if ($field instanceof formiefields\Group) {
                $groupUids[] = $field->uid;

                foreach ($field->getFields() as $child) {
                    $destinations[$child->uid] = $field->uid;
                }
            } else {
                $destinations[$field->uid] = null;
            }
        }

        $groupUids = array_values(array_unique($groupUids));

        $db = Craft::$app->getDb();
        $submissionIds = (new Query())->select('id')->from(Table::FORMIE_SUBMISSIONS)
            ->where(['formId' => $this->formId])->orderBy(['id' => SORT_ASC])->column();

        foreach ($submissionIds as $i => $id) {
            $this->setProgress($queue, $i / count($submissionIds), Translation::prep('app', '{step, number} of {total, number}', [
                'step' => $i + 1,
                'total' => count($submissionIds),
            ]));

            $db->transaction(function () use ($db, $id, $destinations, $groupUids): void {
                // Lock the latest row until relocation completes so a concurrent edit cannot be overwritten.
                $submission = $db->createCommand(
                    'SELECT [[content]] FROM ' . Table::FORMIE_SUBMISSIONS . ' WHERE [[id]] = :id AND [[formId]] = :formId FOR UPDATE',
                    [':id' => $id, ':formId' => $this->formId],
                )->noCache()->queryOne();

                if ($submission === false) {
                    return;
                }

                $original = Json::decode($submission['content']) ?? [];
                $content = $original;

                foreach ($destinations as $fieldUid => $destinationUid) {
                    // A later submission edit at the destination wins over stale queued content.
                    $destination = $destinationUid === null ? $content : ($content[$destinationUid] ?? []);
                    $found = is_array($destination) && array_key_exists($fieldUid, $destination);
                    $value = $found ? $destination[$fieldUid] : null;

                    if (!$found && array_key_exists($fieldUid, $content)) {
                        $value = $content[$fieldUid];
                        $found = true;
                    }

                    foreach ($groupUids as $groupUid) {
                        if ($groupUid === $destinationUid || !isset($content[$groupUid]) || !is_array($content[$groupUid])) {
                            continue;
                        }

                        if (array_key_exists($fieldUid, $content[$groupUid])) {
                            if (!$found) {
                                $value = $content[$groupUid][$fieldUid];
                                $found = true;
                            }

                            unset($content[$groupUid][$fieldUid]);
                        }
                    }

                    if ($found) {
                        if ($destinationUid === null) {
                            $content[$fieldUid] = $value;
                        } else {
                            unset($content[$fieldUid]);
                            $content[$destinationUid][$fieldUid] = $value;
                        }
                    }
                }

                if ($content !== $original) {
                    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $id]);
                }
            });
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Updating form submission content.');
    }
}

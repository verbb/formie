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

        $submissions = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $this->formId])->all();

        foreach ($submissions as $i => $submission) {
            $this->setProgress($queue, $i / count($submissions), Translation::prep('app', '{step, number} of {total, number}', [
                'step' => $i + 1,
                'total' => count($submissions),
            ]));

            $original = Json::decode($submission['content']);
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
                Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission['id']]);
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Updating form submission content.');
    }
}

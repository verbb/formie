<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\Table;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use Throwable;

class UpdateSubmissionContent extends BaseJob
{
    // Properties
    // =========================================================================

    public ?int $formId = null;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $form = Form::find()->id($this->formId)->one();

        if (!$form) {
            return;
        }

        // Check if we've moved fields in or our of Group fields. Their content needs to be re-arranged.
        $nonGroupFields = [];
        $groupFields = [];

        foreach ($form->getFields() as $field) {
            // Just handle Group fields. Sub-Fields and Repeaters cannot be extracted out. T
            if ($field instanceof formiefields\Group) {
                $groupFields[] = $field;
            } else {
                $nonGroupFields[] = $field;
            }
        }

        $submissions = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $this->formId])->all();

        foreach ($submissions as $i => $submission) {
            $this->setProgress($queue, $i / count($submissions), Translation::prep('app', '{step, number} of {total, number}', [
                'step' => $i + 1,
                'total' => count($submissions),
            ]));

            $contentChanged = false;
            $content = Json::decode($submission['content']);

            foreach ($groupFields as $groupField) {
                $groupFieldUid = Db::uidById(Table::FORMIE_FIELDS, $groupField->id);

                // Was the content for a grouped field found at the top level?
                foreach ($groupField->getFields() as $nestedField) {
                    $nestedFieldUid = Db::uidById(Table::FORMIE_FIELDS, $nestedField->id);

                    if ($foundValue = ArrayHelper::remove($content, $nestedFieldUid)) {
                        // Move it to the Group field content
                        $content[$groupFieldUid][$nestedFieldUid] = $foundValue;
                        $contentChanged = true;
                    }
                }

                // Was the content for a non-grouped field found within the group field?
                foreach ($nonGroupFields as $nonGroupField) {
                    $nonGroupFieldUid = Db::uidById(Table::FORMIE_FIELDS, $nonGroupField->id);

                    if ($foundValue = ArrayHelper::getValue($content, $groupFieldUid . '.' . $nonGroupFieldUid)) {
                        // Move it out of the Group field content
                        $content[$nonGroupFieldUid] = $foundValue;
                        unset($content[$groupFieldUid][$nonGroupFieldUid]);
                        $contentChanged = true;
                    }
                }
            }

            if ($contentChanged) {
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

<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\Group;
use verbb\formie\fields\formfields\Repeater;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\migrations\BaseContentRefactorMigration;

class m231125_000000_craft5 extends BaseContentRefactorMigration
{
    // Properties
    // =========================================================================

    protected bool $preserveOldData = true;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // TODO: remove all deprecated and old database tables/columns at the next breakpoint. We need them for our inital migrations
        // and it's a good idea to have them hand around for a litlte while, just in case.

        // Add the new field layout column for forms
        if (!$this->db->columnExists('{{%formie_forms}}', 'formFieldLayout')) {
            $this->addColumn('{{%formie_forms}}', 'formFieldLayout', $this->mediumText()->after('handle'));
        }

        // Migrate Craft field layouts to Formie's field layout. Work already done in Formie 2 before Craft blows away field layout tables
        $forms = (new Query())->from('{{%formie_forms}}')->all();

        // Update the field layout and form elements (not the form field layout fields)
        foreach ($forms as $form) {
            $fieldLayout = null;
            $templateId = $form['templateId'];

            if ($templateId) {
                $template = (new Query())->from('{{%formie_formtemplates}}')->where(['id' => $templateId])->one();

                if ($template) {
                    $fieldLayout = Craft::$app->getFields()->getLayoutById($template['fieldLayoutId']);
                }
            }

            $this->updateElements([$form['id']], $fieldLayout);
        }

        foreach ($forms as $form) {
            $config = (new Query())->from('{{%formie_newlayout}}')->where(['formId' => $form['id']])->one();

            if (!$config) {
                continue;
            }

            Db::update('{{%formie_forms}}', [
                'formFieldLayout' => $config['layoutConfig'],
            ], ['id' => $form['id']]);
        }

        $applyFieldMigration = function($fields) {
            foreach ($fields as $field) {
                $config = (new Query())->from('{{%formie_newnestedlayout}}')->where(['fieldId' => $field['id']])->one();

                if (!$config) {
                    continue;
                }

                $settings = Json::decode($field['settings']);
                $settings['rowsConfig'] = Json::decode($config['layoutConfig'])['rowsConfig'] ?? [];

                Db::update('{{%fields}}', [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']]);
            }
        };

        // Migrate Group/Repeater field settings to not use field layouts
        $groupFields = (new Query())->from('{{%fields}}')->where(['type' => Group::class])->all();
        $repeaterFields = (new Query())->from('{{%fields}}')->where(['type' => Repeater::class])->all();

        $applyFieldMigration($groupFields);
        $applyFieldMigration($repeaterFields);

        // Move all content from custom content tables to `elements_sites`
        foreach ($forms as $form) {
            if (!$form['fieldContentTable'] || !$form['fieldLayoutId']) {
                continue;
            }

            $fieldLayout = Craft::$app->getFields()->getLayoutById($form['fieldLayoutId']);

            if (!$fieldLayout) {
                continue;
            }

            // A new element query is required for these two different operations
            $submissions = (new Query())->from('{{%formie_submissions}}')->where(['formId' => $form['id']])->all();
            $submissionIds = ArrayHelper::getColumn($submissions, 'id');

            // We need to "fix" the field layout field UID's to use the field UID's, as that's how Formie's layouts work
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $layoutElement->uid = $layoutElement->getField()->uid;
            }

            // Re-save the field layout
            Craft::$app->getFields()->saveLayout($fieldLayout);

            // Migrate content from custom tables
            $this->updateElements($submissionIds, $fieldLayout, $form['fieldContentTable']);

            // Bring across title too, `updateElements()` doesn't handle that
            foreach ($submissions as $submission) {
                Db::update('{{%elements_sites}}', ['title' => $submission['title']], ['elementId' => $submission['id']]);
            }
        }

        // Migrate Group/Repeater field content to store on the submission, not its own element
        $applyNestedFieldContentMigration = function($fields) {
            foreach ($fields as $field) {
                $settings = Json::decode($field['settings']);
                $contentTable = $settings['contentTable'] ?? null;

                if (!$contentTable) {
                    continue;
                }

                $nestedField = (new Query())->from('{{%formie_nested}}')->where(['fieldId' => $field['id']])->one();
                $fieldLayoutId = $nestedField['fieldLayoutId'] ?? null;

                if (!$fieldLayoutId) {
                    continue;
                }

                $fieldLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

                if (!$fieldLayout) {
                    continue;
                }

                $nestedRows = (new Query())->from('{{%formie_nestedfieldrows}}')->where(['fieldId' => $field['id']])->all();

                foreach ($nestedRows as $nestedRowKey => $nestedRow) {
                    $rowId = $nestedRow['id'];
                    $submissionId = $nestedRow['ownerId'];

                    $contentRow = (new Query())->from($contentTable)->where(['elementId' => $rowId])->one();
                    $submissionContentRow = (new Query())->from('{{%elements_sites}}')->where(['elementId' => $submissionId])->one();

                    if (!$contentRow || !$submissionContentRow) {
                        continue;
                    }

                    $newContent = [];

                    foreach ($contentRow as $column => $value) {
                        if (!str_starts_with($column, 'field_')) {
                            continue;
                        }

                        $handle = str_replace('field_', '', $column);
                        $handle = substr($handle, 0, -9);

                        if ($value && Json::isJsonObject($value)) {
                            $newContent[$handle] = Json::decode($value);
                        } else {
                            $newContent[$handle] = $value;
                        }
                    }

                    $submissionContent = Json::decode($submissionContentRow['content']);

                    if ($field['type'] === Group::class) {
                        $submissionContent[$field['uid']] = $newContent;
                    } else {
                        $submissionContent[$field['uid']][$nestedRowKey] = $newContent;
                    }

                    // Update the submission content
                    Db::update('{{%elements_sites}}', ['content' => $submissionContent], ['elementId' => $submissionId]);

                    echo '    > Moved nested row ' . $nestedRow['id'] . ' content to submission ' . $submissionId . ' ...' . PHP_EOL;
                }
            }
        };

        $applyNestedFieldContentMigration($groupFields);
        $applyNestedFieldContentMigration($repeaterFields);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231125_000000_craft5 cannot be reverted.\n";

        return false;
    }
}

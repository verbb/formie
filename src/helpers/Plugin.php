<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\web\assets\forms\FormsAsset;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class Plugin
{
    // Static Methods
    // =========================================================================

    public static function registerAsset(string $path): void
    {
        $viteService = Formie::$plugin->getVite();

        $scriptOptions = [
            'depends' => [
                FormsAsset::class,
            ],
            'onload' => '',
        ];

        $styleOptions = [
            'depends' => [
                FormsAsset::class,
            ],
        ];

        $viteService->register($path, false, $scriptOptions, $styleOptions);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }

    public static function saveFormie3Layout(Form $form): void
    {
        $fieldLayout = $form->getFormFieldLayout();

        if (!$fieldLayout) {
            return;
        }

        $newLayoutConfig = [];
        $nestedFields = [];

        foreach ($fieldLayout->getPages() as $pageKey => $page) {
            $newLayoutConfig['pages'][$pageKey]['label'] = $page->name;
            $newLayoutConfig['pages'][$pageKey]['settings'] = $page->settings->toArray();

            foreach ($page->getRows() as $rowKey => $row) {
                foreach ($row['fields'] as $fieldKey => $field) {
                    $newFieldConfig = $field->getSettings();
                    $newFieldConfig['type'] = get_class($field);
                    $newFieldConfig['label'] = $field->name;
                    $newFieldConfig['handle'] = $field->handle;
                    $newFieldConfig['required'] = $field->required;
                    $newFieldConfig['instructions'] = $field->instructions;

                    if ($field instanceof NestedFieldInterface) {
                        if ($fieldLayout = $field->getFieldLayout()) {
                            foreach ($fieldLayout->getPages() as $nestedPageKey => $nestedPage) {
                                foreach ($nestedPage->getRows() as $nestedRowKey => $nestedRow) {
                                    foreach ($nestedRow['fields'] as $nestedFieldKey => $nestedField) {
                                        $nestedFieldConfig = $nestedField->getSettings();
                                        $nestedFieldConfig['type'] = get_class($nestedField);
                                        $nestedFieldConfig['label'] = $nestedField->name;
                                        $nestedFieldConfig['handle'] = $nestedField->handle;
                                        $nestedFieldConfig['required'] = $nestedField->required;
                                        $nestedFieldConfig['instructions'] = $nestedField->instructions;

                                        $newFieldConfig['rows'][$nestedRowKey]['fields'][] = $nestedFieldConfig;
                                    }
                                }
                            }
                        }
                    }

                    $newLayoutConfig['pages'][$pageKey]['rows'][$rowKey]['fields'][] = $newFieldConfig;
                }
            }
        }

        $existingLayout = (new Query())->from('{{%formie_newlayout}}')->where(['formId' => $form->id])->one();

        $newLayoutConfig = StringHelper::emojiToShortcodes(Json::encode($newLayoutConfig));

        if ($existingLayout) {
            Db::update('{{%formie_newlayout}}', [
                'formId' => $form->id,
                'layoutConfig' => $newLayoutConfig,
            ], ['id' => $existingLayout['id']]);
        } else {
            Db::insert('{{%formie_newlayout}}', [
                'formId' => $form->id,
                'layoutConfig' => $newLayoutConfig,
            ]);
        }
    }
}

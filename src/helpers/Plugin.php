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

use verbb\base\helpers\Plugin as BasePlugin;

class Plugin extends BasePlugin
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
                    $newLayoutConfig['pages'][$pageKey]['rows'][$rowKey]['fields'][] = [
                        'fieldUid' => $field->uid,
                        'required' => $field->required,
                    ];

                    if ($field instanceof NestedFieldInterface) {
                        $nestedFields[] = $field;
                    }
                }
            }
        }

        $existingLayout = (new Query())->from('{{%formie_newlayout}}')->where(['formId' => $form->id])->one();

        if ($existingLayout) {
            Db::update('{{%formie_newlayout}}', [
                'formId' => $form->id,
                'layoutConfig' => Json::encode($newLayoutConfig),
            ], ['id' => $existingLayout['id']]);
        } else {
            Db::insert('{{%formie_newlayout}}', [
                'formId' => $form->id,
                'layoutConfig' => Json::encode($newLayoutConfig),
            ]);
        }

        // Do a similar thing for Group/Repeater fields, which have field layouts, but won't in Formie 3.
        foreach ($nestedFields as $nestedField) {
            $newNestedConfig = [];

            if ($fieldLayout = $nestedField->getFieldLayout()) {
                foreach ($fieldLayout->getPages() as $pageKey => $page) {
                    foreach ($page->getRows() as $rowKey => $row) {
                        foreach ($row['fields'] as $fieldKey => $field) {
                            $newNestedConfig['rowsConfig'][$rowKey]['fields'][] = [
                                'fieldUid' => $field->uid,
                                'required' => $field->required,
                            ];
                        }
                    }
                }
            }

            if ($newNestedConfig) {
                $existingLayout = (new Query())->from('{{%formie_newnestedlayout}}')->where(['fieldId' => $nestedField->id])->one();

                if ($existingLayout) {
                    Db::update('{{%formie_newnestedlayout}}', [
                        'fieldId' => $nestedField->id,
                        'layoutConfig' => Json::encode($newNestedConfig),
                    ], ['id' => $existingLayout['id']]);
                } else {
                    Db::insert('{{%formie_newnestedlayout}}', [
                        'fieldId' => $nestedField->id,
                        'layoutConfig' => Json::encode($newNestedConfig),
                    ]);
                }
            }
        }
    }
}

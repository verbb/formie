<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use Craft;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Form;
use verbb\formie\factories\FormFactory as PluginFormFactory;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

final class ConditionFormFactory
{
    public static function make(): self
    {
        return new self();
    }

    public static function browserPath(Form $form): string
    {
        return '/formie/tests/conditions/' . $form->handle;
    }

    public function optionsValueVisibility(array $formConfig = []): Form
    {
        return $this->form(array_merge([
            'title' => 'Test Conditions Options Value',
        ], $formConfig))
            ->singleFieldRows()
            ->radioField('enquiryType', [
                'label' => 'Enquiry Type',
                'layout' => 'horizontal',
                'options' => self::optionRows(),
            ])
            ->singleLineTextField('otherReason', [
                'label' => 'Other Reason',
                'enableConditions' => true,
                'conditions' => self::simpleConditions('enquiryType', ConditionOperator::EQ, 'other'),
            ])
            ->create();
    }

    public function nestedGroupRowCollapse(array $formConfig = []): Form
    {
        return $this->form(array_merge([
            'title' => 'Test Conditions Nested Group',
        ], $formConfig))
            ->singleFieldRows()
            ->groupField('contactGroup', [
                'label' => 'Contact Group',
                'rows' => self::rows(
                    [[
                        'type' => Dropdown::class,
                        'handle' => 'preferredChannel',
                        'label' => 'Preferred Channel',
                        'options' => self::channelRows(),
                    ]],
                    [[
                        'type' => SingleLineText::class,
                        'handle' => 'smsNumber',
                        'label' => 'SMS Number',
                        'enableConditions' => true,
                        'conditions' => self::simpleConditions('preferredChannel', ConditionOperator::EQ, 'sms'),
                    ]],
                ),
            ])
            ->create();
    }

    public function pageVisibility(array $formConfig = []): Form
    {
        $form = $this->form(array_merge([
            'title' => 'Test Conditions Pages',
        ], $formConfig))
            ->multiPage(2)
            ->onPage(1)
            ->singleFieldRows()
            ->dropdownField('journey', [
                'label' => 'Journey',
                'options' => [
                    ['label' => 'Basic', 'value' => 'basic', 'isDefault' => true],
                    ['label' => 'Advanced', 'value' => 'advanced'],
                ],
            ])
            ->onPage(2)
            ->singleLineTextField('advancedDetails', [
                'label' => 'Advanced Details',
            ])
            ->create();

        $pages = $form->getPages();

        if (isset($pages[1])) {
            $pageSettings = $pages[1]->getPageSettings();
            $pageSettings->enablePageConditions = true;
            $pageSettings->pageConditions = self::simpleConditions('journey', ConditionOperator::EQ, 'advanced');
            $pages[1]->setPageSettings($pageSettings->toArray());

            $layout = $form->getFormLayout();
            $layout->setPages($pages);
            $form->setFormLayout($layout);

            Craft::$app->getElements()->saveElement($form);
        }

        return $form;
    }

    public function all(array $config = []): array
    {
        $titlePrefix = $config['titlePrefix'] ?? 'Browser Harness';
        $handlePrefix = $config['handlePrefix'] ?? null;

        return [
            'options' => $this->optionsValueVisibility([
                'title' => "{$titlePrefix} Options",
                'handle' => $handlePrefix ? "{$handlePrefix}Options" : null,
            ]),
            'nestedGroup' => $this->nestedGroupRowCollapse([
                'title' => "{$titlePrefix} Nested Group",
                'handle' => $handlePrefix ? "{$handlePrefix}NestedGroup" : null,
            ]),
            'pages' => $this->pageVisibility([
                'title' => "{$titlePrefix} Pages",
                'handle' => $handlePrefix ? "{$handlePrefix}Pages" : null,
            ]),
        ];
    }


    private function form(array $config = []): PluginFormFactory
    {
        $config = array_filter($config, static fn(mixed $value): bool => $value !== null);

        return Formie::$plugin->getFactories()->form($config);
    }

    private static function optionRows(): array
    {
        return [
            ['label' => 'General', 'value' => 'general', 'isDefault' => true],
            ['label' => 'Support', 'value' => 'support'],
            ['label' => 'Other', 'value' => 'other'],
        ];
    }

    private static function channelRows(): array
    {
        return [
            ['label' => 'Email', 'value' => 'email', 'isDefault' => true],
            ['label' => 'Phone', 'value' => 'phone'],
            ['label' => 'SMS', 'value' => 'sms'],
        ];
    }

    private static function rows(array ...$rows): array
    {
        return array_map(static function(array $fields): array {
            return ['fields' => $fields];
        }, $rows);
    }

    private static function simpleConditions(
        string $sourceHandle,
        string $operator,
        string $value,
        string $showRule = 'show',
        string $conditionRule = 'all',
    ): array {
        return [
            'showRule' => $showRule,
            'conditionRule' => $conditionRule,
            'conditions' => [
                [
                    'field' => $sourceHandle,
                    'condition' => $operator,
                    'value' => $value,
                ],
            ],
        ];
    }
}

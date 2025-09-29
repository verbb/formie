<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

use DateTime;

class DateYearDropdown extends DateDropdown implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Year');
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getEmailTemplatePath(): string
    {
        return 'fields/dropdown';
    }


    // Properties
    // =========================================================================

    public string $validationFormatParam = 'Y';
    public int $minYearRange = 100;
    public int $maxYearRange = 100;


    // Public Methods
    // =========================================================================

    public function options(): array
    {
        $options = [['value' => '', 'label' => null, 'disabled' => true]];

        $date = $this->parentField?->defaultValue ?: new DateTime();
        $year = (int)$date->format('Y');
        $minYear = $year + $this->minYearRange;
        $maxYear = $year + $this->maxYearRange;

        for ($y = $minYear; $y <= $maxYear; $y++) {
            $options[] = ['value' => $y, 'label' => $y];
        }

        return $options;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Year Range'),
                'help' => Craft::t('formie', 'Set the range of years relative to this year that are available to select. Use negative values for start to offset into the past from the current year.'),
                'if' => '$get(displayType).value == dropdowns',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'name' => 'minYearRange',
                                'inputClass' => 'text flex-grow',
                                'validation' => 'number',
                                'sections-schema' => [
                                    'prefix' => [
                                        '$el' => 'span',
                                        'attrs' => ['class' => 'fui-prefix-text'],
                                        'children' => Craft::t('formie', 'Start'),
                                    ],
                                ],
                            ]),
                            SchemaHelper::numberField([
                                'name' => 'maxYearRange',
                                'inputClass' => 'text flex-grow',
                                'validation' => 'number',
                                'sections-schema' => [
                                    'prefix' => [
                                        '$el' => 'span',
                                        'attrs' => ['class' => 'fui-prefix-text'],
                                        'children' => Craft::t('formie', 'End'),
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }
}

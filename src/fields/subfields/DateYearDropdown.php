<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

use DateTime;
use verbb\formie\fields\values\DateFieldValue;

class DateYearDropdown extends DateDropdown implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Year');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getReferenceBlockTemplatePath(): string
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

        $date = DateFieldValue::toDateTime($this->parentField->getInitialValue()) ?: new DateTime();
        $year = (int)$date->format('Y');
        $minYear = $year - $this->minYearRange;
        $maxYear = $year + $this->maxYearRange;

        for ($y = $minYear; $y < $maxYear; $y++) {
            $options[] = ['value' => $y, 'label' => $y];
        }

        return $options;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Year Range'),
                'instructions' => Craft::t('formie', 'Set the range of years relative to this year that are available to select.'),
                'if' => 'displayType == "dropdowns"',
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'minYearRange',
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
                        'sections-schema' => [
                            'prefix' => [
                                '$el' => 'span',
                                'attrs' => ['class' => 'fui-prefix-text'],
                                'children' => Craft::t('formie', 'End'),
                            ],
                        ],
                    ]),
                ],
            ]),
        ];
    }
}

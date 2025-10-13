<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\fields\subfields;
use verbb\formie\models\FieldLayout;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m240313_000000_subfields extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELDS])
            ->all();

        foreach ($fields as $field) {
            $fieldLayout = null;
            $settings = Json::decode($field['settings']);

            if ($field['type'] === fields\Address::class) {
                // Get the field layout for the field, or if it already exists with fields, skip
                $fieldLayout = $this->_getFieldLayout($settings);

                if ($fieldLayout->getFields()) {
                    continue;
                }

                $fieldLayout->getPages()[0]->setRows($this->_getAddressConfig($settings));
            }

            if ($field['type'] === fields\Date::class) {
                $displayType = $settings['displayType'] ?? 'calendar';

                if ($displayType == 'calendar' || $displayType == 'datePicker') {
                    // Get the field layout for the field, or if it already exists with fields, skip
                    $fieldLayout = $this->_getFieldLayout($settings);

                    if ($fieldLayout->getFields()) {
                        continue;
                    }

                    $fieldLayout->getPages()[0]->setRows($this->_getDateCalendarConfig($settings));
                }

                if ($displayType == 'dropdowns') {
                    // Get the field layout for the field, or if it already exists with fields, skip
                    $fieldLayout = $this->_getFieldLayout($settings);

                    if ($fieldLayout->getFields()) {
                        continue;
                    }

                    $fieldLayout->getPages()[0]->setRows($this->_getDateDropdownsConfig($settings));
                }

                if ($displayType == 'inputs') {
                    // Get the field layout for the field, or if it already exists with fields, skip
                    $fieldLayout = $this->_getFieldLayout($settings);

                    if ($fieldLayout->getFields()) {
                        continue;
                    }

                    $fieldLayout->getPages()[0]->setRows($this->_getDateInputsConfig($settings));
                }
            }

            if ($field['type'] === fields\Name::class) {
                $useMultipleFields = $settings['useMultipleFields'] ?? false;

                if ($useMultipleFields) {
                    // Get the field layout for the field, or if it already exists with fields, skip
                    $fieldLayout = $this->_getFieldLayout($settings);

                    if ($fieldLayout->getFields()) {
                        continue;
                    }

                    $fieldLayout->getPages()[0]->setRows($this->_getNameConfig($settings));
                }
            }

            if ($fieldLayout) {
                if (!Formie::$plugin->getFields()->saveLayout($fieldLayout)) {
                    echo '    > ' . $field['handle'] . ': Unable to save field layout - ' . Json::encode($fieldLayout->getErrors()) . PHP_EOL;
                    // echo '    > ' . Json::encode($layoutConfig);

                    return false;
                }

                $settings['nestedLayoutId'] = $fieldLayout->id;

                $this->update(Table::FORMIE_FIELDS, [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240313_000000_subfields cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _getFieldLayout(array $settings): FieldLayout
    {
        $fieldLayout = null;
        $nestedLayoutId = $settings['nestedLayoutId'] ?? null;

        if ($nestedLayoutId) {
            $fieldLayout = Formie::$plugin->getFields()->getLayoutById($nestedLayoutId);
        }

        return $fieldLayout ?? new FieldLayout();
    }

    private function _getAddressConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\Address1::class,
                        'label' => $settings['address1Label'] ?? Craft::t('formie', 'Address 1'),
                        'handle' => 'address1',
                        'enabled' => $settings['address1Enabled'] ?? true,
                        'required' => $settings['address1Required'] ?? false,
                        'errorMessage' => $settings['address1ErrorMessage'] ?? null,
                        'placeholder' => $settings['address1Placeholder'] ?? null,
                        'defaultValue' => $settings['address1DefaultValue'] ?? null,
                        'prePopulate' => $settings['address1PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line1',
                            ],
                            [
                                'label' => 'data-address1',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address2::class,
                        'label' => $settings['address2Label'] ?? Craft::t('formie', 'Address 2'),
                        'handle' => 'address2',
                        'enabled' => $settings['address2Enabled'] ?? false,
                        'required' => $settings['address2Required'] ?? false,
                        'errorMessage' => $settings['address2ErrorMessage'] ?? null,
                        'placeholder' => $settings['address2Placeholder'] ?? null,
                        'defaultValue' => $settings['address2DefaultValue'] ?? null,
                        'prePopulate' => $settings['address2PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line2',
                            ],
                            [
                                'label' => 'data-address2',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address3::class,
                        'label' => $settings['address3Label'] ?? Craft::t('formie', 'Address 3'),
                        'handle' => 'address3',
                        'enabled' => $settings['address3Enabled'] ?? false,
                        'required' => $settings['address3Required'] ?? false,
                        'errorMessage' => $settings['address3ErrorMessage'] ?? null,
                        'placeholder' => $settings['address3Placeholder'] ?? null,
                        'defaultValue' => $settings['address3DefaultValue'] ?? null,
                        'prePopulate' => $settings['address3PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line3',
                            ],
                            [
                                'label' => 'data-address3',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressCity::class,
                        'label' =>  $settings['cityLabel'] ?? Craft::t('formie', 'City'),
                        'handle' => 'city',
                        'enabled' => $settings['cityEnabled'] ?? true,
                        'required' => $settings['cityRequired'] ?? false,
                        'errorMessage' => $settings['cityErrorMessage'] ?? null,
                        'placeholder' => $settings['cityPlaceholder'] ?? null,
                        'defaultValue' => $settings['cityDefaultValue'] ?? null,
                        'prePopulate' => $settings['cityPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level2',
                            ],
                            [
                                'label' => 'data-city',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressZip::class,
                        'label' => $settings['zipLabel'] ?? Craft::t('formie', 'ZIP / Postal Code'),
                        'handle' => 'zip',
                        'enabled' => $settings['zipEnabled'] ?? true,
                        'required' => $settings['zipRequired'] ?? false,
                        'errorMessage' => $settings['zipErrorMessage'] ?? null,
                        'placeholder' => $settings['zipPlaceholder'] ?? null,
                        'defaultValue' => $settings['zipDefaultValue'] ?? null,
                        'prePopulate' => $settings['zipPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'postal-code',
                            ],
                            [
                                'label' => 'data-zip',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressState::class,
                        'label' => $settings['stateLabel'] ?? Craft::t('formie', 'State / Province'),
                        'handle' => 'state',
                        'enabled' => $settings['stateEnabled'] ?? true,
                        'required' => $settings['stateRequired'] ?? false,
                        'errorMessage' => $settings['stateErrorMessage'] ?? null,
                        'placeholder' => $settings['statePlaceholder'] ?? null,
                        'defaultValue' => $settings['stateDefaultValue'] ?? null,
                        'prePopulate' => $settings['statePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level1',
                            ],
                            [
                                'label' => 'data-state',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressCountry::class,
                        'label' => $settings['countryLabel'] ?? Craft::t('formie', 'Country'),
                        'handle' => 'country',
                        'enabled' => $settings['countryEnabled'] ?? true,
                        'required' => $settings['countryRequired'] ?? false,
                        'errorMessage' => $settings['countryErrorMessage'] ?? null,
                        'placeholder' => $settings['countryPlaceholder'] ?? null,
                        'defaultValue' => $settings['countryDefaultValue'] ?? null,
                        'prePopulate' => $settings['countryPrePopulate'] ?? null,
                        'optionLabel' => $settings['countryOptionLabel'] ?? 'full',
                        'optionValue' => $settings['countryOptionValue'] ?? 'short',
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'country',
                            ],
                            [
                                'label' => 'data-country',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getDateCalendarConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateDate::class,
                        'label' => $settings['dateLabel'] ?? Craft::t('formie', 'Date'),
                        'handle' => 'date',
                        'enabled' => $settings['includeDate'] ?? true,
                        'required' => $settings['required'] ?? false,
                        'placeholder' => $settings['placeholder'] ?? null,
                        'errorMessage' => $settings['errorMessage'] ?? null,
                        'defaultValue' => $settings['defaultValue'] ?? null,
                        'labelPosition' => HiddenPosition::class,
                        'inputAttributes' => [
                            [
                                'label' => 'type',
                                'value' => 'date',
                            ],
                            [
                                'label' => 'autocomplete',
                                'value' => 'off',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\DateTime::class,
                        'label' => $settings['timeLabel'] ?? Craft::t('formie', 'Time'),
                        'handle' => 'time',
                        'enabled' => $settings['includeTime'] ?? true,
                        'required' => $settings['required'] ?? false,
                        'placeholder' => $settings['placeholder'] ?? null,
                        'errorMessage' => $settings['errorMessage'] ?? null,
                        'defaultValue' => $settings['defaultValue'] ?? null,
                        'labelPosition' => HiddenPosition::class,
                        'inputAttributes' => [
                            [
                                'label' => 'type',
                                'value' => 'time',
                            ],
                            [
                                'label' => 'autocomplete',
                                'value' => 'off',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getDateDropdownsConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateYearDropdown::class,
                        'label' => $settings['yearLabel'] ?? Craft::t('formie', 'Year'),
                        'handle' => 'year',
                        'enabled' => true,
                        'placeholder' => $settings['yearPlaceholder'] ?? null,
                        'options' => [],
                    ],
                    [
                        'type' => subfields\DateMonthDropdown::class,
                        'label' => $settings['monthLabel'] ?? Craft::t('formie', 'Month'),
                        'handle' => 'month',
                        'enabled' => true,
                        'placeholder' => $settings['monthPlaceholder'] ?? null,
                        'options' => $this->_getMonthOptions(),
                    ],
                    [
                        'type' => subfields\DateDayDropdown::class,
                        'label' => Craft::t('formie', 'Day'),
                        'handle' => 'day',
                        'enabled' => true,
                        'placeholder' => $settings['dayPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(1, 31),
                    ],
                    [
                        'type' => subfields\DateHourDropdown::class,
                        'label' => $settings['hourLabel'] ?? Craft::t('formie', 'Hour'),
                        'handle' => 'hour',
                        'enabled' => true,
                        'placeholder' => $settings['hourPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 23),
                    ],
                    [
                        'type' => subfields\DateMinuteDropdown::class,
                        'label' => $settings['minueLabel'] ?? Craft::t('formie', 'Minute'),
                        'handle' => 'minute',
                        'enabled' => true,
                        'placeholder' => $settings['minutePlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 59),
                    ],
                    [
                        'type' => subfields\DateSecondDropdown::class,
                        'label' => $settings['secondLabel'] ?? Craft::t('formie', 'Second'),
                        'handle' => 'second',
                        'enabled' => false,
                        'placeholder' => $settings['secondPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 59),
                    ],
                    [
                        'type' => subfields\DateAmPmDropdown::class,
                        'label' => $settings['ampmLabel'] ?? Craft::t('formie', 'AM/PM'),
                        'handle' => 'ampm',
                        'enabled' => false,
                        'placeholder' => $settings['ampmPlaceholder'] ?? null,
                        'options' => [
                            ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                            ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getDateInputsConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateYearNumber::class,
                        'label' => $settings['yearLabel'] ?? Craft::t('formie', 'Year'),
                        'handle' => 'year',
                        'enabled' => true,
                        'placeholder' => $settings['yearPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1924,
                        'max' => 2124,
                    ],
                    [
                        'type' => subfields\DateMonthNumber::class,
                        'label' => $settings['monthLabel'] ?? Craft::t('formie', 'Month'),
                        'handle' => 'month',
                        'enabled' => true,
                        'placeholder' => $settings['monthPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1,
                        'max' => 12,
                    ],
                    [
                        'type' => subfields\DateDayNumber::class,
                        'label' => Craft::t('formie', 'Day'),
                        'handle' => 'day',
                        'enabled' => true,
                        'placeholder' => $settings['dayPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1,
                        'max' => 31,
                    ],
                    [
                        'type' => subfields\DateHourNumber::class,
                        'label' => $settings['hourLabel'] ?? Craft::t('formie', 'Hour'),
                        'handle' => 'hour',
                        'enabled' => true,
                        'placeholder' => $settings['hourPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 23,
                    ],
                    [
                        'type' => subfields\DateMinuteNumber::class,
                        'label' => $settings['minueLabel'] ?? Craft::t('formie', 'Minute'),
                        'handle' => 'minute',
                        'enabled' => true,
                        'placeholder' => $settings['minutePlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 59,
                    ],
                    [
                        'type' => subfields\DateSecondNumber::class,
                        'label' => $settings['secondLabel'] ?? Craft::t('formie', 'Second'),
                        'handle' => 'second',
                        'enabled' => false,
                        'placeholder' => $settings['secondPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 59,
                    ],
                    [
                        'type' => subfields\DateAmPmDropdown::class,
                        'label' => $settings['ampmLabel'] ?? Craft::t('formie', 'AM/PM'),
                        'handle' => 'ampm',
                        'enabled' => false,
                        'placeholder' => $settings['ampmPlaceholder'] ?? null,
                        'options' => [
                            ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                            ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getNameConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\NamePrefix::class,
                        'label' => $settings['prefixLabel'] ?? Craft::t('formie', 'Prefix'),
                        'handle' => 'prefix',
                        'enabled' => $settings['prefixEnabled'] ?? false,
                        'required' => $settings['prefixRequired'] ?? false,
                        'errorMessage' => $settings['prefixErrorMessage'] ?? null,
                        'placeholder' => $settings['prefixPlaceholder'] ?? null,
                        'defaultValue' => $settings['prefixDefaultValue'] ?? null,
                        'prePopulate' => $settings['prefixPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'honorific-prefix',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameFirst::class,
                        'label' => $settings['firstNameLabel'] ?? Craft::t('formie', 'First Name'),
                        'handle' => 'firstName',
                        'enabled' => $settings['firstNameEnabled'] ?? true,
                        'required' => $settings['firstNameRequired'] ?? false,
                        'errorMessage' => $settings['firstNameErrorMessage'] ?? null,
                        'placeholder' => $settings['firstNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['firstNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['firstNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'given-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameMiddle::class,
                        'label' => $settings['middleNameLabel'] ?? Craft::t('formie', 'Middle Name'),
                        'handle' => 'middleName',
                        'enabled' => $settings['middleNameEnabled'] ?? false,
                        'required' => $settings['middleNameRequired'] ?? false,
                        'errorMessage' => $settings['middleNameErrorMessage'] ?? null,
                        'placeholder' => $settings['middleNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['middleNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['middleNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'additional-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameLast::class,
                        'label' => $settings['lastNameLabel'] ?? Craft::t('formie', 'Last Name'),
                        'handle' => 'lastName',
                        'enabled' => $settings['lastNameEnabled'] ?? true,
                        'required' => $settings['lastNameRequired'] ?? false,
                        'errorMessage' => $settings['lastNameErrorMessage'] ?? null,
                        'placeholder' => $settings['lastNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['lastNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['lastNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'family-name',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _generateOptions(int $start, int $end, ?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        for ($i = $start; $i <= $end; $i++) {
            $options[] = ['label' => $i, 'value' => $i];
        }

        return $options;
    }

    private function _getMonthOptions(?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $options[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $options;
    }
}

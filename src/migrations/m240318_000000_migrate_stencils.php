<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\StencilData;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

use Throwable;

class m240318_000000_migrate_stencils extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $stencils = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_STENCILS])
            ->all();

        foreach ($stencils as $stencil) {
            // Don't throw errors here, they can be handled later
            try {
                $data = $stencil['data'] ?? [];
                $data = Json::decode($data);

                // Handle converting fields recursively
                $normalizeField = function(&$field) use (&$normalizeField) {
                    $field['settings']['handle'] = ArrayHelper::remove($field, 'handle');
                    $field['settings']['label'] = ArrayHelper::remove($field, 'label');

                    if (isset($field['rows'])) {
                        $field['settings']['rows'] = ArrayHelper::remove($field, 'rows', []);
                    }

                    $field['type'] = str_replace('verbb\\formie\\fields\\formfields\\', 'verbb\\formie\\fields\\', $field['type']);

                    // Remove everything apart from settings and type - everything should be under settings
                    foreach ($field as $fieldProp => $fieldValue) {
                        if ($fieldProp !== 'settings' && $fieldProp !== 'type') {
                            ArrayHelper::remove($field, $fieldProp);
                        }
                    }

                    // Handle nested fields
                    if (isset($field['settings']['rows']) && is_array($field['settings']['rows'])) {
                        foreach ($field['settings']['rows'] as $nestedRowKey => &$nestedRow) {
                            ArrayHelper::remove($nestedRow, 'id');

                            foreach ($nestedRow['fields'] as $nestedFieldKey => &$nestedField) {
                                $normalizeField($nestedField);
                            }
                        }
                    }

                    // Handle sub-fields
                    if ($field['type'] === fields\Address::class) {
                        $field['settings']['rows'] = $this->_getAddressConfig($field['settings']);
                    }
                    
                    if ($field['type'] === fields\Date::class) {
                        $field['settings']['rows'] = $this->_getDateConfig($field['settings']);
                    }
                    
                    if ($field['type'] === fields\Name::class) {
                        $field['settings']['rows'] = $this->_getNameConfig($field['settings']);
                    }
                };

                // Migrate page data from Formie v2
                if (isset($data['pages'])) {
                    foreach ($data['pages'] as $pageKey => &$page) {
                        ArrayHelper::remove($page, 'id');
                        ArrayHelper::remove($page, 'notificationFlag');

                        if (isset($page['settings']['label'])) {
                            unset($page['settings']['label']);
                        }

                        if (isset($page['rows'])) {
                            foreach ($page['rows'] as $rowKey => &$row) {
                                ArrayHelper::remove($row, 'id');

                                if (isset($row['fields'])) {
                                    foreach ($row['fields'] as $fieldKey => &$field) {
                                        $normalizeField($field);
                                    }
                                }
                            }
                        }
                    }
                }

                // Create a field layout so that's everything is setup correctly like a normal form
                $layout = new FieldLayout();
                $layout->setPages($data['pages']);

                // Serialize it back to the stencil data
                $data['pages'] = StencilData::getSerializedLayout($layout);

                // While we're at it, normalize the notifications and settings too, as they've been updated with serialization handling
                $notifications = $data['notifications'] ?? [];
                $data['notifications'] = StencilData::getSerializedNotifications($notifications);

                $settings = $data['settings'] ?? [];
                $data['settings'] = StencilData::getSerializedFormSettings($settings);

                $this->update(Table::FORMIE_STENCILS, [
                    'data' => Json::encode($data),
                ], ['id' => $stencil['id']], [], false);
            } catch (Throwable $e) {

            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240318_000000_migrate_stencils cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

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

    private function _getDateConfig(array $settings): array
    {
        $displayType = $settings['displayType'] ?? 'calendar';

        if ($displayType == 'calendar') {
            return $this->_getDateCalendarConfig($settings);
        }

        if ($displayType == 'dropdowns') {
            return $this->_getDateDropdownsConfig($settings);
        }

        if ($displayType == 'inputs') {
            return $this->_getDateInputsConfig($settings);
        }

        return [];
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

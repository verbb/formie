<?php
namespace verbb\formie\elements\exporters;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

class SubmissionExport extends ElementExporter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Default');
    }

    /**
     * @inheritdoc
     */
    public function export(ElementQueryInterface $query): array
    {
        // Eager-load as much as we can
        $eagerLoadableFields = [];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFields[] = $field->handle;
            }
        }

        $data = [];

        $attributes = [
            'id',
            'formId',
            'status',
            'userId',
            'ipAddress',
            'isIncomplete',
            'isSpam',
            'spamReason',
            'title',
            'dateCreated',
            'dateUpdated',
            'dateDeleted',
            'trashed',
        ];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        // Fix when trying to export from "All Forms"
        if (is_array($query->formId)) {
            $query->formId = null;
        }

        foreach ($query->each() as $element) {
            $row = $element->toArray($attributes);

            if (($fieldLayout = $element->getFieldLayout()) !== null) {
                foreach ($fieldLayout->getFields() as $field) {
                    $value = $element->getFieldValue($field->handle);

                    if (method_exists($field, 'serializeValueForExport')) {
                        $fieldValue = $field->serializeValueForExport($value, $element);

                        if (is_array($fieldValue)) {
                            $row = array_merge($row, $fieldValue);
                        } else {
                            $row[$field->handle] = $fieldValue;
                        }
                    } else {
                        $row[$field->handle] = $field->serializeValue($value, $element);
                    }
                }
            }

            $data[] = $row;
        }

        // Normalise the columns. Due to repeaters/table fields, some rows might not have the correct columns.
        // We need to have all rows have the same column definitions. Run through once to build all possible keys.
        $keys = [];
        foreach ($data as $key => $value) {
            $keys = array_merge($keys, array_keys($value));
        }
        $keys = array_values(array_unique($keys));

        // Then, fill in any gaps in rows with empty columns, so they all have the same columns
        foreach ($data as $rowIndex => &$columns) {
            foreach ($keys as $key) {
                if (!isset($columns[$key])) {
                    $columns[$key] = '';
                }
            }
        }

        // We might have to do some post-processing for CSV's and nested fields like Table/Repeater
        // We want to split the rows of these fields into new lines, which is a bit tedious..
        // Comment out for the moment...
        // $format = Craft::$app->getRequest()->getBodyParam('format', 'csv');

        // if ($format === 'csv') {
        //     $csvData = [];
        //     $rowIndex = 0;

        //     foreach ($data as $i => $column) {
        //         $extraRows = [];

        //         foreach ($column as $j => $value) {
        //             if (is_array($value)) {
        //                 // Split out each row into extra rows to add later.
        //                 foreach ($value as $k => $v) {
        //                     // Add this first value here though.
        //                     if ($k === 0) {
        //                         $csvData[$rowIndex][$j] = $v;
        //                     } else {
        //                         $extraRows[$k][$j] = $v;
        //                     }
        //                 }
        //             } else {
        //                 $csvData[$rowIndex][$j] = $value;
        //             }
        //         }

        //         if ($extraRows) {
        //             $rowIndex++;

        //             // We have to loop through each existing column to add blank column values
        //             foreach ($extraRows as $ii => $extraRow) {
        //                 foreach ($column as $j => $value) {
        //                     $csvData[$rowIndex][$j] = isset($extraRow[$j]) ? $extraRow[$j] : '';
        //                 }

        //                 // Increment to cater for all the new rows
        //                 $rowIndex++;
        //             }
        //         }

        //         $rowIndex++;
        //     }

        //     return $csvData;
        // }

        return $data;
    }
}

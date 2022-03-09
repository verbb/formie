<?php
namespace verbb\formie\elements\exporters;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

class SubmissionExport extends ElementExporter
{
    // Static Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Default');
    }


    // Public Methods
    // =========================================================================

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

        foreach ($query->each() as $element) {
            // Fetch the attributes for the element
            $row = $element->toArray($attributes);

            // Fetch the custom field content, already prepped
            $fieldValues = $element->getValuesForExport();

            $data[] = array_merge($row, $fieldValues);
        }

        // Normalise the columns. Due to repeaters/table fields, some rows might not have the correct columns.
        // We need to have all rows have the same column definitions. 
        // First, find the row with the largest columns to use as our template for all other rows
        $counts = array_map('count', $data);
        $key = array_flip($counts)[max($counts)];
        $largestRow = $data[$key];

        // Now we have the largest row in columns, normalise all other rows, filling in blanks
        $keys = array_keys($largestRow);
        $template = array_fill_keys($keys, '');

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

        return array_map(function($item) use ($template) {
            return array_merge($template, $item);
        }, $data);
    }
}

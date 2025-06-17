<?php
namespace verbb\formie\elements\exporters;

use verbb\formie\Formie;
use verbb\formie\events\ModifySubmissionExportDataEvent;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;

use DateTime;
use Throwable;

class SubmissionExport extends ElementExporter
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_EXPORT_DATA = 'modifyExportData';
    

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Default');
    }


    // Public Methods
    // =========================================================================
    
    public function getFilename(): string
    {
        return 'formie-submissions-' . (new DateTime())->format('Y-m-d-H-i');
    }

    public function export(ElementQueryInterface $query): array
    {
        try {
            $data = [];

            $attributes = [
                'id' => Craft::t('site', 'ID'),
                'formId' => Craft::t('site', 'Form ID'),
                'formName' => Craft::t('site', 'Form Name'),
                'userId' => Craft::t('site', 'User ID'),
                'ipAddress' => Craft::t('site', 'IP Address'),
                'isIncomplete' => Craft::t('site', 'Is Incomplete?'),
                'isSpam' => Craft::t('site', 'Is Spam?'),
                'spamReason' => Craft::t('site', 'Spam Reason'),
                'spamClass' => Craft::t('site', 'Spam Type'),
                'title' => Craft::t('site', 'Title'),
                'dateCreated' => Craft::t('site', 'Date Created'),
                'dateUpdated' => Craft::t('site', 'Date Updated'),
                'dateDeleted' => Craft::t('site', 'Date Deleted'),
                'trashed' => Craft::t('site', 'Trashed'),
                'status' => Craft::t('site', 'Status'),
            ];

            foreach ($query->each() as $element) {
                // Fetch the attributes for the element
                $values = [];

                foreach ($attributes as $attr => $label) {
                    $value = $element->$attr;
                    
                    if ($value instanceof DateTime) {
                        $value = DateTimeHelper::toIso8601($value) ?: null;
                    }

                    $values[] = $value;
                }

                $row = array_combine(array_values($attributes), $values);

                // Fetch the custom field content, already prepped
                $fieldValues = $element->getValuesForExport();

                $data[] = array_merge($row, $fieldValues);
            }

            // Normalise the columns. Due to repeaters/table fields, some rows might not have the correct columns.
            // We need to have all rows have the same column definitions. 
            // First, find the row with the largest columns to use as our template for all other rows
            $exportData = [];
            $counts = array_map('count', $data);
            
            if ($counts) {
                $key = array_flip($counts)[max($counts)];
                $largestRow = $data[$key];

                // Now we have the largest row in columns, normalise all other rows, filling in blanks
                $keys = array_keys($largestRow);
                $template = array_fill_keys($keys, '');

                $exportData = array_map(function($item) use ($template) {
                    return array_merge($template, $item);
                }, $data);
            }

            $event = new ModifySubmissionExportDataEvent([
                'exportData' => $exportData,
                'query' => $query,
            ]);
            $this->trigger(self::EVENT_MODIFY_EXPORT_DATA, $event);

            return $event->exportData;
        } catch (Throwable $e) {
            Formie::info('{message} {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return [];
    }
}

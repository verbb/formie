<?php
namespace verbb\formie\elements\exporters;

use verbb\formie\events\ModifySubmissionExportDataEvent;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ElementHelper;

use DateTime;

class SubmissionExport extends ElementExporter
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_EXPORT_DATA = 'modifyExportData';
    

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
    public function getFilename(): string
    {
        return 'formie-submissions-' . (new DateTime())->format('Y-m-d-H-i');
    }

    /**
     * @inheritdoc
     */
    public function export(ElementQueryInterface $query): array
    {
        try {
            // Eager-load as much as we can
            $eagerLoadableFields = [];

            foreach (Craft::$app->getFields()->getAllFields() as $field) {
                if ($field instanceof EagerLoadingFieldInterface && strstr($field->context, 'formie')) {
                    $eagerLoadableFields[] = $field->handle;
                }
            }

            $data = [];

            $attributes = [
                'id' => Craft::t('site', 'ID'),
                'formId' => Craft::t('site', 'Form ID'),
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

            /** @var ElementQuery $query */
            $query->with($eagerLoadableFields);

            foreach ($query->each() as $element) {
                // Fetch the attributes for the element
                $values = $element->toArray(array_keys($attributes));

                // Convert values to strings
                $values = array_map(function($item) {
                    return (string)$item;
                }, $values);

                $row = array_combine(array_values($attributes), $values);

                // Because Craft doesn't suppport querying elements across multiple content tables in one go, 
                // we need to do some extra work to handle custom fields across multiple forms (and content tables).
                // This can be a little un-performant.
                $this->_populateElementContent($element);

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

            $exportData = array_map(function($item) use ($template) {
                return array_merge($template, $item);
            }, $data);

            $event = new ModifySubmissionExportDataEvent([
                'exportData' => $exportData,
                'query' => $query,
            ]);
            $this->trigger(self::EVENT_MODIFY_EXPORT_DATA, $event);

            return $event->exportData;
        } catch (Throwable $e) {
            Formie::log(Craft::t('app', '{message} {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return [];
    }


    // Private Methods
    // =========================================================================

    private function _populateElementContent(ElementInterface $element)
    {
        // Make sure the element has content
        if (!$element->hasContent()) {
            return;
        }

        if ($row = $this->_getContentRow($element)) {
            $element->contentId = $row['id'];

            if ($element->hasTitles() && isset($row['title'])) {
                $element->title = $row['title'];
            }

            if ($fieldLayout = $element->getFieldLayout()) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    if ($field::hasContentColumn()) {
                        $type = $field->getContentColumnType();

                        if (is_array($type)) {
                            $value = [];

                            foreach (array_keys($type) as $i => $key) {
                                $column = ElementHelper::fieldColumn('', $field->handle, $field->columnSuffix, $i !== 0 ? $key : null);
                                $value[$key] = $row[$column];
                            }

                            $element->setFieldValue($field->handle, $value);
                        } else {
                            $column = ElementHelper::fieldColumn('', $field->handle, $field->columnSuffix);
                            $element->setFieldValue($field->handle, $row[$column]);
                        }
                    }
                }
            }
        }
    }

    private function _getContentRow(ElementInterface $element)
    {
        if (!$element->id || !$element->siteId) {
            return null;
        }

        $contentTable = $element->getContentTable();
        $fieldColumnPrefix = $element->getFieldColumnPrefix();

        $row = (new Query())
            ->from([$contentTable])
            ->where([
                'elementId' => $element->id,
                'siteId' => $element->siteId,
            ])
            ->one();

        if ($row) {
            $row = $this->_removeColumnPrefixesFromRow($row, $fieldColumnPrefix);
        }

        return $row;
    }

    private function _removeColumnPrefixesFromRow(array $row, $fieldColumnPrefix): array
    {
        foreach ($row as $column => $value) {
            if (strpos($column, $fieldColumnPrefix) === 0) {
                $fieldHandle = substr($column, strlen($fieldColumnPrefix));
                $row[$fieldHandle] = $value;
                unset($row[$column]);
            } elseif (!in_array($column, ['id', 'elementId', 'title', 'dateCreated', 'dateUpdated', 'uid', 'siteId'], true)) {
                unset($row[$column]);
            }
        }

        return $row;
    }
}

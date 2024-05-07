<?php
namespace verbb\formie\elements\exporters;

use verbb\formie\helpers\ImportExportHelper;

use Craft;
use craft\base\ElementExporter;
use craft\elements\db\ElementQueryInterface;

use DateTime;

class FormExport extends ElementExporter
{
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
        return 'formie-form-' . (new DateTime())->format('Y-m-d-H-i');
    }

    public function export(ElementQueryInterface $query): array
    {
        $data = [];

        foreach ($query->each() as $element) {
            $data[] = ImportExportHelper::generateFormExport($element);
        }

        return $data;
    }
}

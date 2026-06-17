<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ReportDateBoundHelper;

use craft\base\Model;

class ReportSettings extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromArray(mixed $config): self
    {
        if (!is_array($config)) {
            return new self();
        }

        $settings = new self();

        if (isset($config['filters']) && is_array($config['filters'])) {
            $settings->filters = array_merge(self::defaultFilters(), $config['filters']);
        }

        if (isset($config['columns']) && is_array($config['columns'])) {
            $settings->columns = $config['columns'];
        }

        if (isset($config['display']) && is_array($config['display'])) {
            $settings->display = array_merge(self::defaultDisplay(), $config['display']);
        }

        if (isset($config['chart']) && is_array($config['chart'])) {
            $settings->chart = array_merge(self::defaultChart(), $config['chart']);
        }

        if (isset($config['export']) && is_array($config['export'])) {
            $settings->export = array_merge(self::defaultExport(), $config['export']);
        }

        return $settings;
    }

    public static function defaultFilters(): array
    {
        return [
            'formIds' => [],
            'includeComplete' => true,
            'includeIncomplete' => true,
            'includeSpam' => false,
            'statusIds' => [],
            'startBound' => ReportDateBoundHelper::defaultStartBound(),
            'endBound' => ReportDateBoundHelper::defaultEndBound(),
        ];
    }

    public static function defaultDisplay(): array
    {
        return [
            'useFieldHandles' => false,
            'useOptionLabels' => true,
        ];
    }

    public static function defaultChart(): array
    {
        return [
            'enabled' => true,
        ];
    }

    public static function defaultExport(): array
    {
        return [
            'filename' => '',
        ];
    }


    // Properties
    // =========================================================================

    public array $filters = [];
    public array $columns = [];
    public array $display = [];
    public array $chart = [];
    public array $export = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        if ($this->filters === []) {
            $this->filters = self::defaultFilters();
        }

        if ($this->display === []) {
            $this->display = self::defaultDisplay();
        }

        if ($this->chart === []) {
            $this->chart = self::defaultChart();
        }

        if ($this->export === []) {
            $this->export = self::defaultExport();
        }
    }

    public function toStorageArray(): array
    {
        return [
            'filters' => $this->filters,
            'columns' => $this->columns,
            'display' => $this->display,
            'chart' => $this->chart,
            'export' => $this->export,
        ];
    }
}

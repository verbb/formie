<?php
namespace verbb\formie\migrations\plugins;

use craft\helpers\Html;

class MigrationResult
{
    // Properties
    // =========================================================================

    public bool $ok = true;
    public array $lines = [];
    public array $stats = [];


    // Public Methods
    // =========================================================================

    public function addLine(MigrationLine $line): void
    {
        $this->lines[] = $line;
    }

    public function setStat(string $key, mixed $value): void
    {
        $this->stats[$key] = $value;
    }

    public function incrementStat(string $key, int $value = 1): void
    {
        $this->stats[$key] = (int)($this->stats[$key] ?? 0) + $value;
    }

    /**
     * @param MigrationLine[] $lines
     */
    public static function renderLinesHtml(array $lines): string
    {
        if (!$lines) {
            return '';
        }

        $html = '<div class="formie-settings-migrate-log">';

        foreach ($lines as $line) {
            if (!$line instanceof MigrationLine) {
                continue;
            }

            $classes = ['log-label'];

            $colorClass = match ($line->level) {
                'success' => 'color-32',
                'error' => 'color-31',
                'warning' => 'color-33',
                'info' => 'log-label--info',
                default => null,
            };

            if ($colorClass) {
                $classes[] = $colorClass;
            }

            if ($line->depth > 0) {
                $classes[] = 'log-label--nested';
            }

            $html .= '<div class="' . implode(' ', $classes) . '">' . Html::encode($line->message) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}


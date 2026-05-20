<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

/**
 * Manages Formie repair utilities.
 */
class RepairController extends Controller
{
    // Properties
    // =========================================================================

    public bool $dryRun = false;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'emoji-shortcodes') {
            $options[] = 'dryRun';
        }

        return $options;
    }

    /**
     * Converts legacy emoji shortcodes in Formie content to Unicode emoji.
     */
    public function actionEmojiShortcodes(): int
    {
        $result = Formie::$plugin->getRepair()->repairEmojiShortcodes($this->dryRun, function(string $message) {
            $this->stdout($message . PHP_EOL, Console::FG_GREY);
        });

        if (!$result['safe']) {
            $this->stderr('Unable to convert emoji shortcodes because some Formie columns cannot safely store 4-byte emoji characters.' . PHP_EOL, Console::FG_RED);
            $this->stderr('Run Craft\'s `db/convert-charset` command, then run this command again.' . PHP_EOL, Console::FG_YELLOW);

            foreach ($result['unsafeColumns'] as $column) {
                $this->stderr("- {$column}" . PHP_EOL, Console::FG_YELLOW);
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $action = $this->dryRun ? 'Would update' : 'Updated';
        $this->stdout("{$action} {$result['changedValues']} values across {$result['changedRows']} rows." . PHP_EOL, Console::FG_GREEN);

        if ($result['invalidJson']) {
            $this->stderr('Skipped invalid JSON values:' . PHP_EOL, Console::FG_YELLOW);

            foreach ($result['invalidJson'] as $value) {
                $this->stderr("- {$value}" . PHP_EOL, Console::FG_YELLOW);
            }
        }

        return ExitCode::OK;
    }
}

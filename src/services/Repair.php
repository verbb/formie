<?php
namespace verbb\formie\services;

use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use Throwable;

use yii\base\Component;

class Repair extends Component
{
    // Public Methods
    // =========================================================================

    public function repairEmojiShortcodes(bool $dryRun = false, ?callable $logger = null): array
    {
        $unsafeColumns = $this->getUnsafeEmojiColumns();

        if ($unsafeColumns) {
            return [
                'success' => false,
                'safe' => false,
                'dryRun' => $dryRun,
                'changedRows' => 0,
                'changedValues' => 0,
                'invalidJson' => [],
                'unsafeColumns' => $unsafeColumns,
            ];
        }

        $changedRows = 0;
        $changedValues = 0;
        $invalidJson = [];

        foreach ($this->emojiShortcodeColumns() as $table => $columns) {
            if (!Craft::$app->getDb()->tableExists($table)) {
                continue;
            }

            $existingColumns = [];

            foreach ($columns as $column => $isJson) {
                if (Craft::$app->getDb()->columnExists($table, $column)) {
                    $existingColumns[$column] = $isJson;
                }
            }

            if (!$existingColumns) {
                continue;
            }

            $select = array_merge(['id'], array_keys($existingColumns));
            $rows = (new Query())->select($select)->from($table)->each();

            foreach ($rows as $row) {
                $update = [];

                foreach ($existingColumns as $column => $isJson) {
                    $value = $row[$column] ?? null;

                    if ($value === null || $value === '') {
                        continue;
                    }

                    if ($isJson) {
                        try {
                            $decodedValue = Json::decode($value);
                        } catch (Throwable) {
                            $invalidJson[] = "$table.$column:{$row['id']}";

                            continue;
                        }

                        $convertedValue = $this->convertEmojiShortcodes($decodedValue);

                        if ($convertedValue !== $decodedValue) {
                            $update[$column] = Json::encode($convertedValue);
                        }
                    } else {
                        $convertedValue = $this->convertEmojiShortcodes($value);

                        if ($convertedValue !== $value) {
                            $update[$column] = $convertedValue;
                        }
                    }
                }

                if (!$update) {
                    continue;
                }

                $changedRows++;
                $changedValues += count($update);

                if (!$dryRun) {
                    Craft::$app->getDb()->createCommand()
                        ->update($table, $update, ['id' => $row['id']])
                        ->execute();
                }
            }

            $this->log($logger, "Checked {$table}.");
        }

        return [
            'success' => true,
            'safe' => true,
            'dryRun' => $dryRun,
            'changedRows' => $changedRows,
            'changedValues' => $changedValues,
            'invalidJson' => $invalidJson,
            'unsafeColumns' => [],
        ];
    }

    public function getUnsafeEmojiColumns(?array $columns = null): array
    {
        $unsafeColumns = [];

        // This is specifically guarding MySQL/MariaDB utf8mb3 columns. Other
        // drivers either store Unicode differently or don't expose charsets here.
        if (!in_array(Craft::$app->getDb()->getDriverName(), ['mysql', 'mysqli'], true)) {
            return [];
        }

        foreach ($columns ?? $this->emojiShortcodeColumns() as $table => $tableColumns) {
            if (!Craft::$app->getDb()->tableExists($table)) {
                continue;
            }

            foreach ($tableColumns as $column => $isJson) {
                if (!Craft::$app->getDb()->columnExists($table, $column)) {
                    continue;
                }

                if (!$this->columnCanStoreEmoji($table, $column)) {
                    $unsafeColumns[] = "$table.$column";
                }
            }
        }

        return $unsafeColumns;
    }

    public function canStoreEmojiInLayoutTables(): bool
    {
        return $this->getUnsafeEmojiColumns($this->layoutEmojiShortcodeColumns()) === [];
    }

    public function convertEmojiShortcodes(mixed $value): mixed
    {
        if (is_string($value)) {
            return StringHelper::shortcodesToEmoji($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->convertEmojiShortcodes($item);
            }
        }

        return $value;
    }


    // Private Methods
    // =========================================================================

    private function emojiShortcodeColumns(): array
    {
        return [
            Table::FORMIE_EMAIL_TEMPLATES => [
                'name' => false,
            ],
            Table::FORMIE_FIELD_LAYOUT_PAGES => [
                'label' => false,
                'settings' => true,
            ],
            Table::FORMIE_FIELDS => [
                'label' => false,
                'settings' => true,
            ],
            Table::FORMIE_FORMS => [
                'settings' => true,
            ],
            Table::FORMIE_FORM_TEMPLATES => [
                'name' => false,
            ],
            Table::FORMIE_INTEGRATIONS => [
                'name' => false,
                'settings' => true,
            ],
            Table::FORMIE_NOTIFICATIONS => [
                'name' => false,
                'subject' => false,
                'to' => false,
                'toConditions' => true,
                'cc' => false,
                'bcc' => false,
                'replyTo' => false,
                'replyToName' => false,
                'from' => false,
                'fromName' => false,
                'sender' => false,
                'content' => false,
                'attachAssets' => true,
                'conditions' => true,
                'customSettings' => true,
            ],
            Table::FORMIE_PDF_TEMPLATES => [
                'name' => false,
                'filenameFormat' => false,
            ],
            Table::FORMIE_SENT_NOTIFICATIONS => [
                'title' => false,
                'subject' => false,
                'to' => false,
                'cc' => false,
                'bcc' => false,
                'replyTo' => false,
                'replyToName' => false,
                'from' => false,
                'fromName' => false,
                'sender' => false,
                'body' => false,
                'htmlBody' => false,
                'info' => true,
                'message' => false,
            ],
            Table::FORMIE_SUBMISSION_STATUSES => [
                'name' => false,
                'description' => false,
            ],
            Table::FORMIE_STENCILS => [
                'name' => false,
                'data' => true,
            ],
            Table::FORMIE_SUBMISSIONS => [
                'spamReason' => false,
                'snapshot' => true,
            ],
        ];
    }

    private function layoutEmojiShortcodeColumns(): array
    {
        return [
            Table::FORMIE_FIELD_LAYOUT_PAGES => [
                'label' => false,
                'settings' => true,
            ],
            Table::FORMIE_FIELDS => [
                'label' => false,
                'settings' => true,
            ],
        ];
    }

    private function columnCanStoreEmoji(string $table, string $column): bool
    {
        $db = Craft::$app->getDb();
        $schema = $db->getSchema();
        $rawTableName = method_exists($schema, 'getRawTableName') ? $schema->getRawTableName($table) : trim(str_replace(['{{%', '{{', '}}'], ['', '', ''], $table), '{}%');
        $databaseName = $db->createCommand('SELECT DATABASE()')->queryScalar();

        $charset = (new Query())
            ->select(['CHARACTER_SET_NAME'])
            ->from('information_schema.COLUMNS')
            ->where([
                'TABLE_SCHEMA' => $databaseName,
                'TABLE_NAME' => $rawTableName,
                'COLUMN_NAME' => $column,
            ])
            ->scalar($db);

        if ($charset === false || $charset === null) {
            return true;
        }

        return strcasecmp((string)$charset, 'utf8mb4') === 0;
    }

    private function log(?callable $logger, string $message): void
    {
        if ($logger) {
            $logger($message);
        }
    }
}

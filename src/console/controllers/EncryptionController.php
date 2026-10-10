<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\content\ContentEncryption;
use verbb\formie\content\FieldStorageCodec;
use verbb\formie\elements\Submission;
use verbb\formie\exceptions\EncryptedContentUnavailableException;
use verbb\formie\helpers\Table;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;

use Throwable;
use yii\console\ExitCode;

/**
 * Manages Formie field-content encryption.
 */
class EncryptionController extends Controller
{
    // Properties
    // =========================================================================

    public bool $stdout = false;
    public bool $rotate = false;
    public bool $confirmBackup = false;
    public bool $adoptNewKey = false;
    public bool $restart = false;
    public int $batchSize = 250;
    public ?string $keyId = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'generate-key') {
            $options[] = 'stdout';
            $options[] = 'rotate';
        }

        if ($actionID === 'reencrypt') {
            array_push($options, 'confirmBackup', 'adoptNewKey', 'restart', 'batchSize', 'keyId');
        }

        return $options;
    }

    /**
     * Generates a new field-content encryption key and saves it to .env.
     */
    public function actionGenerateKey(): int
    {
        try {
            $configuredKey = ContentEncryption::currentKey();
        } catch (\RuntimeException $exception) {
            if (!$this->rotate) {
                $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

                return ExitCode::UNSPECIFIED_ERROR;
            }

            $configuredKey = null;
        }

        if ($configuredKey && !$this->rotate) {
            $this->stderr('A Formie encryption key is already configured.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $key = ContentEncryption::generateKey();

        if ($this->stdout) {
            $this->stdout($key->value . PHP_EOL);

            return ExitCode::OK;
        }

        $config = Craft::$app->getConfig();
        $path = $config->getDotEnvPath();

        if (!file_exists($path)) {
            if ($this->interactive && !$this->confirm("A .env file doesn't exist at $path. Would you like to create one?", true)) {
                $this->stdout('Action aborted.' . PHP_EOL, Console::FG_YELLOW);

                return ExitCode::UNSPECIFIED_ERROR;
            }

            try {
                FileHelper::writeToFile($path, '');
            } catch (Throwable $exception) {
                $this->stderr("Unable to create $path: {$exception->getMessage()}" . PHP_EOL, Console::FG_RED);

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        try {
            $config->setDotEnvVar('FORMIE_ENCRYPTION_KEY', $key->value);
        } catch (Throwable $exception) {
            $this->stderr("Unable to save FORMIE_ENCRYPTION_KEY to $path: {$exception->getMessage()}" . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Saved FORMIE_ENCRYPTION_KEY to $path." . PHP_EOL, Console::FG_GREEN);
        $this->stdout('Keep a recoverable copy of this key and deploy the same value to every environment that must read encrypted submissions. Losing it makes that content permanently unavailable.' . PHP_EOL, Console::FG_YELLOW);
        $this->stdout('FORMIE_ENCRYPTION_KEY=' . $key->value . PHP_EOL, Console::FG_GREEN);
        $this->stdout('Key ID: ' . $key->id . PHP_EOL, Console::FG_GREY);

        return ExitCode::OK;
    }

    /**
     * Verifies a Formie encryption key and prints its non-secret ID.
     */
    public function actionVerifyKey(string $value): int
    {
        try {
            $this->stdout(ContentEncryption::parseKey($value)->id . PHP_EOL, Console::FG_GREEN);
        } catch (\RuntimeException $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

            return ExitCode::USAGE;
        }

        return ExitCode::OK;
    }

    /**
     * Shows the field-content encryption rollout state.
     */
    public function actionStatus(): int
    {
        $invalidKey = false;

        try {
            $key = ContentEncryption::currentKey();
        } catch (\RuntimeException $exception) {
            $key = null;
            $invalidKey = true;
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);
        }

        $version = ContentEncryption::activeVersion();
        $expected = ContentEncryption::expectedKeyId();
        $counts = ['v2' => 0, 'v1' => 0, 'formie3' => 0];

        foreach ((new Query())->select(['content'])->from(Table::FORMIE_SUBMISSIONS)->orderBy(['id' => SORT_ASC])->each(250) as $row) {
            $this->_countFormats(Json::decodeIfJson($row['content']), $counts);
        }

        $this->stdout('Write format: v' . $version . PHP_EOL);
        $this->stdout('Configured key ID: ' . ($key?->id ?? 'none') . PHP_EOL);
        $this->stdout('Expected key ID: ' . ($expected ?? 'none') . PHP_EOL);
        $this->stdout("Stored values: {$counts['v2']} v2, {$counts['v1']} v1, {$counts['formie3']} Formie 3" . PHP_EOL);

        return $invalidKey || ($version >= 2 && !$key) ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    /**
     * Activates v2 writes after every web and queue process has the new reader.
     */
    public function actionActivate(): int
    {
        try {
            $key = ContentEncryption::currentKey();
        } catch (\RuntimeException $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$key) {
            $this->stderr('Configure FORMIE_ENCRYPTION_KEY before activating v2 writes.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $expected = ContentEncryption::expectedKeyId();

        if ($expected && $expected !== $key->id) {
            $this->stderr("Configured key {$key->id} differs from expected key $expected. Add the former key to FORMIE_ENCRYPTION_PREVIOUS_KEYS and use formie/encryption/reencrypt --adopt-new-key instead of activating again." . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_ENCRYPTION_SETTINGS, [
            'contentVersion' => 2,
            'expectedKeyId' => $expected ?? $key->id,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => 1])->execute();

        $this->stdout("Formie v2 field-content writes are active with key {$key->id}." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Converts readable legacy and prior-key values to the active v2 key.
     */
    public function actionReencrypt(): int
    {
        try {
            $key = ContentEncryption::currentKey();
        } catch (\RuntimeException $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$key || ContentEncryption::activeVersion() < 2) {
            $this->stderr('Configure FORMIE_ENCRYPTION_KEY and run formie/encryption/activate before re-encrypting stored content.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$this->confirmBackup) {
            $this->stderr('Create and verify a database backup, then rerun with --confirm-backup.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $expected = ContentEncryption::expectedKeyId();

        if ($expected && $expected !== $key->id && !$this->adoptNewKey) {
            $this->stderr("Configured key {$key->id} differs from expected key $expected. Add the former key to FORMIE_ENCRYPTION_PREVIOUS_KEYS, then rerun with --adopt-new-key." . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $confirmedKeyId = $this->keyId;

        if ($confirmedKeyId === null && $this->interactive) {
            $confirmedKeyId = $this->prompt("Type target key ID {$key->id} to continue:");
        }

        if (!is_string($confirmedKeyId) || !hash_equals($key->id, $confirmedKeyId)) {
            $this->stderr('The supplied --key-id does not match the configured Formie encryption key.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->batchSize < 1 || $this->batchSize > 1000) {
            $this->stderr('--batch-size must be between 1 and 1000.' . PHP_EOL, Console::FG_RED);

            return ExitCode::USAGE;
        }

        $cursor = $this->restart ? 0 : ContentEncryption::reencryptCursor($key->id);
        $converted = 0;
        $unchanged = 0;
        $concurrent = 0;
        $locked = [];

        while (true) {
            $rows = (new Query())
                ->select(['id', 'formId', 'content', 'stateVersion'])
                ->from(Table::FORMIE_SUBMISSIONS)
                ->where(['>', 'id', $cursor])
                ->orderBy(['id' => SORT_ASC])
                ->limit($this->batchSize)
                ->all();

            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $submissionId = (int)$row['id'];
                $cursor = $submissionId;
                $content = Json::decodeIfJson($row['content']);
                $originalContent = $content;

                if (!is_array($content)) {
                    $unchanged++;
                    continue;
                }

                $form = Formie::$plugin->getForms()->getFormById((int)$row['formId']);

                if (!$form) {
                    $locked[$submissionId][] = 'Form definition is unavailable.';
                    continue;
                }

                $fields = [];

                foreach ($form->getFields() as $field) {
                    if ($field->uid) {
                        $fields[$field->uid] = $field;
                    }
                }

                $changed = false;
                $fieldIds = [];

                foreach ($content as $fieldUid => $storedValue) {
                    $field = $fields[$fieldUid] ?? null;

                    if (!$field || !$this->_requiresReencryption($field, $storedValue, $key->id)) {
                        continue;
                    }

                    try {
                        $decoded = $field->decodeValueFromStorage($storedValue);
                        $content[$fieldUid] = $field->serializeValueForDb($decoded);
                        $changed = true;

                        if ($field->id) {
                            $fieldIds[] = (int)$field->id;
                        }
                    } catch (EncryptedContentUnavailableException $exception) {
                        $locked[$submissionId][] = $field->handle . ': ' . $exception->getMessage();
                    }
                }

                if (!$changed) {
                    $unchanged++;
                    continue;
                }

                $updated = $this->_updateContentIfUnchanged(
                    $submissionId,
                    (int)$row['stateVersion'],
                    $originalContent,
                    $content,
                );

                if (!$updated) {
                    $concurrent++;
                    continue;
                }

                $converted++;

                if ($fieldIds) {
                    Db::delete(CraftTable::SEARCHINDEX, [
                        'elementId' => $submissionId,
                        'attribute' => 'field',
                        'fieldId' => array_values(array_unique($fieldIds)),
                    ]);
                }
            }

            ContentEncryption::setReencryptCursor($key->id, $cursor);
            $this->stdout("Processed through submission $cursor." . PHP_EOL, Console::FG_GREY);
        }

        Craft::$app->getElements()->invalidateCachesForElementType(Submission::class);

        if ($locked) {
            ContentEncryption::setReencryptCursor($key->id, 0);

            foreach ($locked as $submissionId => $messages) {
                $this->stderr("Submission $submissionId: " . implode('; ', $messages) . PHP_EOL, Console::FG_RED);
            }

            $this->stderr('No unreadable value was changed. Add the required rescue key and rerun the command.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        ContentEncryption::completeReencryption($key->id);
        $this->stdout("Re-encryption complete: $converted changed, $unchanged unchanged, $concurrent concurrent edits skipped." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    private function _updateContentIfUnchanged(int $submissionId, int $stateVersion, array $originalContent, array $content): bool
    {
        $db = Craft::$app->getDb();

        return $db->transaction(function() use ($db, $submissionId, $stateVersion, $originalContent, $content): bool {
            $latest = $db->createCommand(
                'SELECT [[content]], [[stateVersion]] FROM ' . Table::FORMIE_SUBMISSIONS . ' WHERE [[id]] = :id FOR UPDATE',
                [':id' => $submissionId],
            )->noCache()->queryOne();

            if ($latest === false || (int)$latest['stateVersion'] !== $stateVersion || Json::decodeIfJson($latest['content']) !== $originalContent) {
                return false;
            }

            Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submissionId]);

            return true;
        });
    }

    private function _requiresReencryption(mixed $field, mixed $storedValue, string $targetKeyId): bool
    {
        if ($field->enableContentEncryption) {
            return !FieldStorageCodec::isCurrentEncryptedValue($storedValue, $targetKeyId);
        }

        if (!$field instanceof ParentFieldInterface) {
            return false;
        }

        if (FieldStorageCodec::isEncryptedValue($storedValue)) {
            // The parent was encrypted previously. It must be opened once so
            // currently encrypted descendants can move to their own envelopes.
            return true;
        }

        if (FieldStorageCodec::isProtectedPlaintext($storedValue)) {
            $storedValue = FieldStorageCodec::decode($storedValue);
        }

        if (!is_array($storedValue)) {
            return false;
        }

        if ($field instanceof RepeatableParentFieldInterface) {
            foreach ($storedValue as $rowKey => $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach ($field->getFields($rowKey) as $child) {
                    $childKey = array_key_exists($child->uid, $row) ? $child->uid : (array_key_exists($child->handle, $row) ? $child->handle : null);

                    if ($childKey === null) {
                        continue;
                    }

                    if ($this->_requiresReencryption($child, $row[$childKey], $targetKeyId)) {
                        return true;
                    }
                }
            }

            return false;
        }

        foreach ($field->getFields() as $child) {
            $childKey = array_key_exists($child->uid, $storedValue) ? $child->uid : (array_key_exists($child->handle, $storedValue) ? $child->handle : null);

            if ($childKey === null) {
                continue;
            }

            if ($this->_requiresReencryption($child, $storedValue[$childKey], $targetKeyId)) {
                return true;
            }
        }

        return false;
    }

    private function _countFormats(mixed $value, array &$counts): void
    {
        if (is_string($value)) {
            $decoded = str_starts_with($value, 'base64:') ? base64_decode(substr($value, 7), true) : false;

            if ($decoded !== false && str_starts_with($decoded, 'crypt:')) {
                $counts['formie3']++;
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        if (FieldStorageCodec::isProtectedPlaintext($value)) {
            return;
        }

        if (($value['__formie_enc'] ?? null) === 2) {
            $counts['v2']++;

            return;
        }

        if (($value['__formie_encrypted'] ?? null) === 1) {
            $counts['v1']++;

            return;
        }

        foreach ($value as $item) {
            $this->_countFormats($item, $counts);
        }
    }
}

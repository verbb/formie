<?php
namespace verbb\formie\storage;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class DbStorage extends AbstractStorage
{
    // Public Methods
    // =========================================================================

    public function get(string $key, mixed $default = null): mixed
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            return $default;
        }

        $row = (new Query())
            ->from(Table::FORMIE_SUBMISSION_DRAFTS)
            ->where(['storageKey' => $key])
            ->one();

        if (!$row) {
            return $default;
        }

        $expiresAt = isset($row['dateExpires']) ? strtotime((string)$row['dateExpires']) : null;
        if ($expiresAt && $expiresAt <= time()) {
            // Expired state is pruned lazily on read as well as by scheduled GC
            // so stale resume attempts do not keep seeing dead draft records.
            $this->delete($key);

            return $default;
        }

        $encoded = (string)($row['value'] ?? '');
        if ($encoded === '') {
            return $default;
        }

        $decoded = Json::decodeIfJson($encoded);

        return is_array($decoded) && array_key_exists('value', $decoded) ? $decoded['value'] : $default;
    }

    public function set(string $key, mixed $value, null|int $ttl = null): bool
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            return false;
        }

        $now = Db::prepareDateForDb(new \DateTime());
        $expires = null;

        if (is_int($ttl) && $ttl > 0) {
            $expires = Db::prepareDateForDb(new \DateTime('@' . (time() + $ttl)));
        }

        $payload = [
            'storageKey' => $key,
            'value' => Json::encode(['value' => $value]),
            'dateExpires' => $expires,
            'dateUpdated' => $now,
        ];

        Craft::$app->getDb()->createCommand()->upsert(
            Table::FORMIE_SUBMISSION_DRAFTS,
            array_merge($payload, ['dateCreated' => $now]),
            $payload
        )->execute();

        return true;
    }

    public function delete(string $key): bool
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            return true;
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_SUBMISSION_DRAFTS, ['storageKey' => $key])
            ->execute();

        return true;
    }

    public function clear(): bool
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            return true;
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_SUBMISSION_DRAFTS)
            ->execute();

        return true;
    }
}

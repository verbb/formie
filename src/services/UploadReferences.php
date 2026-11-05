<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UploadReferenceHelper;
use verbb\formie\jobs\RebuildUploadReferences;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Queue;

use yii\base\Component;
use yii\db\Connection;

use RuntimeException;
use Throwable;

class UploadReferences extends Component
{
    // Constants
    // =========================================================================

    public const REFERENCED = 'referenced';
    public const UNREFERENCED = 'unreferenced';
    public const UNKNOWN = 'unknown';
    public const STATE_TABLE = '{{%formie_upload_reference_state}}';

    // Properties
    // =========================================================================

    private array $_locks = [];
    private array $_pins = [];
    private array $_queueForms = [];
    private array $_pairVersions = [];
    private bool $_listening = false;
    private ?Connection $_freshConnection = null;

    // Public Methods
    // =========================================================================

    public function isInstalled(): bool
    {
        $db = Craft::$app->getDb();

        foreach ([Table::FORMIE_SUBMISSION_ASSETS, Table::FORMIE_UPLOAD_REFERENCE_FORMS, Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, self::STATE_TABLE] as $table) {
            if (!$db->tableExists($table)) {
                return false;
            }
        }

        return $db->columnExists(Table::FORMIE_SUBMISSIONS, 'uploadReferenceVersion');
    }

    public function guard(bool $exclusive = false): void
    {
        $db = Craft::$app->getDb();

        if (!$db->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Upload reference changes require a transaction.');
        }

        // A shared metadata lock permits concurrent saves/deletions. Schema and
        // unreadable-content publication take the exclusive side of the same lock.
        $suffix = $exclusive ? ' FOR UPDATE' : ($db->getIsMysql() ? ' LOCK IN SHARE MODE' : ' FOR SHARE');

        if (!$db->createCommand('SELECT [[id]] FROM ' . self::STATE_TABLE . ' WHERE [[id]] = 1' . $suffix)->queryScalar()) {
            throw new RuntimeException('Upload reference schema is incomplete. Run migrations.');
        }
    }

    public function acquireAsset(int $assetId, int $timeout = 0): bool
    {
        if (!isset($this->_locks[$assetId])) {
            if (!Craft::$app->getMutex()->acquire('formie.upload.' . $assetId, $timeout)) {
                return false;
            }

            $this->_locks[$assetId] = 0;
        }

        $this->_locks[$assetId]++;

        if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            $this->_pins[$assetId] = true;
            $this->_listen();
        }

        return true;
    }

    public function releaseAsset(int $assetId): void
    {
        if (isset($this->_locks[$assetId])) {
            $this->_locks[$assetId]--;
            $this->_releaseUnusedLocks();
        }
    }

    public function reconcile(int $submissionId, bool $validateNewAssets = false, mixed $previousContent = null): void
    {
        if (!$this->isInstalled()) {
            return;
        }

        $db = Craft::$app->getDb();
        $this->guard();
        $row = $db->createCommand('SELECT [[id]], [[formId]], [[content]], [[uploadReferenceVersion]] FROM ' . Table::FORMIE_SUBMISSIONS . ' WHERE [[id]] = :id FOR UPDATE', [':id' => $submissionId])->queryOne();

        if (!$row) {
            return;
        }

        $schema = $this->_schema((int)$row['formId']);
        $result = $schema === null ? ['ids' => [], 'unknown' => true] : UploadReferenceHelper::extract($row['content'], $schema);
        $old = $schema === null ? [] : UploadReferenceHelper::extract($previousContent, $schema)['ids'];
        // The source-row lock stabilizes committed pairs. A revision token also
        // identifies our own uncommitted pairs across nested savepoint rollbacks,
        // without locking an empty index range or trusting an old SQL snapshot.
        $versionKey = $submissionId . ':' . ($row['uploadReferenceVersion'] ?? 'new');
        $existing = $this->_pairVersions[$versionKey] ??= array_map('intval', (new Query())->select('assetId')->from(Table::FORMIE_SUBMISSION_ASSETS)->where(['submissionId' => $submissionId])->column($this->_primaryReader()));
        $ids = array_values(array_unique(array_merge($existing, $result['ids'])));
        sort($ids, SORT_NUMERIC);

        foreach ($ids as $assetId) {
            // Never wait for an advisory lock while holding a source-row lock.
            // A conflicting writer rolls its whole save back and can retry safely.
            if (!$this->acquireAsset($assetId)) {
                throw new RuntimeException('Upload is busy. Please retry.');
            }

            $this->releaseAsset($assetId);
        }

        if ($validateNewAssets) {
            foreach (array_diff($result['ids'], $old) as $assetId) {
                // Locking reads see deletions committed after a repeatable-read snapshot.
                if (!$db->createCommand('SELECT [[id]] FROM {{%assets}} WHERE [[id]] = :id FOR UPDATE', [':id' => $assetId])->queryScalar()) {
                    throw new RuntimeException('An attached upload no longer exists.');
                }
            }
        }

        if ($result['unknown']) {
            $this->guard(true);
            Db::upsert(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, ['submissionId' => $submissionId, 'formId' => $row['formId'], 'reasonCode' => 'unreadableContent']);
        } else {
            Db::delete(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, ['submissionId' => $submissionId]);

            // Delete known rows only: deleting a missing pair can lock a MySQL gap.
            foreach (array_diff($existing, $result['ids']) as $assetId) {
                Db::delete(Table::FORMIE_SUBMISSION_ASSETS, ['submissionId' => $submissionId, 'assetId' => $assetId]);
            }
        }

        foreach ($result['ids'] as $assetId) {
            Db::upsert(Table::FORMIE_SUBMISSION_ASSETS, ['submissionId' => $submissionId, 'assetId' => $assetId], false);
        }

        $version = \craft\helpers\StringHelper::UUID();
        Db::update(Table::FORMIE_SUBMISSIONS, ['uploadReferenceVersion' => $version], ['id' => $submissionId], [], false);
        $this->_pairVersions[$submissionId . ':' . $version] = $result['unknown'] ? $ids : $result['ids'];
        $this->_listen();
    }

    public function referenceState(int $assetId, ?int $exceptSubmissionId = null, ?string $exceptContentKey = null): string
    {
        if (!$this->isInstalled()) {
            return self::UNKNOWN;
        }

        $query = (new Query())->from(Table::FORMIE_SUBMISSION_ASSETS)->where(['assetId' => $assetId])->andFilterWhere(['not', ['submissionId' => $exceptSubmissionId]]);

        if ($this->_exists($query)) {
            return self::REFERENCED;
        }

        if ($exceptSubmissionId !== null && $exceptContentKey !== null) {
            $row = Craft::$app->getDb()->createCommand('SELECT [[formId]], [[content]] FROM ' . Table::FORMIE_SUBMISSIONS . ' WHERE [[id]] = :id FOR UPDATE', [':id' => $exceptSubmissionId])->queryOne();

            if ($row) {
                $schema = $this->_schema((int)$row['formId']);

                if ($schema === null) {
                    return self::UNKNOWN;
                }

                $result = UploadReferenceHelper::extract($row['content'], $schema, $exceptContentKey);

                if (in_array($assetId, $result['ids'], true)) {
                    return self::REFERENCED;
                }

                if ($result['unknown']) {
                    return self::UNKNOWN;
                }
            }
        }

        return $this->hasCompleteCoverage($exceptSubmissionId) ? self::UNREFERENCED : self::UNKNOWN;
    }

    public function hasCompleteCoverage(?int $exceptSubmissionId = null): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        $unknown = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN)->andFilterWhere(['not', ['submissionId' => $exceptSubmissionId]]);
        $pending = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['state' => 'pending']);
        $missing = (new Query())->from(['f' => Table::FORMIE_FORMS])->leftJoin(['r' => Table::FORMIE_UPLOAD_REFERENCE_FORMS], '[[r.formId]] = [[f.id]]')->where(['r.formId' => null]);

        return !$this->_exists($unknown) && !$this->_exists($pending) && !$this->_exists($missing);
    }

    public function withDeletionCheck(int $assetId, callable $callback, ?int $exceptSubmissionId = null, ?string $exceptContentKey = null): bool
    {
        if (!$this->isInstalled() || !$this->acquireAsset($assetId)) {
            return false;
        }

        try {
            return Craft::$app->getDb()->transaction(function() use ($assetId, $callback, $exceptSubmissionId, $exceptContentKey): bool {
                $this->guard();
                // Pin the outer lock through commit, including nested element transactions.
                $this->acquireAsset($assetId);
                $this->releaseAsset($assetId);

                if (Formie::$plugin->getFileUploads()->isReferenced($assetId, $exceptSubmissionId, $exceptContentKey)) {
                    return false;
                }

                return (bool)$callback();
            });
        } finally {
            $this->releaseAsset($assetId);
        }
    }

    public function refreshSchemas(?int $formId = null, ?array $layoutIds = null, array $definitionIds = []): void
    {
        if (!$this->isInstalled()) {
            return;
        }

        $this->guard(true);
        $query = (new Query())->select(['ff.layoutId', 'ff.fieldId', 'ff.uid', 'f.type', 'f.handle', 'f.settings'])->from(['ff' => Table::FORMIE_FORM_FIELDS])->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]');
        $forms = (new Query())->select(['id', 'layoutId'])->from(Table::FORMIE_FORMS)->andFilterWhere(['id' => $formId]);
        $layouts = [];

        if ($formId !== null) {
            // Ordinary form creation only reads that form's layout graph.
            $pending = array_map('intval', (clone $forms)->select('layoutId')->column());

            while ($pending) {
                foreach ($pending as $id) {
                    $layouts[$id] = [];
                }

                $children = [];

                foreach ((clone $query)->where(['ff.layoutId' => $pending])->all() as $field) {
                    $layouts[(int)$field['layoutId']][] = $field;
                    $settings = Json::decodeIfJson($field['settings']) ?: [];

                    if (!empty($settings['nestedLayoutId'])) {
                        $children[] = (int)$settings['nestedLayoutId'];
                    }
                }

                $pending = array_values(array_diff(array_unique($children), array_keys($layouts)));
            }
        } else {
            // Schema edits can affect shared definitions and their parent layouts.
            // Compare affected forms only; labels never invalidate stored coverage.
            $fields = $query->all();
            $affected = array_fill_keys($layoutIds ?? [], true);

            foreach ($fields as $field) {
                $layouts[(int)$field['layoutId']][] = $field;

                if (in_array((int)$field['fieldId'], $definitionIds, true)) {
                    $affected[(int)$field['layoutId']] = true;
                }
            }

            if ($layoutIds !== null) {
                do {
                    $count = count($affected);

                    foreach ($fields as $field) {
                        $settings = Json::decodeIfJson($field['settings']) ?: [];

                        if (isset($affected[(int)($settings['nestedLayoutId'] ?? 0)])) {
                            $affected[(int)$field['layoutId']] = true;
                        }
                    }
                } while (count($affected) !== $count);

                $forms->andWhere(['layoutId' => array_keys($affected)]);
            }
        }

        foreach ($forms->all() as $form) {
            $id = (int)$form['id'];
            $schema = UploadReferenceHelper::schemaFromLayouts((int)$form['layoutId'], $layouts);
            $fingerprint = UploadReferenceHelper::fingerprint($schema);
            $state = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['formId' => $id])->one();

            if (!$state || $state['schemaFingerprint'] !== $fingerprint) {
                $this->invalidate($id, $fingerprint, $schema);
            }
        }
    }

    public function invalidate(int $formId, ?string $fingerprint = null, ?array $schema = null): void
    {
        Craft::$app->getDb()->transaction(function() use ($formId, $fingerprint, $schema): void {
            $this->guard(true);
            $state = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['formId' => $formId])->one();
            $highWater = (int)(new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $formId])->max('id');
            Db::upsert(Table::FORMIE_UPLOAD_REFERENCE_FORMS, [
                'formId' => $formId,
                'generation' => (int)($state['generation'] ?? 0) + 1,
                'schemaFingerprint' => $fingerprint ?? ($state['schemaFingerprint'] ?? ''),
                'schemaData' => $schema ?? ($state ? Json::decodeIfJson($state['schemaData']) : null),
                'cursor' => 0,
                'highWaterId' => $highWater,
                'state' => $highWater ? 'pending' : 'ready',
            ]);

            if ($highWater) {
                $this->_queueForms[$formId] = true;
                $this->_listen();
            }
        });
    }

    public function rebuildBatch(int $formId, int $batchSize = 100): bool
    {
        $state = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['formId' => $formId])->one();

        if (!$state || $state['state'] === 'ready') {
            return true;
        }

        $ids = (new Query())->select('id')->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $formId])->andWhere(['>', 'id', $state['cursor']])->andWhere(['<=', 'id', $state['highWaterId']])->orderBy(['id' => SORT_ASC])->limit(max(1, $batchSize))->column();

        foreach ($ids as $id) {
            Craft::$app->getDb()->transaction(fn() => $this->reconcile((int)$id));
        }

        // Another schema publication must not be marked complete by an older worker.
        Db::update(Table::FORMIE_UPLOAD_REFERENCE_FORMS, [
            'cursor' => $ids ? max($ids) : $state['highWaterId'],
            'state' => count($ids) < $batchSize ? 'ready' : 'pending',
        ], ['formId' => $formId, 'generation' => $state['generation'], 'cursor' => $state['cursor']]);

        return count($ids) < $batchSize;
    }

    public function verify(int $formId, int $batchSize = 100): array
    {
        $report = ['checked' => 0, 'mismatched' => 0, 'unknown' => 0];
        $form = $this->_form($formId);

        if (!$form) {
            throw new RuntimeException('Form not found.');
        }

        $schema = UploadReferenceHelper::schema($form);
        $cursor = 0;

        do {
            $rows = (new Query())->select(['id', 'content'])->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $formId])->andWhere(['>', 'id', $cursor])->orderBy(['id' => SORT_ASC])->limit($batchSize)->all();

            foreach ($rows as $row) {
                $result = UploadReferenceHelper::extract($row['content'], $schema);
                $actual = array_map('intval', (new Query())->select('assetId')->from(Table::FORMIE_SUBMISSION_ASSETS)->where(['submissionId' => $row['id']])->orderBy(['assetId' => SORT_ASC])->column());
                $report['checked']++;
                $report['unknown'] += (int)$result['unknown'];
                $recordedUnknown = (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN)->where(['submissionId' => $row['id']])->exists();
                $report['mismatched'] += (int)($actual !== $result['ids'] || $recordedUnknown !== $result['unknown']);
                $cursor = (int)$row['id'];
            }
        } while (count($rows) === $batchSize);

        return $report;
    }

    public function existsCurrent(Query $query): bool
    {
        return $this->_exists($query);
    }

    // Private Methods
    // =========================================================================

    private function _exists(Query $query): bool
    {
        $db = Craft::$app->getDb();

        if ($db->useMaster(fn() => $query->exists($db))) {
            return true;
        }

        if (!$db->getTransaction()?->getIsActive()) {
            return false;
        }

        // Include this transaction's own writes above, then check committed primary
        // state without a stale REPEATABLE READ snapshot or locking index gaps.
        // Stale positives conservatively retain a file until the next cleanup.
        if (!$query->exists($this->_primaryReader())) {
            return false;
        }

        if ($query->join || ($db->getIsPgsql() && !in_array($db->createCommand('SHOW transaction_isolation')->queryScalar(), ['read committed', 'read uncommitted'], true))) {
            // PostgreSQL locking reads cannot refresh a repeatable-read snapshot.
            // Preserve the committed positive until that outer transaction ends.
            return true;
        }

        // A committed positive may be a reference removed by our own transaction.
        // Confirm only these exceptional disagreements with a current locking read;
        // ordinary misses never lock empty asset-index ranges.
        $current = clone $query;
        $current->select(new \yii\db\Expression('1'))->limit(1);

        return (bool)$db->createCommand($current->createCommand($db)->getRawSql() . ' FOR UPDATE')->queryScalar();
    }

    private function _primaryReader(): Connection
    {
        $db = Craft::$app->getDb();

        if ($this->_freshConnection === null) {
            $primary = $db->getMaster() ?? $db;
            $this->_freshConnection = new Connection([
                'dsn' => $primary->dsn, 'username' => $primary->username, 'password' => $primary->password,
                'charset' => $primary->charset, 'tablePrefix' => $db->tablePrefix,
                'attributes' => array_replace($primary->attributes ?? [], [\PDO::ATTR_PERSISTENT => false]),
            ]);

            if ($db->getIsPgsql()) {
                // Craft supports a non-default PostgreSQL schema. Match the
                // active primary's search path on the independent reader too.
                $searchPath = $db->useMaster(fn() => $db->createCommand('SHOW search_path')->queryScalar());
                $this->_freshConnection->createCommand("SELECT set_config('search_path', :path, false)", [':path' => $searchPath])->queryScalar();
            }
        }

        return $this->_freshConnection;
    }

    private function _schema(int $formId): ?array
    {
        $db = Craft::$app->getDb();
        $suffix = $db->getIsMysql() ? ' LOCK IN SHARE MODE' : ' FOR SHARE';
        $value = $db->createCommand('SELECT [[schemaData]] FROM ' . Table::FORMIE_UPLOAD_REFERENCE_FORMS . ' WHERE [[formId]] = :id' . $suffix, [':id' => $formId])->queryScalar();

        return $value ? Json::decodeIfJson($value) : null;
    }

    private function _form(int $formId): ?Form
    {
        return Form::find()->withoutCpIndexScope()->id($formId)->site('*')->unique()->status(null)->trashed(null)->one();
    }

    private function _listen(): void
    {
        if ($this->_listening) {
            return;
        }

        $this->_listening = true;
        $db = Craft::$app->getDb();
        $release = null;
        $release = function($event) use ($db, &$release): void {
            $db->off(Connection::EVENT_COMMIT_TRANSACTION, $release);
            $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, $release);
            $this->_pins = [];
            $this->_pairVersions = [];
            $this->_listening = false;
            $this->_releaseUnusedLocks();
            $formIds = array_keys($this->_queueForms);
            $this->_queueForms = [];

            if ($event->name === Connection::EVENT_COMMIT_TRANSACTION) {
                foreach ($formIds as $formId) {
                    try {
                        Queue::push(new RebuildUploadReferences(['formId' => $formId]));
                    } catch (Throwable) {
                        // Coverage stays pending if the queue is unavailable; the
                        // console resume command can safely continue it later.
                        Formie::error("Unable to queue upload indexing for form #$formId. Run formie/upload-references/resume.");
                    }
                }
            }
        };
        $db->on(Connection::EVENT_COMMIT_TRANSACTION, $release);
        $db->on(Connection::EVENT_ROLLBACK_TRANSACTION, $release);
    }

    private function _releaseUnusedLocks(): void
    {
        foreach ($this->_locks as $id => $count) {
            if ($count <= 0 && !isset($this->_pins[$id])) {
                Craft::$app->getMutex()->release('formie.upload.' . $id);
                unset($this->_locks[$id]);
            }
        }
    }
}

<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\state\DraftSubmissionState;
use verbb\formie\state\FormInstanceKey;
use verbb\formie\state\ResumeToken;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Session;
use yii\base\Component;
use DateTime;

class SubmissionDrafts extends Component
{
    // Constants
    // =========================================================================

    public const CLEAR_VALUE_SENTINEL = '__FORMIE_CLEAR__';
    public const RESUME_CAPABILITY_READ = 'read';
    public const RESUME_CAPABILITY_UPDATE = 'update';
    public const RESUME_CAPABILITY_FINALIZE = 'finalize';
    public const RESUME_CAPABILITY_EDIT = 'edit';

    private const RESUME_TOKEN_DEFAULT_TTL = 1209600; // 14 days
    private const RESUME_SNAPSHOT_KEY = '_formieSubmissionState';
    private const STORAGE_KEY_PREFIX = 'formie:submission-state:';


    // Public Methods
    // =========================================================================

    public function resolveFormInstanceKey(Form $form, ?Submission $submission = null, array $context = []): FormInstanceKey
    {
        $siteId = $this->_resolveSiteId();
        $ownerId = isset($context['ownerId']) ? (int)$context['ownerId'] : null;
        $scope = $this->_normalizeScope((string)($context['scope'] ?? 'default'));
        $instance = $this->_normalizeInstance((string)($context['instance'] ?? 'default'));
        $submissionId = $submission?->id ? (int)$submission->id : null;

        $sessionInstanceNonce = $this->_resolveGuestInstanceNonce();

        // Anonymous visitors can share the same form/scope tuple, so salt the
        // continuity key with a session nonce to keep one guest's in-progress
        // state from colliding with another's.
        if ($sessionInstanceNonce) {
            $instance .= ':session:' . $sessionInstanceNonce;
        }

        $key = new FormInstanceKey();
        $key->formId = (int)$form->id;
        $key->siteId = (int)$siteId;
        $key->scope = $scope;
        $key->submissionId = $submissionId;
        $key->ownerId = $ownerId;
        $key->fingerprint = $this->deriveFormInstanceFingerprint($form, $submission, [
            'siteId' => $siteId,
            'ownerId' => $ownerId,
            'scope' => $scope,
            'instance' => $instance,
            'submissionId' => $submissionId,
        ]);

        return $key;
    }

    public function deriveFormInstanceFingerprint(Form $form, ?Submission $submission = null, array $context = []): string
    {
        $normalized = [
            'formId' => (int)$form->id,
            'siteId' => isset($context['siteId']) ? (int)$context['siteId'] : $this->_resolveSiteId(),
            'ownerId' => isset($context['ownerId']) ? (int)$context['ownerId'] : null,
            'submissionId' => isset($context['submissionId']) ? (int)$context['submissionId'] : (int)($submission?->id ?? 0),
            'scope' => $this->_normalizeScope((string)($context['scope'] ?? 'default')),
            'instance' => $this->_normalizeInstance((string)($context['instance'] ?? 'default')),
        ];

        ksort($normalized);

        return hash('sha256', Json::encode($normalized));
    }

    public function getProgressState(Form $form): ?DraftSubmissionState
    {
        $key = $this->resolveFormInstanceKey($form, null, [
            'scope' => 'submit',
            'instance' => $form->getSubmitStateKey(),
        ]);
        $draftState = $this->loadDraftState($key);

        if ($draftState) {
            return $draftState;
        }

        // Keep reading the identity-derived key as a fallback because resumed
        // drafts can be rebound into a newer submit-state key after the form is
        // rendered, but older callers may still have written progress under the
        // previous identity-based key.
        $fallbackKey = $this->resolveFormInstanceKey($form, null, [
            'scope' => 'submit',
            'instance' => $form->getSubmitStateIdentity(),
        ]);

        return $this->loadDraftState($fallbackKey);
    }

    public function upsertProgressState(Form $form, Submission $submission, ?int $currentPageId = null): ?DraftSubmissionState
    {
        if (!$submission->id) {
            return null;
        }

        $key = $this->resolveFormInstanceKey($form, null, [
            'scope' => 'submit',
            'instance' => $form->getSubmitStateKey(),
        ]);
        $draftState = $this->loadDraftState($key) ?? new DraftSubmissionState([
            'formInstanceKey' => $key,
            'version' => 1,
        ]);

        $draftState->submissionId = (int)$submission->id;
        $draftState->currentPageId = $currentPageId ? (int)$currentPageId : null;
        $draftState->content = $submission->serializeFieldValues();
        $draftState->snapshot = is_array($submission->snapshot) ? $submission->snapshot : [];

        $savedState = $this->saveDraftState($draftState);
        $this->_saveProgressFallbackState($form, $savedState);

        return $savedState;
    }

    public function upsertPageState(Form $form, ?int $currentPageId = null, ?int $submissionId = null): DraftSubmissionState
    {
        $key = $this->resolveFormInstanceKey($form, null, [
            'scope' => 'submit',
            'instance' => $form->getSubmitStateKey(),
        ]);
        $draftState = $this->loadDraftState($key) ?? new DraftSubmissionState([
            'formInstanceKey' => $key,
            'version' => 1,
        ]);

        if ($submissionId) {
            $draftState->submissionId = $submissionId;
        }

        $draftState->currentPageId = $currentPageId ? (int)$currentPageId : null;

        $savedState = $this->saveDraftState($draftState);
        $this->_saveProgressFallbackState($form, $savedState);

        return $savedState;
    }

    public function clearProgressState(Form $form): void
    {
        $keys = [
            $this->resolveFormInstanceKey($form, null, [
                'scope' => 'submit',
                'instance' => $form->getSubmitStateKey(),
            ]),
            $this->resolveFormInstanceKey($form, null, [
                'scope' => 'submit',
                'instance' => $form->getSubmitStateIdentity(),
            ]),
        ];

        foreach ($keys as $key) {
            $draftState = $this->loadDraftState($key);

            if ($draftState) {
                $this->deleteDraftState($draftState);
            }
        }
    }

    public function loadDraftState(FormInstanceKey|ResumeToken $key): ?DraftSubmissionState
    {
        $storage = Formie::$plugin->getStorageManager();
        $payload = null;

        if ($key instanceof ResumeToken) {
            $tokenRow = $this->_getResumeTokenRow($key->token);
            $storageKey = is_array($tokenRow) ? ($tokenRow['storageKey'] ?? null) : null;

            if (!is_string($storageKey) || trim($storageKey) === '') {
                return null;
            }

            $payload = $storage->get(trim($storageKey));
        } else {
            $payload = $storage->get($this->_storageKey($key));
        }

        if (!is_array($payload)) {
            return null;
        }

        return $this->_hydrateDraftState($payload);
    }

    public function saveDraftState(DraftSubmissionState $state): DraftSubmissionState
    {
        if ($state->formInstanceKey) {
            // The continuity stream is keyed by form instance, not by whichever
            // incomplete submission row currently backs it. The payload itself
            // carries the active submission id so the same draft stream can keep
            // pointing at the latest backing row without changing storage keys.
            $state->formInstanceKey->submissionId = null;
        }

        if (!$state->formInstanceKey) {
            return $state;
        }

        $storage = Formie::$plugin->getStorageManager();
        $storageKey = $this->_storageKey($state->formInstanceKey);
        $existing = $storage->get($storageKey);
        $nowTimestamp = time();
        $now = new DateTime('@' . $nowTimestamp);
        $state->dateUpdated = $now;

        $payload = [
            'storageKey' => $storageKey,
            'resumeToken' => $state->resumeToken ?: null,
            'formId' => (int)$state->formInstanceKey->formId,
            'siteId' => (int)$state->formInstanceKey->siteId,
            'submissionId' => $state->submissionId ?: null,
            'currentPageId' => $state->currentPageId ?: null,
            'content' => Json::encode($state->content),
            'snapshot' => Json::encode($state->snapshot),
            'version' => $state->version,
            'etag' => $state->etag,
            'dateUpdated' => $nowTimestamp,
            'dateCreated' => (is_array($existing) && isset($existing['dateCreated'])) ? (int)$existing['dateCreated'] : $nowTimestamp,
        ];

        $ttl = $this->_resolveStateTtlSeconds();
        $storage->set($storageKey, $payload, $ttl > 0 ? $ttl : null);

        return $state;
    }

    public function deleteDraftState(DraftSubmissionState $state): void
    {
        if (!$state->formInstanceKey) {
            return;
        }

        $storage = Formie::$plugin->getStorageManager();
        $storageKey = $this->_storageKey($state->formInstanceKey);
        $this->_deleteResumeTokenRowsByStorageKey($storageKey);

        $storage->delete($storageKey);
    }

    public function pruneDraftStates(?int $olderThanTimestamp = null): int
    {
        $settings = Formie::$plugin->getSettings();

        if ($olderThanTimestamp === null) {
            $olderThanTimestamp = time() - ((int)$settings->submissionStateRetentionDays * 86400);
        }

        if (!$this->_resumeTokenTableExists()) {
            return 0;
        }

        $now = time();
        $olderThan = gmdate('Y-m-d H:i:s', $olderThanTimestamp);
        $nowDate = gmdate('Y-m-d H:i:s', $now);

        return (int)Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_SUBMISSION_RESUME_TOKENS, [
                'or',
                ['<', 'dateExpires', $nowDate],
                ['<', 'dateUpdated', $olderThan],
            ])
            ->execute();
    }

    public function pruneExpiredDraftStorage(?int $olderThanTimestamp = null): int
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            return 0;
        }

        $settings = Formie::$plugin->getSettings();

        if ($olderThanTimestamp === null) {
            $olderThanTimestamp = time() - ((int)$settings->submissionStateRetentionDays * 86400);
        }

        $olderThan = gmdate('Y-m-d H:i:s', $olderThanTimestamp);
        $nowDate = gmdate('Y-m-d H:i:s', time());

        return (int)Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_SUBMISSION_DRAFTS, [
                'or',
                ['and', ['not', ['dateExpires' => null]], ['<', 'dateExpires', $nowDate]],
                ['<', 'dateUpdated', $olderThan],
            ])
            ->execute();
    }

    public function mergeDraftContentByUid(array $existingContent, array $incomingContent, array $clearFieldUids = []): array
    {
        return $this->_mergeContent($existingContent, $incomingContent, $clearFieldUids);
    }

    public function issueResumeToken(
        DraftSubmissionState $state,
        array $capabilities = [self::RESUME_CAPABILITY_READ, self::RESUME_CAPABILITY_UPDATE],
        ?int $ttlSeconds = null
    ): ResumeToken {
        $token = Craft::$app->getSecurity()->generateRandomString(64);
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->_resolveResumeTokenTtl($ttlSeconds);

        $state->resumeToken = $token;
        $state->snapshot = $this->_setResumeTokenMetadata($state->snapshot, [
            'capabilities' => array_values(array_unique($capabilities)),
            'issuedAt' => $issuedAt,
            'expiresAt' => $expiresAt,
            'revokedAt' => null,
        ]);
        $state->version++;
        $state->etag = $this->_computeDraftEtag($state);

        // Persist resume metadata in both places: the draft payload keeps the
        // state self-describing, while the token table gives us a revocable
        // lookup index without scanning draft blobs.
        $state = $this->saveDraftState($state);
        $this->_upsertResumeTokenRecord($token, $state, $capabilities, $issuedAt, $expiresAt, null);

        return new ResumeToken([
            'token' => $token,
            'formId' => $state->formInstanceKey?->formId,
            'siteId' => $state->formInstanceKey?->siteId,
            'submissionId' => $state->submissionId,
            'capabilities' => array_values(array_unique($capabilities)),
            'issuedAt' => $issuedAt,
            'expiresAt' => $expiresAt,
            'revokedAt' => null,
        ]);
    }

    public function issueSubmissionEditToken(Form $form, Submission $submission, ?int $ttlSeconds = null): ?ResumeToken
    {
        if (!$form->id || !$submission->id) {
            return null;
        }

        $siteId = (int)($submission->siteId ?: Craft::$app->getSites()->getCurrentSite()->id);
        $fingerprint = hash('sha256', Json::encode([
            'purpose' => 'formie-edit-submission',
            'formId' => (int)$form->id,
            'formUid' => (string)$form->uid,
            'siteId' => $siteId,
            'submissionId' => (int)$submission->id,
            'submissionUid' => (string)$submission->uid,
        ]));

        $state = new DraftSubmissionState([
            'formInstanceKey' => new FormInstanceKey([
                'formId' => (int)$form->id,
                'siteId' => $siteId,
                'scope' => 'edit-submission',
                'submissionId' => (int)$submission->id,
                'fingerprint' => $fingerprint,
            ]),
            'submissionId' => (int)$submission->id,
            'content' => [],
            'snapshot' => [
                'purpose' => 'formie-edit-submission',
                'formUid' => (string)$form->uid,
                'submissionUid' => (string)$submission->uid,
            ],
            'version' => 1,
        ]);

        return $this->issueResumeToken($state, [self::RESUME_CAPABILITY_EDIT], $ttlSeconds);
    }

    public function verifyResumeToken(string $token, array $requiredCapabilities = []): ?ResumeToken
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $tokenRow = $this->_getResumeTokenRow($token);

        if (!$tokenRow) {
            Formie::info('Resume token verification failed: token not found or mismatched.');
            return null;
        }

        if (!empty($tokenRow['revokedAt'])) {
            Formie::info('Resume token verification failed: token revoked.');
            return null;
        }

        $expiresAt = !empty($tokenRow['expiresAt']) ? (int)$tokenRow['expiresAt'] : 0;
        if ($expiresAt > 0 && time() > $expiresAt) {
            Formie::info('Resume token verification failed: token expired.');
            return null;
        }

        $capabilities = Json::decodeIfJson($tokenRow['capabilities'] ?? '[]');
        $capabilities = is_array($capabilities) ? $capabilities : [];

        foreach ($requiredCapabilities as $requiredCapability) {
            if (!in_array($requiredCapability, $capabilities, true)) {
                Formie::info('Resume token verification failed: missing required capability "{capability}".', [
                    'capability' => $requiredCapability,
                ]);
                return null;
            }
        }

        $storageKey = trim((string)($tokenRow['storageKey'] ?? ''));
        if ($storageKey === '') {
            Formie::info('Resume token verification failed: token missing storage key.');
            return null;
        }

        // The token row is only an index into the current draft state. If the
        // backing payload is gone, treat the token as invalid instead of letting
        // an orphaned lookup row resurrect stale resume state.
        $statePayload = Formie::$plugin->getStorageManager()->get($storageKey);
        if (!is_array($statePayload)) {
            Formie::info('Resume token verification failed: draft state not found.');
            return null;
        }

        return $this->_buildResumeTokenModelFromRow($tokenRow, $capabilities);
    }

    public function revokeResumeToken(string $token): bool
    {
        $token = trim($token);

        if ($token === '') {
            return false;
        }

        $tokenRow = $this->_getResumeTokenRow($token);

        if (!$tokenRow) {
            return false;
        }

        if (!$this->_resumeTokenTableExists()) {
            return false;
        }

        Craft::$app->getDb()->createCommand()
            ->update(Table::FORMIE_SUBMISSION_RESUME_TOKENS, [
                'revokedAt' => time(),
                'dateUpdated' => Db::prepareDateForDb(new DateTime()),
            ], ['token' => $token])
            ->execute();

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _resolveSiteId(): int
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return (int)Craft::$app->getSites()->getPrimarySite()->id;
        }

        return (int)Craft::$app->getSites()->getCurrentSite()->id;
    }

    private function _normalizeScope(string $scope): string
    {
        $scope = trim($scope);

        return $scope !== '' ? $scope : 'default';
    }

    private function _normalizeInstance(string $instance): string
    {
        $instance = trim($instance);

        return $instance !== '' ? $instance : 'default';
    }

    private function _resolveGuestInstanceNonce(): ?string
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return null;
        }

        $session = Craft::$app->getSession();

        if (!$session->getIsActive()) {
            $session->open();
        }

        $nonce = Session::get('formie:guest-instance-nonce');

        if (!is_string($nonce) || trim($nonce) === '') {
            $nonce = Craft::$app->getSecurity()->generateRandomString(24);
            Session::set('formie:guest-instance-nonce', $nonce);
        }

        return hash('sha256', $nonce);
    }

    private function _mergeContent(array $existing, array $incoming, array $clearKeys = []): array
    {
        $merged = $existing;

        foreach ($clearKeys as $clearKey) {
            unset($merged[$clearKey]);
        }

        foreach ($incoming as $key => $incomingValue) {
            if ($incomingValue === self::CLEAR_VALUE_SENTINEL) {
                unset($merged[$key]);
                continue;
            }

            if (!array_key_exists($key, $merged)) {
                $merged[$key] = $incomingValue;
                continue;
            }

            $existingValue = $merged[$key];

            if (is_array($existingValue) && is_array($incomingValue)) {
                if ($this->_isAssocArray($existingValue) || $this->_isAssocArray($incomingValue)) {
                    $merged[$key] = $this->_mergeContent($existingValue, $incomingValue);
                } else {
                    $merged[$key] = $incomingValue;
                }

                continue;
            }

            $merged[$key] = $incomingValue;
        }

        return $merged;
    }

    private function _saveProgressFallbackState(Form $form, DraftSubmissionState $state): void
    {
        if (!$state->formInstanceKey) {
            return;
        }

        $fallbackKey = $this->resolveFormInstanceKey($form, null, [
            'scope' => 'submit',
            'instance' => $form->getSubmitStateIdentity(),
        ]);

        if ($fallbackKey->toStorageKey() === $state->formInstanceKey->toStorageKey()) {
            return;
        }

        // Mirror the same state under the identity-based key so callers that do
        // not yet know the newer submit-state key can still recover continuity.
        $fallbackState = clone $state;
        $fallbackState->formInstanceKey = $fallbackKey;
        $this->saveDraftState($fallbackState);
    }

    private function _isAssocArray(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function _resolveResumeTokenTtl(?int $ttlSeconds = null): int
    {
        if ($ttlSeconds !== null && $ttlSeconds > 0) {
            return $ttlSeconds;
        }

        $configuredDays = (int)Formie::$plugin->getSettings()->saveResumeTokenTtlDays;

        if ($configuredDays > 0) {
            return $configuredDays * 86400;
        }

        return self::RESUME_TOKEN_DEFAULT_TTL;
    }

    private function _resolveStateTtlSeconds(): int
    {
        $retentionDays = (int)Formie::$plugin->getSettings()->submissionStateRetentionDays;

        if ($retentionDays <= 0) {
            return 0;
        }

        return $retentionDays * 86400;
    }

    private function _storageKey(FormInstanceKey $key): string
    {
        $raw = trim($key->toStorageKey());

        return str_starts_with($raw, self::STORAGE_KEY_PREFIX) ? $raw : (self::STORAGE_KEY_PREFIX . $raw);
    }

    private function _computeDraftEtag(DraftSubmissionState $state): string
    {
        return hash('sha256', Json::encode([
            'submissionId' => $state->submissionId,
            'currentPageId' => $state->currentPageId,
            'content' => $state->content,
            'snapshot' => $state->snapshot,
            'version' => $state->version,
        ]));
    }

    private function _getResumeTokenMetadata(array $snapshot): array
    {
        return $snapshot[self::RESUME_SNAPSHOT_KEY]['resumeToken'] ?? [];
    }

    private function _setResumeTokenMetadata(array $snapshot, array $metadata): array
    {
        $snapshot[self::RESUME_SNAPSHOT_KEY]['resumeToken'] = $metadata;

        return $snapshot;
    }

    private function _resumeTokenTableExists(): bool
    {
        return Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_RESUME_TOKENS);
    }

    private function _getResumeTokenRow(string $token): ?array
    {
        if (!$this->_resumeTokenTableExists()) {
            return null;
        }

        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $row = (new Query())
            ->from(Table::FORMIE_SUBMISSION_RESUME_TOKENS)
            ->where(['token' => $token])
            ->one();

        return is_array($row) ? $row : null;
    }

    private function _deleteResumeTokenRowsByStorageKey(string $storageKey): void
    {
        if (!$this->_resumeTokenTableExists()) {
            return;
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_SUBMISSION_RESUME_TOKENS, ['storageKey' => $storageKey])
            ->execute();
    }

    private function _upsertResumeTokenRecord(string $token, DraftSubmissionState $state, array $capabilities, int $issuedAt, int $expiresAt, ?int $revokedAt): void
    {
        if (!$this->_resumeTokenTableExists() || !$state->formInstanceKey) {
            return;
        }

        $now = new DateTime();
        $storageKey = $this->_storageKey($state->formInstanceKey);
        $payload = [
            'token' => $token,
            'storageKey' => $storageKey,
            'formId' => (int)$state->formInstanceKey->formId,
            'siteId' => (int)$state->formInstanceKey->siteId,
            'submissionId' => $state->submissionId ?: null,
            'capabilities' => Json::encode(array_values(array_unique($capabilities))),
            'issuedAt' => $issuedAt,
            'dateExpires' => Db::prepareDateForDb(new DateTime('@' . $expiresAt)),
            'expiresAt' => $expiresAt,
            'revokedAt' => $revokedAt,
            'dateUpdated' => Db::prepareDateForDb($now),
        ];

        Craft::$app->getDb()->createCommand()->upsert(
            Table::FORMIE_SUBMISSION_RESUME_TOKENS,
            array_merge($payload, ['dateCreated' => Db::prepareDateForDb($now)]),
            $payload
        )->execute();
    }

    private function _buildResumeTokenModelFromRow(array $row, array $capabilities): ResumeToken
    {
        $token = trim((string)($row['token'] ?? ''));
        $capabilities = array_values(array_filter($capabilities, static fn($value) => is_string($value) && trim($value) !== ''));

        return new ResumeToken([
            'token' => $token,
            'formId' => !empty($row['formId']) ? (int)$row['formId'] : null,
            'siteId' => !empty($row['siteId']) ? (int)$row['siteId'] : null,
            'submissionId' => !empty($row['submissionId']) ? (int)$row['submissionId'] : null,
            'capabilities' => $capabilities,
            'issuedAt' => !empty($row['issuedAt']) ? (int)$row['issuedAt'] : null,
            'expiresAt' => !empty($row['expiresAt']) ? (int)$row['expiresAt'] : null,
            'revokedAt' => isset($row['revokedAt']) && $row['revokedAt'] !== null ? (int)$row['revokedAt'] : null,
        ]);
    }

    private function _hydrateDraftState(array $row): DraftSubmissionState
    {
        $state = new DraftSubmissionState();
        $state->submissionId = !empty($row['submissionId']) ? (int)$row['submissionId'] : null;
        $state->currentPageId = !empty($row['currentPageId']) ? (int)$row['currentPageId'] : null;
        $state->content = is_string($row['content'] ?? null) ? (Json::decodeIfJson($row['content']) ?: []) : ((is_array($row['content'] ?? null)) ? $row['content'] : []);
        $state->snapshot = is_string($row['snapshot'] ?? null) ? (Json::decodeIfJson($row['snapshot']) ?: []) : ((is_array($row['snapshot'] ?? null)) ? $row['snapshot'] : []);
        $state->version = (int)($row['version'] ?? 1);
        $state->etag = $row['etag'] ?: null;
        $state->resumeToken = $row['resumeToken'] ?: null;
        $state->dateUpdated = !empty($row['dateUpdated'])
            ? (is_numeric($row['dateUpdated']) ? new DateTime('@' . (int)$row['dateUpdated']) : new DateTime((string)$row['dateUpdated']))
            : null;

        $key = new FormInstanceKey();
        $key->formId = (int)$row['formId'];
        $key->siteId = (int)$row['siteId'];
        $key->submissionId = null;
        $key->fingerprint = null;

        if (isset($row['storageKey']) && is_string($row['storageKey'])) {
            // Scope/fingerprint live inside the opaque storage key rather than as
            // first-class columns. Rehydrate enough of the key so future saves or
            // deletes stay on the same continuity stream.
            $parts = explode(':', $row['storageKey']);
            $scopeIndex = array_search('scope', $parts, true);
            $fingerprintIndex = array_search('fingerprint', $parts, true);
            $key->scope = ($scopeIndex !== false && isset($parts[$scopeIndex + 1])) ? $parts[$scopeIndex + 1] : 'default';
            $key->fingerprint = ($fingerprintIndex !== false && isset($parts[$fingerprintIndex + 1])) ? $parts[$fingerprintIndex + 1] : null;
        }

        $state->formInstanceKey = $key;

        return $state;
    }
}

<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\FileUploadRetentionHelper;
use verbb\formie\helpers\UploadAccess;
use verbb\formie\services\SubmissionGrants;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

class FileUploadController extends Controller
{
    // Constants
    // =========================================================================

    private const UPLOAD_RATE_LIMIT = 30;
    private const UPLOAD_RATE_WINDOW_SECONDS = 60;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'upload' => self::ALLOW_ANONYMOUS_LIVE,
        'delete' => self::ALLOW_ANONYMOUS_LIVE,
        'hydrate' => self::ALLOW_ANONYMOUS_LIVE,
        'view' => self::ALLOW_ANONYMOUS_LIVE,
    ];
    

    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // Image/download requests carry an explicit view capability and cannot
        // supply the custom session headers used by cross-origin form mutations.
        if ($action->id === 'view') {
            return parent::beforeAction($action);
        }

        $profile = \verbb\formie\helpers\BrowserRequestProfile::enter();
        if ($profile === \verbb\formie\helpers\BrowserRequestProfile::CROSS_ORIGIN) {
            $this->enableCsrfValidation = false;
        }
        \verbb\formie\helpers\CrossOriginRequestHelper::applyHeaders($this->request, $this->response);
        if ($this->request->getIsOptions()) {
            $this->response->setStatusCode(204);
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionUpload(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formHandle = trim((string)$this->request->getBodyParam('handle', ''));
        $fieldHandle = trim((string)$this->request->getBodyParam('fieldHandle', ''));
        $inputKey = trim((string)$this->request->getBodyParam('inputKey', ''));
        $renderId = trim((string)$this->request->getBodyParam('renderId', ''));
        $draftContextToken = trim((string)$this->request->getBodyParam('draftContextToken', ''));
        $draftContext = trim((string)$this->request->getBodyParam('draftContext', ''));
        $submissionId = $this->request->getBodyParam('submissionId');
        $submissionId = is_numeric($submissionId) ? (int)$submissionId : null;

        if ($formHandle === '' || $fieldHandle === '') {
            throw new BadRequestHttpException('Missing handle or fieldHandle.');
        }

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle, \verbb\formie\helpers\SiteHelper::resolveSiteIdFromRequest());

        if (!$form) {
            throw new BadRequestHttpException('Invalid form handle.');
        }

        $this->_enforceUploadRateLimit($formHandle, $fieldHandle);

        if ($renderId !== '') {
            $form->setRenderId($renderId);
        }

        if ($draftContextToken !== '') {
            $draftContext = (string)$form->resolveDraftContextToken($draftContextToken);
        }

        if ($draftContext !== '') {
            $form->setDraftContext($draftContext);
        }

        $submissionId = $this->_resolveSubmissionId($form, $submissionId);
        // Upload Manager posts data-formie-field-handle (valueKey), including nested
        // Group/Repeater paths like `group.documents` or `repeater.0.documents`.
        $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $fieldHandle);

        if (!$field) {
            throw new BadRequestHttpException('Invalid file upload field.');
        }

        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file was uploaded.');
        }

        $filename = $field->sanitizeUploadedFilename($uploadedFile->name);
        $this->_validateUploadRequestFile($field, $filename, $uploadedFile->tempName, (int)$uploadedFile->size, $uploadedFile->type ?: null);

        $uploadFolder = Formie::$plugin->getFileUploads()->getStagingFolder();
        $tempPath = Assets::tempFilePath($filename);
        $this->_moveUploadedFile($uploadedFile->tempName, $tempPath);

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = $uploadFolder->id;
        $asset->setVolumeId($uploadFolder->volumeId);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!Formie::$plugin->getFileUploads()->saveStagedAsset($asset, $form, $submissionId, $field->uid, $fieldHandle)) {
            return $this->asJson([
                'success' => false,
                'errors' => $asset->getErrors(),
            ]);
        }

        $uploadToken = UploadAccess::issueToken((int)$asset->id, (int)$form->id, (string)$field->uid);

        return $this->asJson([
            'success' => true,
            'assetId' => (int)$asset->id,
            'filename' => $asset->filename,
            'url' => UploadAccess::viewUrl($uploadToken),
            'uploadToken' => $uploadToken,
            'uploadUid' => Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId((int)$asset->id)['uid'],
            'deleteToken' => UploadAccess::issueToken((int)$asset->id, (int)$form->id, (string)$field->uid, purpose: 'delete'),
            'attachToken' => UploadAccess::issueToken((int)$asset->id, (int)$form->id, (string)$field->uid, purpose: 'attach'),
            'inputKey' => $inputKey !== '' ? $inputKey : null,
        ]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetId = (int)$this->request->getRequiredBodyParam('assetId');
        $uploadToken = trim((string)$this->request->getBodyParam('uploadToken', ''));
        [$form, $field] = $this->_resolveUploadContext();

        // Capability tokens bind asset+form+field; CSRF alone is not ownership.
        if (!UploadAccess::matches($assetId, (int)$form->id, (string)$field->uid, $uploadToken, 'delete')) {
            throw new BadRequestHttpException('Invalid upload capability.');
        }

        if (!Formie::$plugin->getFileUploads()->removeUploadByAssetId($assetId, (int)$form->id, $field->uid)) {
            throw new BadRequestHttpException('Upload not found.');
        }

        return $this->asJson(['success' => true]);
    }

    public function actionView(): Response
    {
        if (!$this->request->getIsGet() && !$this->request->getIsHead()) {
            throw new MethodNotAllowedHttpException('Upload previews require GET or HEAD.');
        }
        $upload = UploadAccess::resolveToken((string)$this->request->getQueryParam('token', ''), 'view');
        $asset = $upload ? Asset::find()->id((int)$upload['assetId'])->status(null)->one() : null;
        if (!$asset) {
            throw new NotFoundHttpException('Upload is unavailable.');
        }

        $headers = $this->response->getHeaders();
        $headers->set('Cache-Control', 'private, no-store, max-age=0');
        $headers->set('Pragma', 'no-cache');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $inline = in_array($asset->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);

        return $this->response->sendStreamAsFile($asset->getVolume()->getFileStream($asset->getPath()), $asset->filename, [
            'fileSize' => (int)$asset->size,
            'mimeType' => $inline ? $asset->getMimeType() : 'application/octet-stream',
            'inline' => $inline,
        ]);
    }

    public function actionHydrate(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetIds = $this->request->getBodyParam('assetIds', []);

        if (!is_array($assetIds)) {
            throw new BadRequestHttpException('Invalid assetIds payload.');
        }

        [$form, $field] = $this->_resolveUploadContext();
        $assetIds = array_values(array_filter(array_map('intval', $assetIds)));
        $uploadTokens = $this->_normalizeUploadTokensParam();
        $formId = (int)$form->id;
        $fieldUid = (string)$field->uid;

        $authorizedByToken = [];

        foreach ($assetIds as $assetId) {
            $token = $uploadTokens[$assetId] ?? null;

            if (UploadAccess::matches($assetId, $formId, $fieldUid, is_string($token) ? $token : null)) {
                $authorizedByToken[] = $assetId;
            }
        }

        $authorizedAssetIds = array_values(array_unique($authorizedByToken));
        $missingAssetIds = array_values(array_diff($assetIds, $authorizedAssetIds));

        // Submission-linked assets require continuation/edit access — not bare submissionUid.
        if ($missingAssetIds) {
            $authorizedAssetIds = array_values(array_unique(array_merge(
                $authorizedAssetIds,
                $this->_resolveAuthorizedSubmissionAssetIds($form, $field, $missingAssetIds),
            )));
        }

        $assets = Asset::find()
            ->id(array_filter($authorizedAssetIds))
            ->status(null)
            ->all();
        $assetMap = [];

        foreach ($assets as $asset) {
            $assetId = (int)$asset->id;
            $token = $uploadTokens[$assetId] ?? null;

            if (!is_string($token) || !UploadAccess::matches($assetId, $formId, $fieldUid, $token)) {
                $token = UploadAccess::issueToken($assetId, $formId, $fieldUid);
            }

            $tracked = Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);
            $mayDelete = $tracked && $tracked['state'] === 'staged'
                && (int)$tracked['siteId'] === (int)$form->siteId
                && hash_equals((string)$tracked['browserHash'], Formie::$plugin->getSubmissionGrants()->browserHash($form));
            $assetMap[$assetId] = [
                'assetId' => $assetId,
                'filename' => (string)$asset->filename,
                'url' => UploadAccess::viewUrl($token) ?? (($tracked['state'] ?? null) !== 'staged' ? $asset->url : null),
                'uploadToken' => $token,
                'deleteToken' => $mayDelete ? UploadAccess::issueToken($assetId, $formId, $fieldUid, purpose: 'delete') : null,
            ];
        }

        $uploads = array_map(static fn($row) => array_intersect_key($row, array_flip(['uid', 'assetId', 'state', 'isFinalized', 'expiresAt'])), Formie::$plugin->getFileUploads()->getUploadMetadata($authorizedAssetIds, $formId, $fieldUid));

        return $this->asJson([
            'success' => true,
            'uploads' => $uploads,
            'assets' => array_values(array_filter(array_map(function(int $assetId) use ($assetMap) {
                return $assetMap[$assetId] ?? null;
            }, $authorizedAssetIds))),
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _normalizeUploadTokensParam(): array
    {
        $raw = $this->request->getBodyParam('uploadTokens', []);

        if (!is_array($raw)) {
            return [];
        }

        $tokens = [];

        foreach ($raw as $key => $value) {
            $assetId = is_numeric($key) ? (int)$key : 0;
            $token = is_string($value) ? trim($value) : '';

            if ($assetId > 0 && $token !== '') {
                $tokens[$assetId] = $token;
            }
        }

        return $tokens;
    }

    private function _resolveAuthorizedSubmissionAssetIds(Form $form, FileUpload $field, array $assetIds): array
    {
        $submissionUid = trim((string)$this->request->getBodyParam('submissionUid', ''));

        if ($submissionUid === '' || !$assetIds) {
            return [];
        }

        $submission = Submission::find()
            ->uid($submissionUid)
            ->siteId((int)$form->siteId)
            ->isIncomplete(null)
            ->isSpam(null)
            ->formId((int)$form->id)
            ->status(null)
            ->one();

        if (!$submission || !$this->_canAccessSubmissionUploads($form, $submission)) {
            return [];
        }

        // Prefer the posted content key so repeater row indexes (`repeater.0.upload`)
        // resolve; fall back to the field's valueKey for Group-scoped instances.
        $contentKey = trim((string)$this->request->getBodyParam('fieldHandle', ''));
        $value = $submission->getFieldValue($contentKey !== '' ? $contentKey : $field->valueKey());

        if (!$value || !method_exists($value, 'ids')) {
            return [];
        }

        $allowedIds = array_map('intval', $value->ids());

        return array_values(array_intersect($assetIds, $allowedIds));
    }

    private function _canAccessSubmissionUploads(Form $form, Submission $submission, bool $write = false): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($user && ($write ? Formie::$plugin->getPermissions()->canSaveSubmissions($user, $form) : Formie::$plugin->getPermissions()->canViewSubmissions($user, $form))) {
            return true;
        }

        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            return false;
        }

        $progressState = Formie::$plugin->getSubmissionProgress()->getProgressState($form);

        if ($progressState && (int)$progressState->submissionId === (int)$submission->id) {
            return true;
        }

        $purpose = $submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE;
        if (Formie::$plugin->getSubmissionGrants()->bound($form, $purpose, (int)$submission->id)) {
            return true;
        }
        $resumeToken = trim((string)$this->request->getBodyParam('submissionEditToken', $this->request->getBodyParam('resumeToken', '')));

        if ($resumeToken === '') {
            return false;
        }

        $purpose = $submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE;
        return Formie::$plugin->getSubmissionGrants()->exchange($resumeToken, $purpose, $form, (int)$submission->id) !== null;

    }

    private function _resolveUploadContext(): array
    {
        $formHandle = trim((string)$this->request->getBodyParam('handle', ''));
        $fieldHandle = trim((string)$this->request->getBodyParam('fieldHandle', ''));

        if ($formHandle === '' || $fieldHandle === '') {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle, \verbb\formie\helpers\SiteHelper::resolveSiteIdFromRequest());

        if (!$form) {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        $contextToken = $this->request->getBodyParam('draftContextToken');
        Formie::$plugin->getSubmissionProcessor()->applyFormRequestContext($form,
            $this->request->getBodyParam('renderId'),
            $contextToken ? $form->resolveDraftContextToken($contextToken) : $this->request->getBodyParam('draftContext'),
        );
        $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $fieldHandle);

        if (!$field) {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        return [$form, $field];
    }

    private function _resolveSubmissionId(Form $form, ?int $submissionId): ?int
    {
        if (!$submissionId) {
            return null;
        }

        $submission = Submission::find()
            ->id($submissionId)
            ->formId((int)$form->id)
            ->isIncomplete(null)
            ->siteId((int)$form->siteId)
            ->isSpam(null)
            ->one();

        if (!$submission || !$this->_canAccessSubmissionUploads($form, $submission, true)) {
            throw new BadRequestHttpException('Invalid upload submission.');
        }

        return (int)$submission->id;
    }

    private function _enforceUploadRateLimit(string $formHandle, string $fieldHandle): void
    {
        $window = self::UPLOAD_RATE_WINDOW_SECONDS;
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $fingerprint = md5($formHandle . '|' . $fieldHandle . '|' . $ipAddress);
        $cacheKey = 'formie.file-upload-rate.' . $fingerprint;
        $mutexKey = 'formie.file-upload-rate-lock.' . $fingerprint;
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + $window,
                ];
            }

            if ((int)$entry['count'] >= self::UPLOAD_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many upload requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }

    private function _validateUploadRequestFile(FileUpload $field, string $filename, ?string $path, int $size, ?string $mimeType = null): void
    {
        if ($filename === '') {
            throw new BadRequestHttpException('Invalid upload filename.');
        }

        foreach ($field->getUploadTypeValidationErrors($filename, $path, $mimeType) as $message) {
            throw new BadRequestHttpException($message);
        }

        if ($field->exceedsMaxUploadSize($size)) {
            throw new BadRequestHttpException('Uploaded file exceeds the maximum allowed size.');
        }
    }

    private function _moveUploadedFile(string $sourcePath, string $targetPath): void
    {
        if (move_uploaded_file($sourcePath, $targetPath)) {
            return;
        }

        if ((getenv('ENVIRONMENT') ?: '') === 'testing' && is_file($sourcePath) && rename($sourcePath, $targetPath)) {
            return;
        }

        throw new BadRequestHttpException('Unable to move uploaded file.');
    }

}

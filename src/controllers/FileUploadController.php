<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\web\BadRequestHttpException;
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
    ];
    

    // Public Methods
    // =========================================================================

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

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

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
        $field = $form->getFieldByHandle($fieldHandle);

        if (!$field || !($field instanceof FileUpload)) {
            throw new BadRequestHttpException('Invalid file upload field.');
        }

        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file was uploaded.');
        }

        $filename = $field->sanitizeUploadedFilename($uploadedFile->name);
        $this->_validateUploadRequestFile($field, $filename, $uploadedFile->tempName, (int)$uploadedFile->size, $uploadedFile->type ?: null);

        $tempPath = Assets::tempFilePath($filename);
        $this->_moveUploadedFile($uploadedFile->tempName, $tempPath);

        $uploadFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = $uploadFolder->id;
        $asset->setVolumeId($uploadFolder->volumeId);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            return $this->asJson([
                'success' => false,
                'errors' => $asset->getErrors(),
            ]);
        }

        Formie::$plugin->getFileUploads()->trackSubmissionAsset(
            $asset,
            (int)$form->id,
            $submissionId,
            $field->uid
        );

        return $this->asJson([
            'success' => true,
            'assetId' => (int)$asset->id,
            'filename' => $asset->filename,
            'url' => $asset->url,
            'inputKey' => $inputKey !== '' ? $inputKey : null,
        ]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetId = (int)$this->request->getRequiredBodyParam('assetId');
        [$form, $field] = $this->_resolveUploadContext();

        if (!Formie::$plugin->getFileUploads()->removeUploadByAssetId($assetId, (int)$form->id, $field->uid)) {
            throw new BadRequestHttpException('Upload not found.');
        }

        return $this->asJson(['success' => true]);
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
        $uploads = Formie::$plugin->getFileUploads()->getUploadMetadata($assetIds, (int)$form->id, $field->uid);
        $authorizedAssetIds = array_values(array_unique(array_map(static function(array $upload): int {
            return (int)($upload['assetId'] ?? 0);
        }, $uploads)));

        $missingAssetIds = array_values(array_diff($assetIds, $authorizedAssetIds));

        if ($missingAssetIds) {
            $authorizedAssetIds = array_values(array_unique(array_merge(
                $authorizedAssetIds,
                $this->_resolveSubmissionAssetIds($form, $field, $missingAssetIds),
            )));
        }

        $assets = Asset::find()
            ->id(array_filter($authorizedAssetIds))
            ->status(null)
            ->all();
        $assetMap = [];

        foreach ($assets as $asset) {
            $assetMap[(int)$asset->id] = [
                'assetId' => (int)$asset->id,
                'filename' => (string)$asset->filename,
                'url' => $asset->url ?: null,
            ];
        }

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

    private function _resolveSubmissionAssetIds(Form $form, FileUpload $field, array $assetIds): array
    {
        $submissionUid = trim((string)$this->request->getBodyParam('submissionUid', ''));

        if ($submissionUid === '' || !$assetIds) {
            return [];
        }

        $submission = Submission::find()
            ->uid($submissionUid)
            ->formId((int)$form->id)
            ->status(null)
            ->one();

        if (!$submission) {
            return [];
        }

        $value = $submission->getFieldValue($field->handle);

        if (!$value || !method_exists($value, 'ids')) {
            return [];
        }

        $allowedIds = array_map('intval', $value->ids());

        return array_values(array_intersect($assetIds, $allowedIds));
    }

    private function _resolveUploadContext(): array
    {
        $formHandle = trim((string)$this->request->getBodyParam('handle', ''));
        $fieldHandle = trim((string)$this->request->getBodyParam('fieldHandle', ''));

        if ($formHandle === '' || $fieldHandle === '') {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

        if (!$form) {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        $field = $form->getFieldByHandle($fieldHandle);

        if (!$field || !($field instanceof FileUpload)) {
            throw new BadRequestHttpException('Invalid upload context.');
        }

        return [$form, $field];
    }

    private function _resolveSubmissionId(Form $form, ?int $submissionId): ?int
    {
        if (!$submissionId) {
            return null;
        }

        if (Craft::$app->getRequest()->getIsSiteRequest() && Craft::$app->getUser()->getIsGuest()) {
            $progressState = Formie::$plugin->getSubmissionDrafts()->getProgressState($form);

            if (!$progressState || (int)$progressState->submissionId !== $submissionId) {
                throw new BadRequestHttpException('Invalid upload submission.');
            }
        }

        $submission = Submission::find()
            ->id($submissionId)
            ->formId((int)$form->id)
            ->isIncomplete(true)
            ->isSpam(null)
            ->one();

        if (!$submission) {
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

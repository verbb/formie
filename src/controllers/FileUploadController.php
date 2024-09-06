<?php
namespace verbb\formie\controllers;

use Craft;
use craft\controllers\AssetsControllerTrait;
use craft\elements\Asset;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\VolumeException;
use craft\helpers\Assets;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\UploadedFile;
use Throwable;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;

class FileUploadController extends Controller
{
    use AssetsControllerTrait;

    // Constants
    // =========================================================================

    public const EVENT_AFTER_FILE_UPLOAD = 'afterFileUpload';
    public const EVENT_BEFORE_FILE_UPLOAD = 'beforeFileUpload';

    // Protected Properties
    // =========================================================================
    protected array|bool|int $allowAnonymous = [
        'load-file' => self::ALLOW_ANONYMOUS_LIVE,
        'remove-file' => self::ALLOW_ANONYMOUS_LIVE,
        'process-file' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    // Private Properties
    // =========================================================================

    private string $_namespace = 'fields';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action);
    }

    /**
     * @return Response
     * @throws HttpException
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionLoadFile(): Response {
        $id = $this->request->getParam('id');
        $asset = Asset::find()
            ->id($id)
            ->one();
        $content = $asset->getContents();
        return $this->response->sendContentAsFile($content, "$asset->title.$asset->extension", [
            'inline' => true,
            'mimeType' => $asset->mimeType
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRemoveFile(): Response {
        $this->requirePostRequest();

        $handle = $this->_getTypedParam('handle', 'string');

        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        $removeFile = $this->_getTypedParam('removeFile', 'id');

        if (!$removeFile) {
            throw new BadRequestHttpException("No asset id passed in request.");
        }

        $asset = Craft::$app->getAssets()->getAssetById($removeFile);

        if ($asset) {
            $this->_deleteAsset($removeFile);
        } else {
            throw new BadRequestHttpException("No asset exists with the ID: \"$removeFile\"");
        }


        return $this->asRaw(true);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidFieldException
     * @throws VolumeException
     * @throws Exception
     */
    public function actionProcessFile(): Response {
        $this->requirePostRequest();
        $handle = $this->_getTypedParam('handle', 'string');
        $submissionId = $this->_getTypedParam('submissionId', 'id');


        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        $submission = $this->_populateSubmission($form);
        $initiator = $this->_getTypedParam('initiator', 'string');
        $field = $form->getFieldByHandle($initiator);
        $volume = Craft::$app->getVolumes()->getVolumeByUid(explode(':', $field->uploadLocationSource)[1]);
        $file = UploadedFile::getInstanceByName("file");

        $filename = Assets::prepareAssetName($file->name);
        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
        $subpath = $field->uploadLocationSubpath;
        if ($field->uploadLocationSubpath) {
            $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $volume);
        }
        $asset = new Asset();
        $asset->tempFilePath = $file->tempName;
        $asset->setFilename($filename);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($volume->id);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;

        $asset->setScenario(Asset::SCENARIO_CREATE);
        $result = Craft::$app->getElements()->saveElement($asset);

        if ($submissionId) {
            $assetIds = $submission->getFieldValue($initiator)->ids();
            $assetIds[] = $asset->id;
            $submission->setFieldValue($initiator, $assetIds);
            // Save the submission
            $success = Craft::$app->getElements()->saveElement($submission, false);

            if ($success) {
                return $this->asJson(["id" => $asset->id, "url" => $asset->getUrl(), "submissionId" => $submission->id]);
            } else {
                throw new BadRequestHttpException("Unable to save Formie submission.");
            }
        }

        if ($result) {
            return $this->asJson(["id" => $asset->id, "url" => $asset->getUrl()]);
        }

        throw new BadRequestHttpException("Unable to process upload asset request.");
    }

    // Private Methods
    // =========================================================================

    private function _populateSubmission($form, $isIncomplete = true): Submission
    {
        $request = $this->request;

        // Ensure we validate some params here to prevent potential malicious-ness
        $editingSubmission = $this->_getTypedParam('editingSubmission', 'boolean');
        $submissionId = $this->_getTypedParam('submissionId', 'id');
        $siteId = $this->_getTypedParam('siteId', 'id');
        $userParam = $request->getBodyParam('user');

        if ($submissionId) {
            // Allow fetching spammed submissions for multistep forms, where it has been flagged as spam
            // already, but we want to complete the form submission.
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete($isIncomplete)
                ->isSpam(null)
                ->one();

            if (!$submission) {
                throw new BadRequestHttpException("No submission exists with the ID \"$submissionId\"");
            }
        } else {
            $submission = new Submission();
        }

        $submission->setForm($form);

        $siteId = $siteId ?: null;
        $submission->siteId = $siteId ?? $submission->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;

        $submission->setFieldValuesFromRequest($this->_namespace);
        $submission->setFieldParamNamespace($this->_namespace);

        // Only ever set for a brand-new submission
        if (!$submission->id && $form->settings->collectIp) {
            $submission->ipAddress = $request->userIP;
        }

        if ($form->settings->collectUser) {
            if ($user = Craft::$app->getUser()->getIdentity()) {
                $submission->setUser($user);
            }

            // Allow a `user` override (when editing a submission through the CP)
            if ($request->getIsCpRequest() && $userParam) {
                $submission->userId = $userParam[0] ?? null;
            }
        }

        $this->_setTitle($submission, $form);

        // If we're editing a submission, ensure we set our flag
        if ($editingSubmission) {
            $form->setSubmission($submission);
        }

        return $submission;
    }

    private function _getTypedParam(string $name, string $type, mixed $default = null, bool $bodyParam = true): mixed
    {
        $request = $this->request;

        if ($bodyParam) {
            $value = $request->getBodyParam($name);
        } else {
            $value = $request->getParam($name);
        }

        // Special case for `submitAction`, where we don't want just anything passed in to change behaviour
        if ($name === 'submitAction') {
            if (!in_array($value, ['submit', 'back', 'save'])) {
                return $default;
            }
        }

        if ($value !== null) {
            // Go case-by-case, so it's easier to handle, and more predictable
            if ($type === 'string' && is_string($value)) {
                return $value;
            }

            if ($type === 'boolean' && is_string($value)) {
                return StringHelper::toBoolean($value);
            }

            if ($type === 'int' && (is_numeric($value) || $value === '')) {
                return (int)$value;
            }

            if ($type === 'id' && is_numeric($value) && (int)$value > 0) {
                return (int)$value;
            }

            throw new BadRequestHttpException('Request has invalid param ' . $name);
        }

        return $default;
    }

    private function _setTitle($submission, $form): void
    {
        $submission->title = Variables::getParsedValue($form->settings->submissionTitleFormat, $submission, $form);

        // Set the default title for the submission, so it can save correctly
        if (!$submission->title) {
            $now = new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));
            $submission->title = $now->format('D, d M Y H:i:s');
        }
    }

    private function _getForm(string $handle): ?Form
    {
        $form = Form::find()->handle($handle)->one();

        if ($form) {
            if ($sessionKey = $this->_getTypedParam('sessionKey', 'string')) {
                $form->setSessionKey($sessionKey);
            }
        }

        return $form;
    }

    private function _deleteAsset($assetId) {
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        // Check if it's possible to delete objects in the target volume.
        $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);

        $success = Craft::$app->getElements()->deleteElement($asset, true);

        if (!$success) {
            throw new BadRequestHttpException("Unable to delete asset on disk: $assetId", "Upload");
        }
    }
}
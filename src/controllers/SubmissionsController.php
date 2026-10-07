<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\compatibility\payments\LegacyPaymentSubmitResponse;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\errors\SubmissionUnavailableException;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SetPageReturnUrlHelper;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\TypeHelper;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionErrors;
use verbb\formie\models\SubmissionResponse;

use Craft;
use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use InvalidArgumentException;

class SubmissionsController extends Controller
{
    // Static Methods
    // =========================================================================

    private static function _resolveSubmittedPageId(
        Form $form,
        Submission $submission,
        SubmissionCommand $submissionRequest,
        SubmissionResponse $response,
    ): ?int {
        $pageId = (int)($submissionRequest->pageId ?? 0);

        if ($pageId > 0) {
            return $pageId;
        }

        if ($response->nextPage) {
            $previousPage = $form->getPreviousPage($response->nextPage, $submission);

            return $previousPage?->id ? (int)$previousPage->id : null;
        }

        $pages = $form->getPages();

        return $pages ? (int)$pages[0]->id : null;
    }


    // Constants
    // =========================================================================

    public const EVENT_AFTER_SUBMISSION_REQUEST = 'afterSubmissionRequest';

    private const STALE_SUBMISSION_STATE_CODE = 'STALE_SUBMISSION_STATE';
    private const STALE_SUBMISSION_STATE_QUERY_PARAMS = ['pageId', 'resumeToken', 'submissionId'];


    // Traits
    // =========================================================================

    use LegacyPaymentSubmitResponse;
    use CrossOriginRequestTrait;
    use AnonymousSiteRequestGuardTrait;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'submit' => self::ALLOW_ANONYMOUS_LIVE,
        'set-page' => self::ALLOW_ANONYMOUS_LIVE,
        'save-submission' => self::ALLOW_ANONYMOUS_LIVE,
        'clear-submission' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    private string $_namespace = 'fields';
    private bool $_allowTestOverrides = false;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $settings = Formie::$plugin->getSettings();
        $publicProfile = null;

        if (in_array($action->id, ['submit', 'set-page', 'clear-submission'], true)) {
            $publicProfile = BrowserRequestProfile::enter();
        }

        $this->forbidGuestControlPanelAnonymousActions($action->id);

        if (in_array($action->id, ['submit', 'save-submission'], true) && Craft::$app->getUser()->isGuest && !$settings->enableCsrfValidationForGuests) {
            $this->enableCsrfValidation = false;
        }

        if (in_array($action->id, ['submit', 'save-submission'], true)) {
            $resumeToken = $this->request->getParam('resumeToken');

            if (is_string($resumeToken) && trim($resumeToken) !== '') {
                $this->_markStatefulResponseNoCache();
            }
        }

        // Check for live preview requests, or unpublished pages
        if ($this->request->getIsLivePreview() || $this->request->getIsPreview()) {
            $this->enableCsrfValidation = false;
        }

        if ($publicProfile === BrowserRequestProfile::CROSS_ORIGIN) {
            $this->enableCsrfValidation = false;
        } elseif ($publicProfile && $this->request->getHeaders()->has('X-Formie-Profile')) {
            $this->enableCsrfValidation = true;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $siteIds = Craft::$app->getSites()->getAllSiteIds();
        $activeSiteId = Formie::$plugin->getFormSiteOverrides()->getActiveSiteId();
        $canCreateAnySubmissions = $currentUser->can('formie-createSubmissions');

        Formie::$plugin->registerCpSubmissionsAssets();

        $this->requirePermission('formie-accessSubmissions');

        // Avoid hydrating full Form elements for index bootstrap payload.
        $forms = (new Query())
            ->select([
                'f.id',
                'e.uid',
                'f.handle',
                'es.title',
            ])
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->innerJoin(['es' => Table::ELEMENTS_SITES], '[[es.elementId]] = [[f.id]] AND [[es.siteId]] = :siteId', [
                ':siteId' => $activeSiteId,
            ])
            ->where(['e.dateDeleted' => null])
            ->all();

        $canonicalTitles = [];

        foreach ($forms as $form) {
            $formId = (int)($form['id'] ?? 0);

            if ($formId) {
                $canonicalTitles[$formId] = (string)($form['title'] ?? $form['handle'] ?? '');
            }
        }

        $displayTitles = Formie::$plugin->getFormSiteOverrides()->resolveFormTitlesForSite(
            $canonicalTitles,
            $activeSiteId,
        );

        $editableForms = [];

        foreach ($forms as $form) {
            if (!$canCreateAnySubmissions && !$currentUser->can('formie-createSubmissions:' . ($form['uid'] ?? ''))) {
                continue;
            }

            $formId = (int)($form['id'] ?? 0);

            $editableForms[] = [
                'id' => $formId,
                'handle' => (string)($form['handle'] ?? ''),
                'name' => $displayTitles[$formId] ?? (string)($form['title'] ?? ''),
                'sites' => $siteIds,
                'uid' => (string)($form['uid'] ?? ''),
            ];
        }

        return $this->renderTemplate('formie/submissions/index', [
            'defaultState' => $settings->submissionsBehaviour,
            'editableForms' => $editableForms,
        ]);
    }

    public function actionEditSubmission(string $formHandle, int $submissionId = null, ?Submission $submission = null, ?string $site = null): Response
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $sitesService = Craft::$app->getSites();
        $editableSiteIds = $sitesService->getEditableSiteIds();

        if ($site !== null) {
            $siteModel = $sitesService->getSiteByHandle($site);

            if (!$siteModel) {
                throw new BadRequestHttpException("Invalid site handle: $site");
            }

            if (!in_array($siteModel->id, $editableSiteIds, false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        } else {
            $siteModel = $sitesService->getCurrentSite();

            if (!in_array($siteModel->id, $editableSiteIds, false)) {
                $siteModel = $sitesService->getSiteById($editableSiteIds[0]);
            }
        }

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

        if (!$form) {
            throw new HttpException(404);
        }

        $variables = [
            'formHandle' => $formHandle,
            'submissionId' => $submissionId,
            'submission' => $submission,
            'form' => $form,
            'site' => $siteModel,
        ];

        if (!$variables['submission']) {
            if ($variables['submissionId']) {
                $variables['submission'] = Submission::find()
                    ->id($variables['submissionId'])
                    ->isIncomplete(null)
                    ->isSpam(null)
                    ->one();
            } else {
                $variables['submission'] = new Submission();
                $variables['submission']->setForm($form);

                // Set the user to the default
                if ($form->settings->collectUser) {
                    $variables['submission']->setUser(Craft::$app->getUser()->getIdentity());
                }
            }
        }

        if (!$variables['submission']) {
            throw new HttpException(404);
        }

        if (!$variables['submission']->canView($currentUser)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $variables['submission']->setForm($form);

        $this->_prepEditSubmissionVariables($variables);

        if ($variables['submission']->id) {
            $variables['title'] = $variables['submission']->title;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new submission');
        }

        $formConfigJson = $form->getCpEditConfig();

        // Add some settings just for submission editing
        $formConfigJson['settings']['outputJs'] = false;
        $variables['formConfigJson'] = $formConfigJson;
        $variables['submissionClientContext'] = ConditionsHelper::getClientSubmissionContext($variables['submission']);

        return $this->renderTemplate('formie/submissions/_edit', $variables);
    }

    public function actionDeleteSubmission(): ?Response
    {
        $this->requirePostRequest();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $submissionId = $this->request->getRequiredBodyParam('submissionId');

        $submission = Submission::find()
            ->id($submissionId)
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        if (!$submission->canDelete($currentUser)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!Craft::$app->getElements()->deleteElement($submission)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Submission deleted.'));

        return $this->redirectToPostedUrl($submission);
    }

    public function actionGetSendNotificationModalContent(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $view = $this->getView();

        $submission = Submission::find()
            ->id($this->request->getParam('id'))
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        $this->_requireSubmissionPermission($submission);

        $notifications = $submission->getForm()?->getNotifications() ?? [];

        $modalHtml = $view->renderTemplate('formie/submissions/_includes/send-notification-modal', [
            'submission' => $submission,
            'notifications' => $notifications,
        ]);

        return $this->asJson([
            'success' => true,
            'modalHtml' => $modalHtml,
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml(),
        ]);
    }

    public function actionSendNotification(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $notificationId = $this->request->getRequiredParam('notificationId');
        $notification = Formie::$plugin->getNotifications()->getNotificationById($notificationId);

        $submission = Submission::find()
            ->id($this->request->getParam('submissionId'))
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            $error = Craft::t('formie', 'Submission not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        $this->_requireSubmissionPermission($submission, true);

        // The notification must belong to the submission's form. Otherwise an arbitrary notification
        // (including one with attacker-chosen recipients) could be rendered against any submission.
        if (!$notification || (int)$notification->formId !== (int)$submission->formId) {
            $error = Craft::t('formie', 'Notification not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission);

        $message = Craft::t('formie', 'Email Notification was sent successfully.');

        $this->setSuccessFlash($message);

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionDownloadPdf(): Response
    {
        $this->requirePermission('formie-accessSubmissions');

        $submissionId = (int)$this->request->getRequiredParam('submissionId');
        $pdfTemplateId = $this->request->getParam('pdfTemplateId');
        $notificationId = $this->request->getParam('notificationId');

        $submission = Formie::$plugin->getSubmissions()->getSubmissionById($submissionId);

        if (!$submission) {
            throw new NotFoundHttpException(Craft::t('formie', 'Submission not found.'));
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$submission->canView($user)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $pdfTemplates = Formie::$plugin->getPdfTemplates();
        $pdfTemplate = $pdfTemplates->resolveSubmissionPdfTemplate(
            $submission,
            $pdfTemplateId ? (int)$pdfTemplateId : null,
            $notificationId ? (int)$notificationId : null,
        );

        if (!$pdfTemplate) {
            throw new BadRequestHttpException(Craft::t('formie', 'No PDF template configured for this submission.'));
        }

        $notification = $pdfTemplates->resolveSubmissionPdfNotification(
            $submission,
            $pdfTemplate,
            $notificationId ? (int)$notificationId : null,
        );

        $pdf = $pdfTemplates->renderSubmissionPdf($submission, $pdfTemplate, $notification);
        $filename = $pdfTemplates->resolveSubmissionPdfFilename($submission, $pdfTemplate, $notification);

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $filename, [
            'mimeType' => 'application/pdf',
        ]);
    }

    public function actionRunIntegration(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $integrationId = $this->request->getRequiredParam('integrationId');

        $submission = Submission::find()
            ->id($this->request->getParam('submissionId'))
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            $error = Craft::t('formie', 'Submission not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        $this->_requireSubmissionPermission($submission, true);

        $form = $submission->getForm();

        // We need to fetch all submissions for the form, which are prepped correctly
        $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);
        $resolvedIntegration = null;

        foreach ($integrations as $integration) {
            if ($integration->id != $integrationId) {
                continue;
            }

            $resolvedIntegration = $integration;

            // Allow integrations to add extra data before running
            $resolvedIntegration->populateContext($submission);
        }

        if (!$resolvedIntegration) {
            $error = Craft::t('formie', 'Integration not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        $response = Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($resolvedIntegration, $submission);

        if (!$response->isSuccessful()) {
            $message = Craft::t('formie', 'Integration result: {status}.', ['status' => $response->status->value]);

            $this->setFailFlash($message);

            return $this->asJson([
                'success' => false,
            ]);
        }

        $message = Craft::t('formie', 'Integration was run successfully.');

        $this->setSuccessFlash($message);

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionSaveSubmission(): ?Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        return $this->processSubmissionRequest(SubmissionOperation::REVISE, SubmissionAuthorityType::VISITOR);
    }

    public function actionSaveAdminSubmission(): ?Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireCpRequest();
        return $this->processSubmissionRequest(SubmissionOperation::REVISE, SubmissionAuthorityType::CONTROL_PANEL);
    }

    public function actionSubmit(): ?Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        return $this->processSubmissionRequest(SubmissionOperation::SUBMIT, SubmissionAuthorityType::VISITOR);
    }

    public function actionSetPage(): Response
    {
        if ($response = $this->handleCrossOriginRequest(['POST', 'OPTIONS'])) {
            return $response;
        }

        $this->requirePostRequest();

        $handle = $this->_parseTypedParam('handle', TypeHelper::TYPE_STRING, null, false);
        $pageId = $this->_parseTypedParam('pageId', TypeHelper::TYPE_ID, null, false);
        $renderId = $this->_parseTypedParam('renderId', TypeHelper::TYPE_STRING, null, false);
        $draftContextToken = $this->_parseTypedParam('draftContextToken', TypeHelper::TYPE_STRING, null, false);
        $draftContext = null;

        if ($draftContext === null) {
            $draftContext = $this->_parseTypedParam('draftContext', TypeHelper::TYPE_STRING, null, false);
        }

        if (!$handle || !$pageId) {
            throw new BadRequestHttpException('Missing required handle or pageId.');
        }

        /* @var Form $form */
        $form = Formie::$plugin->getForms()->getFormByHandle($handle);

        if (!$form) {
            throw new BadRequestHttpException('Form not found');
        }

        if ($draftContextToken) {
            $draftContext = $form->resolveDraftContextToken($draftContextToken);
        }

        if ($renderId) {
            $form->setRenderId($renderId);
        }

        if ($draftContext) {
            $form->setDraftContext($draftContext);
        }

        $result = Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => $handle,
            'targetPageId' => (string)$pageId,
            'values' => $this->request->getBodyParam('fields', []),
            'session' => [
                'tokens' => ['render' => $renderId, 'request' => $this->request->getBodyParam('requestToken')],
                'version' => $this->request->getBodyParam('expectedVersion'),
                'continuation' => ['draftContext' => $draftContext, 'progressId' => $this->request->getBodyParam('progressId')],
            ],
        ]), true);

        $redirectBase = SetPageReturnUrlHelper::resolveLegacySetPageRedirectUrl($this->request);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $result->success,
                'pageId' => $result->currentPageId,
                'errors' => SubmissionErrors::fromClient($result->errors, $form)->toValuePathMap(),
                'session' => $result->session?->toArrayRecursive(),
            ]);
        }

        if (!$result->success) {
            Formie::$plugin->getService()->setError($form->getFlashNamespace(), implode(' ', $result->errors['form'] ?: [Craft::t('formie', 'Please correct the form errors before continuing.')]));
        }
        return $this->redirect($redirectBase);
    }

    public function processSubmissionRequest(SubmissionOperation $operation, SubmissionAuthorityType $authorityType): ?Response
    {
        // Handle is required to get the form
        $handle = $this->_parseTypedParam('handle', TypeHelper::TYPE_STRING);

        if (!$handle) {
            throw new BadRequestHttpException('No form handle was provided.');
        }

        Formie::info("Submission triggered for {$handle}.");
        $siteId = SiteHelper::resolveSiteIdFromRequest(
            Craft::$app->getSites()->getCurrentSite()->id
        );
        $cpUserId = null;

        if ($authorityType === SubmissionAuthorityType::CONTROL_PANEL && ($userParam = $this->request->getBodyParam('user'))) {
            $cpUserId = isset($userParam[0]) ? (int)$userParam[0] : null;
        }

        try {
            $result = Formie::$plugin->getSubmissionRequests()->executeManaged(new ManagedSubmissionRequest([
                'handle' => $handle,
                'operation' => $operation,
                'expectedVersion' => $this->_parseTypedParam('expectedVersion', TypeHelper::TYPE_INT, null, false),
                'operationId' => $this->_parseTypedParam('operationId', TypeHelper::TYPE_STRING, null, false),
                'siteId' => $siteId,
                'renderId' => $this->_parseTypedParam('renderId', TypeHelper::TYPE_STRING, null, false),
                'requestToken' => $this->_parseTypedParam('requestToken', TypeHelper::TYPE_STRING),
                'draftContext' => $this->_parseTypedParam('draftContext', TypeHelper::TYPE_STRING, null, false),
                'draftContextToken' => $this->_parseTypedParam('draftContextToken', TypeHelper::TYPE_STRING, null, false),
                'resumeToken' => $this->_parseTypedParam('resumeToken', TypeHelper::TYPE_STRING, null, false),
                'submissionId' => $this->_parseTypedParam('submissionId', TypeHelper::TYPE_ID, null, false),
                'submissionUid' => $this->_parseTypedParam('submissionUid', TypeHelper::TYPE_STRING, null, false),
                'submissionEditToken' => $this->_parseTypedParam('submissionEditToken', TypeHelper::TYPE_STRING, null, false),
                'submitAction' => $this->_parseSubmissionAction(),
                'pageId' => $this->_parseTypedParam('pageId', TypeHelper::TYPE_ID),
                'targetPageId' => $this->_parseTypedParam('targetPageId', TypeHelper::TYPE_ID),
                'fieldParamNamespace' => $this->_namespace,
                'userId' => $cpUserId,
                'uploadPayloadVersion' => $this->_parseTypedParam('formieUploadPayloadVersion', TypeHelper::TYPE_INT, null, false),
            ]), $authorityType);
        } catch (SubmissionUnavailableException $exception) {
            return $this->_handleStaleSubmissionState($exception->form, $exception->source, $exception->value);
        }

        $submissionRequest = $result->command;
        $response = $result->response;
        $form = $response->form;
        $submission = $response->submission;

        if (!$response->success) {
            $responseSubmission = $response->submission ?? $submission;

            if ($this->request->getAcceptsJson()) {
                return $this->asJson($this->_createSubmitJsonResponsePayload(
                    $response,
                    $response->submitAction,
                    [],
                    $submissionRequest,
                ));
            }

            $formErrorMessages = $responseSubmission->getSubmissionErrors()->forValuePath('form');
            $flashError = $formErrorMessages
                ? implode('<br>', $formErrorMessages)
                : $form->settings->getErrorMessage();

            Formie::$plugin->getService()->setError($form->getFlashNamespace(), $flashError);

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $response->form,
                'submission' => $responseSubmission,
                'errors' => $responseSubmission->getSubmissionErrors()->toValuePathMap(),
            ]);

            return null;
        }

        $saveResumePayload = [];

        if ($response->submitAction === 'save') {
            $saveResumePayload = $this->_createSaveResumePayload($form, $submission);
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson($this->_createSubmitJsonResponsePayload(
                $response,
                $response->submitAction,
                $saveResumePayload,
                $submissionRequest,
            ));
        }

        if ($response->success) {
            $this->_stashPageReloadClientEvents($form, $submission, $submissionRequest, $response);
        }

        if ($response->submitAction === 'save') {
            $message = $form->settings->getSuccessMessage($submission);
            $resumeUrl = $saveResumePayload['resumeUrl'] ?? null;

            Formie::$plugin->getService()->setNotice($form->getFlashNamespace(), $message);

            if (is_string($resumeUrl) && $resumeUrl !== '') {
                return $this->redirect($resumeUrl);
            }

            Craft::$app->getUrlManager()->setRouteParams(['submission' => $submission]);

            return $this->refresh();
        }

        if ($response->nextPage) {
            // For page-reload multipage progression, render the next page in this response
            // using the updated in-memory form state, without query params or flash transport.
            $form->setCurrentPage($response->nextPage);

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'submission' => $submission,
                'pageId' => (int)$response->nextPage->id,
                'renderId' => $form->getRenderId(),
            ]);

            return null;
        }

        Formie::$plugin->getService()->setFlash($form->getFlashNamespace(), 'submitted', true);

        if ($submissionRequest->authority->type === SubmissionAuthorityType::CONTROL_PANEL) {
            return $this->_redirectToPostedCpSubmissionUrl($submission);
        }

        $completion = $response->outcome->data['completion'] ?? null;

        if (($completion['behavior'] ?? null) === 'message') {
            Formie::$plugin->getService()->setNotice($form->getFlashNamespace(), $completion['message']);
        }

        if (($completion['behavior'] ?? null) === 'redirect' && $completion['url']) {
            if ($completion['target'] === 'new-tab') {
                $this->response->format = Response::FORMAT_HTML;
                $this->response->data = Html::tag('p', Html::a(Craft::t('formie', 'Continue'), $completion['url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']));
                return $this->response;
            }
            return $this->redirect($completion['url']);
        }
        return $this->redirect($this->_currentUrlWithoutParams(self::STALE_SUBMISSION_STATE_QUERY_PARAMS));
    }

    public function setAllowTestOverrides(bool $allow): void
    {
        $this->_allowTestOverrides = $allow;
    }


    // Private Methods
    // =========================================================================

    /**
     * Sending notifications / re-running integrations distribute submission content, so view-only
     * access is not enough when `$requireSave` is true. Prefer Permissions helpers (group + form
     * scopes) over raw `User::can()` checks.
     */
    private function _requireSubmissionPermission(Submission $submission, bool $requireSave = false): void
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser || !$submission->canView($currentUser)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!$requireSave) {
            return;
        }

        // Note that `Submission::canSave()` intentionally allows site-request edits without Formie
        // save permissions — those checks live in the controller/processor. CP side-effects must
        // use the Permissions service directly.
        if (Formie::$plugin->getPermissions()->canSaveSubmissions($currentUser, $submission->getForm())) {
            return;
        }

        throw new ForbiddenHttpException('User is not permitted to perform this action');
    }

    private function _createSubmitJsonResponsePayload(
        SubmissionResponse $response,
        string $submitAction,
        array $payload = [],
        ?SubmissionCommand $submissionRequest = null,
    ): array {
        $form = $response->form;
        $submission = $response->submission;
        $nextPage = $response->nextPage;
        $pages = $form->getPages();
        $nextPageId = $nextPage?->id ?? null;

        $payload['success'] = $response->success;
        $payload['payment'] = $response->payment;
        $payload['submissionUid'] = $submission->uid;
        $payload['submitAction'] = $submitAction;
        $payload['outcome'] = $response->outcome?->type->value;
        $payload['version'] = $response->outcome?->version;
        $payload['events'] = [];
        $submitData = $form->getSubmitData();

        if ($submitData) {
            $payload['submitData'] = $submitData;
        }

        if (!$response->success) {
            $payload['errors'] = SubmissionErrors::fromSubmission($submission)->toValuePathMap();
            $this->_appendPaymentResponsePayload($payload, $response);

            return $payload;
        }

        if ($submitAction === 'save') {
            $payload['nextPageId'] = null;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = false;
        } else {
            $payload['nextPageId'] = $nextPageId;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = $nextPageId === null;
        }

        if ($submitAction === 'save') {
            $payload['successMessage'] = StringHelper::sanitizeMessageHtml($form->settings->getSuccessMessage($submission));
        }

        $payload['completion'] = $response->outcome->data['completion'] ?? null;
        $payload['redirect'] = $response->outcome->data['redirect'] ?? null;

        if ($completion = $payload['completion']) {
            $payload['redirectUrl'] = $completion['url'];
            $payload['redirectTarget'] = $completion['target'];
            $payload['successMessage'] = $completion['message'];
        }

        if (array_key_exists('successMessage', $payload)) {
            $payload['submitActionMessage'] = $payload['successMessage'];
        }

        if ($response->quizResult) {
            $payload['quizResult'] = $response->quizResult;
        }

        if ($submissionRequest) {
            $clientEvents = ClientEventsHelper::resolveForSubmittedPage(
                $form,
                $submission,
                self::_resolveSubmittedPageId($form, $submission, $submissionRequest, $response),
                $submitAction,
            );

            if ($clientEvents) {
                $payload['clientEvents'] = $clientEvents;
            }
        }

        return $payload;
    }

    private function _appendPaymentResponsePayload(array &$payload, SubmissionResponse $response): void
    {
        $payload['payment'] = $response->payment;

        if (isset($payload['payment']['message'])) {
            $payload['payment']['message'] = StringHelper::sanitizeMessageHtml($payload['payment']['message']);
        }
        $this->_appendLegacyPaymentSubmitResponse($payload);
    }

    private function _stashPageReloadClientEvents(
        Form $form,
        Submission $submission,
        SubmissionCommand $submissionRequest,
        SubmissionResponse $response,
    ): void {
        if ($form->settings->submitMethod !== 'page-reload') {
            return;
        }

        $clientEvents = ClientEventsHelper::resolveForSubmittedPage(
            $form,
            $submission,
            self::_resolveSubmittedPageId($form, $submission, $submissionRequest, $response),
            $response->submitAction,
        );

        if ($clientEvents) {
            Formie::$plugin->getService()->setFlash($form->getFlashNamespace(), 'clientEvents', $clientEvents);
        }
    }

    private function _handleStaleSubmissionState(Form $form, string $source, int|string $value): Response
    {
        $message = Craft::t('formie', 'Your previous submission session is no longer available. Please review the form and submit again.');

        Formie::warning('Recovered stale submission continuity state for form "{form}" ({source}: {value}).', [
            'form' => $form->handle,
            'source' => $source,
            'value' => (string)$value,
        ]);

        Formie::$plugin->getSubmissionProgress()->clearProgressState($form);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => false,
                'code' => self::STALE_SUBMISSION_STATE_CODE,
                'message' => $message,
                'errors' => [
                    'form' => [$message],
                ],
                'recoverable' => true,
                'resetState' => true,
            ]);
        }

        Formie::$plugin->getService()->setError($form->getFlashNamespace(), $message);

        return $this->redirect($this->_currentUrlWithoutParams(self::STALE_SUBMISSION_STATE_QUERY_PARAMS));
    }

    private function _markStatefulResponseNoCache(): void
    {
        $headers = Craft::$app->getResponse()->getHeaders();
        $headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $headers->set('Pragma', 'no-cache');
        $headers->set('Expires', '0');
    }

    private function _currentUrlWithoutParams(array $paramsToRemove): string
    {
        $queryParams = $this->request->getQueryParams();

        foreach ($paramsToRemove as $paramName) {
            unset($queryParams[$paramName]);
        }

        return UrlHelper::siteUrl(trim((string)$this->request->getPathInfo(), '/'), $queryParams);
    }

    private function _redirectToPostedCpSubmissionUrl(Submission $submission): Response
    {
        $redirect = $this->request->getValidatedBodyParam('redirect');
        $url = is_string($redirect) && $redirect !== ''
            ? References::parseUrl($redirect, $submission)
            : null;

        if ($url === null || $url === '') {
            $url = $submission->getCpEditUrl() ?? UrlHelper::cpUrl('formie/submissions');
        }

        return $this->redirect($this->_normalizeCpSubmissionRedirectUrl($url));
    }

    private function _normalizeCpSubmissionRedirectUrl(string $url): string
    {
        $parts = parse_url($url);

        if (!is_array($parts)) {
            return $url;
        }

        $path = ltrim((string)($parts['path'] ?? $url), '/');

        if (!str_starts_with($path, 'formie/submissions')) {
            return $url;
        }

        if (isset($parts['host'])) {
            $requestHost = parse_url($this->request->getHostInfo(), PHP_URL_HOST);

            if (!is_string($requestHost) || strcasecmp($parts['host'], $requestHost) !== 0) {
                return $url;
            }
        }

        $normalized = UrlHelper::cpUrl($path, $parts['query'] ?? null);

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $normalized .= '#' . $parts['fragment'];
        }

        return $normalized;
    }

    private function _createSaveResumePayload(Form $form, Submission $submission): array
    {
        $baseResumeUrl = Formie::$plugin->getSubmissionRequests()->resolveTrustedResumeBaseUrl(
            $this->request->getReferrer(),
            (string)$this->request->getPathInfo()
        );

        return Formie::$plugin->getSubmissionRequests()->createSaveResumePayload($form, $submission, $baseResumeUrl);
    }

    private function _prepEditSubmissionVariables(array &$variables): void
    {
        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this form');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }
            // $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];

            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Define the content tabs
        // ---------------------------------------------------------------------

        $variables['tabs'] = [];

        foreach ($variables['submission']->getPages() as $page) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['submission']->hasErrors()) {
                foreach ($page->getFields() as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $variables['submission']->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => $page->label,
                'url' => '#page-' . $page->id,
                'class' => $hasErrors ? 'error' : null,
            ];
        }
    }

    private function _parseTypedParam(string $name, string $type, mixed $default = null, bool $bodyParam = true): mixed
    {
        if ($bodyParam) {
            $value = $this->request->getBodyParam($name);
        } else {
            $value = $this->request->getParam($name);
        }

        try {
            return TypeHelper::parseTypedParam($value, $type, $default);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException('Request has invalid param ' . $name, 0, $e);
        }
    }

    private function _parseSubmissionAction(): string
    {
        $action = $this->_parseTypedParam('submitAction', TypeHelper::TYPE_STRING);

        return TypeHelper::getEnumParam($action, ['submit', 'save', 'back'], 'submit');
    }
}

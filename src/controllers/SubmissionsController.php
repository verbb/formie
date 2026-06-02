<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\StaleSubmissionStateException;
use verbb\formie\helpers\SetPageReturnUrlHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\TypeHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\models\PaymentDecision;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\services\SubmissionWorkflow;
use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use craft\db\Query;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SubmissionsController extends Controller
{
    // Constants
    // =========================================================================

    private const STALE_SUBMISSION_STATE_CODE = 'STALE_SUBMISSION_STATE';
    private const STALE_SUBMISSION_STATE_QUERY_PARAMS = ['pageId', 'resumeToken', 'submissionId'];
    

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


    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;


    // Public Methods
    // =========================================================================
    
    public function beforeAction($action): bool
    {
        $settings = Formie::$plugin->getSettings();

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

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $siteIds = Craft::$app->getSites()->getAllSiteIds();
        $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;
        $canCreateAnySubmissions = $currentUser->can('formie-createSubmissions');

        Formie::$plugin->registerCpSubmissionsAssets();

        $this->requirePermission('formie-accessSubmissions');

        // Avoid hydrating full Form elements for index bootstrap payload.
        $forms = (new Query())
            ->select([
                'f.id',
                'f.uid',
                'f.handle',
                'es.title',
            ])
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->innerJoin(['es' => Table::ELEMENTS_SITES], '[[es.elementId]] = [[f.id]] AND [[es.siteId]] = :siteId', [
                ':siteId' => $currentSiteId,
            ])
            ->where(['e.dateDeleted' => null])
            ->all();

        $editableForms = [];

        foreach ($forms as $form) {
            if (!$canCreateAnySubmissions && !$currentUser->can('formie-createSubmissions:' . ($form['uid'] ?? ''))) {
                continue;
            }

            $editableForms[] = [
                'id' => (int)($form['id'] ?? 0),
                'handle' => (string)($form['handle'] ?? ''),
                'name' => (string)($form['title'] ?? ''),
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

        $formConfigJson = $form->getClientConfig();

        // Add some settings just for submission editing
        $formConfigJson['settings']['outputJs'] = false;
        $variables['formConfigJson'] = $formConfigJson;

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
        $this->requireAcceptsJson();

        $view = $this->getView();

        $submission = Submission::find()
            ->id($this->request->getParam('id'))
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        $notifications = $submission->getForm()->getNotifications();

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
        $this->requireAcceptsJson();

        $notificationId = $this->request->getRequiredParam('notificationId');
        $notification = Formie::$plugin->getNotifications()->getNotificationById($notificationId);

        $submission = Submission::find()
            ->id($this->request->getParam('submissionId'))
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$notification) {
            $error = Craft::t('formie', 'Notification not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        if (!$submission) {
            $error = Craft::t('formie', 'Submission not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        Formie::$plugin->getSubmissions()->sendNotificationEmail($notification, $submission);

        $message = Craft::t('formie', 'Email Notification was sent successfully.');

        $this->setSuccessFlash($message);

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionRunIntegration(): Response
    {
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
            $resolvedIntegration->populateContext();
        }

        if (!$resolvedIntegration) {
            $error = Craft::t('formie', 'Integration not found.');

            $this->setFailFlash($error);

            return $this->asFailure($error);
        }

        $response = Formie::$plugin->getSubmissions()->sendIntegrationPayload($resolvedIntegration, $submission);

        if (($response instanceof IntegrationResponse) && !$response->success) {
            $message = Craft::t('formie', 'Integration failed to run.');

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

        return $this->processSubmissionRequest(SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING);
    }

    public function actionSubmit(): ?Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        return $this->processSubmissionRequest(SubmissionWorkflow::PROCESS_MODE_SUBMIT);
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

        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();
        $progressState = $submissionDrafts->getProgressState($form);
        $submissionId = $progressState?->submissionId ? (int)$progressState->submissionId : null;

        Formie::$plugin->getSubmissionWorkflow()->setPageNavigationState($form, $pageId, $submissionId);

        $redirectBase = SetPageReturnUrlHelper::resolveLegacySetPageRedirectUrl($this->request);
        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'pageId' => $pageId,
            ]);
        }

        return $this->redirect($redirectBase);
    }

    public function processSubmissionRequest(string $processMode): ?Response
    {
        // Handle is required to get the form
        $handle = $this->_parseTypedParam('handle', TypeHelper::TYPE_STRING);

        if (!$handle) {
            throw new BadRequestHttpException('No form handle was provided.');
        }

        Formie::info("Submission triggered for {$handle}.");
        $siteId = $this->_parseTypedParam('siteId', TypeHelper::TYPE_ID, Craft::$app->getSites()->getCurrentSite()->id);
        $cpUserId = null;

        if ($this->request->getIsCpRequest() && ($userParam = $this->request->getBodyParam('user'))) {
            $cpUserId = isset($userParam[0]) ? (int)$userParam[0] : null;
        }

        try {
            $result = Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
                'handle' => $handle,
                'processMode' => $processMode,
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
            ]));
        } catch (StaleSubmissionStateException $exception) {
            return $this->_handleStaleSubmissionState($exception->form, $exception->source, $exception->value);
        }

        $submissionRequest = $result->submissionRequest;
        $response = $result->response;
        $form = $response->form;
        $submission = $response->submission;

        if (!$response->success) {
            $responseSubmission = $response->submission ?? $submission;

            if ($this->request->getAcceptsJson()) {
                return $this->asJson($this->_createSubmitJsonResponsePayload($response, $submissionRequest->submitAction));
            }

            $formErrorMessages = $responseSubmission->getErrors('form');
            $flashError = $formErrorMessages
                ? implode('<br>', $formErrorMessages)
                : $form->settings->getErrorMessage();

            Formie::$plugin->getService()->setError($form->getFlashNamespace(), $flashError);

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $response->form,
                'submission' => $responseSubmission,
                'errors' => $responseSubmission->errors,
            ]);

            return null;
        }

        $saveResumePayload = [];
        if ($submissionRequest->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $saveResumePayload = $this->_createSaveResumePayload($form, $submission);
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson($this->_createSubmitJsonResponsePayload($response, $submissionRequest->submitAction, $saveResumePayload));
        }

        if ($submissionRequest->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $message = $form->settings->getSubmitActionMessage($submission);
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

        if ($submissionRequest->processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING && $this->request->getIsCpRequest()) {
            return $this->_redirectToPostedCpSubmissionUrl($submission);
        }

        if ($form->settings->submitAction === 'message' || $form->settings->submitAction === 'reload') {
            if ($form->settings->submitAction === 'message') {
                Formie::$plugin->getService()->setNotice($form->getFlashNamespace(), $form->settings->getSubmitActionMessage($submission));
            }

            Craft::$app->getUrlManager()->setRouteParams(['submission' => $submission]);

            return $this->redirect($this->_currentUrlWithoutParams(self::STALE_SUBMISSION_STATE_QUERY_PARAMS));
        }

        return $this->redirectToPostedUrl($submission, $form->getRedirectUrl());
    }

    public function setAllowTestOverrides(bool $allow): void
    {
        $this->_allowTestOverrides = $allow;
    }


    // Private Methods
    // =========================================================================

    private function _createSubmitJsonResponsePayload(SubmissionResponse $response, string $submitAction, array $payload = []): array
    {
        $form = $response->form;
        $submission = $response->submission;
        $nextPage = $response->nextPage;
        $pages = $form->getPages();
        $nextPageId = $nextPage?->id ?? null;

        $payload['success'] = $response->success;
        $payload['submissionUid'] = $submission->uid;
        $payload['submitAction'] = $submitAction;
        $payload['events'] = [];
        $submitData = $form->getSubmitData();

        if ($submitData) {
            $payload['submitData'] = $submitData;
        }

        if (!$response->success) {
            $payload['errors'] = $submission->getErrors();
            $payload['errors'] = StringHelper::sanitizeMessageHtmlRecursive($payload['errors']);
            $payload['keepSubmitLoading'] = in_array($response->paymentStatus, [
                PaymentDecision::STATUS_ACTION_REQUIRED,
                PaymentDecision::STATUS_PENDING,
            ], true);

            if ($response->paymentRedirectUrl) {
                $payload['redirectUrl'] = $response->paymentRedirectUrl;
            }

            return $payload;
        }

        if ($submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $payload['nextPageId'] = null;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = false;
        } else {
            $payload['nextPageId'] = $nextPageId;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = $nextPageId === null;
        }

        if ($submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $payload['submitActionMessage'] = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
        }

        if ($nextPageId === null) {
            if (in_array($form->settings->submitAction, ['entry', 'url'], true)) {
                $payload['redirectUrl'] = $form->getRedirectUrl();
                $payload['submitActionTab'] = $form->settings->submitActionTab;
            }

            if ($form->settings->submitAction === 'message') {
                $payload['submitActionMessage'] = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
            }
        }

        return $payload;
    }

    private function _handleStaleSubmissionState(Form $form, string $source, int|string $value): Response
    {
        $message = Craft::t('formie', 'Your previous submission session is no longer available. Please review the form and submit again.');

        Formie::warning('Recovered stale submission continuity state for form "{form}" ({source}: {value}).', [
            'form' => $form->handle,
            'source' => $source,
            'value' => (string)$value,
        ]);

        Formie::$plugin->getSubmissionDrafts()->clearProgressState($form);

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
        $url = $this->getPostedRedirectUrl($submission);

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
        $baseResumeUrl = Formie::$plugin->getSubmissionProcessor()->resolveTrustedResumeBaseUrl(
            $this->request->getReferrer(),
            (string)$this->request->getPathInfo()
        );

        return Formie::$plugin->getSubmissionProcessor()->createSaveResumePayload($form, $submission, $baseResumeUrl);
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
                'label' => Craft::t('formie', $page->label),
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
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException('Request has invalid param ' . $name, 0, $e);
        }
    }

    private function _parseSubmissionAction(): string
    {
        $action = $this->_parseTypedParam('submitAction', TypeHelper::TYPE_STRING);

        return TypeHelper::getEnumParam($action, SubmissionWorkflow::getAllowedSubmitActions(), SubmissionWorkflow::SUBMIT_ACTION_SUBMIT);
    }

}

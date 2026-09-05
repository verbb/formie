<?php
namespace verbb\formie\controllers\server;

use verbb\formie\Formie;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\controllers\AnonymousSiteRequestGuardTrait;
use verbb\formie\controllers\CrossOriginRequestTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\StaleSubmissionStateException;
use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SubmissionsController extends Controller
{
    // Constants
    // =========================================================================

    private const STALE_SUBMISSION_STATE_CODE = 'STALE_SUBMISSION_STATE';


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'submit' => self::ALLOW_ANONYMOUS_LIVE,
        'set-page' => self::ALLOW_ANONYMOUS_LIVE,
        'clear-submission' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;
    use AnonymousSiteRequestGuardTrait;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->forbidGuestControlPanelAnonymousActions($action->id);

        if (in_array($action->id, ['submit', 'set-page', 'clear-submission'], true)) {
            $settings = Formie::$plugin->getSettings();

            if (Craft::$app->getUser()->isGuest && !$settings->enableCsrfValidationForGuests) {
                $this->enableCsrfValidation = false;
            }
        }

        if ($this->request->getIsLivePreview() || $this->request->getIsPreview()) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionSubmit(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $siteId = SiteHelper::resolveSiteIdFromRequest(
            Craft::$app->getSites()->getCurrentSite()->id
        );

        try {
            $result = Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
                'handle' => $this->_stringParam('handle'),
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'siteId' => $siteId,
                'renderId' => $this->_nullableStringParam('renderId'),
                'requestToken' => $this->_stringParam('requestToken'),
                'draftContext' => $this->_nullableStringParam('draftContext'),
                'draftContextToken' => $this->_nullableStringParam('draftContextToken'),
                'resumeToken' => $this->_nullableStringParam('resumeToken') ?? $this->_nullableStringParam('continuationToken'),
                'submissionUid' => $this->_nullableStringParam('submissionUid'),
                'submitAction' => $this->_nullableStringParam('submitAction'),
                'pageId' => $this->_nullableIntParam('pageId'),
                'targetPageId' => $this->_nullableIntParam('targetPageId'),
            ]));
        } catch (StaleSubmissionStateException $exception) {
            return $this->_staleSubmissionStateResponse($exception->form, $exception->source, $exception->value);
        }

        $submissionRequest = $result->submissionRequest;
        $response = $result->response;
        $form = $response->form;
        $submission = $response->submission;
        $saveResumePayload = [];

        if ($response->success && $submissionRequest->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $saveResumePayload = $this->_createSaveResumePayload($form, $submission);
        }

        $this->response->setNoCacheHeaders();

        return $this->asJson($this->_createSubmitJsonResponsePayload(
            $response,
            $submissionRequest->submitAction,
            $saveResumePayload,
            $submissionRequest,
        ));
    }

    public function actionSetPage(): Response
    {
        if ($response = $this->handleCrossOriginRequest(['POST', 'OPTIONS'])) {
            return $response;
        }

        $this->requirePostRequest();

        $handle = $this->_stringParam('handle');
        $pageId = $this->_nullableIntParam('pageId');
        $siteId = SiteHelper::resolveSiteIdFromRequest(
            Craft::$app->getSites()->getCurrentSite()->id
        );

        if (!$pageId) {
            throw new BadRequestHttpException('Missing required pageId.');
        }

        Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => $handle,
            'siteId' => $siteId,
            'targetPageId' => (string)$pageId,
            'session' => [
                'tokens' => [
                    'render' => $this->_nullableStringParam('renderId'),
                ],
                'continuation' => array_filter([
                    'draftContext' => $this->_nullableStringParam('draftContext'),
                    'draftContextToken' => $this->_nullableStringParam('draftContextToken'),
                    'continuationToken' => $this->_nullableStringParam('continuationToken'),
                ], static function($value) {
                    return $value !== null && $value !== '';
                }),
            ],
        ]), true);
        $this->response->setNoCacheHeaders();

        return $this->asJson([
            'success' => true,
            'pageId' => $pageId,
        ]);
    }

    public function actionClearSubmission(): Response
    {
        if ($response = $this->handleCrossOriginRequest(['POST', 'OPTIONS'])) {
            return $response;
        }

        $this->requirePostRequest();

        $handle = $this->_stringParam('handle');
        $siteId = SiteHelper::resolveSiteIdFromRequest(
            Craft::$app->getSites()->getCurrentSite()->id
        );

        if ($handle === '') {
            throw new BadRequestHttpException('Missing required handle.');
        }

        $form = Formie::$plugin->getSubmissionProcessor()->requireFormByHandle($handle, $siteId);
        $draftContext = $this->_nullableStringParam('draftContextToken')
            ? $form->resolveDraftContextToken($this->_nullableStringParam('draftContextToken'))
            : $this->_nullableStringParam('draftContext');

        Formie::$plugin->getSubmissionProcessor()->applyFormRequestContext(
            $form,
            $this->_nullableStringParam('renderId'),
            $draftContext,
        );
        Formie::$plugin->getSubmissionDrafts()->clearProgressState($form);
        $this->response->setNoCacheHeaders();

        return $this->asJson([
            'success' => true,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _createSubmitJsonResponsePayload(
        SubmissionResponse $response,
        string $submitAction,
        array $payload = [],
        ?SubmissionRequest $submissionRequest = null,
    ): array {
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

            $this->_appendPaymentResponsePayload($payload, $response);

            return $payload;
        }

        if ($submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $payload['nextPageId'] = null;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = false;
            $payload['submitActionMessage'] = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
        } else {
            $payload['nextPageId'] = $nextPageId;
            $payload['totalPages'] = count($pages);
            $payload['isFinalPage'] = $nextPageId === null;
        }

        if ($nextPageId === null) {
            $effectiveSubmitAction = $form->settings->getEffectiveSubmitAction($submission);
            $payload['effectiveSubmitAction'] = $effectiveSubmitAction;

            if (in_array($effectiveSubmitAction, ['entry', 'url'], true)) {
                $payload['redirectUrl'] = $form->getRedirectUrl();
                $payload['submitActionTab'] = $form->settings->submitActionTab;
            }

            if ($effectiveSubmitAction === 'message') {
                $payload['submitActionMessage'] = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
            }
        }

        if ($response->quizResult) {
            $payload['quizResult'] = $response->quizResult;
        }

        if ($submissionRequest && $response->success) {
            $pageId = (int)($submissionRequest->pageId ?? 0);

            if ($pageId <= 0) {
                if ($response->nextPage) {
                    $previousPage = $form->getPreviousPage($response->nextPage, $submission);
                    $pageId = $previousPage?->id ? (int)$previousPage->id : 0;
                } else {
                    $pages = $form->getPages();
                    $pageId = $pages ? (int)$pages[0]->id : 0;
                }
            }

            $clientEvents = ClientEventsHelper::resolveForSubmittedPage(
                $form,
                $submission,
                $pageId ?: null,
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
        if ($response->paymentStatus) {
            $payload['paymentStatus'] = $response->paymentStatus;
        }

        if ($response->paymentMessage) {
            $payload['paymentMessage'] = StringHelper::sanitizeMessageHtml($response->paymentMessage);
        }

        if ($response->paymentAction) {
            $payload['paymentAction'] = $response->paymentAction;
        }

        if ($response->paymentDecision) {
            $payload['paymentDecision'] = $response->paymentDecision;
        }
    }

    private function _createSaveResumePayload(Form $form, Submission $submission): array
    {
        $baseResumeUrl = Formie::$plugin->getSubmissionProcessor()->resolveTrustedResumeBaseUrl(
            $this->request->getReferrer(),
            (string)$this->request->getPathInfo()
        );

        return Formie::$plugin->getSubmissionProcessor()->createSaveResumePayload($form, $submission, $baseResumeUrl);
    }

    private function _staleSubmissionStateResponse(Form $form, string $source, string $value): Response
    {
        $message = Craft::t('formie', 'Your previous submission session is no longer available. Please review the form and submit again.');

        Formie::warning('Recovered stale submission continuity state for form "{form}" ({source}: {value}).', [
            'form' => $form->handle,
            'source' => $source,
            'value' => $value,
        ]);

        Formie::$plugin->getSubmissionDrafts()->clearProgressState($form);
        $this->response->setNoCacheHeaders();

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

    private function _stringParam(string $name): string
    {
        return trim((string)$this->request->getParam($name, ''));
    }

    private function _nullableStringParam(string $name): ?string
    {
        $value = trim((string)$this->request->getParam($name, ''));

        return $value !== '' ? $value : null;
    }

    private function _nullableIntParam(string $name): ?int
    {
        $value = $this->request->getParam($name);

        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}

<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\Settings;
use verbb\formie\storage\SessionStorage;
use verbb\formie\web\assets\cp\CpAsset;

use Craft;
use craft\base\Element;
use craft\errors\SiteNotFoundException;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\models\Site;
use craft\web\Controller;

use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use DateTime;
use DateTimeZone;
use Throwable;

class SubmissionsController extends Controller
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_SUBMISSION_REQUEST = 'afterSubmissionRequest';
    public const EVENT_BEFORE_SUBMISSION_REQUEST_FORM = 'beforeSubmissionRequestForm';
    public const EVENT_BEFORE_SUBMISSION_REQUEST = 'beforeSubmissionRequest';


    // Protected Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'api' => self::ALLOW_ANONYMOUS_LIVE,
        'submit' => self::ALLOW_ANONYMOUS_LIVE,
        'set-page' => self::ALLOW_ANONYMOUS_LIVE,
        'save-submission' => self::ALLOW_ANONYMOUS_LIVE,
        'clear-submission' => self::ALLOW_ANONYMOUS_LIVE,
    ];


    // Properties
    // =========================================================================

    private string $_namespace = 'fields';


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $settings = Formie::$plugin->getSettings();

        // Guard against Control Panel context-confusion for anonymous-allowed front-end actions.
        // Craft can be tricked into classifying a request as a Control Panel request (e.g. via a
        // percent-encoded `/%61dmin/actions/...` path). Because CP requests skip the site-request
        // authorization/ownership checks and are redirected via Craft's *unsandboxed* object-template
        // renderer, a guest hitting the CP variant of these actions could turn a signed `redirect`/
        // `returnUrl` into arbitrary Twig execution. Guests must only ever use the site route.
        if (is_array($this->allowAnonymous) && array_key_exists($action->id, $this->allowAnonymous)) {
            if (Craft::$app->getUser()->getIsGuest() && !$this->request->getIsSiteRequest()) {
                throw new ForbiddenHttpException('Anonymous submissions are only permitted through the site request.');
            }
        }

        if ($action->id === 'submit' && Craft::$app->getUser()->isGuest && !$settings->enableCsrfValidationForGuests) {
            $this->enableCsrfValidation = false;
        }

        if ($action->id === 'api') {
            $this->enableCsrfValidation = false;
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

        $this->getView()->registerAssetBundle(CpAsset::class);

        $this->requirePermission('formie-accessSubmissions');

        return $this->renderTemplate('formie/submissions/index', [
            'defaultState' => $settings->submissionsBehaviour,
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

        $form = $this->_getForm($formHandle);

        if (!$form) {
            throw new HttpException(404);
        }

        $variables = [
            'formHandle' => $formHandle,
            'submissionId' => $submissionId,
            'submission' => $submission,
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

        $formConfigJson = $variables['submission']->getForm()->getFrontEndJsVariables();

        // Add some settings just for submission editing
        $formConfigJson['settings']['outputJsTheme'] = false;
        $variables['formConfigJson'] = $formConfigJson;

        return $this->renderTemplate('formie/submissions/_edit', $variables);
    }

    public function actionSaveSubmission(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $currentUser = Craft::$app->getUser()->getIdentity();

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string');
        $pageIndex = $this->_getTypedParam('pageIndex', 'int');
        $goToPageId = $this->_getTypedParam('goToPageId', 'id');
        $completeSubmission = $this->_getTypedParam('completeSubmission', 'boolean');
        $submitAction = $this->_getTypedParam('submitAction', 'string', 'submit');

        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        // Get the submission, or create a new one
        $submission = $this->_populateSubmission($form, null);

        if ($request->getIsSiteRequest() && !$submission->id) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if ($currentUser && !$submission->canSave($currentUser)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $pages = $form->getPages();
        $settings = $form->settings;
        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Now populate the rest of it from the post data
        $submission->enabled = true;
        $submission->enabledForSite = true;
        $submission->setScenario(Element::SCENARIO_LIVE);

        if ($request->getIsCpRequest()) {
            $submission->title = $request->getBodyParam('title') ?: $submission->title;
            $submission->statusId = $request->getBodyParam('statusId', $submission->statusId);
            $submission->isSpam = (bool)$request->getBodyParam('isSpam', $submission->isSpam);
        }

        if ($request->getBodyParam('markAsComplete')) {
            $submission->isIncomplete = false;
        }

        // Save the submission
        if ($request->getBodyParam('saveAction') === 'draft') {
            $submission->setScenario(Element::SCENARIO_ESSENTIALS);
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            // Ensure we set the current submission on the form. This keeps track of session info for
            // multipage forms, separate to "new" submissions
            $form->setSubmission($submission);

            // If we're going back, and want to  navigate without saving
            if ($submitAction === 'back' && !$formieSettings->enableBackSubmission) {
                $nextPage = $form->getPreviousPage(null, $submission, true);

                // Update the current page to reflect the next page
                $form->setCurrentPage($nextPage);

                if ($request->getAcceptsJson()) {
                    return $this->_returnJsonResponse(true, $submission, $form, $nextPage);
                }

                return $this->_redirectToReturnUrl();
            }

            // Set a specific page as the current page. This will override the session-based
            // current page, but is useful for headless setups, or template overrides.
            if (is_numeric($pageIndex)) {
                $currentPage = $pages[$pageIndex] ?? null;

                if ($currentPage) {
                    $form->setCurrentPage($currentPage);
                }
            }

            // Allow full submission payload to be provided for multipage forms.
            // Skip straight to the last page.
            if ($completeSubmission) {
                $currentPage = $pages[(is_countable($pages) ? count($pages) : 0) - 1] ?? null;

                if ($currentPage) {
                    $form->setCurrentPage($currentPage);
                }
            }

            // Determine the next page to navigate to
            if (is_numeric($goToPageId)) {
                $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
            } else if ($submitAction === 'back') {
                $nextPage = $form->getPreviousPage(null, $submission, true);
            } else if ($submitAction === 'save') {
                $nextPage = $form->getCurrentPage();
            } else {
                $nextPage = $form->getNextPage(null, $submission);
            }

            // Only validate when submitting
            if ($submitAction === 'submit') {
                // Turn on validation, but set a flag to only validate the current page.
                $submission->setScenario(Element::SCENARIO_LIVE);
                $submission->validateCurrentPageOnly = true;
            }

            // Check if we're on the last page of the form, or need to keep going
            if (empty($nextPage)) {
                $submission->isIncomplete = false;
                $submission->validateCurrentPageOnly = false;
            }
        }

        // Only validate for submitting.
        if ($submitAction === 'submit') {
            $submission->validate();
        }

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

            Formie::info('Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]);

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $errors,
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn’t save submission due to errors.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $submission->getForm(),
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        // Save the submission
        $success = Craft::$app->getElements()->saveElement($submission, false);

        if (!$success || $submission->getErrors()) {
            $errors = $submission->getErrors();

            Formie::info('Couldn’t save submission - {e}.', ['e' => Json::encode($errors)]);

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $errors,
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn’t save submission.'));

            // Send the submission back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $submission->getForm(),
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        // Check if we should trigger email notifications or integrations if this was spam
        if ($request->getIsCpRequest()) {
            if ($request->getBodyParam('sendNotifications')) {
                Formie::$plugin->getSubmissions()->sendNotifications($submission);
            }

            if ($request->getBodyParam('triggerIntegrations')) {
                Formie::$plugin->getSubmissions()->triggerIntegrations($submission);
            }
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            if (!empty($nextPage)) {
                // Update the current page to reflect the next page
                $form->setCurrentPage($nextPage);
            } else {
                // Reset pages, now we're on the last step
                $form->resetCurrentPage();
            }

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(true, $submission, $form, $nextPage);
            }

            if (!empty($nextPage)) {
                // Refresh, there's still more pages to complete
                return $this->_redirectToReturnUrl();
            }

            Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);

            if ($form->settings->submitAction == 'message' || $form->settings->submitAction == 'reload') {
                if ($form->settings->submitAction == 'message') {
                    Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));
                }

                // When reloading the page, provide a `submission` variable to pick up on the finalise submission
                Craft::$app->getUrlManager()->setRouteParams([
                    'submission' => $submission,
                ]);

                return $this->_redirectToReturnUrl();
            }

            return $this->_redirectToPostedUrl($form, $submission);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $submission->id,
                'title' => $submission->title,
                'status' => $submission->getStatusModel()->handle ?? '',
                'url' => $submission->getUrl(),
                'cpEditUrl' => $submission->getCpEditUrl(),
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Submission saved.'));

        return $this->_redirectToPostedUrl($form, $submission);
    }

    public function actionSubmit(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string');
        $pageIndex = $this->_getTypedParam('pageIndex', 'int');
        $goToPageId = $this->_getTypedParam('goToPageId', 'id');
        $completeSubmission = $this->_getTypedParam('completeSubmission', 'boolean');
        $submitAction = $this->_getTypedParam('submitAction', 'string', 'submit');
        $storage = $this->_getTypedParam('storage', 'string', 'session');

        // Fire a 'beforeSubmissionRequestForm' event, allowing requests to be filtered before querying for a form.
        $event = new SubmissionEvent([
            'handle' => $handle,
            'submitAction' => $submitAction,
        ]);
        $this->trigger(self::EVENT_BEFORE_SUBMISSION_REQUEST_FORM, $event);

        if (!$event->isValid) {
            if ($event->response) {
                return $event->response;
            }

            $this->response->setStatusCode(400);
            $this->response->format = Response::FORMAT_RAW;
            $this->response->data = '';

            return $this->response;
        }

        $handle = $event->handle ?? $handle;

        Formie::info("Submission triggered for {$handle}.");

        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        // Get the submission, or create a new one
        $submission = $this->_populateSubmission($form);
        $submission->isNewSubmission = true;

        $pages = $form->getPages();
        $settings = $form->settings;
        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Update the storage mechanism, which can't be set via sessions, and we've re-fetched the form
        $form->setStorageBehaviour($storage);

        // If using session storage, we need to ensure the session is started before using it. `Session::exists()` isn't enough.
        // There's no `Session::open()` function yet, so simply set a random value to kick off sessions.
        // See https://github.com/verbb/formie/issues/2194
        if ($form->getStorage() instanceof SessionStorage) {
            Session::set('formie:nonce', rand());
        }

        // If we're going back, and want to  navigate without saving
        if ($submitAction === 'back' && !$formieSettings->enableBackSubmission) {
            // Ensure that we don't set the next page to `null` which would mean form completion
            $nextPage = $form->getPreviousPage(null, $submission, true) ?? $form->getCurrentPage();

            // Allow `goToPageId` to override session behaviour.
            // TODO: remove this when we sort out proper session/db layer
            if (is_numeric($goToPageId)) {
                $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId) ?? $nextPage;
            }

            // Update the current page to reflect the next page
            $form->setCurrentPage($nextPage);

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(true, $submission, $form, $nextPage);
            }

            return $this->_redirectToReturnUrl();
        }

        // Set a specific page as the current page. This will override the session-based
        // current page, but is useful for headless setups, or template overrides.
        if (is_numeric($pageIndex)) {
            $currentPage = $pages[$pageIndex] ?? null;

            if ($currentPage) {
                $form->setCurrentPage($currentPage);
            }
        }

        // Allow full submission payload to be provided for multipage forms.
        // Skip straight to the last page.
        if ($completeSubmission) {
            $currentPage = $pages[(is_countable($pages) ? count($pages) : 0) - 1] ?? null;

            if ($currentPage) {
                $form->setCurrentPage($currentPage);
            }
        }

        // Determine the next page to navigate to. Be sure to fallback to the current page, as `nextPage = null`
        // signifies the end of the form.
        if (is_numeric($goToPageId)) {
            $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId) ?? $form->getCurrentPage();
        } else if ($submitAction === 'back') {
            $nextPage = $form->getPreviousPage(null, $submission, true) ?? $form->getCurrentPage();
        } else if ($submitAction === 'save') {
            $nextPage = $form->getCurrentPage();
        } else {
            $nextPage = $form->getNextPage(null, $submission);
        }

        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Only validate when submitting
        if ($submitAction === 'submit') {
            // Turn on validation, but set a flag to only validate the current page.
            $submission->setScenario(Element::SCENARIO_LIVE);
            $submission->validateCurrentPageOnly = true;
        }

        // Check if we're on the last page of the form, or need to keep going
        if (empty($nextPage)) {
            $submission->setStatus($defaultStatus);
            $submission->isIncomplete = false;
            $submission->validateCurrentPageOnly = false;
        } else {
            $submission->isIncomplete = true;
        }

        // Fire an 'beforeSubmissionRequest' event
        $event = new SubmissionEvent([
            'submission' => $submission,
            'form' => $form,
            'handle' => $handle,
            'submitAction' => $submitAction,
        ]);
        $this->trigger(self::EVENT_BEFORE_SUBMISSION_REQUEST, $event);

        // Allow the event to modify the submission and form
        $submission = $event->submission;
        $form = $event->form;

        // Only validate for submitting, and if the event has marked it as invalid. If the event adds errors to the submission
        // model, and `validate()` is run again, it'll clear any errors. Instead, skip straight to regular error handling.
        if ($submitAction === 'submit' && $event->isValid) {
            $submission->validate();
        }

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

            Formie::info('Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]);

            // If there are page field errors, set the current page to the page with the error for good UX.
            $nextPage = $this->_checkPageFieldErrors($submission, $form, $nextPage);

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(false, $submission, $form, $nextPage, [
                    'errors' => $errors,
                    'pageFieldErrors' => $form->getPageFieldErrors($submission),
                    'errorMessage' => $errorMessage,
                ]);
            }

            Formie::$plugin->getService()->setError($form->id, $errorMessage);

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        // Only process captchas if we're submitting
        if ($submitAction === 'submit') {
            // Check against all enabled captchas. Also take into account multi-pages
            $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

            foreach ($captchas as $captcha) {
                // Some captchas have already run their validation earlier with submission validation
                if ($captcha->hasStrictValidation()) {
                    continue;
                }

                $valid = $captcha->validateSubmission($submission);

                if (!$valid) {
                    $submission->isSpam = true;
                    $submission->spamReason = Craft::t('formie', 'Failed Captcha “{c}”: “{m}”', ['c' => $captcha::displayName(), 'm' => $captcha->spamReason]);
                    $submission->spamClass = get_class($captcha);
                }
            }

            // Final spam checks for things like keywords
            Formie::$plugin->getSubmissions()->spamChecks($submission);
        }

        // Check events right before our saving
        Formie::$plugin->getSubmissions()->onBeforeSubmission($submission, $submitAction);

        // Save the submission
        $success = Craft::$app->getElements()->saveElement($submission, false);

        // Set the custom title - only if set to save parsing, and after the submission is saved,
        // so we have access to not only field variables, but submission attributes
        if (trim($form->settings->submissionTitleFormat)) {
            $submission->updateTitle($form);
        }

        // Run this regardless of the success state, or incomplete state
        Formie::$plugin->getSubmissions()->onAfterSubmission($success, $submission, $submitAction);

        // Persist incomplete submissions in session so follow-up requests (payment confirmation /
        // multi-page resume) can pass `_authorizeExistingSubmission()`. Single-page and last-page
        // payment flows mark the submission incomplete again after creating a PaymentIntent, then
        // return via the error path below — previously only multi-page "next page" navigation
        // seeded the session, so payment resubmits on single-page forms got a 403.
        if ($submission->id && $submission->isIncomplete) {
            $form->setCurrentSubmission($submission);
        }

        // If this submission is marked as spam, there will be errors - so choose how we treat feedback
        if ($submission->isSpam) {
            // Check if we need to show an error based on spam - we want to stop right here
            if ($formieSettings->spamBehaviour === Settings::SPAM_BEHAVIOUR_MESSAGE) {
                $success = false;
                $errorMessage = $formieSettings->spamBehaviourMessage;
            }

            // If there are errors, but its marked as spam, and we want to simulate success, press on
            if ($formieSettings->spamBehaviour === Settings::SPAM_BEHAVIOUR_SUCCESS) {
                $success = true;
            }
        }

        if (!$success || $submission->getErrors()) {
            $errors = $submission->getErrors();

            Formie::info('Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]);

            // If there are page field errors, set the current page to the page with the error for good UX.
            $nextPage = $this->_checkPageFieldErrors($submission, $form, $nextPage);

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(false, $submission, $form, $nextPage, [
                    'errors' => $errors,
                    'pageFieldErrors' => $form->getPageFieldErrors($submission),
                    'errorMessage' => $errorMessage,
                ]);
            }

            Formie::$plugin->getService()->setError($form->id, $errorMessage);

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        if (!empty($nextPage)) {
            // Update the current page to reflect the next page
            $form->setCurrentPage($nextPage);
        }

        // We're all done with pages, delete any saved page state
        if (!$submission->isIncomplete) {
            // Delete the currently saved page
            $form->resetCurrentPage();

            // Delete the incomplete submission we've been using
            $form->resetCurrentSubmission();
        }

        // Fire an 'afterSubmissionRequest' event
        $event = new SubmissionEvent([
            'submission' => $submission,
            'form' => $form,
            'handle' => $handle,
            'submitAction' => $submitAction,
            'success' => true,
        ]);
        $this->trigger(self::EVENT_AFTER_SUBMISSION_REQUEST, $event);

        // Allow the event to modify the submission and form
        $submission = $event->submission;
        $form = $event->form;

        if ($request->getAcceptsJson()) {
            return $this->_returnJsonResponse(true, $submission, $form, $nextPage, [
                // Check if `EVENT_AFTER_SUBMISSION_REQUEST` has overridden the redirectUrl
                'redirectUrl' => $event->redirectUrl,
            ]);
        }

        if (!empty($nextPage)) {
            // Refresh, there's still more pages to complete. Or check if we should "redirect" to a template-defined
            // URL, which is set for every page (commonly the first one, once a submission is available)
            if ($settings->pageRedirectUrl) {
                $url = $form->renderRedirectUrl($submission, $settings->pageRedirectUrl);

                return $this->redirect($url);
            }

            return $this->_redirectToReturnUrl();
        }

        Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);

        if ($form->settings->submitAction == 'message' || $form->settings->submitAction == 'reload') {
            if ($form->settings->submitAction == 'message') {
                Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));
            }

            // When reloading the page, provide a `submission` variable to pick up on the finalise submission
            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return $this->_redirectToReturnUrl();
        }

        // Check if `EVENT_AFTER_SUBMISSION_REQUEST` has overridden the redirectUrl
        if ($event->redirectUrl) {
            return $this->redirect($event->redirectUrl);
        }

        // Get the URL for redirection (ignore last page checks, already done)
        $url = $this->getPostedRedirectUrl($submission);

        if ($url === null) {
            $url = $form->renderRedirectUrl($submission, $form->getRedirectUrl(false));
        } else {
            $url = UrlHelper::appendRequestQueryString($url);
        }

        return $this->redirect($url);
    }

    public function actionSetPage(): Response
    {
        $request = $this->request;

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string', null, false);
        $pageId = $this->_getTypedParam('pageId', 'id', null, false);
        $submissionId = $this->_getTypedParam('submissionId', 'id', null, false);

        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle `$handle`.");
        }

        // Check if we're editing a submission
        if ($submissionId) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(null)
                ->isSpam(null)
                ->one();

            if ($submission) {
                $form->setSubmission($submission);
            }
        }

        $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $pageId);

        $form->setCurrentPage($nextPage);

        return $this->redirect(UrlHelper::getSafeReferrerUrl());
    }

    public function actionClearSubmission(): Response
    {
        $request = $this->request;

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string', null, false);
        $redirect = $this->_getTypedParam('redirect', 'string', null, false);

        // Ensure the redirect passed is validated, otherwise fallback to a safe referrer
        $redirect = Craft::$app->getSecurity()->validateData($redirect) ?: UrlHelper::getSafeReferrerUrl();

        /* @var Form $form */
        $form = $this->_getForm($handle);

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle `$handle`.");
        }

        // Delete the currently saved page
        $form->resetCurrentPage();

        // Delete the incomplete submission we've been using
        $form->resetCurrentSubmission();

        return $this->redirect($redirect);
    }

    public function actionDeleteSubmission(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $currentUser = Craft::$app->getUser()->getIdentity();
        $submissionId = $request->getRequiredBodyParam('submissionId');

        $submission = Submission::find()
            ->id($submissionId)
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        if (!$currentUser || !$submission->canDelete($currentUser)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!Craft::$app->getElements()->deleteElement($submission)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Submission deleted.'));

        return $this->redirectToPostedUrl($submission);
    }

    public function actionGetSendNotificationModalContent(): Response
    {
        $this->requireAcceptsJson();

        $request = $this->request;
        $view = $this->getView();

        $submission = Submission::find()
            ->id($request->getParam('id'))
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

        $request = $this->request;

        $notificationId = $request->getRequiredParam('notificationId');
        $notification = Formie::$plugin->getNotifications()->getNotificationById($notificationId);

        $submission = Submission::find()
            ->id($request->getParam('submissionId'))
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

        $request = $this->request;
        $integrationId = $request->getRequiredParam('integrationId');

        $submission = Submission::find()
            ->id($request->getParam('submissionId'))
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

    public function actionApi(): Response
    {
        // Add CORS headers
        $headers = $this->response->getHeaders();
        $headers->setDefault('Access-Control-Allow-Credentials', 'true');
        $headers->setDefault('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Craft-Token, Cache-Control, X-Requested-With');

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (is_array($generalConfig->allowedGraphqlOrigins)) {
            if (($origins = $this->request->getOrigin()) !== null) {
                $origins = ArrayHelper::filterEmptyStringsFromArray(array_map('trim', explode(',', $origins)));

                foreach ($origins as $origin) {
                    if (in_array($origin, $generalConfig->allowedGraphqlOrigins)) {
                        $headers->setDefault('Access-Control-Allow-Origin', $origin);
                        break;
                    }
                }
            }
        } else if ($generalConfig->allowedGraphqlOrigins !== false) {
            $headers->setDefault('Access-Control-Allow-Origin', '*');
        }

        if ($this->request->getIsPost()) {
            return Craft::$app->runAction($this->request->getParam('action'));
        }

        // This is just a preflight request, no need to run the actual query yet
        if ($this->request->getIsOptions()) {
            $this->response->format = Response::FORMAT_RAW;
            $this->response->data = '';
            return $this->response;
        }

        return $this->response;
    }


    // Private Methods
    // =========================================================================

    private function _returnJsonResponse(bool $success, Submission $submission, Form $form, ?FieldLayoutPage $nextPage, array $extras = []): Response
    {
        // Try and get the redirect from the template, as it might've been altered in templates
        $redirect = $this->request->getValidatedBodyParam('redirect');

        $redirectUrl = $redirect
            ? $form->renderRedirectUrl($submission, $redirect)
            : $form->renderRedirectUrl($submission);

        // Set the `redirectUrl` unless we've passed in an override
        $extras['redirectUrl'] = $extras['redirectUrl'] ?? $redirectUrl;

        $params = array_merge([
            'success' => $success,
            'submissionId' => $submission->id,
            'submissionUid' => $submission->uid,
            'nextPageId' => $nextPage->id ?? null,
            'nextPageIndex' => $form->getPageIndex($nextPage) ?? null,
            'isFinalPage' => $nextPage ? false : true,
            'totalPages' => is_countable($form->getPages()) ? count($form->getPages()) : 0,
            'submitActionMessage' => $form->settings->getSubmitActionMessage($submission),
            'submitData' => $form->getSubmitData(),
        ], $extras);

        return $this->asJson($params);
    }

    private function _prepEditSubmissionVariables(array &$variables): void
    {
        $request = $this->request;

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

    private function _populateSubmission(Form $form, ?bool $isIncomplete = true): Submission
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

            if ($submission->formId && (int)$submission->formId !== (int)$form->id) {
                throw new BadRequestHttpException("No submission exists with the ID \"$submissionId\"");
            }

            if ($request->getIsSiteRequest()) {
                $this->_authorizeExistingSubmission($form, $submission, $editingSubmission);
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

    private function _checkPageFieldErrors(submission $submission, Form $form, ?FieldLayoutPage $nextPage): ?FieldLayoutPage
    {
        // Find the first page with a field error and set that as the current page
        if ($pageFieldErrors = $form->getPageFieldErrors($submission)) {
            $firstErrorPageId = array_keys($pageFieldErrors)[0];

            if ($firstErrorPageId) {
                $errorPage = ArrayHelper::firstWhere($form->getPages(), 'id', $firstErrorPageId);

                $form->setCurrentPage($errorPage);

                // We must return the next page to navigate to. In this case, it'll be the current page
                // as we've already set that to be the page with the first field error
                return $form->getCurrentPage();
            }
        }

        return $nextPage;
    }

    private function _setTitle(Submission $submission, Form $form): void
    {
        $title = Variables::getParsedValue($form->settings->submissionTitleFormat, $submission, $form);

        // In case any values are encoded for HTML, we should decode them here. This is after sanitization
        $submission->title = html_entity_decode($title);

        // // Set the default title for the submission, so it can save correctly
        if (!$submission->title) {
            $now = new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));
            $submission->title = $now->format('D, d M Y H:i:s');
        }
    }

    private function _validateSubmissionEditToken(Form $form, Submission $submission): bool
    {
        $token = $this->_getTypedParam('submissionEditToken', 'string');

        if (!$token) {
            return false;
        }

        $payload = Craft::$app->getSecurity()->validateData($token);

        if (!is_string($payload)) {
            return false;
        }

        try {
            $data = Json::decode($payload);
        } catch (Throwable) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        return ($data['purpose'] ?? null) === 'formie-edit-submission' &&
            (int)($data['formId'] ?? 0) === (int)$form->id &&
            hash_equals((string)$form->uid, (string)($data['formUid'] ?? '')) &&
            (int)($data['submissionId'] ?? 0) === (int)$submission->id &&
            hash_equals((string)$submission->uid, (string)($data['submissionUid'] ?? ''));
    }

    private function _authorizeExistingSubmission(Form $form, Submission $submission, ?bool $editingSubmission): void
    {
        if ($editingSubmission) {
            if (!$this->_validateSubmissionEditToken($form, $submission)) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }

            return;
        }

        $sessionSubmission = $form->getCurrentSubmission();

        if (!$sessionSubmission || (int)$sessionSubmission->id !== (int)$submission->id) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    private function _redirectToReturnUrl(): Response
    {
        return $this->redirect(UrlHelper::getSubmissionReturnUrl(
            $this->_getTypedParam('siteId', 'id'),
        ));
    }

    private function _redirectToPostedUrl(Form $form, Submission $submission): Response
    {
        // Never use Craft's `redirectToPostedUrl()` here, as it renders the posted `redirect` value with
        // the *unsandboxed* Twig view. The `redirect` value is HMAC-signed, but Formie signs
        // attacker-influenced URLs (e.g. `returnUrl` derived from the request URL), so it must always be
        // rendered through Formie's restricted sandbox to prevent object-template injection.
        $redirect = $this->request->getValidatedBodyParam('redirect');

        if ($redirect) {
            return $this->redirect($form->renderRedirectUrl($submission, $redirect));
        }

        return $this->_redirectToReturnUrl();
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
}

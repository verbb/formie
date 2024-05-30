<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Settings;
use verbb\formie\web\assets\cp\CpAsset;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\models\Site;
use craft\web\Controller;

use yii\base\Exception;
use yii\base\ExitException;
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

    const EVENT_BEFORE_SUBMISSION_REQUEST = 'beforeSubmissionRequest';
    const EVENT_AFTER_SUBMISSION_REQUEST = 'afterSubmissionRequest';


    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [
        'api' => self::ALLOW_ANONYMOUS_LIVE,
        'submit' => self::ALLOW_ANONYMOUS_LIVE,
        'set-page' => self::ALLOW_ANONYMOUS_LIVE,
        'clear-submission' => self::ALLOW_ANONYMOUS_LIVE,
    ];


    // Private Properties
    // =========================================================================

    private $_namespace = 'fields';


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $settings = Formie::$plugin->getSettings();

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

    /**
     * Shows all the submissions in a list.
     *
     * @return Response|null
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $this->getView()->registerAssetBundle(CpAsset::class);

        $this->requirePermission('formie-viewSubmissions');

        return $this->renderTemplate('formie/submissions/index', []);
    }

    /**
     * Edits a submission.
     *
     * @param int|null $submissionId
     * @param string|null $siteHandle
     * @param Submission|null $submission
     * @return Response|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws HttpException
     */
    public function actionEditSubmission(string $formHandle, int $submissionId = null, ?Submission $submission = null, ?string $site = null): Response
    {
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

        $form = Form::find()->handle($formHandle)->one();

        if (!$form) {
            throw new HttpException(404);
        }

        // User must have at least one of these permissions to edit (all, or the specific form)
        $submissionsPermission = Craft::$app->getUser()->checkPermission('formie-editSubmissions');
        $submissionPermission = Craft::$app->getUser()->checkPermission('formie-manageSubmission:' . $form->uid);

        if (!$submissionsPermission && !$submissionPermission) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
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

                // Set the title to the default
                $this->_setTitle($variables['submission'], $form);

                // Set the user to the default
                if ($form->settings->collectUser) {
                    $variables['submission']->setUser(Craft::$app->getUser()->getIdentity());
                }
            }
        }

        if (!$variables['submission']) {
            throw new HttpException(404);
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

    /**
     * Saves a submission.
     *
     * @return Response|null
     * @throws Throwable
     */
    public function actionSaveSubmission()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        // Ensure we validate some params here to prevent potential malicious-ness
        $formHandle = $this->_getTypedParam('handle', 'string');

        $form = Form::find()->handle($formHandle)->one();

        // Get the submission, or create a new one
        $submission = $this->_populateSubmission($form, null);
        $form = $submission->form;

        // Check against permissions to save at all, or per-form
        if (!Craft::$app->getUser()->checkPermission('formie-editSubmissions')) {
            if (!Craft::$app->getUser()->checkPermission('formie-manageSubmission:' . $form->uid)) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        // Now populate the rest of it from the post data
        $submission->enabled = true;
        $submission->enabledForSite = true;
        $submission->title = $request->getParam('title', $submission->title);
        $submission->statusId = $request->getParam('statusId', $submission->statusId);
        $submission->isSpam = (bool)$request->getParam('isSpam', $submission->isSpam);

        // Save the submission
        if ($submission->enabled && $submission->enabledForSite) {
            $submission->setScenario(Element::SCENARIO_LIVE);
        }

        if ($request->getParam('saveAction') === 'draft') {
            $submission->setScenario(Element::SCENARIO_ESSENTIALS);
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            $goingBack = (bool)$request->getParam('goingBack');

            // Ensure we set the current submission on the form. This keeps track of session info for
            // multi-page forms, separate to "new" submissions
            $form->setSubmission($submission);

            // Check for the next page - if there is one
            $nextPage = $form->getNextPage(null, $submission);

            // Or, if we've passed in a specific page to go to
            if ($goToPageId = $request->getParam('goToPageId')) {
                $goingBack = true;
                $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
            } else if ($goingBack) {
                $nextPage = $form->getPreviousPage(null, $submission);
            }

            // Don't validate when going back
            if (!$goingBack) {
                // Turn on validation, but set a flag to only validate the current page.
                $submission->validateCurrentPageOnly = true;
            }

            // Check if we're on the last page of the form, or need to keep going
            if (empty($nextPage)) {
                $submission->validateCurrentPageOnly = false;

                // Always ensure the submission is completed at the end
                $submission->isIncomplete = false;
            }
        }

        $submission->validate();

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

            Formie::error(Craft::t('app', 'Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]));

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $errors,
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save submission due to errors.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $submission->getForm(),
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $errors = $submission->getErrors();

            Formie::error(Craft::t('app', 'Couldn’t save submission - {e}.', ['e' => Json::encode($errors)]));

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $errors,
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save submission.'));

            // Send the submission back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $submission->getForm(),
                'submission' => $submission,
                'errors' => $errors,
            ]);

            return null;
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            if (!empty($nextPage)) {
                // Update the current page to reflect the next page
                $form->setCurrentPage($nextPage);

                // Set the active submission so we can keep going
                $form->setCurrentSubmission($submission);
            } else {
                // Reset pages, now we're on the last step
                $form->resetCurrentPage();
            }

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(true, $submission, $form, $nextPage);
            }

            if (!empty($nextPage)) {
                // Refresh, there's still more pages to complete
                return $this->refresh();
            }

            Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);

            if ($form->settings->submitAction == 'message') {
                Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));

                return $this->refresh();
            }

            return $this->redirectToPostedUrl($submission);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $submission->id,
                'title' => $submission->title,
                'status' => $submission->getStatus(true)->handle ?? '',
                'url' => $submission->getUrl(),
                'cpEditUrl' => $submission->getCpEditUrl(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Submission saved.'));

        return $this->redirectToPostedUrl($submission);
    }

    /**
     * Submits and saves a form submission.
     *
     * @return Response|null
     * @throws Throwable
     */
    public function actionSubmit()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $goingBack = false;

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string');
        $goingBack = $this->_getTypedParam('goingBack', 'boolean');
        $pageIndex = $this->_getTypedParam('pageIndex', 'int');
        $goToPageId = $this->_getTypedParam('goToPageId', 'id');
        $completeSubmission = $this->_getTypedParam('completeSubmission', 'boolean');

        Formie::log("Submission triggered for ${handle}.");

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        $pages = $form->getPages();
        $settings = $form->settings;
        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Set a specifc page as the current page. This will override the session-based
        // current page, but is useful for headless setups, or template overrides.
        // TODO: make this the default behaviour at the next breakpoint, to not rely
        // on session-based saving for the current page.
        if (is_numeric($pageIndex)) {
            $currentPage = $pages[$pageIndex] ?? null;

            if ($currentPage) {
                $form->setCurrentPage($currentPage);
            }
        }

        // Allow full submission payload to be provided for multi-page forms.
        // Skip straight to the last page.
        if ($completeSubmission) {
            $currentPage = $pages[count($pages) - 1] ?? null;

            if ($currentPage) {
                $form->setCurrentPage($currentPage);
            }
        }

        // Get the submission, or create a new one
        $submission = $this->_populateSubmission($form);

        // Check for the next page - if there is one
        $nextPage = $form->getNextPage(null, $submission);

        // Or, if we've passed in a specific page to go to
        if (is_numeric($goToPageId)) {
            $goingBack = true;
            $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
        } else if ($goingBack) {
            $nextPage = $form->getPreviousPage(null, $submission);
        }

        // Ensure that we don't set the next page to `null` which would mean form completion
        if ($goingBack && !$nextPage) {
            $nextPage = $form->getCurrentPage(null, $submission);
        }

        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Don't validate when going back
        if (!$goingBack) {
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
        ]);
        $this->trigger(self::EVENT_BEFORE_SUBMISSION_REQUEST, $event);

        // Allow the event to modify the submission
        $submission = $event->submission;

        // Don't validate when going back, and if the event has marked it as invalid. If the event adds errors to the submission
        // model, and `validate()` is run again, it'll clear any errors. Instead, skip straight to regular error handling.
        if (!$goingBack && $event->isValid) {
            $submission->validate();
        }

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

            Formie::error(Craft::t('app', 'Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]));

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

        // Check against all enabled captchas. Also take into account multi-pages
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

        foreach ($captchas as $captcha) {
            // If we're heading back to a previous page, don't validate
            if ($goingBack) {
                continue;
            }

            $valid = $captcha->validateSubmission($submission);

            if (!$valid) {
                $submission->isSpam = true;
                $submission->spamReason = Craft::t('formie', 'Failed Captcha “{c}”: “{m}”', ['c' => $captcha::displayName(), 'm' => $captcha->spamReason]);
            }
        }

        // Final spam checks for things like keywords
        Formie::$plugin->getSubmissions()->spamChecks($submission);

        // Check events right before our saving
        Formie::$plugin->getSubmissions()->onBeforeSubmission($submission);

        // Save the submission
        $success = Craft::$app->getElements()->saveElement($submission, false);

        // Set the custom title - only if set to save parsing, and after the submission is saved
        // so we have access to not only field variables, but submission attributes
        if (trim($form->settings->submissionTitleFormat)) {
            $submission->updateTitle($form);
        }

        // Run this regardless of the success state, or incomplete state
        Formie::$plugin->getSubmissions()->onAfterSubmission($success, $submission);

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

        if (!$success) {
            $errors = $submission->getErrors();

            Formie::error(Craft::t('app', 'Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]));

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

            // Set the active submission so we can keep going
            $form->setCurrentSubmission($submission);
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
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_SUBMISSION_REQUEST, $event);

        if ($request->getAcceptsJson()) {
            return $this->_returnJsonResponse($success, $submission, $form, $nextPage);
        }

        if (!empty($nextPage)) {
            // Refresh, there's still more pages to complete
            return $this->refresh();
        }

        Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);

        if ($form->settings->submitAction == 'message') {
            Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));

            return $this->refresh();
        }

        // If this is being forced-completed, handle the redirect URL now. This isn't included
        // in the request, to ensure users don't inspect the form for non last-page multi-page forms.
        if ($completeSubmission) {
            // Bypass the last-page check
            $url = $form->getRedirectUrl(false);

            return $this->redirectToPostedUrl($submission, $url);
        }

        return $this->redirectToPostedUrl($submission);
    }

    /**
     * @inheritDoc
     */
    public function actionSetPage()
    {
        $request = Craft::$app->getRequest();

        // Ensure we validate some params here to prevent potential malicious-ness
        $handle = $this->_getTypedParam('handle', 'string');
        $pageId = $this->_getTypedParam('pageId', 'id');
        $submissionId = $this->_getTypedParam('submissionId', 'id');

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
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

        return $this->redirect($request->referrer);
    }

    /**
     * @inheritDoc
     */
    public function actionClearSubmission()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $handle = $request->getRequiredBodyParam('handle');

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        // Delete the currently saved page
        $form->resetCurrentPage();

        // Delete the incomplete submission we've been using
        $form->resetCurrentSubmission();

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @inheritDoc
     */
    public function actionDeleteSubmission()
    {
        $this->requirePostRequest();

        $this->requirePermission('formie-editSubmissions');

        $request = Craft::$app->getRequest();
        $submissionId = $request->getRequiredBodyParam('submissionId');

        $submission = Submission::find()
            ->id($submissionId)
            ->isIncomplete(null)
            ->isSpam(null)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        if (!Craft::$app->getElements()->deleteElement($submission)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Submission deleted.'));

        return $this->redirectToPostedUrl($submission);
    }
    
    /**
     * @inheritDoc
     */
    public function actionLegacyEdit(int $submissionId = null, string $siteHandle = null, Submission $submission = null): Response
    {
        if (!$submission) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(null)
                ->isSpam(null)
                ->one();
        }

        if (!$submission) {
            throw new HttpException(404);
        }

        $formHandle = $submission->getForm()->handle;

        $variables = [
            'formHandle' => $formHandle,
            'submissionId' => $submission->id,
        ];

        return $this->runAction('edit-submission', $variables);
    }
    
    /**
     * @inheritDoc
     */
    public function actionGetSendNotificationModalContent()
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $view = $this->getView();

        $submission = Submission::find()
            ->id($request->getParam('id'))
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
    
    /**
     * @inheritDoc
     */
    public function actionSendNotification()
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();

        $notificationId = $request->getRequiredParam('notificationId');
        $notification = Formie::$plugin->getNotifications()->getNotificationById($notificationId);

        $submission = Submission::find()
            ->id($request->getParam('submissionId'))
            ->one();

        if (!$notification) {
            $error = Craft::t('formie', 'Notification not found.');

            Craft::$app->getSession()->setError($error);

            return $this->asErrorJson($error);
        }

        if (!$submission) {
            $error = Craft::t('formie', 'Submission not found.');

            Craft::$app->getSession()->setError($error);

            return $this->asErrorJson($error);
        }

        Formie::$plugin->getSubmissions()->sendNotificationEmail($notification, $submission);

        $message = Craft::t('formie', 'Email Notification was sent successfully.');
        
        Craft::$app->getSession()->setNotice($message);

        return $this->asJson([
            'success' => true,
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function actionRunIntegration()
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $integrationId = $request->getRequiredParam('integrationId');

        $submission = Submission::find()
            ->id($request->getParam('submissionId'))
            ->one();

        if (!$submission) {
            $error = Craft::t('formie', 'Submission not found.');

            Craft::$app->getSession()->setError($error);

            return $this->asErrorJson($error);
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

            // Add additional useful info for the integration
            // TODO: refactor this to allow integrations access to control this
            $resolvedIntegration->referrer = Craft::$app->getRequest()->getReferrer();
            $resolvedIntegration->ipAddress = Craft::$app->getRequest()->getUserIP();
        }

        if (!$resolvedIntegration) {
            $error = Craft::t('formie', 'Integration not found.');

            Craft::$app->getSession()->setError($error);

            return $this->asErrorJson($error);
        }

        Formie::$plugin->getSubmissions()->sendIntegrationPayload($resolvedIntegration, $submission);

        $message = Craft::t('formie', 'Integration was run successfully.');
        
        Craft::$app->getSession()->setNotice($message);

        return $this->asJson([
            'success' => true,
        ]);
    }

    /**
     * Provides CORS support for when making a form submission.
     *
     * @return Response
     */
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
            return Craft::$app->runAction(Craft::$app->getRequest()->getParam('action'));
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
    
    /**
     * @inheritDoc
     */
    private function _returnJsonResponse($success, $submission, $form, $nextPage, $extras = [])
    {
        // Try and get the redirect from the template, as it might've been altered in templates
        $redirect = Craft::$app->getRequest()->getValidatedBodyParam('redirect');

        // Otherwise, use the form defined
        if (!$redirect) {
            $redirect = $form->getRedirectUrl();
        }

        $redirectUrl = Craft::$app->getView()->renderObjectTemplate($redirect, $submission);

        $params = array_merge([
            'success' => $success,
            'submissionId' => $submission->id,
            'currentPageId' => $form->getCurrentPage()->id,
            'nextPageId' => $nextPage->id ?? null,
            'nextPageIndex' => $form->getPageIndex($nextPage) ?? 0,
            'totalPages' => count($form->getPages()),
            'redirectUrl' => $redirectUrl,
            'submitActionMessage' => $form->settings->getSubmitActionMessage($submission),
        ], $extras);

        return $this->asJson($params);
    }

    /**
     * @inheritDoc
     */
    private function _prepEditSubmissionVariables(array &$variables)
    {
        $request = Craft::$app->getRequest();

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

        foreach ($variables['submission']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['submission']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var FormField $field */
                    if ($hasErrors = $variables['submission']->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][$tab->getHtmlId()] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    private function _populateSubmission($form, $isIncomplete = true)
    {
        $request = Craft::$app->getRequest();

        // Ensure we validate some params here to prevent potential malicious-ness
        $submissionId = $this->_getTypedParam('submissionId', 'id');
        $siteId = $this->_getTypedParam('siteId', 'id');
        $userParam = $request->getParam('user');
        
        if ($submissionId) {
            // Allow fetching spammed submissions for multi-step forms, where its been flagged as spam
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

        Craft::$app->getContent()->populateElementContent($submission);
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
            if ($request->getIsCpRequest() && $user = $userParam) {
                $submission->userId = $user[0] ?? null;
            }
        }

        $this->_setTitle($submission, $form);

        return $submission;
    }

    /**
     * @inheritDoc
     */
    private function _checkPageFieldErrors($submission, $form, $nextPage)
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

    /**
     * @inheritDoc
     */
    private function _setTitle($submission, $form)
    {
        $submission->title = Variables::getParsedValue(
            $form->settings->submissionTitleFormat,
            $submission,
            $form
        );

        // Set the default title for the submission so it can save correctly
        if (!$submission->title) {
            $now = new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));
            $submission->title = $now->format('D, d M Y H:i:s');
        }
    }

    /**
     * Returns the named parameter value from either GET or the request body, or bails on the request with a 400 error
     * if that parameter doesn’t exist anywhere, or if it isn't of the specified type.
     *
     * @param string $name The parameter name.
     * @param string $type The parameter type to be enforced.
     * @return mixed The parameter value.
     * @throws BadRequestHttpException if the request is not the valid type
     */
    private function _getTypedParam(string $name, string $type)
    {
        $request = Craft::$app->getRequest();
        $value = $request->getParam($name);

        // Special case for `submitAction`, where we don't want just anything passed in
        if ($name === 'submitAction') {
            if (!in_array($value, ['submit', 'back', 'save'])) {
                throw new BadRequestHttpException('Request has invalid param ' . $name);
            }
        }

        if ($value !== null) {
            // Go case-by-case, so it's easier to handle, and more predictable
            if ($type === 'string' && is_string($value)) {
                return (string)$value;
            }

            if ($type === 'boolean' && is_string($value)) {
                return StringHelper::toBoolean($value);
            }

            if ($type === 'int' && (is_numeric($value) || $value === '')) {
                return intval($value);
            }

            if ($type === 'id' && is_numeric($value) && intval($value) > 0) {
                return intval($value);
            }

            throw new BadRequestHttpException('Request has invalid param ' . $name);
        }
    }
}

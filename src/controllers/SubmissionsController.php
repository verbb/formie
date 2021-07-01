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
     * Shows all the submissions in a list.
     *
     * @return Response|null
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $this->getView()->registerAssetBundle(CpAsset::class);

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
    public function actionEditSubmission(int $submissionId = null, string $siteHandle = null, Submission $submission = null): Response
    {
        $variables = compact('submissionId', 'submission');

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        if (!$variables['submission']) {
            if ($variables['submissionId']) {
                $variables['submission'] = Submission::find()
                    ->id($variables['submissionId'])
                    ->isIncomplete(null)
                    ->isSpam(null)
                    ->one();

                if (!$variables['submission']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['submission'] = new Submission();
            }
        }

        $this->_prepEditSubmissionVariables($variables);

        if ($variables['submission']->id) {
            $variables['title'] = $variables['submission']->title;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new submission');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'formie/submissions/edit/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
            (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

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

        $this->requirePermission('formie-editSubmissions');

        // Get the requested submission
        $request = Craft::$app->getRequest();
        $submissionId = $request->getBodyParam('submissionId');
        $siteId = $request->getBodyParam('siteId');

        if ($submissionId) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(null)
                ->isSpam(null)
                ->one();

            if (!$submission) {
                throw new NotFoundHttpException(Craft::t('formie', 'No submission with the ID “{id}”', ['id' => $submissionId]));
            }
        } else {
            $submission = new Submission();
        }

        $form = $submission->form;

        // Check against permissions to save at all, or per-form
        if (!Craft::$app->getUser()->checkPermission('formie-editSubmissions')) {
            if (!Craft::$app->getUser()->checkPermission('formie-manageSubmission:' . $form->uid)) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        // Now populate the rest of it from the post data
        $submission->siteId = $siteId ?? $submission->siteId;
        $submission->enabled = true;
        $submission->enabledForSite = true;
        $submission->title = $request->getBodyParam('title', $submission->title);
        $submission->statusId = $request->getBodyParam('statusId', $submission->statusId);

        $submission->setFieldValuesFromRequest('fields');

        // Save the submission
        if ($submission->enabled && $submission->enabledForSite) {
            $submission->setScenario(Element::SCENARIO_LIVE);
        }

        if ($request->getParam('saveAction') === 'draft') {
            $submission->setScenario(Element::SCENARIO_ESSENTIALS);
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            $goingBack = (bool)$request->getBodyParam('goingBack');

            // Ensure we set the current submission on the form. This keeps track of session info for
            // multi-page forms, separate to "new" submissions
            $form->setSubmission($submission);

            // Check for the next page - if there is one
            $nextPage = $form->getNextPage(null, $submission);

            // Or, if we've passed in a specific page to go to
            if ($goToPageId = $request->getBodyParam('goToPageId')) {
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

        $currentPage = null;

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        $handle = $request->getRequiredBodyParam('handle');
        $goingBack = (bool)$request->getBodyParam('goingBack');

        Formie::log("Submission triggered for ${handle}.");

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        if ($pageIndex = $request->getParam('pageIndex')) {
            $pages = $form->getPages();
            $currentPage = $pages[$pageIndex];
        }

        // Allow full submission payload to be provided for multi-page forms.
        // Skip straight to the last page.
        if ($request->getParam('completeSubmission')) {
            $pages = $form->getPages();
            $form->setCurrentPage($pages[count($pages) - 1]);
        }

        $settings = $form->settings;
        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        if ($submissionId = $request->getBodyParam('submissionId')) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(true)
                ->one();

            if (!$submission) {
                throw new BadRequestHttpException("No submission exists with the ID \"$submissionId\"");
            }
        } else {
            $submission = new Submission();
            $submission->setForm($form);
        }

        $submission->siteId = $request->getParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id;

        Craft::$app->getContent()->populateElementContent($submission);
        $submission->setFieldValuesFromRequest($this->_namespace);
        $submission->setFieldParamNamespace($this->_namespace);

        if ($form->settings->collectIp) {
            $submission->ipAddress = Craft::$app->getRequest()->userIP;
        }

        if ($form->settings->collectUser) {
            if ($user = Craft::$app->getUser()->getIdentity()) {
                $submission->setUser($user);
            }
        }

        $submission->title = Variables::getParsedValue(
            $settings->submissionTitleFormat,
            $submission,
            $form
        );

        if (!$submission->title) {
            $timeZone = Craft::$app->getTimeZone();
            $now = new DateTime('now', new DateTimeZone($timeZone));
            $submission->title = $now->format('Y-m-d H:i');
        }

        // Check for the next page - if there is one
        $nextPage = $form->getNextPage(null, $submission);

        // Or, if we've passed in a specific page to go to
        if ($goToPageId = $request->getBodyParam('goToPageId')) {
            $goingBack = true;
            $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
        } else if ($goingBack) {
            $nextPage = $form->getPreviousPage(null, $submission);
        }

        $defaultStatus = $form->getDefaultStatus();
        $errorMessage = $form->settings->getErrorMessage();

        // Get the submission, or create a new one
        $submission = $this->_populateSubmission($form);

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

        // Don't validate when going back
        if (!$goingBack) {
            $submission->validate();
        }

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

            Formie::error(Craft::t('app', 'Couldn’t save submission due to errors - {e}.', ['e' => Json::encode($errors)]));

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(false, $submission, $form, $nextPage, [
                    'errors' => $errors,
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

            if ($request->getAcceptsJson()) {
                return $this->_returnJsonResponse(false, $submission, $form, $nextPage, [
                    'errors' => $errors,
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
        if ($request->getParam('completeSubmission')) {
            // Bypass the last-page check
            $url = $form->getRedirectUrl(false);

            return $this->redirectToPostedUrl($submission, $url);
        }

        return $this->redirectToPostedUrl($submission);
    }

    public function actionSetPage()
    {
        $request = Craft::$app->getRequest();

        $pageId = $request->getParam('pageId');
        $handle = $request->getParam('handle');

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        // Check if we're editing a submission
        if ($submissionId = $request->getParam('submissionId')) {

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


    // Private Methods
    // =========================================================================

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
            'nextPageIndex' => $form->getCurrentPageIndex() ?? 0,
            'totalPages' => count($form->getPages()),
            'redirectUrl' => $redirectUrl,
            'submitActionMessage' => $form->settings->getSubmitActionMessage($submission),
        ], $extras);

        return $this->asJson($params);
    }

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

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        return null;
    }

    private function _populateSubmission($form)
    {
        $request = Craft::$app->getRequest();

        if ($submissionId = $request->getBodyParam('submissionId')) {
            $submission = Submission::find()
                ->id($submissionId)
                ->isIncomplete(true)
                ->one();

            if (!$submission) {
                throw new BadRequestHttpException("No submission exists with the ID \"$submissionId\"");
            }
        } else {
            $submission = new Submission();
        }

        $submission->setForm($form);
        $submission->siteId = $request->getParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id;

        Craft::$app->getContent()->populateElementContent($submission);
        $submission->setFieldValuesFromRequest($this->_namespace);
        $submission->setFieldParamNamespace($this->_namespace);

        if ($form->settings->collectIp) {
            $submission->ipAddress = Craft::$app->getRequest()->userIP;
        }

        if ($form->settings->collectUser) {
            if ($user = Craft::$app->getUser()->getIdentity()) {
                $submission->setUser($user);
            }
        }

        // Set the default title for the submission so it can save correctly
        $now = new DateTime('now', new DateTimeZone(Craft::$app->getTimeZone()));
        $submission->title = $now->format('D, d M Y H:i:s');

        return $submission;
    }
}

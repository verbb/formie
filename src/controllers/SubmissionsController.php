<?php
namespace verbb\formie\controllers;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
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

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Settings;
use verbb\formie\web\assets\cp\CpAsset;

class SubmissionsController extends Controller
{
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
            $submission->siteId = $siteId ?? $submission->siteId;
        }

        $form = $submission->form;

        // Now populate the rest of it from the post data
        $submission->enabled = true;
        $submission->enabledForSite = true;
        $submission->title = $request->getBodyParam('title', $submission->title);
        $submission->statusId = $request->getBodyParam('statusId', $submission->statusId);

        $submission->setFieldValuesFromRequest('fields');

        // Save the submission
        if ($submission->enabled && $submission->enabledForSite) {
            $submission->setScenario(Element::SCENARIO_LIVE);
        }

        // Check if this is a front-end edit
        if ($request->getIsSiteRequest()) {
            $goingBack = false;

            // Ensure we set the current submission on the form. This keeps track of session info for
            // multi-page forms, separate to "new" submissions
            $form->setSubmission($submission);

            // Check for the next page - if there is one
            $nextPage = $form->getNextPage();

            // Or, if we've passed in a specific page to go to
            if ($goToPageId = $request->getBodyParam('goToPageId')) {
                $goingBack = true;
                $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
            }

            // Don't validate when going back
            if (!$goingBack) {
                // Turn on validation, but set a flag to only validate the current page.
                $submission->validateCurrentPageOnly = true;
            }

            // Check if we're on the last page of the form, or need to keep going
            if (empty($nextPage)) {
                $submission->validateCurrentPageOnly = false;
            }
        }

        $submission->validate();

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

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
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $submission->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save submission.'));

            // Send the submission back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
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
                Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage());

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

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        $handle = $request->getRequiredBodyParam('handle');
        $goingBack = false;

        /* @var Form $form */
        $form = Form::find()->handle($handle)->one();

        if (!$form) {
            throw new BadRequestHttpException("No form exists with the handle \"$handle\"");
        }

        // Check for the next page - if there is one
        $nextPage = $form->getNextPage();

        // Or, if we've passed in a specific page to go to
        if ($goToPageId = $request->getBodyParam('goToPageId')) {
            $goingBack = true;
            $nextPage = ArrayHelper::firstWhere($form->getPages(), 'id', $goToPageId);
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
            $submission->originSiteId = $request->sites->currentSite->id;
        }

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

        $submission->validate();

        if ($submission->hasErrors()) {
            $errors = $submission->getErrors();

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
                $submission->spamReason = Craft::t('formie', 'Failed Captcha {c}', ['c' => get_class($captcha)]);
            }
        }

        // Final spam checks for things like keywords
        Formie::$plugin->getSubmissions()->spamChecks($submission);

        // Save the submission
        $success = Craft::$app->getElements()->saveElement($submission, false);

        // Run this regardless of the success state
        if (!$submission->isIncomplete) {
            Formie::$plugin->getSubmissions()->onAfterSubmission($success, $submission);
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

        if (!$success) {
            $errors = $submission->getErrors();

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

        if ($request->getAcceptsJson()) {
            return $this->_returnJsonResponse($success, $submission, $form, $nextPage);
        }

        if (!empty($nextPage)) {
            // Refresh, there's still more pages to complete
            return $this->refresh();
        }

        Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);

        if ($form->settings->submitAction == 'message') {
            Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage());

            return $this->refresh();
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
        return $this->asJson(array_merge([
            'success' => $success,
            'submissionId' => $submission->id,
            'currentPageId' => $form->getCurrentPage()->id,
            'nextPageId' => $nextPage->id ?? null,
            'nextPageIndex' => $form->getCurrentPageIndex() ?? 0,
            'totalPages' => count($form->getPages()),
        ], $extras));
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
}

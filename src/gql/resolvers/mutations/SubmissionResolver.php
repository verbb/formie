<?php
namespace verbb\formie\gql\resolvers\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Settings;

use Craft;
use craft\base\Element;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Gql;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

use DateTime;
use DateTimeZone;

class SubmissionResolver extends ElementMutationResolver
{
    // Properties
    // =========================================================================

    /** @inheritdoc */
    protected $immutableAttributes = ['id', 'uid'];


    // Public Methods
    // =========================================================================

    public function saveSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $form = $this->getResolutionData('form');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
        $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');
        
        $scope = 'formieSubmissions.' . $form->uid;
        $canCreate = Gql::canSchema($scope, 'create');
        $canSave = Gql::canSchema($scope, 'save');

        if ($canIdentify) {
            if (!$canSaveAll && !$canSave) {
                throw new Error('Unable to perform the action.');
            }

            if (!empty($arguments['uid'])) {
                $submission = $elementService->createElementQuery(Submission::class)->uid($arguments['uid'])->one();
            } else {
                $submission = $elementService->getElementById($arguments['id'], Submission::class);
            }

            if (!$submission) {
                throw new Error('No such submission exists');
            }
        } else {
            if (!$canCreateAll && !$canCreate) {
                throw new Error('Unable to perform the action.');
            }

            $submission = $elementService->createElement(['type' => Submission::class, 'formId' => $form->id]);
        }

        if ($submission->formId != $form->id) {
            throw new Error('Impossible to change the form of an existing submission');
        }

        $submission = $this->populateElementWithData($submission, $arguments);

        // TODO: refactor by combining this from the submit controller...

        /* @var Settings $settings */
        $formieSettings = Formie::$plugin->getSettings();

        // Populate the default status if none
        if (!$submission->statusId) {
            $defaultStatus = $form->getDefaultStatus();
            $submission->setStatus($defaultStatus);
        }

        if (!$submission->title) {
            $settings = $form->settings;

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
        }

        // Only handle single-pages in GQL for now
        $submission->setScenario(Element::SCENARIO_LIVE);
        $submission->isIncomplete = false;
        $submission->validateCurrentPageOnly = false;

        $submission->validate();

        if ($submission->hasErrors()) {
            throw new Error('Unable to save submission: ' . Json::encode($submission->getErrors()));
        }

        // Check against all enabled captchas. Also take into account multi-pages
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

        foreach ($captchas as $captcha) {
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

        if (!$success || $submission->hasErrors()) {
            throw new Error('Unable to save submission: ' . Json::encode($submission->getErrors()));
        }

        if (!$submission->id) {
            throw new Error('Unable to save submission ' . $submission->id . ': ' . Json::encode($submission->getErrors()));
        }

        return $elementService->getElementById($submission->id, Submission::class, $submission->siteId);
    }

    public function deleteSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $submissionId = $arguments['id'];
        $siteId = $arguments['siteId'] ?? null;

        $elementService = Craft::$app->getElements();
        $submission = $elementService->getElementById($submissionId, Submission::class, $siteId);

        if (!$submission) {
            return false;
        }

        $formUid = Db::uidById('{{%formie_forms}}', $submission->getForm()->id);

        $scope = 'formieSubmissions.' . $formUid;
        $canDeleteAll = Gql::canSchema('formieSubmissions.all', 'delete');
        $canDelete = Gql::canSchema($scope, 'delete');

        if (!$canDeleteAll && !$canDelete) {
            throw new Error('Unable to perform the action.');
        }

        return $elementService->deleteElementById($submissionId, Submission::class, $siteId);
    }
}

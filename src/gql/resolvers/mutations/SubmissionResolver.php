<?php
namespace verbb\formie\gql\resolvers\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Settings;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
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

    protected array $immutableAttributes = ['id', 'uid'];


    // Public Methods
    // =========================================================================

    public function saveSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo): ?ElementInterface
    {
        $form = $this->getResolutionData('form');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
        $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');

        $scope = 'formieSubmissions.' . $form->uid;
        $canCreate = Gql::canSchema($scope, 'create');
        $canSave = Gql::canSchema($scope, 'save');

        $submission = null;

        if ($canIdentify) {
            if (!$canSaveAll && !$canSave) {
                throw new Error('Unable to perform the action.');
            }

            $query = $elementService->createElementQuery(Submission::class)->status(null)->isSpam(null)->isIncomplete(null);

            if (!empty($arguments['uid'])) {
                /* @var Submission $submission */
                $submission = $query->uid($arguments['uid'])->one();
            } else {
                /* @var Submission $submission */
                $submission = $query->id($arguments['id'])->one();
            }

            if (!$submission) {
                throw new Error('No such submission exists');
            }
        } else {
            if (!$canCreateAll && !$canCreate) {
                throw new Error('Unable to perform the action.');
            }

            /* @var Submission $submission */
            $submission = $elementService->createElement(['type' => Submission::class, 'formId' => $form->id]);
        }

        if ($submission->formId != $form->id) {
            throw new Error('Impossible to change the form of an existing submission');
        }

        $submission = $this->populateElementWithData($submission, $arguments, $resolveInfo);

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

        $submission->setScenario(Element::SCENARIO_LIVE);
        $submission->validateCurrentPageOnly = (bool)$submission->isIncomplete;

        $submission->validate();

        if ($submission->hasErrors()) {
            throw new Error(Json::encode($submission->getErrors()));
        }

        // Check against all enabled captchas. Also take into account multi-pages
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

        foreach ($captchas as $captcha) {
            $valid = $captcha->validateSubmission($submission);

            if (!$valid) {
                $submission->isSpam = true;
                $submission->spamReason = Craft::t('formie', 'Failed Captcha “{c}”: “{m}”', ['c' => $captcha::displayName(), 'm' => $captcha->spamReason]);
                $submission->spamClass = get_class($captcha);
            }
        }

        // Final spam checks for things like keywords
        Formie::$plugin->getSubmissions()->spamChecks($submission);

        // Check events right before our saving
        Formie::$plugin->getSubmissions()->onBeforeSubmission($submission);

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

        if (!$success || $submission->hasErrors() || !$submission->id) {
            throw new Error(Json::encode($submission->getErrors()));
        }

        // Refresh data from the DB
        return Craft::$app->getElements()->createElementQuery(Submission::class)
            ->id($submission->id)
            ->siteId($submission->siteId)
            ->status(null)
            ->isSpam(null)
            ->isIncomplete(null)
            ->one();
    }

    public function deleteSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo): bool
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

<?php
namespace verbb\formie\gql\resolvers\mutations;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;

use Craft;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Db;

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

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $submission = $elementService->createElementQuery(Submission::class)->uid($arguments['uid'])->one();
            } else {
                $submission = $elementService->getElementById($arguments['id'], Submission::class);
            }

            if (!$submission) {
                throw new Error('No such submission exists');
            }
        } else {
            $submission = $elementService->createElement(['type' => Submission::class, 'formId' => $form->id]);
        }

        if ($submission->formId != $form->id) {
            throw new Error('Impossible to change the form of an existing submission');
        }

        $submission = $this->populateElementWithData($submission, $arguments);

        // TODO: refactor by combining this from the submit controller...

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

        $submission = $this->saveElement($submission);

        return $elementService->getElementById($submission->id, Submission::class);
    }

    public function deleteSubmission($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $submissionId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        $submission = $elementService->getElementById($submissionId, Submission::class);

        if (!$submission) {
            return true;
        }

        $elementService->deleteElementById($submissionId);

        return true;
    }
}

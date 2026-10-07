<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;

use craft\base\Model;

class SubmissionResponse extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromOutcome(SubmissionOutcome $outcome, Form $form, Submission $submission, SubmissionCommand $command): self
    {
        $response = new self([
            'outcome' => $outcome,
            'form' => $form,
            'submission' => $submission,
            'success' => in_array($outcome->type, [
                SubmissionOutcomeType::PAGE_CHANGED,
                SubmissionOutcomeType::DRAFT_SAVED,
                SubmissionOutcomeType::COMPLETED,
                SubmissionOutcomeType::REVISED,
            ], true) || ($outcome->data['fakeSuccess'] ?? false),
            'submitAction' => $command->operation === SubmissionOperation::SAVE_DRAFT ? 'save'
                : ($command->navigation === NavigationIntent::BACK ? 'back' : 'submit'),
            'httpStatus' => match ($outcome->type) {
                SubmissionOutcomeType::VALIDATION_FAILED => 422,
                SubmissionOutcomeType::STATE_CONFLICT => 409,
                SubmissionOutcomeType::REJECTED => ($outcome->data['fakeSuccess'] ?? false) ? 200 : 403,
                SubmissionOutcomeType::PAYMENT_FAILED => 422,
                default => 200,
            },
        ]);

        foreach ($form->getPages() as $page) {
            if ((int)$page->id === $outcome->nextPageId) {
                $response->nextPage = $page;
                break;
            }
        }
        $payment = $outcome->data['payment'] ?? null;

        if (is_array($payment)) {
            $response->payment = $payment;
        }

        if ($form->settings->quizShowScoreAfterSubmit && $submission->id) {
            $scoring = Formie::$plugin->getQuestionnaireScoring();
            $quizResult = $scoring->getQuizResultForSubmission((int)$submission->id);

            if ($quizResult) {
                $response->quizResult = $scoring->getQuizResultPayload($quizResult, $form, true);
            }
        }
        return $response;
    }


    // Properties
    // =========================================================================

    public ?SubmissionOutcome $outcome = null;
    public string $submitAction = 'submit';
    public int $httpStatus = 200;
    public bool $success = false;
    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?FieldLayoutPage $nextPage = null;
    public ?array $payment = null;
    public ?array $quizResult = null;
}

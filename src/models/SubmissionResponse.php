<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

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
                \verbb\formie\enums\SubmissionOutcomeType::PAGE_CHANGED,
                \verbb\formie\enums\SubmissionOutcomeType::DRAFT_SAVED,
                \verbb\formie\enums\SubmissionOutcomeType::COMPLETED,
                \verbb\formie\enums\SubmissionOutcomeType::REVISED,
            ], true) || ($outcome->data['fakeSuccess'] ?? false),
            'submitAction' => $command->operation === \verbb\formie\enums\SubmissionOperation::SAVE_DRAFT ? 'save'
                : ($command->navigation === \verbb\formie\enums\NavigationIntent::BACK ? 'back' : 'submit'),
            'httpStatus' => match ($outcome->type) {
                \verbb\formie\enums\SubmissionOutcomeType::VALIDATION_FAILED => 422,
                \verbb\formie\enums\SubmissionOutcomeType::STATE_CONFLICT => 409,
                \verbb\formie\enums\SubmissionOutcomeType::REJECTED => ($outcome->data['fakeSuccess'] ?? false) ? 200 : 403,
                \verbb\formie\enums\SubmissionOutcomeType::PAYMENT_FAILED => 422,
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
            $response->paymentDecision = $payment;
            $response->paymentStatus = $payment['status'] ?? null;
            $response->paymentMessage = $payment['message'] ?? null;
            $response->paymentRedirectUrl = $payment['redirectUrl'] ?? null;
            $response->paymentAction = $payment['action'] ?? null;
        }
        if ($form->settings->quizShowScoreAfterSubmit && $submission->id) {
            $scoring = \verbb\formie\Formie::$plugin->getQuestionnaireScoring();
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
    public ?string $paymentStatus = null;
    public ?string $paymentMessage = null;
    public ?string $paymentRedirectUrl = null;
    public ?array $paymentAction = null;
    public ?array $paymentDecision = null;
    public ?array $quizResult = null;
}

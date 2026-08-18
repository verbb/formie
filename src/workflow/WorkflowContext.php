<?php
namespace verbb\formie\workflow;

use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\services\SubmissionWorkflow;

class WorkflowContext
{
    // Properties
    // =========================================================================

    public SubmissionRequest $request;
    public SubmissionResponse $response;
    public WorkflowPolicy $workflow;
    public bool $processingSuccess = false;
    public WorkflowResult $workflowExecution;
    public ?FieldLayoutPage $nextPage = null;

    /**
     * Set by page-flow / payment tasks when this request flips the submission
     * to complete. Distinct from "is currently complete" so edits of already
     * finished submissions do not look like a first-time completion.
     */
    public bool $becameComplete = false;
    public bool $halted = false;
    public bool $success = false;
    public array $taskState = [];

    /**
     * @var list<self>
     */
    private static array $stack = [];


    // Public Methods
    // =========================================================================

    public function __construct(SubmissionRequest $request, WorkflowPolicy $workflow)
    {
        $this->request = $request;
        $this->workflow = $workflow;
        $this->workflowExecution = new WorkflowResult();
        $this->response = new SubmissionResponse([
            'success' => false,
            'form' => $request->form,
            'submission' => $request->submission,
        ]);
    }

    /**
     * The workflow request currently executing, if any. Used so element
     * `afterSave` does not fire `EVENT_AFTER_COMPLETE` a second time while
     * the save-stage lifecycle hook still has to run.
     */
    public static function current(): ?self
    {
        return self::$stack ? self::$stack[array_key_last(self::$stack)] : null;
    }

    public static function push(self $context): void
    {
        self::$stack[] = $context;
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    /**
     * Successful Next: this POST accepted the current page and there is another
     * reachable page. Next and Submit both post `submitAction=submit`; the
     * discriminator is `nextPage` / `isIncomplete` after resolve-page-flow.
     */
    public function isPageAdvance(): bool
    {
        $request = $this->request;

        if ($request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
            return false;
        }

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return false;
        }

        return $this->nextPage !== null && $request->submission->isIncomplete;
    }

    /**
     * This request finished the form (last reachable page, including when later
     * pages are conditionally hidden). Not the same as "the submission is
     * complete" — that is also true on later edits.
     */
    public function isCompletion(): bool
    {
        $request = $this->request;

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return false;
        }

        if (!in_array($request->processMode, [
            SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        ], true)) {
            return false;
        }

        return $this->becameComplete
            && $this->nextPage === null
            && !$request->submission->isIncomplete;
    }

    public function halt(bool $success): void
    {
        $this->halted = true;
        $this->success = $success;
    }
}

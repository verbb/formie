<?php
namespace verbb\formie\workflow;

use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionResponse;

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
    public bool $halted = false;
    public bool $success = false;
    public array $taskState = [];


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

    public function halt(bool $success): void
    {
        $this->halted = true;
        $this->success = $success;
    }
}

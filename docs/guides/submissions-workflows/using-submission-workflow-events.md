# Using Submission Workflow Events

Workflow events let a Craft module observe the stages and public tasks that actually run for an operation. This guide records when validation has finished and when integration dispatch has been requested. These are observation hooks; use a [registered task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) when custom logic must stop processing.

Start with an installed Formie form and a bootstrapped Craft module. Add these listeners to the module's `init()` method after `parent::init()`. Import the classes at the top of the module file:

```php
use Craft;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;
```

## Observe Validation

```php
Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, function(SubmissionWorkflowStageEvent $event) {
    if ($event->stage !== 'validate' || $event->command->operation !== SubmissionOperation::SUBMIT) {
        return;
    }

    Craft::info([
        'formId' => $event->command->form->id,
        'valid' => !$event->command->submission->hasErrors(),
    ], 'formie-project');
});
```

The event carries the resolved command and execution context. Only applicable stages emit execution events. SaveDraft skips Validate, and invalid submissions never reach Screen. Avoid logging submitted values or request tokens.

## Observe Integration Dispatch

```php
Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_TASK, function(SubmissionWorkflowTaskEvent $event) {
    if ($event->task !== Task::DISPATCH_TRIGGER_INTEGRATIONS->value) {
        return;
    }

    Craft::info('Integration dispatch requested for submission ' . $event->command->submission->id, 'formie-project');
});
```

An after-task event indicates that the logical dispatch task ran. If integrations use the queue, remote delivery may happen later. Internal tasks such as receipt bookkeeping have no public task observation events.

Neither listener can set `isValid` to cancel a stage or task. To reject an order reference after Formie's validation, insert a Submit task after `validate.submission` and return a validation-failed outcome, as shown in the [task walkthrough](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch).

## Check the Behaviour

Submit an invalid required field and inspect Craft's logs for the validation record. There should be no integration dispatch record for that attempt. Correct the field and complete the form; dispatch should then be recorded when applicable. Save a draft and confirm that neither listener runs.

When you need a page-accepted or form-completed hook, use the [semantic lifecycle events](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit) instead of deriving completion from a task name. The [event reference](/developers/events/submission-events) lists the available payloads.

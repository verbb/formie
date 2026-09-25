# Adding a Custom Workflow Task from Scratch

This guide adds a project-specific validation rule after Formie's field validation and before spam checks or CAPTCHA. A task gives the rule an explicit place in the workflow and a typed way to stop processing.

Start with an installed Formie form containing a required Single-Line Text field with the handle `orderReference`. You also need a bootstrapped Craft module. The example uses the namespace `modules\formieworkflow`, mapped to `modules/formieworkflow/src/` in your project's Composer autoload configuration. If you don't have a module yet, follow Craft's [module setup guide](https://craftcms.com/docs/5.x/extend/module-guide.html), including registering and bootstrapping the module, before continuing.

## Create the Task

Create `modules/formieworkflow/src/tasks/CheckOrderTask.php`:

```php
<?php
namespace modules\formieworkflow\tasks;

use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class CheckOrderTask implements TaskInterface
{
    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->command->submission;
        $reference = (string)$submission->getFieldValue('orderReference');

        if ($reference !== '' && !str_starts_with($reference, 'ORD-')) {
            $submission->addError('field:orderReference', 'Enter a reference beginning with ORD-.');
            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }

        return TaskResult::continue();
    }
}
```

The task only executes work. Its name, stage placement and applicable operations belong to registration. `TaskResult::continue()` lets the next task run. A validation-failed outcome stops processing, preserves errors and leaves the request token available for corrected input. Place rejection checks before Persist: stopping later does not undo already committed work.

## Register the Task

Add these imports to your module class:

```php
use modules\formieworkflow\tasks\CheckOrderTask;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\TaskDefinition;
use yii\base\Event;
```

Inside the module's `init()` method, after `parent::init()`, register the listener. Replace `orders` with your form's handle:

```php
Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    if ($event->stage !== Stage::VALIDATE) {
        return;
    }

    $event->insertTaskAfter(Task::VALIDATE_SUBMISSION, new TaskDefinition(
        'acme.validateOrder',
        new CheckOrderTask(),
        [SubmissionOperation::SUBMIT],
    ));
});
```

The registration runs for each workflow plan. To restrict this task to the `orders` form, add this at the start of `CheckOrderTask::execute()`:

```php
if ($context->command->form->handle !== 'orders') {
    return TaskResult::continue();
}
```

`acme.validateOrder` must be unique within the stage. The explicit Submit operation keeps the rule out of SaveDraft, Revise and PaymentReplay. Add Revise to the operation list if saved-record edits must follow the same rule.

## Choose a Position

`insertTaskBefore()` and `insertTaskAfter()` take a public anchor and a `TaskDefinition`. `prepend()` and `append()` place a task at a stage boundary. An unknown or internal anchor throws an exception, so use the [public anchor reference](/developers/submission-workflow#stages-and-public-anchors) when choosing a position.

To check valid input before spam screening, the example's position after `validate.submission` is appropriate. To enqueue work for a completed submission before integrations, register a task in Dispatch before `Task::DISPATCH_TRIGGER_INTEGRATIONS`. The submission has been saved at that point. Queued work still needs its own delivery and retry policy; an after-task event does not prove that a remote request has completed.

## Test the Result

Submit the form with an empty required field. Formie's validation should reject it before any screening runs. Next, enter `INVALID-123`; your rule should show its field error without saving or using CAPTCHA. Correct the value to `ORD-123` and submit again. The normal workflow should continue.

If your form supports save-and-continue, save a draft containing `INVALID-123`. The draft should save because the registration declares only Submit. Test another form too: the handle check should leave it unaffected. If the task is missing, check that the module is bootstrapped, its Composer namespace resolves, and the stage comparison uses `Stage::VALIDATE`.

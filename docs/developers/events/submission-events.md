# Submission Events

## Submission Lifecycle Events

### The `defineSubmissionRules` event
The event that is triggered to modify or define additional validation rules for submissions.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionRulesEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $submission = $event->submission;
    $rules = $event->rules;
    // ...

    // Add a required field for field with handle `emailAddress`
    $event->rules[] = [['field:emailAddress'], 'required'];
});
```

### The `beforeSaveSubmission` event
The event that is triggered before a submission is saved. For multi-page forms, this event will occur on each page submission, as the submission is saved in its incomplete state.

You can set `$event->isValid` to false to prevent saving.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    $event->isValid = false;
});
```

### The `afterSaveSubmission` event
The event that is triggered after a submission is saved. For multi-page forms, this event will occur on each page submission, as the submission is saved in its incomplete state.

If you need to run code during the submission lifecycle rather than on every element save, use the submission workflow events.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;
});
```

### Submission workflow events
The submission workflow handles each page request and the final completed submission. Use these events when you need to hook into a specific stage or task in the submission process.

For a fuller explanation of stages and tasks, see [Submission Workflow](/developers/submission-workflow).

### The `beforeSetPage` event
The event that is triggered before Formie stores the current page navigation state for a form.

```php
use verbb\formie\events\SubmissionRequestEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_SET_PAGE, function(SubmissionRequestEvent $event) {
    $request = $event->request;

    // Prevent the page state from being stored.
    // $event->isValid = false;
});
```

### The `afterSetPage` event
The event that is triggered after Formie stores the current page navigation state for a form.

```php
use verbb\formie\events\SubmissionRequestEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_SET_PAGE, function(SubmissionRequestEvent $event) {
    $request = $event->request;
    // ...
});
```

### The `beforeStage` event
The event that is triggered before a submission workflow stage runs.

```php
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, function(SubmissionWorkflowStageEvent $event) {
    $request = $event->request;
    $context = $event->context;
    $stage = $event->stage;

    if ($stage === 'validate') {
        // ...
    }
});
```

### The `afterStage` event
The event that is triggered after a submission workflow stage runs.

```php
use verbb\formie\events\SubmissionWorkflowStageEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, function(SubmissionWorkflowStageEvent $event) {
    $request = $event->request;
    $context = $event->context;
    $stage = $event->stage;
    $result = $event->result;
    // ...
});
```

### The `beforeTask` event
The event that is triggered before a task runs inside a submission workflow stage.

```php
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_TASK, function(SubmissionWorkflowTaskEvent $event) {
    $stage = $event->stage;
    $task = $event->task;

    if ($stage === 'dispatch' && $task === 'notifications') {
        // ...
    }
});
```

### The `afterTask` event
The event that is triggered after a task runs inside a submission workflow stage.

```php
use verbb\formie\events\SubmissionWorkflowTaskEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_TASK, function(SubmissionWorkflowTaskEvent $event) {
    $stage = $event->stage;
    $task = $event->task;
    $result = $event->result;
    // ...
});
```

### The `registerWorkflowStages` event
The event that is triggered when Formie registers submission workflow stages.

```php
use verbb\formie\events\RegisterWorkflowStagesEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_WORKFLOW_STAGES, function(RegisterWorkflowStagesEvent $event) {
    $stages = $event->stages;
    // ...
});
```

### The `registerStageTasks` event
The event that is triggered when Formie registers the tasks for a submission workflow stage.

```php
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    $stage = $event->stage;
    $tasks = $event->tasks;
    // ...
});
```

### The `beforeDeleteSubmission` event
The event that is triggered before a submission is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_DELETE, function(Event $event) {
    $submission = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteSubmission` event
The event that is triggered after a submission is deleted.

```php
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_DELETE, function(Event $event) {
    $submission = $event->sender;
});
```

### The `afterPruneSubmission` event
The event that is triggered after a submission is pruned (permanently deleted) according to the data retention rules for the form.

```php
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_PRUNE_SUBMISSION, function(PruneSubmissionEvent $event) {
    $submission = $event->submission;
});
```

### The `beforeSendNotification` event
The event that is triggered before an email notification is sent.

The `isValid` event property can be set to `false` to prevent the notification from proceeding.

```php
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, function(SendNotificationEvent $event) {
    $submission = $event->submission;
    $notification = $event->notification;
    // ...
});
```

### The `beforeTriggerIntegration` event
The event that is triggered before an integration is triggered.

The `isValid` event property can be set to `false` to prevent the integration from proceeding.

```php
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, function(TriggerIntegrationEvent $event) {
    $submission = $event->submission;
    $integration = $event->integration;
    $type = $event->type;
    // ...
});
```

### The `modifyExportData` event
The event that is triggered when preparing submission(s) to be exported.

Modify the `exportData` event property to change the export data.

```php
use verbb\formie\elements\exporters\SubmissionExport;
use verbb\formie\events\ModifySubmissionExportDataEvent;
use yii\base\Event;

Event::on(SubmissionExport::class, SubmissionExport::EVENT_MODIFY_EXPORT_DATA, function(ModifySubmissionExportDataEvent $event) {
    $exportData = $event->exportData;
    $query = $event->query;
});
```

## Spam Events

### The `beforeMarkedAsSpam` event
The event that is triggered before a submission is marked as spam.

The `isValid` event property can be set to `false` to prevent the submission from being marked as spam.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_MARKED_AS_SPAM, function(SubmissionMarkedAsSpamEvent $event) {
    $submission = $event->submission;
    $isNew = $event->isNew;
    $isValid = $event->isValid;
    // ...
});
```

## Submission Status Events

### The `beforeSaveStatus` event
The event that is triggered before a submission status is saved.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_SAVE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveStatus` event
The event that is triggered after a submission status is saved.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_AFTER_SAVE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteStatus` event
The event that is triggered before a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_DELETE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

### The `beforeApplyStatusDelete` event
The event that is triggered before a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_APPLY_STATUS_DELETE, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

### The `afterDeleteStatus` event
The event that is triggered after a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_AFTER_DELETE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

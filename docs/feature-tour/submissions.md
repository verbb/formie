# Submissions
When someone fills out your form from the front-end of your website, their content is saved as a Submission. You can view Submissions in the control panel via Formie → Submissions.

Submissions contain all the content a user has provided when filling out the form. You can also edit the submission in the control panel, should you wish to alter the content.

## Multi-page forms
For mutli-page forms, submissions are saved on every page submission. If the page is not the last page, the submission will be marked as `isIncompleted`. This allows you to see the content of incomplete submissions, which can be important for form abandonment. It also provides users of the form a means of saving their submission along the way.

You can view incomplete submissions in the control panel, but filtering the Submissions index.

## Spam
For submissions that have been classified as spam, the submission will not appear alongside other submissions. Instead, it will be categorized as spam. If you have chosen to opt to save spam submissions to the database (in your Formie Settings), you'll be able to filter submissions by their spam status, on the Submissions index.

## Privacy
An important part in any system that deals with user-provided content is privacy and data retention. Formie provides a handful of settings for you to manage user submissions. Each setting can be managed per-form (see [Forms](docs:feature-tour/forms)), where you can opt to retain submission data for a set number of minutes, hours, days, weeks, months or years.

If a submission is associated with a user (through the "Collect User" form setting), when that user is deleted, you'll be able to decide how submission data should be handled. You can either transfer it to another user, or delete it permanently.

Similarly, with file uploads, you can also choose how your forms handle this when deleting a submission.

See also our [Data Retention](docs:feature-tour/data-retention) section.
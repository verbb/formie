# Submission

Whenever you're dealing with a submission in your template, you're actually working with a `Submission` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the submission.
`formId` | The form ID this submission was made on.
`form` | The [Form]() this submission was made on.
`statusId` | The status ID this submission is set to.
`status` | The [Status]() this submission is set to.
`userId` | The user ID of the user that created the submission (if enabled on the form).
`user` | The [User](https://docs.craftcms.com/api/v3/craft-elements-user.html) that created the submission (if enabled on the form).
`ipAddress` | If set to capture IP addresses, this will be the IP address of the submitter.
`isIncomplete` | For multi-page forms, this will show whether the submission is partially completed.
`isSpam` | Whether or not this submission is marked as spam.
`spamReason` | If this submission is marked as spam, it will provide a reason description for why it was.
`validateCurrentPageOnly` | For multi-page forms, whether to validate only the current page.
`dateCreated` | The date this submission was created.

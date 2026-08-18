# Submission Statuses

::: tip
For statuses combined with form conditions and post-submit routing, see [Submission statuses and conditional workflows](/guides/submissions-workflows/submission-statuses-and-conditional-workflows).
:::

Statuses are labels you can use to organize and manage submissions after they have been saved.

They are useful when “new submission” is not enough detail for your team’s workflow.

Typical examples are:

- `new`
- `in-progress`
- `approved`
- `closed`

This gives teams a simple workflow around submissions without changing the form itself.

Each form can choose a default status for new submissions, and optional **submission status rules** that change the status on **final submit** or **every page**.

That means different forms can enter your process in different states, depending on how they are handled internally.

Enable rules under the form’s **Submissions** settings. Rules run in order; the first match wins. Optional conditions can limit a rule to certain answers. For module code when a rule cannot be expressed per form, see [Run PHP on Next vs when the form is finished](/guides/submissions-workflows/run-php-on-next-vs-when-the-form-is-finished).

Statuses are separate from whether a submission is:

- complete
- incomplete
- spam

Those are system states. A status is your workflow label on top of that.

Statuses are managed in Formie settings under **Submission Statuses**.

Each status has:

- a name
- a handle
- a color
- an optional description

One status can be marked as the default.

- You cannot delete the default status while it is the default.
- A status that is still in use by submissions cannot be freely removed.
- Statuses are stored in project config, so they can travel with the project like other shared settings.

Add statuses when a team actually needs them for review or follow-up.

If every submission is handled the same way, the default status may be all you need.

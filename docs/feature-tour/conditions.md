# Conditions
Conditional handling is a powerful feature of Formie, allowing you to hide fields based on certain conditional values, skip pages entirely, or send Email Notifications based on submission content.

<img src="https://verbb.io/uploads/plugins/formie/formie-notification-conditions.png" />

Fields, Pages and Email Notifications all use the same conditions builder, so they share very similar functionality.

You can set whether to match against "All" rules, or just "Any" rule. Building your conditions is a matter of specifying 3 important bits of information: "Field", "Condition", "Value". For "Field", pick the field you want to test a condition against. A "Condition" will be one of the following:

- `is`
- `is not`
- `greater than`
- `less than`
- `contains`
- `starts with`
- `ends with`

And provide a "Value" you wish to compare against. For fields that support set options (Dropdown, Radio, Checkboxes), you must pick from your list of defined options. Otherwise, text values are supported.

## Fields
You can create conditions to show or hide fields in your form, depending on their values. You can choose whether you want these sets of rules to "Show" or "Hide" the field. For instance, you might like to only show an "Other Reason" Single-Line Text field, when the user selects "Other" for a Radio Button field.

## Pages
You can create conditions to show or hide entire pages in your form, depending on values for your fields. You can choose whether you want these sets of rules to "Show" or "Hide" the field. For example, you might only want to show a page if the user has selected a value in a Dropdown field, otherwise the page's fields may not be applicable to the submission.

In addition, any hidden page will not be navigable in a multi-page form. If you have 4 pages, but the 3rd page is hidden, when proceeding to the next page from page 2, you'll be navigated to page 4.

In a similar scenario, you might have pages 2-4 hidden, so that when submitting on the first page, if no conditions are set up to show pages 2-4, the form will be submitted and finished. This functionality provides you the means to skip straight to submission.

### Buttons
You can also set conditions on the buttons on any page. This allows hiding or showing the "next" button. This could be useful in preventing users from proceeding, unless they provide appropriate values for fields.

## Email Notifications
Email notifications can also have set conditions on whether they send or not. Through the conditions' builder, you can create complex rules for each of your email notifications.

You can choose whether you want these sets of rules to "Send" or "Not Send" the email notification. For example, you might like to only send an email notification if the users' email isn't for a number of domain names.

### Conditional Recipients
You can also set different recipients based on submission values. For instance, you might like your accounts team to be included in the user includes the term "account issue" in a field, or selects a similar value from another field. This gives you the benefit of not needing to create an entirely new email notification just for this condition.

## How it works
When toggling fields, pages and buttons, Formie will add (or remove) a `data-conditionally-hidden` data attribute on respective elements. Through this, you're free to alter the specifics of the hiding behaviour if required. This does not apply to Email Notifications.

In addition, any field that is required, but hidden through your conditions will be marked as un-required, as a hidden field cannot be required, as it's impossible for a user to fill it out. This prevents validation issues for invisible fields.

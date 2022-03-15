# Multi-page Forms
When creating a new form, you'll have a single page, named "Page 1". For short forms, a single page will suffice, and for a front-end user, they won't even see the page.

For more complex forms however, you may need to create additional pages, splitting fields over multiple pages. This is what's called a Multi-page form. For each page, users are required to fill out the pages' fields, including any required fields, and proceed to the next page. Each step saves the users' content before proceeding on to the next, so their content won't be forgotten.

<img src="https://verbb.io/uploads/plugins/formie/formie-pages.png" />

Submissions are set to `isIncomplete` at this point, and are not shown in the control panel by default. You can use the dropdown in the Submissions' element index to show these as required.

Navigating to previous or next pages is possible with Formie, in case users want to skip ahead, or go back to previous pages. At the final step of the form however, Formie will ensure all pages validate, just in case the user goes back to previous pages and changes a required field's value.

You can have an infinite number of pages for a form. Each page does however require a unique name. Each page can be renamed, deleted, or re-ordered as desired.

For the first page of a multi-page form, you'll have a single submit button in the form builder. For each subsequent page, you'll have 2 buttons - one for going back to the previous page, the other for going to the next. These are of course configurable to your needs. You can rename or exclude the "back" button altogether.

Captchas can also be set to be enabled on each page, or only at the end of the form.
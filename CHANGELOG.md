# Changelog

## 2.0.18 - 2022-11-19

### Added
- Added pagination to Monday integration to fetch boards over 100.
- Added `IntegrationField::TYPE_DATECLASS` to handle mapping to Date fields and date attributes for Entry element integrations.
- Added `aria-hidden="true"` to required field asterisk indicator for screen readers.

### Changed
- Improve performance of Microsoft Dynamics CRM integration when fetching entity definitions.

### Fixed
- Fixed "Overwrite Values" for element integrations for User photos.
- Fixed return type for Google Sheets integration for `getProxyRedirect()`.
- Fixed an issue where `setFieldSettings()` snapshot data was being removed upon a successful Ajax-based submission.
- Fixed an error with custom filename formats for File Upload fields in Group fields.
- Fixed `setFieldSettings` not applying correctly before submission validation.
- Fixed a recursive loop error when trying to determine whether hidden fields were conditionally hidden or not.
- Fixed being unable to edit Submissions, Forms and Sent Notifications due to Craft 4.3.x changes.
- Fixed nested fields and conditionally-hidden field validation, and implement `FieldLayout::getVisibleCustomFieldElements()`.
- Fixed Table field with Dropdown column saving incorrect values.
- Fixed Agree field’s not converting correctly to boolean values for integrations.
- Fixed return type for Google Sheets integration for `getProxyRedirect()`.
- Fixed some field translations still in the `site` category instead of `formie`.

## 2.0.17 - 2022-11-13

### Added
- Added “Overwrite Content” setting for Element integrations to control whether null values should be applied to element content.

### Fixed
- Fixed the “Proxy Redirect URI” for Google Sheets not saving correctly when using .env variables.
- Fixed an error when using `page` variables in Theme Config settings.
- Fixed an error when rendering a form with both `renderJs` and `renderCss` set to `false`.
- Fixed PHP errors that could occur when executing GraphQL queries.
- Fixed phone field input having the incorrect id attribute.
- Fixed missing `descriptionHtml` attribute for Agree fields for GraphQL queries.
- Fixed an error when rendering an Address field containing instruction text.
- Fixed an error when saving a draft submission from the front-end.

## 2.0.16 - 2022-11-08

### Fixed
- Fix an error introduced in 2.0.15.

## 2.0.15 - 2022-11-06

### Added
- Add Dotdigital CRM integration.
- Add more clarity to Freeform/Sprout Forms migrations when a submission failed to migrate.

### Fixed
- Fix the “View Submissions” link when editing a form not being correct for Craft 4.3+.
- Fix an error when viewing form usage for soft deleted entries that contained a Formie form relation.
- Fix visibly disabled fields not having their default value used when populating a submission content.

## 2.0.14 - 2022-10-29

### Added
- Added `includeDraftElementUsage` and `includeRevisionElementUsage`.
- Added a “View Submissions” button to the form builder.

### Changed
- Form usage now excludes draft and revision elements.

### Fixed
- Fixed an error when saving new integrations.
- Fixed email notification content using Formie 1 nodes in some cases (hard break, list items, etc).
- Fixed `setFieldSettings` not applying correctly before submission validation.
- Fixed an edge-case error where deleting submissions through custom code and in a queue job would trigger a session error.
- Fixed Salesforce and Zoho integrations resetting their `apiDomain` after project config changes.
- Fixed an error when setting the submission status from the submission element index action.
- Fixed Mailjet integration not working correctly.

## 2.0.13 - 2022-10-23

### Added
- Added emoji support to the HTML field.
- Added better descriptions to integration and email notification queue jobs.

### Changed
- Email notification previews now limit element field values depending on their display type for accurate results.
- Update exported submission filename to `formie-submissions-{date}` format.
- When previewing element fields in email notifications, random elements are now shown.
- Updated some bouncer.js classes to remove the reliance on `fui-*` classes.
- Editing a submission from the front-end now does not require user permissions on editing/managing submissions.
- Fields with the handle `username` are now allowed.

### Fixed
- Fixed an issue deleting assets when a form contained multiple File Upload fields.
- Fixed an issue when logging errors for Element integrations.
- Fixed Address field not using `fieldset` and `legend` elements for accessibility.
- Fixed an error with Entries, Products and Users fields when selecting multiple sources.
- Fixed an error with Date fields and their default date in some timezones.
- Fixed an error when saving email/form templates when selecting multi-site specific templates.
- Fixed an error for Ajax forms using Craft native forms.
- Fixed lack of `fieldError` theme config support for client-side validation.
- Fixed some variables not working for the “Submission Message” setting.
- Fixed email notification previews not working for stencils.
- Fixed word limits of text fields not showing words left correctly.
- Fixed duplicate “Limit” settings for entries field.
- Fixed an error when re-sending a sent notification.
- Fixed CSS Classes field settings being removed when applying `resetClasses` via Theme Config.
- Fixed “Attach Assets” setting for email notifications not working correctly.
- Fixed edge-cases for Google places autocomplete fields not working in some instances due to loading times.
- Fixed CSS Classes field settings being removed when applying `resetClasses` via Theme Config.
- Fixed an error when submitting forms with File Upload fields in a Repeater or Group field.
- Fixed captchas incorrectly rendering multiple times for multi-page forms.
- Fixed edge-cases for Google places autocomplete fields not working in some instances due to loading times.
- Fixed theme config being stored in project config.
- Fixed an error when trying to select existing notifications for a stencil.
- Fixed showing existing fields when editing stencils.

## 2.0.12 - 2022-09-25

### Added
- Added support for Emoji’s in Trello boards and lists.
- Added “Form Handler” endpoint settings to Pardot CRM integration.
- Added “sender” email header setting for email notifications to control email deliverability.

### Changed
- Changed integration Redirect URI’s to no longer rely on `usePathInfo`, instead use a site route.
- Changed Trello integration to not include closed boards.
- Consolidate payload-creation for Webhook/Miscellaneous integrations.

### Fixed
- Fixed an error when editing a submission from the front-end
- Fixed when exporting submissions from “All” custom field values were missing.
- Fixed submitting an incomplete submission from the front not being marked as incomplete.
- Allow non-inline Markdown to be included in field instructions.
- Fixed when editing a submission from the front-end, submission actions weren’t being applied (`enableBackSubmission`, `submitAction`).
- Fixed toggling pages for Ajax-based forms without standard `fui-*` classes.
- Fixed progress bar not working correctly when switch tabs on Ajax-based forms.
- Fixed a visual issue for progress bars when Ajax-based forms failed validation.

## 2.0.11 - 2022-09-18

### Added
- Added email notification preview support for Repeater fields.
- Added `isIncomplete` argument to GraphQL mutations for submissions, allowing partial payloads to be saved via GraphQL.
- Added support for all integrations to define front-end JS via `getFrontEndJsVariables()`.
- Added `form.setIntegrationSettings` function to set integration settings in Twig.
- Added “Filename Format” setting to File Upload fields to allow for renaming of files on upload to a given format.
- Added handle to duplicate fields when exporting submissions, to prevent ambiguity for same-named fields.
- Added min/max field value support for Single-Line and Multi-Line Text fields.
- Added “Save Spam Submissions” setting to captchas to control whether to save spam submissions as the captcha level.
- Added `spamClass` to submissions to record the captcha that marked the submission as spam.
- Added support for `<details>` and `<summary>` tags in HTML field.
- Added warning message for “Redirect URI” setting for OAuth integrations about `usePathInfo = false`.
- Added before/after events when sending payloads for Webhook & Zapier integration.
- Added Azure admin note to Microsoft Dynamics 365 integration.
- Added `formie/forms/delete` console command.
- Added support for Zoho CRM integration to map to a Quote object.
- Added support for Salesforce CRM integration to map to a Case object.
- Added support for Phone fields have their country ISO and country full name be able to be picked when mapping to integrations.

### Changed
- Changed conditions builder’s field column to show 60 characters of field labels.

### Fixed
- Fixed field, page and button conditionals evaluating for blank conditions.
- Fixed an error with Redirect URI’s for integrations that have `usePathInfo = false`.
- Fixed an error being thrown when Trello didn’t have a OAuth token.
- Fixed an error when querying some fields on a Group field with GraphQL.
- Fixed space characters being added to variable picker field values.
- Fixed a type error for integration’s `getOauthProvider()` function, not supporting OAuth1 providers.
- Fixed an error when querying some fields on a Group field with GraphQL.
- Fixed Calculations field evaluating empty formulas.
- Fixed (again) Microsoft Dynamics not using `SchemaName` for custom field handles.
- Fixed Redirect URI for some providers containing the `site` query param.
- Fixed Repeater field and inner-field JS registration.

## 2.0.10 - 2022-09-11

### Added
- Added support for emoji’s in option fields’ labels.
- Added Forms element select field support for Feed Me.
- Added a “disabled” option to Dropdown, Checkboxes and Radio Button field options, to hide options from the front-end, but still retain their values in past submissions.
- Added “Usage” tab to the form builder, to see which elements reference a form.
- Added support for Captchas to use .env variables for their enabled state.
- Added formatting options for Calculations field to better handle numbers.
- Added support for paginated requests for Slack integration.
- Added “Available Days” setting to Date fields to control which days of the week are enabled.
- Added “Year Range” setting for Date fields to control the min/max years when shown as Dropdowns.
- Added limits to Date fields for offset by today.

### Changed
- Updated to use `App::parseBooleanEnv` where applicable.
- Refactored SharpSpring form object serialization.

### Fixed
- Fixed PayWay unsuccessful payments not being marked as failed.
- Fixed PayWay merchant ID not working with .env variables.
- Fixed an error where boolean integration settings couldn’t be set to an .env variable.
- Fixed “Use Sandbox” setting for PayPal not saving correctly.
- Fixed an error when rendering Element fields with multiple options enabled.
- Fixed container attributes for field settings not rendering.
- Fixed Date fields not having their custom error message text shown.
- Fixed limit settings not saving correctly for Number fields.
- Fixed dropdown option labels not correctly set to `formie` as the translation category.
- Fixed min/max date settings not taking into account time for Date fields.
- Fixed browser warnings for some Date fields for invalid formatted values.
- Fixed submissions not showing the correct status details.
- Fixed JS classes not taking into account Theme Config.
- Fixed `getCurrentPageIndex()` returning `null`, when it should return `0` to represent the first page’s index.
- Fixed duplicated lists for Active Campaign integration.
- Fixed Signature fields outputting their raw base64-encoded value when output in email notifications.
- Fixed Signature fields not generating images correctly for some email clients (web-based Gmail) in email notifications.

## 2.0.9 - 2022-09-04

### Added
- Added Westpac PayWay Payment integration.
- Added `prune-content-table-fields` console command.
- Added “Empty Value Placeholder” plugin setting to manage the “No response” text for email notifications.
- Added support for setting the `siteId` for entries selected as redirects.
- Added Form settings to "Require Logged-in User”, “Schedule Form”, “Limit Submissions”.

### Changed
- Payment fields can now use Calculations, Dropdown, Radio and Single-Line Text fields for dynamic amounts.

### Fixed
- Fixed Microsoft Dynamics not using `SchemaName` for custom field handles.
- Fixed serialization of element fields when being sent via Webhooks.
- Fixed an error with HubSpot CRM integration.
- Fixed File Upload fields including some allowed extensions that they shouldn’t.
- Fixed an issue where setting “Alert Emails” created multiple rows.
- Fixed an error with PayPal payments and dynamic amounts.
- Fixed submission titles not being correct when creating submissions in the control panel.
- Fixed an error when deleting a submission.
- Fixed an error with the Campaign email marketing integration.
- Fixed an error running `resave` console commands.
- Fixed an error when processing PayPal payments.

### Removed
- Removed unused form settings for availability (never implemented).

## 2.0.8 - 2022-08-27

### Changed
- Renamed `ModifySubmissionExportDataEvent::data` to `ModifySubmissionExportDataEvent:exportData` to fix an error when exporting submissions.
- Changed `{num} characters/words left` translation string to `{startTag}{num}{endTag} characters left`.

### Fixed
- Fixed Group fields’ inner fields and conditionals referencing other Group inner field’s.
- Fixed Theme Config not working correctly to remove components when setting to `false` or `null`.
- Fixed Multi-Line Text field rich text formatting buttons not always in the correct order.
- Fixed check for malicious file upload checks, causing submissions with File Upload fields not to save.
- Fixed File Upload `inputTypeName` not returning correctly for GraphQL queries.

## 2.0.7 - 2022-08-22

### Fixed
- Fixed element fields not showing disabled sources in field settings.
- Fixed Name and Address sub-fields not pre-populating values from the URL.
- Fixed incorrect order of `formie.field.*`  ending template hooks.
- Fixed an error with element fields in Repeater fields not working correctly.
- Fixed element fields having their placeholder value duplicated when displaying as a dropdown.
- Fixed an error with Payment fields not retaining the Billing Details when saving.
- Fixed an error with Address fields not removing outdated `enableAutocomplete` setting.

## 2.0.6 - 2022-08-16

### Added
- Added `beforeEvaluate` and `afterEvaluate` for Calculations field’s JS.
- Added `form` property to `SubmissionEvent`.
- Added support for Variable Tag nodes when rendering HTML to ProseMirror schema.

### Changed
- When redirecting to a new tab, form values will now be reset.
- Allow `SubmissionController::EVENT_AFTER_SUBMISSION_REQUEST` to alter the submission.

### Fixed
- Fixed an error for Stripe payment integrations that would throw an error when catching Stripe API errors.
- Fixed typings for Payment integration `getAmount()` and `getCurrency()` functions to properly catch errors.
- Fixed an error when not supplying a `submitAction` for a submission.
- Fixed email notifications not getting the default `recipients` type set correctly.
- Fixed error handling on submission exports.
- Fixed Slack public channels not always showing all channels by increasing limit to 100.
- Fixed an error with the Slack integration, when posting via Webhooks.
- Fixed custom error messages not showing for client-side validation.

## 2.0.5 - 2022-08-07

### Added
- Add new HubSpot CRM integration due to [API changes](https://developers.hubspot.com/changelog/upcoming-api-key-sunset).

### Fixed
- Fixed placeholder for Dropdown fields not working correctly.
- Fixed conditionally-hidden payment fields processing payment.
- Fixed being unable to modify element queries for element fields.
- Fixed an error with Payment fields.
- Fixed an error with Payment fields not submitting values correctly.
- Fixed an error with empty Date fields for integrations.
- Fixed `formie/submissions/run-integration` command not prepping the integration settings correctly.
- Fixed notification recipient conditions not populating correctly.
- Fixed an error when duplicating a formConfig.

## 2.0.4 - 2022-07-25

### Changed
- Update Campaign plugin integration to use new `FormsService::createAndSubscribeContact`. (thanks @bencroker).

### Fixed
- Fix an error when deleting a submission.
- Fix some UI elements not working correctly for integration form settings.
- Fix element and Recipients fields’ not passing through a modified namespace.
- Fix Entry integration “Default Entry Author” element select field not working.

## 2.0.3 - 2022-07-20

### Added
- Added “Update Search Index” setting for Element integrations, to control whether search indexes should be updated. Default to `true`.
- Added “Duplicate” form action in the control panel.
- Added `Submissions::EVENT_AFTER_PRUNE_SUBMISSION` event.
- Added ability to set the `async` and `defer` parameters on `<script>` elements for reCAPTCHA and hCaptcha captchas.
- Added hidden reCAPTCHA note to settings.
- Added support for exporting/importing custom field content on forms.
- Added `formie/submissions/run-integration` and `formie/submissions/send-notification` console commands.
- Added `Date::EVENT_REGISTER_DATE_FORMAT_OPTIONS` and `Date::EVENT_REGISTER_TIME_FORMAT_OPTIONS` events to modify the available formatting options for Date fields.
- Added `SubmissionExport::EVENT_MODIFY_EXPORT_DATA` event to modify data used for submission export.

### Fixed
- Fixed an infinite loop error when Agree fields containing a link with a reference to an element was used in a form.

## 2.0.2 - 2022-07-18

### Added
- Added `descriptionHtml` attribute to Agree fields.
- Added `queuePriority` plugin setting.
- Added better visual feedback for queue jobs.
- Added `Submission Date` to variable picker for email notifications.

### Changed
- Exporting submissions now use each fields’ Name instead of Handle.
- Updated `ModifyFormRenderOptionsEvent` typings.

### Fixed
- Fixed some submission attributes not appearing when previewing an email notification.
- Fixed when `themeConfig` is set only at the plugin config level.
- Fixed dropdown save button not working in the form builder.
- Fixed an error when Repeater/Group fields had a corrupted field layout, causing a fatal error.
- Fixed Repeater/Group fields not working correctly when used as variables for email notifications.
- Fixed status dropdown when editing submissions.
- Fixed submissions chart in the control panel.
- Fixed a front-end error when including instructions with Checkboxes fields.
- Fixed an error when creating a form from an outdated stencil.
- Fixed an error when creating new Table fields.
- Fixed an error when bulk-adding content to Checkboxes/Dropdown/Radio fields.
- Fixed auto-focusing on the field name setting when editing a field in the form builder.
- Fixed Group/Repeater nested fields not exporting correctly.

## 2.0.1 - 2022-07-12

### Added
- Added support for `dompdf/dompdf` v2.

### Changed
- Replace deprecated `Craft.postActionRequest()` for JS.

### Fixed
- Fixed an issue where email notification conditions weren’t being saved correctly.
- Fixed newly created date fields not having their `displayType` setting set correctly.
- Fixed element fields not having all their correct field settings applied when rendering.
- Fixed Phone field country flags not appearing the in control panel when editing a submission.
- Fixed an error with input/dropdown formatted Date fields when editing a submission.
- Fixed input/dropdown formatted Date fields not saving their values.
- Fixed an error with input/dropdown formatted Date fields.
- Fixed an error when exporting submissions where a Craft field had the same handle as a Formie field.

## 2.0.0 - 2022-07-11

> {warning} If you are using custom templates, template overrides, or anything to do with front-end template manipulation, please note we have completely revamped our front-end templates. Refer to the [Upgrading from v1](https://verbb.io/craft-plugins/formie/docs/get-started/upgrading-from-v1#templates) guide.

### Added
- Added Stripe payment integration (single and subscriptions).
- Added PayPal payment integration (single).
- Added the ability to include a "Save" button for front-end templates. Buttons can be styled as a button or a link.
- Added ability to query submissions across multiple forms via GraphQL.
- Added `chunkLoadingGlobal` to front-end JS to avoid conflicts with user-provided JS in Webpack.
- Added `Field::EVENT_MODIFY_HTML_TAG` event.
- Added `Form::EVENT_MODIFY_HTML_TAG` event.
- Added `aria-describedby` attribute to `<fieldset>` tags referencing instructions when they are used for fields.
- Added `_includes/alert-error` and `_includes/alert-success` template partials to make it easier to override alert HTML.
- Added `_includes/field-errors` and `_includes/form-errors` template partials to make it easier to override form and field errors HTML.
- Added `_includes/form-title` template partials to make it easier to override form title HTML.
- Added `{{ formtag(key) }}` Twig function to render a form theme component. Supports the same functionality as [tag](https://craftcms.com/docs/4.x/functions.html#tag).
- Added `{{ fieldtag(key) }}` Twig function to render a field theme component. Supports the same functionality as [tag](https://craftcms.com/docs/4.x/functions.html#tag).
- Added `{% fieldtag %}` Twig tag to render a field theme component. Supports the same functionality as [tag](https://craftcms.com/docs/4.x/tags.html#tag).
- Added `{% formtag %}` Twig tag to render a form theme component. Supports the same functionality as [tag](https://craftcms.com/docs/4.x/tags.html#tag).
- Added support for Group and Repeater-nested fields when using `setFieldSettings()` in templates.
- Added `submitAction` to the `SubmissionEvent` to allow you to act on different submission actions like back, save and submit.
- Added `archiveTableIfExists()` to install migration.
- Added checks for registering events for performance.
- Added `FormInterface::submissionEndpoint` for GraphQL queries.
- Added non-namespaced field handle to Calculations formula variables.
- Added `FieldOption` for checkboxes/radio/dropdown fields.
- Added correct type for `MultiLineText::richTextButtons` for GraphQL.
- Added `FormSettings::submitActionMessagePosition` for GraphQL.
- Added `FormSettings::errorMessagePosition` for GraphQL.
- Added `FormInterface::submissionMutationName` for GraphQL.
- Added Feed Me v5 support.

### Changed
- Re-architected front-end templates to be more maintainable, easier to override, easier to manipulate and better organised. Makes it possible to use Tailwind and Bootstrap classes without writing templates from scratch and maintaining them as overrides. Read up on the [changes](https://verbb.io/craft-plugins/formie/docs/theming).
- Changed `Field::getFrontEndInputOptions()` `$options = null` parameter to `$renderOptions = []`.
- Changed `Field::getFrontEndInputHtml()` `$options = null` parameter to `$renderOptions = []`.
- Changed `Field::getEmailOptions()` `$options = null` parameter to `$renderOptions = []`.
- Changed `Field::getEmailHtml()` `$options = null` parameter to `$renderOptions = []`.
- Changed `AddressProvider::getFrontEndHtml()` `$options = null` parameter to `$renderOptions = []`.
- Changed `Form::getFormId()` to respect the form handle casing. For example, previously output `fui-contact-us` now, `fui-contactUs`.
- Changed `ModifyFormRenderOptionsEvent::options` to `ModifyFormRenderOptionsEvent::renderOptions`.
- Changed `ModifyFrontEndSubfieldsEvent::rows` to now receive an array of `HtmlTag` objects instead of plain arrays. 
- Changed `options` variable to `renderOptions` in Email templates.
- Changed `options` variable to `renderOptions` in Form templates.
- Changed Formie JS initializer to no longer rely on `id^="formie-form-"`. Now a `[data-fui-form]` attribute must exist.
- Sub-field (Address, Date, Name) and nested-field (Repeater, Group) now output proper field templating for their sub fields, consistent with their outer-field counterparts. For example, the "First Name" sub-field for a Name field now renders a Single-Line Text field instance.
- Date fields now show correct time formatting visual aid. For example, previously output `23:59:59 (H:M:S)`, now `23:59:59 (HH:MM:SS)` to better show digit count.
- Repeater fields' front-end templates no longer requires `.fui-repeater-rows` and `.fui-repeater-row` classes.
- Repeater fields' front-end templates now requires `data-repeater-rows` and `data-repeater-row` attributes.
- Signature fields' front-end templates no longer requires `.fui-signature-clear-btn` class for the clear button.
- Signature fields' front-end templates now requires `data-signature-clear` attribute for the clear button.
- Summary fields' front-end templates no longer requires `.fui-type-summary` and `.fui-summary-blocks` classes.
- Summary fields' front-end templates now requires `data-summary-blocks` attribute.
- Table fields' front-end templates no longer requires `.fui-table-row` class.
- Table fields' front-end templates now requires `data-table-row` attribute.
- hCaptcha front-end templates no longer requires `.formie-hcaptcha-placeholder` class.
- reCAPTCHA front-end templates no longer requires `.formie-recaptcha-placeholder` class.
- JS Captcha front-end templates no longer requires `.formie-jscaptcha-placeholder` class.
- hCaptcha front-end templates now requires `data-hcaptcha-placeholder` attribute.
- reCAPTCHA front-end templates now requires `data-recaptcha-placeholder` attribute.
- JS Captcha front-end templates now requires `data-jscaptcha-placeholder` attribute.
- Field instructions no longer produce a `<p>` paragraph element.
- All front-end static translations now use **only** the `formie` category. If you're using static translation to translate any text for front-end forms, ensure you move any of these translations in your `site.php` or `app.php` files into `formie.php`.
- Front-end templates now include a `submitAction` hidden input to determine what action to do when submitting the form (`back`, `submit` or `save`).
- Front-end templates now add a `data-submit-action` attribute to all buttons for back, submit and save.
- Removed ajax-loading when switching form templates in the form builder. This should prevent strange UI glitches and simplify some things.
- Migrate to Vite and Vue 3 for performance for the form builder.
- Rename base plugin methods.
- Memoize all services for performance.
- Updated `league/oauth2-google:^3.0` to `league/oauth2-google:^4.0` to support PHP 8+.
- Updated `league/oauth2-client:^2.4` to `league/oauth2-client:^2.6` to support PHP 8+.
- Updated `league/oauth1-client:^1.7` to `league/oauth1-client:^1.9` to support PHP 8+.
- Updated `commerceguys/addressing:^1.0` to `commerceguys/addressing:^1.2` inline with Craft 4.
- Querying fields via GraphQL will now only return fields that do not have Visibility = “disabled”. Change this behaviour by using `includeDisabled: true`.
- Provide better native typing for GraphQL field properties, thanks to PHP 8.
- Now requires Formie `1.5.15` in order to update from Craft 3.
- `FormInterface::fields` is now `FormInterface::formFields` for GraphQL queries.
- `PageInterface::fields` is now `PageInterface::pageFields` for GraphQL queries.
- `RowInterface::fields` is now `RowInterface::rowFields` for GraphQL queries.
- Now requires PHP `^8.0.2`.
- Now requires Craft `^4.0.0-beta.1`.

### Fixed
- Fix hard-error being thrown when positions chosen for labels/instructions no longer exist.
- Fix markdown output of field instructions.
- Fix an error when exporting submissions.
- Fix an error when exporting element fields.
- Fix performance issues for large forms, when loading the form builder.

### Removed
- Removed `AddressProvider::EVENT_MODIFY_ADDRESS_PROVIDER_HTML` event.
- Removed `Field::renderLabel()`.
- Removed `Field::getIsTextInput()`.
- Removed `Field::getIsSelect()`.
- Removed `Field::getIsFieldset()`.
- Removed `_includes/legend` template partial.
- Removed `_includes/errors` template partial.
- Removed “Top of Fieldset” and “Bottom of Fieldset” positions to prevent confusion. These are replaced by Above Input” and Below Input” respectively.
- Removed all class-binding references in JS files.
- Removed `goingBack` and `form.goBack` from front-end templates. Now uses `submitAction` to control when going back.
- Removed `Field::limit`, `Field::limitType`, `Field::limitAmount` on all fields except element fields and single/multi-text fields.
- Removed `Field::columnWidth`.
- Removed `formie/csrf/*` actions.
- Removed `optgroups` from GraphQL queries for dropdown fields.
- Removed `multiple` from Dropdown GraphQL queries.
- Removed `FormSettngsInterface::submitActionUrl` for GraphQL. Use `FormSettngsInterface::redirectUrl`.
- Removed Craft 3 version checks, no longer needed.
- Removed `enableGatsbyCompatibility` plugin setting, as it's no longer needed.
- Removed `forms`, `form` and `formCount` from GraphQL queries. Please use `formieForms`, `formieForm` and `formieFormCount`.
- Removed `submissions`, `submission` and `submissionCount` from GraphQL queries. Please use `formieSubmissions`, `formieSubmission` and `formieSubmissionCount`.

## 1.6.19 - 2022-11-19

### Added
- Added pagination to Monday integration to fetch boards over 100.
- Added `IntegrationField::TYPE_DATECLASS` to handle mapping to Date fields and date attributes for Entry element integrations.

### Changed
- Improve performance of Microsoft Dynamics CRM integration when fetching entity definitions.

### Fixed
- Fixed "Overwrite Values" for element integrations for User photos.
- Fixed return type for Google Sheets integration for `getProxyRedirect()`.

## 1.6.18 - 2022-11-13

### Added
- Added “Overwrite Content” setting for Element integrations to control whether null values should be applied to element content.

### Changed
- Updated to use `App::parseBooleanEnv` where applicable for integration settings.

### Fixed
- Fixed the “Proxy Redirect URI” for Google Sheets not saving correctly when using .env variables.

## 1.6.17 - 2022-11-06

### Added
- Added more clarity to Freeform/Sprout Forms migrations when a submission failed to migrate.

### Fixed
- Fixed visibly disabled fields not having their default value used when populating a submission content.

## 1.6.16 - 2022-10-29

### Fixed
Fixed an error when setting the submission status from the submission element index action.

## 1.6.15 - 2022-10-23

### Added
- Added `descriptionHtml` attribute to Agree fields.
- Added support for integration fields to contain emojis.

### Changed
- Changed integration Redirect URI’s to no longer rely on `usePathInfo`, instead use a site route.

### Fixed
- Fixed cached integration settings containing emojis.
- Fixed Pardot Endpoint URL setting not persisting correctly.
- Fixed integration settings not persisting on page load.
- Fixed word limits of text fields not showing words left correctly.
- Fixed Category fields where children of the selected Root Category weren't returned. (thanks @taylordaughtry).
- Fixed `Captcha::getOrSet` always throws unnecessary warning in logs. (thanks @leevigraham).

## 1.6.14 - 2022-09-25

### Added
- Add “Form Handler” endpoint settings to Pardot CRM integration.

### Changed
- Consolidate payload-creation for Webhook/Miscellaneous integrations.

## 1.6.13 - 2022-09-18

### Fixed
- Fix (again) Microsoft Dynamics not using `SchemaName` for custom field handles.

## 1.6.12 - 2022-09-11

### Added
- Added `formie/sent-notifications/delete` console command.

## 1.6.11 - 2022-09-04

### Added
- Added `prune-content-table-fields` console command.

### Fixed
- Fixed Microsoft Dynamics not using `SchemaName` for custom field handles.
- Fixed serialization of element fields when being sent via Webhooks.
- Fixed an error with HubSpot CRM integration.
- Fixed File Upload fields including some allowed extensions that they shouldn’t.

## 1.6.10 - 2022-08-27

### Added
- Added new HubSpot CRM integration due to [API changes](https://developers.hubspot.com/changelog/upcoming-api-key-sunset).

### Fixed
- Fixed check for malicious file upload checks, causing submissions with File Upload fields not to save.
- Fixed File Upload `inputTypeName` not returning correctly for GraphQL queries.

## 1.6.9 - 2022-08-22

### Added
- Added more logging to Salesforce integration with regards to duplicate lead task-creation.

### Fixed
- Fixed cloning Group/Repeater fields not correctly cloning their inner fields.

## 1.6.8 - 2022-08-17

### Fixed
- Fixed an error introduced in 1.6.7 causing client-side validation not to work correctly.

## 1.6.7 - 2022-08-16

### Added
- Added `beforeEvaluate` and `afterEvaluate` for Calculations field’s JS.

### Fixed
- Fixed File Upload fields not handling invalid POST data send by malicious parties.
- Fixed an error when trying to create a Sent Notification when the body of a notification contained an Emoji.
- Fixed extra space when using `formClasses` for forms.
- Fixed front-end JS not initializing correctly when using custom ID attributes for the form element.

## 1.6.6 - 2022-08-07

### Added
- Added `ipAddress`, `isIncomplete`, `isSpam`, `spamReason` properties to GraphQL queries for submissions.
- Added `isIncomplete` and `isSpam` arguments to GraphQL queries for submissions.
- Added console formatting for Sprout Forms and Freeform migrations.
- Added `form-handle` option to migrate console commands.
- Added `formie/migrate/migrate-freeform` console command.
- Added Mailjet Email Marketing integration. (thanks @jmauzyk).
- Added `assetId` parameter for GraphQL mutations for File Upload data.
- Added support for Emojis for Single-Line & Multi-Line Text fields.

### Changed
- Single-Line and Multi-Line Text fields with limits now allow over-typing above limits, showing negative character/words.

### Fixed
- Fixed migrations not allowing `EVENT_MODIFY_FIELD` event to override fields.
- Fixed an error when migrating notifications for Sprout Forms and Freeform.
- Fixed Sprout Forms migration with custom fields.
- Fixed an error with Repeater/Table fields and row collisions when deleting and adding the same number of rows.
- Fixed an error where min/max dates for Date fields weren’t being set correctly for Flatpickr.
- Fixed an error when disconnecting from an OAuth-based integration when the original token didn’t exist.
- Fixed JS text limits not counting string with emoji’s properly and improve multibyte string checks.
- Fixed JS text limits not working when pasting in content.
- Fixed JS text limits not showing the correct values when server-side errors exist.
- Fixed incorrect string-length calculation when limiting text field values.
- Fixed `formie/forms/refresh-tokens` endpoint not returning captchas. (thanks @cholawo).

### Removed
- Removed `maxlength` attribute on Single-Line and Multi-Line Text fields, due to inability to properly count emojis.

## 1.6.5 - 2022-07-25

### Fixed
- Fix `allowAdminChanges` for integration settings hiding instructions.

## 1.6.4 - 2022-07-18

### Changed
- Make “Upload Location” setting full-width for File Upload fields in the control panel.

### Fixed
- Fix an error when Repeater fields’ JS wasn’t initialized for some fields in the control panel when editing a submission.
- Fix an error with server-side errors not being placed correctly when rendering multiple forms.

## 1.6.3 - 2022-07-11

### Changed
- Changed front-end JS to handle already-loaded page events when initializing.

### Fixed
- Fixed file size calculation mismatch for File Upload fields and server-side validation.
- Fixed pre-populating a Phone field not working.

## 1.6.2 - 2022-07-01

### Changed
- Table field preview in the form builder now always showing at least one row of cells.

### Fixed
- Fixed an error with Categories fields
- Fixed an error with Salesforce CRM integration, when submitting a contact with an email. (thanks @JeroenOnstuimig).
- Fixed IP Address of a submission being overwritten when editing a submission in the control panel.
- Fixed reCAPTCHA integrations not reporting back the spam reason when failing due to score threshold.
- Fixed Flatpickr 12-hour time formats not being set correctly.
- Fixed some integrations not respecting `.env` variable for boolean-like settings.
- Fixed redirect issue when editing a submission in the control panel on a non-primary site.
- Fixed an incorrect validation for Table fields, when “Maximum instances” was set.
- Fixed scroll-to-alert behaviour not working correctly when also hiding the form after success.
- Fixed JS scroll-to-alert factors in `scroll-margin` and `scroll-padding`.
- Fixed server-side validation errors not appearing for Ajax-enabled forms for some fields (multiple file upload, elements).

## 1.6.1 - 2022-06-20

### Changed
- Slack integration channels now sort channels alphabetically.

### Fixed
- Fix file uploads not respecting data retention settings when run via the `formie/gc/prune-data-retention-submissions` console command.
- Fix pruning incomplete submissions and data retention processes not working correctly.
- Fix hidden field values with custom default value containing variables not evaluating values correctly.
- Fix incorrect mutation input type for File Upload fields for GraphQL.

## 1.6.0 - 2022-06-11

### Added
- Added note to integrations when `allowAdminChanges` is disabled.

### Changed
- Now requires Craft `3.7.22+`.
- Switch all lightswitch integration settings to use `booleanMenuField` to support .env variables.
- Update 12-hour time format for Date fields.

### Fixed
- Fixed element integrations and mapped (empty) table fields not working correctly.
- Fixed an error with Phone fields and client-side validation.
- Fixed connection warning notice for integration settings not appearing when toggling lightswitch fields.
- Fixed File Upload fields not validating correctly in multi-page forms.
- Fixed incorrect output of `fui-row-empty` class.
- Fixed attachments in support requests not being attached correctly.

## 1.5.19 - 2022-06-04

### Added
- Added support for uploading files via GraphQL mutations for File Upload files (with `base64` encoded values).
- Added `aria-disabled`, `aria-autocomplete` and `aria-live` for address fields when using an address provider integration.
- Added loading spinner to “Use my location” for address fields when using an address provider integration.

### Changed
- Changed `onFormieCaptchaValidate` JS event to only trigger are client-side validation passes.

### Fixed
- Fixed querying form template fields on a form via GraphQL not working.
- Fixed a JS error when validating Agree fields.
- Fixed email notifications and integrations firing on each page submission when using `EVENT_AFTER_INCOMPLETE_SUBMISSION` and setting `$event->handled = false`.
- Fixed GraphQL field normalization not always being triggered.

## 1.5.18 - 2022-05-28

### Added
- Added `formie/fields/cleanup-field-layouts` console command to help with cleaning up orphaned field layouts.

### Fixed
- Fixed an error when importing a form with an empty page.
- Fixed element integration not supporting Table fields properly.
- Fixed User element integration auto-logging in non-guests.

## 1.5.17 - 2022-05-23

### Added
- Added “Geocoding API Key” for Google Places address provider integration.
- Added “Use Credentials” option for Salesforce CRM integration.
- Added `defaultCategory`,  `defaultEntry`, `defaultProduct`, `defaultTag`, `defaultUser`, and `defaultVariant` to element field GraphQL queries.
- Added ability to prevent returning early from `Submission::EVENT_AFTER_INCOMPLETE_SUBMISSION` with `$event->handled = false`.

### Fixed
- Fixed an error in Postgres when saving a synced field.
- Fixed a possible type error in HubSpot CRM integration with some array fields.
- Fixed a reactivity error when editing a notification with conditions that used a Recipients fields, where options were overwritten in the form builder.
- Fixed `rootCategory` not being typecasted as a category element for GraphQL queries on Category fields.
- Fixed an error for Freshdesk CRM integration for tickets when no custom fields were used. (thanks @Filipvds).
The fix was already present for Contacts.
- Fixed Recipients field not working correctly when used as the source for field/page/notification conditions.

## 1.5.16 - 2022-04-29

### Fixed
- Fixed an error caused by stencil migrations.
- Fixed Repeater and Table new row HTML for GraphQL queries being incorrectly namespaced.
- Fixed an error when updating from Formie pre-1.5.2 regarding stencils.
- Fixed not being able to import Group/Repeater fields correctly.
- Fixed spacing for some HTML elements for front-end forms.
- Fixed an error when applying project config updates with stencils.

## 1.5.15 - 2022-04-23

> {warning} If you are using custom templates, or template overrides, please read through the breaking changes.

### Added
- Added `field` to `ModifyFrontEndSubfieldsEvent`.
- Added support for double opt-in setting for Campaign plugin email marketing integration.
- Added Submission and Form properties to reserved words for field handles.
- Added `Name::EVENT_MODIFY_FRONT_END_SUBFIELDS`.
- Added `Date::EVENT_MODIFY_FRONT_END_SUBFIELDS`.
- Added `Address::EVENT_MODIFY_FRONT_END_SUBFIELDS`.
- Added `Phone::EVENT_MODIFY_FRONT_END_SUBFIELDS`.
- Added missing (previously automated) email templates for some fields.
- It’s now possible to save a “Redirect Entry” for a stencil.

### Changed
- Refactor email/form template rendering to better handle `defaultTemplateExtensions`, and cleanup switching template paths.
- Changed `autocomplete=false` to `autocomplete=off` for CSRF input.

### Fixed
- Fixed event name of modify time format for Date fields. (thanks @xinningsu).
- Fixed being forced to use `.html` for custom email/form templates.
- Fixed being able to create fields with certain reserved field handles.
- Fixed an error when previewing a multi-dropdown field in email notifications.
- Fixed an error when serializing values for conditions, where a form contained a password field.
- Fix redirecting to a new tab not working correctly for Ajax forms.
- Fix an error with Email field validation pre-Craft 3.7.9.

### Removed
- Removed `aria-checked` for checkboxes/radio buttons, which are no longer required and throw HTML validation errors.
- Removed `aria-hidden` from hidden inputs, which are no longer required and throw HTML validation errors.

### Breaking Changes
- For custom templates or template overrides, ensure you replace all references to `{% include ... %}` with `{{ formieInclude() }}` or refer to the [default templates](https://github.com/verbb/formie/tree/craft-3/src/templates/_special/form-template) for the exact syntax. Changes have needed to be made to support some scenarios where custom templates aren't loaded correctly.

## 1.5.14 - 2022-04-15

### Added
- Added `data-field-type` to the field on front-end templates.
- Added `data-field-handle` to the field on front-end templates.
- Added predefined options for some Prospect fields for Pardot CRM integration (Campaign, Prospect Account, boolean fields).
- Added `onAfterFormieEvaluateConditions` JS event.
- Added handling for existing Freshdesk contacts. (thanks @jmauzyk).

### Changed
- Changed “Match Field” validation message to “{field1Label} must match {field2Label}” instead of showing the value.
- Changed `FormieEvaluateConditions` JS event to `onFormieEvaluateConditions`.

### Fixed
- Fixed repeater fields not working when adding more rows.
- Fixed empty spaces being show in `fui-field` classes.
- Fixed User Element integration not automatically logging in the user when auto-activated, and not processed via the queue.
- Update some more fields to correctly using `formieInclude()` to resolve to the correct template when using overrides.
- Fixed Address field custom templates not resolving to the correct sub-field templates when using overrides.
- Fixed Pipedrive CRM integration not mapping Phone fields with a country dropdown correctly.
- Fixed required Password fields for page-reload, multi-page forms throwing validation errors due to the value already having been submitted in a previous page.

## 1.5.13.2 - 2022-04-11

### Fixed
- Fix element field templating throwing an error (again).

## 1.5.13.1 - 2022-04-11

### Fixed
- Fix element field templating throwing an error.

## 1.5.13 - 2022-04-09

### Added
- Added “IP Address” to integrations that require recording it.
- Added true/false options for Salesforce integration when mapping boolean (checkbox) fields.
- Added support for GDPR fields with HubSpot CRM integration for forms.
- Added support for submissions to be made on disabled sites.
- Added support for Captchas for GQL mutations.
- Added spam reason for reCAPTCHA and hCAPTCHA when available.
- Added `setCurrentSite()` to queue jobs for email notifications and integrations to maintain the `currentSite` variable.
- Added `includeScriptsInline` option to `templateHtml` for GraphQL queries.

### Changed
- Improve `renderFormCss()` and `renderFormJs()` to properly capture all CSS and JS files used by the form and field, that would normally be output in the header/footer.
- Using `renderFormCss()` and `renderFormJs()` now no longer relies on the Form Template render location. It will now be output inline, where the tags are included on the page.

### Fixed
- Fixed some fields not able to have their template overrides resolve correctly.
- Fixed an error on pre-Craft 3.7.32 sites, with `SiteIdValidator::allowDisabled`.
- Fixed HubSpot CRM integration not using the correct referrer when mapping to a form.
- Fixed not triggering a fatal error if form settings had become corrupted.
- Fixed integrations and their `tokenId` values getting out of sync with project config.
- Fixed submissions index allowing any submissions to be viewable.
- Fixed email notifications and integrations not retaining the language for the site it was made on, when triggered from the queue.
- Fixed options fields’ default values not working correctly, if they were imported from Freeform.
- Fixed option fields not importing their default value correctly when migrating from Freeform.
- Fixed an error with Freeform migration.

## 1.5.12 - 2022-03-29

### Added
- Added “Developer API” setting for Zoho CRM integration.
- Added error logging for invalid rows.

### Changed
- When creating a new form, users automatically receive the “Manage form submissions” permission for that form.
- Allow Radio Buttons and Dropdown fields to make use of `Field::EVENT_MODIFY_VALUE_FOR_EMAIL`.

### Fixed
- Fixed proper permissions checks for submission viewing/editing.
- Fixed not being able to view any submissions when only “View Submissions” was enabled.
- Fixed “Manage notification advanced” and “Manage notification templates” permissions not propagating for newly created forms.
- Fixed “Create Submissions” permission not applying correctly.
- Fixed “Scroll To Top” form setting not working for single-page forms.
- Fixed User element integrations not working correctly for updating existing users.
- Fixed static values mapped in integrations not being typecasted correctly.
- Fixed Date fields not respecting their date/time formats in email notifications.
- Fixed when switching Form templates, tabs not working correctly in the form builder.
- Fixed general errors when saving a form not being shown to the user.
- Fixed the payload format for Pardot CRM integration.
- Fixed Pardot using incorrect OAuth endpoints for Sandbox requests.
- Fixed Pardot CRM integration creating duplicate prospects in some instances.
- Fixed Pardot CRM integration not correctly checking for duplicates, due to Prospect Upsert API limitations/incorrectness.
- Fixed Constant Contact integration not generating a refresh token.

## 1.5.11 - 2022-03-12

### Added
- Added `FORMIE_INTEGRATION_CC_NEW_ENDPOINT` .env variable for Constant Contact overriding for endpoints.
- Added `Email::EVENT_MODIFY_UNIQUE_QUERY` event to modify the submissions query that determines if an email is unique.

### Fixed
- Fixed when un-marking a submission as spam, not being saved correct (`null` instead of `0`).
- Fixed Pardot CRM integration not working correctly.
- Fixed Pardot integration connection.
- Fixed Salesforce and Pardot multi-picklist fields not formatting data correctly.
- Fixed an error when editing a Form Template in the control panel.
- Fixed a compatibility error with Craft 3.6.x for email field validation.
- Fixed Email Octopus test connection not working correctly.
- Fixed being able to incorrectly pick Optgroups for conditions (field, page, email notifications) values.
- Fixed Calculations field not working correctly for nested and sub fields.
- Fixed server-side validation for conditionally hidden nested fields for Group/Repeater fields.
- Fixed error notice for GraphQL querying for Repeater fields.
- Fixed an error with OAuth-based integrations when an access token isn’t always available.
- Fixed an error with Pardot CRM integration.
- Fixed agree fields’ “Checked Value” not being taken into account when used as a “Opt-in Field” for integrations.
- Fixed being unable to delete a form if its content table has already been removed.
- Fixed long form handles not being validated and truncated correctly.
- Fixed new forms not throwing an error when the content table cannot be created.

## 1.5.10 - 2022-02-27

### Added
- Added Google Places Geocode API proxy to allow API keys with restricted IPs to query the API (from the server, not client).
- Added full exception information to failed email notification error logs.
- Added `Field::EVENT_MODIFY_VALUE_FOR_EMAIL` event (just for Checkboxes at the moment).

### Changed
- Refactor CSS variables for better global overriding, for themed CSS.

### Fixed
- Fixed importing a form, when a field type isn’t supported on the destination install.
- Fixed `currentPageId` not resolving correctly for JavaScript when changing pages.
- Fixed Group fields not performing server-side validation for nested fields.
- Fixed lack of server-side validation for Email fields.
- Fixed Google Places autocomplete not showing error logging for geocoding.
- Fixed some fields when nested in Group fields throwing an error during previewing an email notification.
- Fixed full error logs not being created when previewing an email notification.
- Fixed un-marking a submission as spam in the control panel not working.
- Fixed incorrect permission checking when editing a submission from the control panel.
- Fixed when toggling “Scroll to top” toggling the “Page Progress Position” setting in the form builder.
- Fixed Multi-Line fields allowing an extra `<p>` wrapping tag in email notifications.
- Fixed an error when creating forms with long names (over 64 characters).
- Fixed page conditions wiping content when saving a completed submission.
- Fixed form submissions not being able to be made for Live Preview and Preview requests.
- Fixed form settings set via `setSettings()` not persisting correctly.
- Fixed a potential error with a migration and user permissions.

### Deprecated
- The Constant Contact Email Marketing integration has a change that will require you to migrate your Constant Contact apps. This is due to a change at Constant Contact. [Continue reading](https://developer.constantcontact.com/api_guide/auth_update_apps.html).

## 1.5.9 - 2022-02-14

### Added
- Added option to Salesforce CRM Integration on creating a task when a duplicate lead is encountered.
- Added bulk delete submissions console command.
- Added `disableCaptchas` form setting to disable captchas on-demand in templates.

### Changed
- Removed masking for encrypted content fields for email notifications.
- `Integration::getMappedFieldValue()` is now publicly accessible.

### Fixed
- Fixed Repeater fields with File Upload nested fields, not attaching correctly to email notifications.
- Fixed conditional logic not working correctly for checkboxes/radio fields in some combinations.
- Fixed conditions set in nested fields within Group/Repeater fields not being initialized correctly.
- Fixed when adding fields to a Group or Repeater field in the form builder, not having their `isNested` attribute properly set.
- Fixed Recipients field set to hidden display, showing the un-encoded value in page source.
- Fixed option fields (Checkboxes, Radio, Dropdown) showing option values for Summary fields, instead of their labels.
- Fixed Table fields with date, time and color columns not displaying their content correctly in email notifications, or throwing errors with `valueAsString()` functions.
- Fixed "Unique Value" setting for Email fields, taking into account deleted submissions.
- Fixed a bug when creating Sent Notifications for multiple recipients, only saving the first recipient.
- Fixed Multi-Line Text field email notification templates not including a wrapping `<p>` tag.
- Fixed Recipients field not working correctly with content encryption enabled.

## 1.5.8 - 2022-01-31

### Added
- Added support for sending attachments via multipart request (thanks @jmauzyk).
- Added looser support for `guzzlehttp/oauth-subscriber` to prevent issues with some other plugins (`dukt/twitter`).

### Fixed
- Fixed "All Non Empty Fields" variable in email notifications not working correctly.
- Fixed File Upload fields not showing the filename of an uploaded file in a Summary field, when uploaded no a non-public-url asset volume.
- Fixed some fields not having `No response` set when no value has been entered for email notifications.
- Fixed an error with `getValuesAsJson()` for element fields which contained complex relations.
- Fixed an error when normalising Recipient field values.
- Fixed options fields (Dropdown, Radio, Checkboxes) using option labels as value for `defineValueAsString()` rather than values.
- Fixed an error with Vue Formulate for users using Craft 3.6.x.
- Fixed Summary field showing conditionally hidden fields.
- Fixed Summary field outputting nested field handles for Group and Repeater fields instead of their field name/label.
- Fixed complex "Date Picker Options" not working correctly for Date fields.
- Fixed the default value of some fields not being applied correctly (Date/Time fields).
- Fixed Group and Repeater fields not retaining values when server-side validation fails on subsequent submissions.
- Fixed integrations with custom fields and empty values incorrectly included in payloads to integrations.
- Fixed checkboxes and radio fields not working correctly for conditionals that were non-equal.
- Fixed Freshdesk CRM integration not checking whether contact/ticket objects were enabled or not.
- Fixed Freshdesk CRM integration not sending attachment values correctly.
- Fixed field conditions logic when both Group and nested fields contained conflicting conditions.
- Fixed email notifications having paragraph tags stripped out of their content.
- Fixed Recipients fields not working correctly for conditions, when being used as target values for other field conditions.
- Fixed Recipients field values when previewing an email notification.
- Fixed Recipients field values not being able to access option labels in email notifications.
- Fixed hidden Recipients field values not working correctly when set as an array.

## 1.5.7 - 2022-01-20

### Fixed
- Fixed Freeform migration and Confirmation fields not migrating correctly
- Fixed Recipients field not using the correct "real" values for email notifications and integrations
- Fixed Phone field with country dropdown enabled triggering unload warnings

## 1.5.6 - 2022-01-17

### Fixed
- Fixed when querying submissions on deleted forms.
- Fixed Agree fields when used as conditions, not evaluating correctly.
- Fixed HubSpot form integration not allowing the `EVENT_BEFORE_SEND_PAYLOAD` event to update payload values.
- Fixed Recipients field not being able to use the "Pre-Populate Value" setting.
- Fixed Phone & Date fields not being prepared for integrations correctly.
- Fixed country code dropdown not saving correctly for a Phone field, when using `setFieldSettings()` or `populateFormValues()`.
- Fixed email notifications sending PDF attachments when not enabled, when sending test emails.
- Fixed an error when previewing email notifications containing a File Upload field.
- Fixed an error when importing a form with an invalid `submitActionEntryId` value.
- Fixed Checkboxes field when using `populateFormValues()`.
- Refactor Recipients field handling, simplifying functionality and fully testing.
- Fixed `populateFormValues()` not working correctly when passing in the handle of a form.
- Fixed Categories field not saving its value correctly (due to how Craft's own Categories field works) when categories has a level greater than 1.
- Fixed option fields (Radio, Checkboxes, Dropdown) not having their default values set properly.
- Fixed element fields not having their default value set properly.

## 1.5.5 - 2022-01-08

### Added
- Added `Field::hasNestedFields`.
- Added `getFields()`, `getFieldByHandle()` and `getFieldById()` methods for nested field rows.

### Changed
- Reduce the maximum width of signature images in email notifications.
- Tidy up `getFieldMappingValues()` method for integrations, to ensure "opt-in" field works consistently.

### Fixed
- Fixed being unable to map to sub-fields (Address, Phone, Name) in nesting fields (Repeater, Group) for integrations.
- Fixed handling of sub-field fields (Address, Phone, Name) for integrations.
- Fixed Table field columns not being set to their correct ID when importing a form.
- Fixed form exports not working correctly with Repeater and Group fields.
- Fixed (properly) an incompatibility with Craft 3.7.28 (`FieldLayout::getTabs()`).

### Removed
- Removed `Field::prepValueForIntegration`.

## 1.5.4 - 2022-01-06

### Fixed
- Fixed an incompatibility with Craft 3.7.28 (`FieldLayout::getTabs()`).
- Fixed time-only Date/Time fields not displaying correctly for Dropdown or Inputs display types.
- Fixed opt-in field for integrations not resolving to the correct field for fields in a Group/Repeater.
- Fixed field conditions not working for pre-populated hidden fields.
- Fixed Calculations field not working correctly with Radio Button field values.
- Fixed Calculations fields triggering unload warnings when no value had been changed (on init).
- Fixed an error for fields not containing their `formId` when importing a form.
- Fixed missing error translation string for Phone field, for front-end validation.
- Fixed an error with the recipients field, for dropdown values not working correctly.
- Fixed signature field not working on multi-page, Ajax-based forms or when navigating using page tabs.
- Fixed invalid HTML for signature field.
- Fixed plain-text fields (Single-Line, Multi-Line, Number, Phone, etc) not having their content escaped properly when used in email notifications.

## 1.5.3 - 2021-12-18

### Added
- Added logging for OAuth-based providers when requesting a refresh token.

### Fixed
- Fixed field conditions not working for brand-new forms without saving the form first.
- Fixed new forms created via stencil.
- Fixed summary field not working with Ajax-based forms.
- Fixed an error when importing forms with group/repeater fields.
- Fixed importing forms, and updating an existing one, submission details would be wiped (due to new fields being created).
- Fixed field handles not being truncated to maximum length for database engine.
- Fixed an error when exporting submissions containing an empty Table field.
- Fixed element integrations when mapping an File Upload field to an Asset field.
- Fixed an error during import, due to `dump()` being included incorrectly.
- Fixed `anyStatus()` submission query param not including spam or incomplete submissions.

## 1.5.2 - 2021-12-12

### Fixed
- Fixed User integrations not sending the correct activation email when using the Password field.
- Fixed an error when exporting Table fields with no columns.
- Fixed stencils not saving their `template` and `defaultStatus` correctly in project config.
- Fixed sent notification preview not showing when Craft's debug bar was enabled.
- Fixed form settings (Appearance/Behaviour) not setting correctly when importing forms.
- Fixed required fields not working correctly when exporting forms.
- Fixed OAuth-based integrations not authenticating correctly.
- Fixed Dynamics CRM instructions.
- Fixed Javascript and Duplicate captchas incorrectly flagging as spam for multi-page Ajax forms.
- Fixed an error when submitting a form via Ajax, straight after another submission.

## 1.5.1 - 2021-12-09

### Added
- Added `contentHtml` to render variables for PDF Templates.

### Fixed
- Fixed some integrations (Elements, AWeber, Benchmark, Drop, Sender) not firing correctly.
- Fixed a potential error when attaching files to support requests.
- Fixed Feed Me error when Commerce wasn't installed.
- Fixed an error with Feed Me, when importing into a Phone field.
- Fixed a PHP 8 error when editing a form.

## 1.5.0 - 2021-12-08

### Added
- Added **Calculations** field for creating read-only content based on other fields' content. Supports arithmetic, bitwise, comparison, logic, string, array, numeric and ternary operators, and of course being able to reference other fields.
- Added **Signature** field to allow users to sign with their mouse or finger, saving as an image.
- Added **Password** field for a specialised, encrypted field just for password-saving. Of course, no plain-text saving.
- Added **Summary** field, to show a summary of all fields. Commonly used on the last page of a multi-page form.
- Added Time-only option to Date fields.
- Added "Match Field" field setting to Text, Number, Password and Email fields to enforce validation where two fields need to have the same value.
- Added Feed Me support for Submissions.
- Added import/export functionality for forms.
- Added dedicated support area, so you can submit bug reports and support requests directly to Verbb. Bundles all we need to know about your form.
- Added Klaviyo CRM integration.
- Added Maximizer CRM integration.
- Added Microsoft Dynamics 365 CRM integration.
- Added SugarCRM CRM integration.
- Added Native Forms support for SharpSpring CRM Integration.
- Added Adestra Email Marketing integration.
- Added EmailOctopus Email Marketing integration.
- Added Klaviyo Email Marketing integration.
- Added Loqate Address Provider integration.
- Added Recruitee Miscellaneous integration.
- Added reCAPTCHA Enterprise captcha support.
- Added hCaptcha captcha support.
- Added Snaptcha plugin captcha support.
- Added conditional recipients option for Email Notifications, allowing you to define what recipients receive an email under what circumstances.
- Added support for Element fields to have their values pre-populated via query string.
- Added PDF Templates, allowing you to attach a custom PDF to Email Notifications.
- Added the ability to set a Google Tag Manager payload for every submit button for forms, within the form builder.
- Added statuses to Sent Notifications, along with error messages to identify issues for failed Email Notifications.
- Added support for Group and Repeater fields when using `setFieldSettings()`.
- Added Submission snapshots to record and persist template-level field settings changes.
- Added "Use my location" setting for Address fields with the Google Places address provider integration.
- Added support to add any arbitrary assets to an email notification as an attachment.
- Added better link support for rich text fields.
- Added indicator in the form builder to show fields configured with conditions.
- Added "Allow Multiple" support for element fields when displaying as a dropdown.
- Added `Field::defineValueAsString()` and `Field::getValueAsString()` to consolidate how to represent field values as a string value.
- Added `Field::defineValueAsJson()` and `Field::getValueAsJson()` to consolidate how to represent field values as JSON object.
- Added `Field::defineValueForExport()` and `Field::getValueForExport()` to consolidate how to represent field values when exporting through Craft's export.
- Added `Field::defineValueForIntegration()` and `Field::getValueForIntegration()` to consolidate how to represent field values when sending to an integration.
- Added `Submission::getValuesAsString()`, `Submission::getValuesAsJson()`, `Submission::getValuesForExport()` to better consolidate field values for various operations.
- Added `Field::EVENT_MODIFY_DEFAULT_VALUE` event to allow modification of the default value for fields.
- Added `Field::EVENT_MODIFY_VALUE_AS_STRING` event for all fields.
- Added `Field::EVENT_MODIFY_VALUE_AS_JSON` event for all fields.
- Added `Field::EVENT_MODIFY_VALUE_FOR_EXPORT` event for all fields.
- Added `Field::EVENT_MODIFY_VALUE_FOR_INTEGRATION` event for all fields.
- Added `Field::EVENT_MODIFY_VALUE_FOR_SUMMARY` event for all fields.
- Added `Integration::EVENT_MODIFY_FIELD_MAPPING_VALUES` event for all integrations.
- Added `Miscellaneous::EVENT_MODIFY_MISCELLANEOUS_PAYLOAD` event for all integrations.
- Added `includeDate` property for Date fields.
- Added `getIsDate()`, `getIsTime()`, `getIsDateTime()` methods for Date fields.
- Added `recipients`, `toConditions`, `pdfTemplateId` to Notification model.

### Changed
- Sent notifications are now saved earlier regardless of success, added statuses and records a failed message.
- Refactored all fields to better handle and consolidate how their content values are represented for various operations (exports, integrations, dev API).
- Renamed `Integration::EVENT_PARSE_MAPPED_FIELD_VALUE` event to `Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE`.
- Allow `Integration::EVENT_BEFORE_SEND_PAYLOAD` to modify the endpoint and method for integrations.

### Fixed
- Fixed captchas not showing the correct name in Formie settings.
- Fixed an error with Recipients fields, where an option value was changed previously, and no longer valid.
- Fixed hidden Recipients fields not being classified as a hidden field.
- Fixed Heading fields not being classified as a cosmetic field.
- Fixed the save shortcut when saving a submission in the control panel.
- Fixed incomplete submissions not being able to have their status updated.
- Fixed File upload fields not always having their upload location source/path set.
- Fixed checkboxes fields not populating values correctly.

### Removed
- Removed `Field::serializeValueForExport()` method. Use `Field::defineValueForExport()` for setting or `Field::getValueForExport()` for getting instead.
- Removed `Field::serializeValueForWebhook()` method. Use `Field::defineValueAsJson()` for setting or `Field::getValueAsJson()` for getting instead.
- Removed `Field::serializeValueForIntegration()` method. Use `Field::defineValueForIntegration()` for setting or `Field::getValueForIntegration()` for getting instead.
- Removed `Field::getFieldMappedValueForIntegration()` method. Use `Field::defineValueForIntegration()` instead.
- Removed `SubmissionExport::EVENT_MODIFY_FIELD_EXPORT` event. Use `Field::EVENT_MODIFY_VALUE_FOR_EXPORT` instead.
- Removed `Submission::getSerializedFieldValuesForIntegration()` method. Use `Submission::getValuesForIntegration()` instead.
- Removed `Submission::EVENT_MODIFY_FIELD_VALUE_FOR_INTEGRATION` event. Use `Field::EVENT_MODIFY_VALUE_FOR_INTEGRATION` instead.

## 1.4.28 - 2021-12-06

### Added
- Added "Scroll To Top" appearance setting for forms.
- Added `fui-subfield-fieldset` class to subfield-supporting field templates.
- Added `force` option for `populateFormValues()`.
- Allow `populateFormValues()` to accept a submission or form object.

### Changed
- Update Copper CRM API endpoint.
- Update an error with Copper CRM.
- Update gray colour palette for front-end theme to "cool gray" for more neutral grays.
- Memoize current submission for performance.
- Cleanup and normalise `error` and `btn` CSS variables.

### Fixed
- Fixed loading captchas when editing a submission in the control panel.
- Fixed an error when duplicating a form without user permissions to manage form settings.
- Fixed GraphQL queries for form integration settings not parsing .env variables, and containing unnecessary data.
- Fixed GraphQL queries for `redirectEntry` not resolving the correct site for an entry.
- Fixed when triggering integrations manually for a submission, integration settings weren't properly prepped.
- Fixed when re-triggering a submission, reloading the page when an error occurred.
- Fixed redirect error when saving Settings > Sent Notifications.

## 1.4.27 - 2021-11-27

### Added
- Added `formCount` and `submissionCount` to GraphQL queries for forms and submissions.

### Fixed
- Fixed invalid conditional logic results when "Enable Conditions" was enabled, but no conditional logic provided.
- Fixed a validation error when passing in `pageIndex` with an empty value for submissions.
- Fixed debug tags for ActiveCampaign being incorrectly sent.
- Fixed an error when trying to save a submission in the control panel when "Collect User" was enabled.
- Fixed element fields not showing correctly when previewing email notifications.
- Fixed an error when previewing Group or Repeater fields in email notifications.


## 1.4.26 - 2021-11-23

### Added
- Added `volumeHandle` for GraphQL queries for FIle Upload fields.
- Added more variables to `MailRenderEvent`.
- Added `Emails::EVENT_MODIFY_RENDER_VARIABLES` event.
- Added `Emails::EVENT_BEFORE_RENDER_EMAIL` and `Emails::EVENT_AFTER_RENDER_EMAIL` events.

### Fixed
- Fixed Group and Repeater fields not serializing correctly for Webhook integrations.
- Fixed `Emails::EVENT_BEFORE_SEND_MAIL` event not allowing modification of the email property.
- Fixed element fields not having a properly configure element query when querying via GraphQL.
- Fixed conditions using an empty string not evaluating correctly.
- Fixed a JS error when viewing the submissions index in Craft 3.6.x.
- Fixed page condition typings for GraphQL.
- Revert GraphQL changes made in 1.4.24 causing errors when querying page conditions.
- Fixed "All Fields" in email notification content showing conditionally hidden fields.


## 1.4.25 - 2021-11-14

### Added
- Added Pardot CRM Integration.
- Added more CSS variables for global `fui-` variables, instead of relying on SCSS variables.
- Added type checks to `submit` endpoint to protect against invalid submission requests, preventing bad payload data.
- Added additional validation to captchas when comparing request payloads for valid submissions, preventing bad payload data.

### Changed
- Update `guzzlehttp/oauth-subscriber:^0.6.0` dependancy to work with `guzzlehttp/psr7:^2.0`.
- The `EVENT_BEFORE_SUBMISSION_REQUEST` is now cancelable, to allow submissions to be marked as invalid.

### Fixed
- Fixed querying forms and submissions via GraphQL when only the "View All" permission is set.
- Fixed `EVENT_BEFORE_SUBMISSION_REQUEST` event not persisting submission errors correctly.
- Fixed Google Sheets integration not requesting a refresh token for OAuth handshake.
- Fixed the "Redirect URI" for integrations not taking into account the `usePathInfo` config setting.
- Fixed File Upload fields not always returning the URL for assets for Integrations.

## 1.4.24 - 2021-11-06

### Added
- Added `formie/submissions/api` action endpoint to handle cross-domain submissions using CORS.
- Added server-side validation for File Upload fields and enforcing min/max file sizes.
- Added the `enableGatsbyCompatibility` config option. Enabling it has a side-effect of changing the `fields` property name on the Form GraphQL type to `formFields`.

### Changed
- File Upload fields now completely replaces uploaded files when re-uploading new files into the field.

### Fixed
- Fixed Number fields enforcing min/max values when "Limit Numbers" was disabled and values were entered for min/max limits.
- Fixed not logging fatal errors when rendering custom email templates for fields.
- Fixed an error when trying to delete submissions, where the owner form was also deleted and had an invalid field layout.
- Fixed some trashed submissions not showing in the submissions element index.
- Fixed trashed submissions not resolving to the correct form, if one still exists.
- Fixed deleted incomplete and spam submissions not appearing in the submissions element index.
- Fixed Tag fields incorrectly always saving the first available tag for a submission.
- Fixed Hidden fields not always having the default value set on submission.
- Fixed an error when using a Hidden field within a Group field.
- Fixed a reactive issue when trying to edit a page name in the form builder.
- Fixed validation error for File Upload fields, when navigating back to a previous page in a multi-form, page reload form.
- Fixed server-side validation for File Upload fields and enforcing total number of files.
- Fixed overflow tabs not working, when editing a submission in the control panel.
- Fixed Date field (dropdown and inputs) incorrectly saving timezone information when editing submission through the control panel.
- Fixed user permissions to `forms/refresh-tokens` controller action.
- Fixed an error when failing to save a form occurs.

## 1.4.23 - 2021-10-30

### Added
- Add support for editing the user of a submission, when editing or creating a submission in the control panel.

### Fixed
- Fix hidden fields not having dynamically-set values persisted.
- Fix boolean-configured fields for integrations not being parsed correctly.
- Fix conditions evaluator to better handle equality checks for Checkboxes fields and `is`, `is not` conditions.
- Fix potential error where Spam and Incomplete options from the Submissions Index dropdown were missing.
- Remove unneeded `pageIndex` param in default templates.
- Fix Sprout Forms and Freeform migrations when a default form/email template hasn't been set.
- Add missing attributes to `PageSettingsInterface` GraphQL interface.
- When creating a new submission in the control panel, and collecting the user, assign the current user to the submission.
- Only show the "IP Address" when editing a submission, if the form is set to collect IPs.
- Fix a PHP 8 issue where `pageIndex` wasn't handled correctly when submitting.

## 1.4.22 - 2021-10-22

### Added
- Added CSS variables for better/easier customisation of the Formie Theme CSS
- Added ability to trigger Integrations when editing a submission.
- Added new `formie/forms/refresh-tokens` to allow captchas to work properly for statically-cached sites.
- Added "Show Structure" field setting for Categories fields, to display a "-" character when outputting categories in a - to denote its hierarchy
- Added "Structure" as a order by option for Categories fields
- Added "Root Category" field setting for Categories field, to control which descendant category to start from during output
- Added "Status" column to submissions index
- Added lead to notes object for Pipedrive CRM integration
- Added note to person, organization and deal objects for Pipedrive CRM integration
- Page Tabs now show an error indicator when any of their fields contain errors.
- Redirect URLs now automatically include any query string params.

### Changed
- Incomplete submissions now show a "draft" icon in the submissions index.
- When multi-page forms contain field errors - on the final page submit, we redirect to the first page with an error for ideal UX.

### Fixed
- Fixed GraphQL generator issues in some cases (Gatsby).
- Fixed missing spam reason for failed JavaScript captchas.
- Fixed creating new submissions in the control panel not working correctly.
- Fixed an error where submissions wouldn't receive the default plugin status.
- Fixed Single-line and Multi-line Text fields not working correctly, when limiting via words.
- Fixed Multi-line Text fields not enforcing character limits.
- Fixed Pipedrive CRM integration with deprecated leads note handling.
- Fixed forms always redirecting if the current URL contained a query string.

### Deprecated
- Deprecated all `formie/csrf/*` action endpoints. Refer to the [updated docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms) on handling static cached forms.

## 1.4.21.1 - 2021-10-18

### Fixed
- Fixed an error with Gatsby Helper plugin (typo introduced in 1.4.21).

## 1.4.21 - 2021-10-17

### Added
- Added support for submissions to be created via the control panel.
- Added a `createSubmissions` permission for submissions.
- Added support for Hidden fields to have their "Custom Value" set to other fields or special variables.
- Added "Send Email Notification" function when editing a submission, or from the submission index.
- Added `spamEmailNotifications` plugin setting to enable email notifications to be sent, even when a submission is marked as spam.
- Added tags support to ActiveCampaign Email Marketing integration.
- Added date picker options field settings for Date fields.
- Added support for UI elements for Form Template fields.
- Added `EVENT_MODIFY_DATE_FORMAT` and `EVENT_MODIFY_TIME_FORMAT` events to control the date/time formatting for Date fields.
- Added "None" position for error and success message options.
- Added `enableCsrfValidationForGuests` setting to disable CSRF validation for submissions, specifically for guests.

### Changed
- When adding new values to Dropdown, Radio and Checkboxes fields via the "Bulk Options" utility now appends any options defined, instead of removing any existing options in the field settings.
- Change `getClient()` and `request()` methods from `protected` to `public` to allow third-parties to utilize Guzzle clients and requests for integrations in their own modules and code.
- Remove abandoned `hoa/ruler` dependancy, used for conditional logic rules parsing.
- Update the `cpEditUrl` for submissions to include the form handle.
- Submissions now always return a default status (according to form defaults).

### Fixed
- Fixed an error with Gatsby Helper plugin.
- Fixed a PHP deprecation notice with Freeform migration.
- Fixed recipients fields not working correctly when populating a hidden field with multiple values.
- Fixed prune functions not taking into account timezone and comparing UTC dates correctly.
- Fixed an error when trying to create a form with a long title, generating an invalid handle.
- Fixed content-change warning when using a default value for a Date field, with Flatpickr enabled.
- Fixed client-side validation triggering for hidden fields in some instances (Flatpickr Date fields).
- Fixed a JS error when using a Tags field.
- Fixed an error when sending the payload for a Webhook integration.
- Fixed when using a Checkboxes field with a single value for the Opt-In Field for an integration not working correctly.
- Fixed an error with email notifications when emails contain Unicode control characters, unassigned, private use, formatting and surrogate code points.
- Fixed when calling `EVENT_DEFINE_RULES` of a submission, where a rule contained a field that didn't exist on the owner form.

## 1.4.20 - 2021-10-12

### Added
- Added `verify = false` to Guzzle requests for Webhook integrations, when `devMode` is enabled.
- Added `EVENT_BEFORE_SUBMISSION` and `EVENT_BEFORE_INCOMPLETE_SUBMISSION` events.
- Added `EVENT_BEFORE_SPAM_CHECK` and `EVENT_AFTER_SPAM_CHECK` events.
- Improved error message when email notification body content returns no content. Some email providers hard-fail when trying to send an empty email.

### Fixed
- Fixed Agree fields not working correctly for Email Notification conditions.
- Fixed Dropdown fields not working correctly for Email Notification conditions.
- Fixed Date fields not working correctly for Email Notification conditions.
- Fixed Group and Repeater fields not working correctly for Email Notification conditions.
- Fixed Table fields inside a Group field not saving correctly.
- Fixed an issue where Group fields, inner fields would receive the incorrect namespace, when validation for the form page failed and page reload enabled.
- Fixed an error for the Webhook integration in some cases when an error occurs.
- Fixed Tag fields and their `beforeInit` options not being applied to Tagify.
- Fixed Date fields and their `beforeInit` options not being applied to Flatpickr.
- Fixed `relations` in POST requests for submissions being always present, when not always needed.
- Fixed `extraFields` in POST requests for submissions being always present, when not always needed.
- Fixed an error with `contains`, `startswith` and `endswith` field conditions, when dealing with empty values.
- Fixed Page Reload forms not evaluating conditions in Group fields, for multi-page forms.
- Fixed Group fields not evaluating field conditions correctly when sending email notifications.
- Fixed sub-fields within Group fields not working correctly for variable picker, for email notifications.
- Fixed when attaching the PDF to an email notification, can sometimes clear the body content of the email.
- Fixed Phone number field values sometimes showing `()` when a country code wasn't provided.
- Fixed `populateFormValues` not working with Phone fields.
- Fixed a deprecation when calling `populateFormValues` when populating elements fields.
- Fixed individual permissions for submissions not working for user permissions.
- Fixed Agree fields not using their "Checked/Unchecked Value" values in integrations, when the destination field in the integration allows string text.

## 1.4.19 - 2021-09-30

### Fixed
- Fixed an error when garbage-collecting orphaned fields, where globally-context fields with the same handle would have their content columns removed. This only affects installs where a Formie field and non-Formie field have the same handle **and** the Formie field has been marked as orphaned (the owner form has been deleted). As such, this should only happen in rare circumstances.
- Fixed email fields set to have unique values not working correctly for multi-page forms.

## 1.4.18 - 2021-09-26

### Added
- Added `beforeInit` and `afterInit` JS events for Tag fields.

### Fixed
- Fixed Date fields (calendar and date picker) not saving time values correctly.
- Fixed Email fields with "Unique Value" set throwing an error on Craft 3.7+.
- Fixed Date fields throwing an error when used in the submission element index columns.
- Fixed Name and Address fields not having their instructions position set correctly for new fields.
- Fixed Date fields not always returning a formatted date as a string, when used in integrations.
- Fixed Mailchimp email marketing integration not casting phone numbers to the correct type in some instances.
- Allow element fields to modify the element query in templates.

## 1.4.17 - 2021-09-17

### Added
- Added `onFormieCaptchaValidate` and `onAfterFormieValidate` JS events.
- Added support for user photo uploading (via File Upload fields), for User element integrations.

### Changed
- Changed reCAPTCHA captchas now use `onFormieCaptchaValidate` to hook into validation, allowing third-party handling of validation events for JS.

### Fixed
- Fixed placeholder text for Phone field in the form builder, when country dropdown was enabled.
- Fixed checkbox fields having their default options set when editing a submission.
- Fixed submission titles not generating correctly in some circumstances.
- Fixed Recipients dropdown field when an option has no value, not validating correctly.

## 1.4.16 - 2021-09-13

### Added
- Added `FORMIE_INTEGRATION_PROXY_REDIRECT` env variable for integrations.
- Added `Current URL (without Query String)` option for hidden fields.
- Added ability for hidden fields to set their column type, to assist with capturing large field values.

### Changed
- Changed Freefom migrations for a HTML field to use `hash` as the field handle instead of a randomly generated handle.
- Changed Freefom migrations for a HTML field to use `HTML` as the field name.
- Freeform/Sprout Forms migrations now auto-prefix fields that have reserved words as their handle.

### Fixed
- Fixed migrated email notifications not respecting the "Default Email Template" plugin setting.
- Fixed migrated forms not respecting the "Default Form Template" plugin setting.
- Fixed an error when migrating a Freeform form, containing a HTML field.
- Fixed Freeform/Sprout Forms migrations when fields contain invalid characters.
- Fixed "Undefined variable" error when failed email notifications with attachments throws an error itself.
- Fixed an error when running garbage collection on deleted forms.
- Fixed an error when trying to migrate "all" Freeform forms.
- Fixed Phone field country dropdown throwing an error when a default country was picked, but not included in the "Allowed Countries".
- Fixed an error when saving a submission from the command line.
- Fixed Checkboxes field not applying default checkboxes.

## 1.4.15 - 2021-09-04

### Added
- Added support for user-based variables in email notifications to support the recorded user on the submission when "Collect User" is enabled on forms.
- Added option to Mailchimp integration to append tags. (thanks @boundstate).
- Added support for Zoho CRM `jsonarray` field types.
- Element field values used in integrations can now include disabled elements.

### Fixed
- Fixed checkbox validation not working correctly when "Validate When Typing" was enabled.
- Fixed conditions used in forms triggering the "content changed" unload warning, when nothing has changed.
- Fixed element fields when used in conditional rules not working correctly.
- Fixed group fields not displaying values correctly, or saving properly in submissions.
- Fixed `populateFormValues()` changing the current language for multi-site installs.
- Fixed multi-page forms when marked as spam on a page, not being able to finalise submission.
- Fixed Phone field country dropdown throwing an error when live validation is set for the form.
- Fixed Phone field country dropdown throwing an error when a default country was picked, but not included in the "Allowed Countries".
- Fixed some failed queue jobs for integrations storing large amounts of cache data when not needed.
- Fixed an error when applying project config, with a stencil with the (incorrect) value of `defaultStatusId = 0`.
- Fixed table fields in notification emails not rendering correctly when containing time or date columns.
- Fixed bouncer.js not processing grouped checkboxes correctly.
- Fixed bouncer.js not properly listening to checkbox change events.
- Fixed element fields not having their "Label Source" and "Options Order" settings use "Title" as the default for new fields.

### Removed
- Removed conditional handling for fields when editing a submission. Too complicated to handle both front-end fields and Craft fields.

## 1.4.14 - 2021-08-17

### Fixed
- Fixed recipients field values not saving correctly.

### Removed
- Removed `columnWidth` from GraphQL queries (it did nothing).

## 1.4.13 - 2021-08-09

> {warning} Please read through the Breaking Changes before updating.

### Added
- Added `field.getHtmlDataId()` which replaces `field.getHtmlId()`.
- Added `data-fui-id` attribute to all inputs for default templates.
- Added `typeName` and `inputTypeName` to the FieldInterface for GQL queries.
- Added `prefixOptions` to Name field for GraphQL queries.
- Added `countryOptions` to Address field for GraphQL queries.
- Added `CsrfTokenInterface` to GraphQL `FormInterface` for easier fetching of CSRF details.
- Added `countryOptions` to Phone field for GraphQL queries.
- Added some error-handling messages to failed-to-parse integration settings.
- Added `users` to User fields for GraphQL queries.
- Added `tags` to Tag fields for GraphQL queries.
- Added `entries` to Entry fields for GraphQL queries.
- Added `categories` to Category fields for GraphQL queries.

### Changed
- All field inputs now have a `data-fui-id`, which replaces the `id` attribute which has been updated to be unique.
- For multi-page, page reload forms, every page is now rendered, and all page data is submitted. This is now the same behaviour as Ajax-based forms. Validation still only occurs every page submission.
- Changed `Phone::getCountries` to `Phone::getCountryOptions`.
- Changed `Address::getCountries` to `Address::getCountryOptions`.

### Fixed
- Fixed an error when fetching a submission via its `uid`, not populating form attributes correctly.
- Fixed rendering the same form multiple times would lead non-unique labels. Causing issues for checkbox/radio/agree fields.
- Fixed rebuild project config not typecasting some settings correctly (therefore showing changes).
- Fixed multiple rows in table fields not saving correctly.
- Fixed multi-page, page reload forms with conditions not working correctly, when page or field conditions are based off previous page values. Ajax-based forms do not have this issue.
- Fixed progress value being incorrect for Ajax-based forms, when clicking on page tabs.
- Fixed Name fields incorrectly casted as NameInputType for GraphQL mutations.
- Fixed Recipients field reporting radio options as multiple.
- Fixed Table fields not sending the correct input type for GraphQL mutations.
- Fixed Recipients field incorrectly encoding options for submissions in the control panel, throwing an error.
- Fixed Recipients field not casting to the correct type for Checkboxes for GraphQL.
- Fixed `FormSettings::submitActionMessageTimeout` not casting as an int.
- Fixed Active Campaign CRM integrating overwriting fields for contacts when mapped but no value set.
- Fixed integrations throwing an error for some fields.
- Fixed hidden fields configured with a cookie value, getting `undefined` set if the cookie didn't exist.
- Fixed the Prefix for a Name field defaulting to the first option when viewing a submission.
- Fixed GraphQL mutation validation errors not always returning a JSON string.
- Fixed GraphQL submission mutations not validating correctly.

### Breaking Changes
- If you rely on the `id` attribute of any `<input>` or `<select>` element on the front-end, these have been changed in order for them to be truly unique. For instance `fields-formie-form-formHandle-formField` now becomes `fields-formie-form-56526107b0a3e1eb3-formHandle-formField`. Please instead use the `data-fui-id` attribute for the old value if you need it.

## 1.4.12 - 2021-07-28

### Fixed
- Fixed data-encrypted fields incorrectly using their encrypted content for integrations.
- Fixed an error when triggering an integration queue job for a non-existant submission.
- Fixed an error with logging element integration payload fields.
- Fixed Craft 3.6 incompatibility with Craft 3.7 changes.

## 1.4.11 - 2021-07-25

### Added
- Added `email`, `notification`, `notificationContent` and `submission` to email notification queue jobs, to assist better with troubleshooting failed queue jobs.
- Added `payload` to integration queue jobs, to assist better with troubleshooting failed queue jobs.

### Fixed
- Fixed handling of element integrations where their objects are too complex for queue-logging.
- Fixed element integrations not firing `EVENT_BEFORE_SEND_PAYLOAD` and `EVENT_AFTER_SEND_PAYLOAD`.
- Removed incorrect placeholder attribute from Agree field input.
- Fixed some email notification fields not filtering out incorrect values like emojis.

## 1.4.10 - 2021-07-15

### Fixed
- Fixed Craft 3.7+ incompatibility when creating new fields.
- Fixed potential issue with sent notifications not saving when long values are used for "From Name".
- Fixed Date fields not getting correct "fake" values when previewing an email notification.
- Fixed agree fields conditions, when trying to evaluate an "Unchecked" state.
- Fixed Group and Repeater fields not having their fields reset if being conditionally hidden with previous content.
- Fixed conditions JS not outputting when only page-based conditions have been created.

## 1.4.9 - 2021-07-11

### Changed
- Removed `from` email from Contact Form stencil. This will default to the system email, and should be used generally for better deliverability.
- Update Mailchimp integration to use `status_if_new` when using “Double Opt-in”.

### Fixed
- Fixed global sets not being site-aware for multi-sites, when used in email notifications as variables.
- Fixed an error that causes variables for email notifications to be incorrectly parsed.
- Fixed address and name required subfields missing `fui-field-required` class for default templates.
- Fixed reCAPTCHA v2 invisible captcha capturing tab autofocus in a form.
- Fixed reCAPTCHA v3 capturing tab autofocus in a form.

## 1.4.8 - 2021-07-03

### Added
- Added support for more error message details for failed email notifications (for Craft 3.7 beta and greater only).
- Added `redirectUrl` and `redirectEntry` to GraphQL FormSettings interface.
- Added “Save as draft” option for submissions to save as a draft state.

### Changed
- Changed behaviour for conditionally-hidden fields, which now set their value to `null` on submission.

### Fixed
- Fixed multi-page forms not hiding page tab, if conditionally hidden/shown based on field conditions.
- Fixed group field conditions not working correctly when revisiting a completed page, on a multi-page form.
- Fixed Group/Repeater inner field conditions not initialising JS correctly, if no other conditions set for the form.
- Fixed date fields not saving values when format is not set to “YYYY-MM-DD” and using the Flatpickr datepicker.
- Fixed element integrations not handling decimals when mapping to number fields.
- Fixed some integrations not casting numbers to floats, when they should be.
- Fixed element integrations throwing an error when trying to catch errors.
- Fixed fields set to Enable Content Encryption showing their content in email notifications.
- Fixed potential GQL issue for Repeater/Group fields when querying.
- Fixed repeater fields throwing an error in email notifications.
- Fixed Group/Repeater fields not saving very long field names, when a database table prefix was set.
- Fixed not showing form errors correctly, in some rare cases.
- Fixed nested field (in Repeater/Group) validating handles incorrectly, where an outer field and inner field couldn’t have the same handle (which is valid).
- Fixed Agree field inside Group/Repeater fields being unable to select value when used in conditionals.
- Fixed submit button edit modal showing “Missing Field” incorrectly.

## 1.4.7 - 2021-06-19

### Added
- Added more logging for mailer-based errors for failed email notifications.

### Fixed
- Potential fix for Google Sheets not inserting into correct columns in some instances.
- Fixed minor alignment for field mapping table text.
- Fixed Google Sheets integration not working correctly when switching between multiple sheets. Please ensure you refresh your form integration settings.
- Fixed a JS error for client-side validation when using custom form rendering.

## 1.4.6 - 2021-06-13

### Added
- Added extra logging output for failed field email rendering.
- Added “Submission” column to sent notifications index.
- Added “Email Notification Name” column to sent notifications index.
- Added `notificationId` to a sent notification, ensuring we keep track of when notification was sent.
- Added name of email notification to logging when sending fails.
- Editing a field in the form builder now shows its field type.

### Changed
- Update some UI elements to better fit CP UI colours.
- Changed User element integrations to import new users as pending.
- Changed `formie-manageForms` permission to `formie-viewForms`.
- Allow `fieldNamespace` form render setting to be `false` to exclude the default `fields` namespace for input name attributes.

### Fixed
- Fixed being able to remove static table field rows in front-end forms.
- Fixed table field dropdown columns not having their options saved for brand-new table fields.
- Fixed table fields not getting the correct defaults when adding new columns.
- Fixed an error when viewing a preview of a sent notification, in some cases.
- Fixed Google Sheets integration not working correctly.
- Fixed an error when trying to disconnect from a OAuth-based integration.
- Fixed field conditions not working correctly, when an entire page is conditionally hidden.
- Fixed conditionally hidden fields having custom validation rules triggered, when they shouldn’t be validated at all.
- Fixed an error when Phone fields are conditionally hidden, and required.
- Fixed namespace issue (due to new `{% script %}` tag) for Repeater and Table fields.
- Fixed an error for table field rows.
- Fixed Table and Repeater fields by switching back row templates to `script` but still works properly with Vue3 (the original issue).
- Fixed requiring edit permissions to select forms/submissions from fields in entries.
- Fixed an error of Dropdown fields where toggling “Allow Multiple” would produce an error.
- Fixed some fields with hidden labels rendering a hidden `<legend>` element twice.
- Fixed exporting Repeater/Table fields not working correctly when submissions had variations in the rows.

## 1.4.5 - 2021-05-30

### Added
- Added `fui-row-empty` class to rows that only have hidden fields.
- Added `craft.formie.getVisibleFields()` to return the number of non-hidden fields for a given row.
- Added “All” checkbox option when migrating Sprout Forms of Freeform forms.
- Added `formiePluginInclude()` twig function to allow including stock Formie form templates.
- Added ability to set a cookie value to the default value of a hidden field.
- Added GDPR marketing permissions to Mailchimp email marketing integration.

### Changed
- Moved `data-conditionally-hidden` styles to core CSS (rather than theme CSS).
- Moved layout styles for form buttons to core CSS, rather than theme CSS. Opinionated styles still kept in theme.
- HTML fields now have their label set as hidden by default.
- Updated file upload file location instruction text.

### Fixed
- Fixed an error when trying to save sent notifications, where `body` and `htmlBody` were more than 64kb.
- Fixed Recipients field values not populating `Single/MultiOptionFieldData`, providing access to option labels and values.
- Fixed File Upload exports not exporting the filename of an asset, when `Assets in this volume have public URLs` setting was turned off for a volume.
- Fixed template layout error when changing the form template for a form.
- Fixed sprout forms migration for HTML and Section fields, where their label was hidden.
- Fixed sprout forms migration showing the incorrect number of notifications to migrate.
- Fixed some breadcrumb links in settings pages.
- Fixed HubSpot multiple checkbox fields not having their values prepared correctly.
- Fixed HubSpot single checkbox fields not having their value prepared correctly.
- Fixed HubSpot integration not assigning correct field mapping types for single checkbox and date fields (from HubSpot).
- Fixed reCAPTCHA errors when Theme JS is disabled.
- Fixed potential error in page-compare templates for ajax-based forms, for PHP 7.4+.
- Removed duplicate Vue dependancy, causing some conflicts with other plugins using Vue.

## 1.4.4 - 2021-05-10

### Added
- Added “User” column to submissions index.
- Added `EVENT_MODIFY_FORM` to Sprout Forms/Freeform migrations.
- Added `EVENT_MODIFY_NOTIFICATION ` to Sprout Forms/Freeform migrations.
- Added `EVENT_MODIFY_SUBMISSION ` to Sprout Forms/Freeform migrations.
- Allow table field column headings to contain markdown.
- Allow table field column headings to be site-translated.

### Changed
- Change behavior for multi-page ajax forms to reset to the first page on success (when showing a message).
- Update `EVENT_MODIFY_FIELD` for Sprout Forms/Freeform migrations.
- For ajax-enabled forms, clicking on tabs (on the front-end) now navigates directly to that page.
- Allow text field-based field settings to have more height in the form builder.
- Update default “Contact Form” stencil to have instructions show “Above Input”.
- Update default instructions position to “Above Input”.
- Update `<th>` styles for front-end table fields.

### Fixed
- Fixed an error when trying to submit an ajax-enabled form again, without refreshing the page.
- Fixed editing an incomplete submission on the front-end, not completing after final submission.
- Fixed `includeInEmail`, `enableContentEncryption` and `enableConditions` GraphQL type definitions.
- Fixed date and time fields within Table not working correctly.
- Fixed `formie_relations` db table not being removed on uninstall.
- Fixed a potential PHP error when trying to find the current page index for a form.
- Fixed SproutForms migration not including field instructions.
- Fixed focus state borders for tabs in the control panel.
- Fixed layout issue for conditions builder with very long field names.
- Fixed current page not persisting when clicking on a tab for an ajax form.
- Fixed new rows for table fields not rendering correctly.
- Fixed some JS errors in the form builder when editing a table field.
- Fixed JS warning in form builder when editing field conditions.
- Fixed variable tag fields not displaying correctly when long text is provided.
- Fixed variable-picker not displaying options correctly if supplied with long field names.
- Fixed instructions showing multiple times for element fields.
- Fixed instructions showing multiple times for recipients field checkboxes.
- Fixed table field instructions position.
- Fixed not being able to search forms via their handle in the control panel.
- Fixed an error when sending a test notification, with for notifications with long subjects.
- Fixed an error with submissions widget when using custom date ranges.
- Fixed non-calendar date fields incorrectly storing timezone information.
- Fixed non-calendar date-only fields incorrectly storing current time information.
- Fixed showing a single row for table field preview in the form builder, when no defaults set.
- Fixed not showing minimum rows for table field preview in the form builder.
- Fixed table field containing invalid extra data, due to Vue3 compatibility change.
- Fixed repeater field containing invalid extra data, due to Vue3 compatibility change.
- Fixed date field email incorrectly using timezone information.
- Fixed non-calendar date fields not producing correct email content values.
- Fixed conditions builder not being able to pick values for certain fields (dropdown, radio, checkboxes) when they were in a Group field.

## 1.4.3 - 2021-04-28

### Added
- Added `EVENT_BEFORE_SUBMISSION_REQUEST`.

### Changed
- Updated Date field to show Time field for a calendar view, only if opting-in to Flatpickr (which includes it).
- Disabled autocomplete on Date fields in “Calendar” view, which obscures the date picker.
- Removed additional time field when “Calendar” is picked for a Date field.
- Updated Google Sheet instructions.
- Updated “Proxy Redirect URI” docs URL.

### Fixed
- Fixed plugin-disabled fields not working correctly, once they’ve been disabled (being unable to re-enable).
- Fixed Date field values not saving correctly when using “Calendar”.
- Fixed empty `formie.yaml` file being created on project config rebuild events.

## 1.4.2 - 2021-04-24

### Added
- Added `mergeUserGroups` option for User element integrations to allow merging of existing user groups, if updating an existing user.
- Added “Interest Categories” support for Mailchimp integration.
- Added `disabledFields` plugin setting to control any globally disabled fields for the form builder.

### Changed
- Changed `limit` to `limitOptions` for element fields, to allow for both limiting the options available in fields, but also how many can be selected.

### Fixed
- Fixed being unable to fetch submission fields directly via `submission.fieldLayout.getField(fieldHandle)`.
- Fixed multi-line text fields not showing its field label when using “All Fields” in email notifications.
- Fixed some errors thrown in Freeform migration due to unsupported fields.

## 1.4.1 - 2021-04-21

### Added
- Added `includeInEmail`, `enableConditions`, `conditions`, `enableContentEncryption`, `visibility` to FieldInterface for GraphQL.
- Added “Spam Reason” and “IP Address” to available columns when viewing submissions in the control panel.

### Fixed
- Fixed potentially fetching the incorrect form for a submission, in some cases.
- Fixed an error when saving a submission through the control panel.

## 1.4.0.1 - 2021-04-21

### Fixed
- Fixed fatal errors when installing from a fresh install.

## 1.4.0 - 2021-04-20

> {warning} Please read through the Breaking Changes before updating.

### Added
- Added field conditions, to conditionally show/hide fields according to your logic.
- Added page button conditions, to conditionally show/hide next button according to your logic.
- Added page conditions, to conditionally show/hide page according to your logic.
- Added all-new page settings manager for form builder. Allows for more settings and flexibility going forward.
- Added “Min Date” and “Max Date” options for Date fields.
- Added “Enable Content Encryption” setting on Address, Email, Hidden, Multi-Line Text, Name, Phone, Recipients and Single-Line Text fields. This will encrypt submission content in the database, preventing human-readable data for sensitive fields.
- Added “Unique Value” to Email field, to control users filling out a form only once.
- Added “Visibility” setting to all fields. Allows you to set any field to hidden, or exclude from rendering. Visibly disabled fields can still have their content set through your templates with `craft.formie.populateFormValues()`, but the benefit is this content is not exposed in front-end templates.
- Added “Predefined Options” to Checkboxes, Radio and Dropdown fields. Select from 25 predefined options, or provide your own through events.
- Added “Bulk Insert” to Checkboxes, Radio and Dropdown fields.
- Added “Recent Submissions” dashboard widget. Provides table, pie or line charts of recent submissions for a provided date range.
- Added `System Name` to available variables for variable picker.

### Changed
- Formie now requires Craft 3.6+.
- Date fields can now use [Flatpickr.js](https://flatpickr.js.org/) when rendered as a calendar.
- Date fields can now content-manage their date and time format.
- Rename `field->getIsVisible()` to `field->getIsHidden()`.
- Change syntax for populating element fields, when using `populateFormValues()`.
- Removed duplicate “Pre-populate” field settings for Hidden fields

### Fixed
- Fixed JS errors showing in form builder error alert.
- Fixed page errors not showing on page labels in the control panel form builder.
- Fixed page models being re-created after saving the form multiple times.
- Fixed form page and row IDs being stripped upon failed validation in the form builder.
- Fixed potential issue with table input in form builder, when rows don't have proper IDs.
- Fixed page settings getting re-created unnecessarily.
- Fixed clicking on page tabs on the front-end not working correctly.
- Fixed an incompatibility with PHP 8.
- Fixed reCAPTCHA v2 Checkbox working incorrectly for Ajax-based, multi-page forms with client-side validation enabled.
- Fixed escaping HTML in rich text field for email notifications.
- Fixed an error with empty Date fields, when formatted as inputs.
- Fixed an error with Hidden fields using “Query Parameter” and an empty string as a value.
- Fixed Checkboxes fields outputting all options in email notifications.

### Removed
- Removed `craft.formie.getVisibleFields()`.

### Breaking Changes
- If you use `craft.formie.populateFormValues()` in your templates to populate **element fields**, please note the changed syntax via the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/populating-forms#element-fields). This has changed from `entriesField: craft.entries.id(123)` to `entriesField: [123]`.

## 1.3.27 - 2021-04-11

### Added
- Added extra error-catching to send notification queue job.
- Added `resave/formie-submissions` and `resave/formie-forms` console commands.

### Changed
- Update translation strings.

### Fixed
- Fixed `completeSubmission` buttons not working correctly with client-side validation enabled.
- Fixed an error when un-registering fieldtypes.
- Fixed lack of redirect support for Page Reload forms, when `completeSubmission` is used.
- Fixed auto-handle generation for forms and fields producing incorrect values when starting with a number.
- Fixed missing `type` attribute on form fields.
- Fixed form builder fields having their IDs stripped from requests, when validation fails, causing sync issues.
- Fixed Group or Repeater nested fields not getting unique handles.
- Fixed Rich rich text link editing not working.
- Fixed CC and BCC showing emails incorrectly for email notification previews.
- Fixed heading showing field label in edit submissions in control panel.
- Fixed Heading, HTML and Section fields appearing in exports as columns.
- Fixed being unable to select site-specific entries for “Redirect Entry”.
- Fixed Sendinblue email marketing integration throwing an error when only email address is mapped.

## 1.3.26 - 2021-04-02

### Added
- Added `autocomplete` option to Address field, for use in GraphQL.
- Added payload info to integration logging.
- Added `FormIntegrationsInterface` for GraphQL, to return information of integrations for a form.
- Added `notification` and `submission` properties to `Emails::EVENT_AFTER_SEND_MAIL`.
- Added `siteId` as a mappable attributes for entry element integrations.
- Added support for entry element integrations to have the entry `siteId` attribute set to the same site the submission is made on, by default.

### Changed
- Improve element integration error logging.
- Disable Section and HTML fields from being able to be used in integration mapping (they do nothing).

### Fixed
- Fixed incorrect validation message for Time field in Date field, complaining about 24-hour values.
- Fixed Time field for Date fields incorrectly converting time values to site timezone.
- Fixed multi-line fields with rich text set, not rendering raw HTML in email notifications.
- Fixed Phone field values potentially returning an invalid value, when no value provided.
- Fixed an error (not firing) for a failed submission through GraphQL mutations.
- Fixed GraphQL mutations permissions for submissions.
- Fixed entry element integration not working correctly for entry types with dynamic title.
- Fixed an error when trying to parse Checkboxes and Multi-Dropdown fields for spam-checks.
- Fixed error when trying to output Checkboxes and Multi-Dropdown fields in email notifications.

## 1.3.25 - 2021-03-22

### Added
- Added “Ajax Submission Timeout” form setting to control the timeout for the XHR request, for Ajax-based forms, using the Theme JS.

### Fixed
- Fixed Agile CRM integration not updating existing contacts correctly.
- Fixed Sendinblue integration not subscribing users to the chosen list.
- Fixed an error when saving a submission in the control panel with a user with restricted permissions.

## 1.3.24 - 2021-03-18

### Changed
- Refactor JavaScript captcha for more error-handling, support for cached forms and removal of inline `<script>` tags.

### Fixed
- Fixed Date fields throwing an error when the default date set to "None" and rendering inputs or a dropdown.
- Fixed Date fields always selecting the first option in the list, when set to dropdown.
- Fixed JavaScript captcha outputting inline `<script>` tags.
- Fixed JavaScript captcha throwing a "modified form" browser warning.
- Fixed form settings not updating when using Blitz caching.
- Fixed element fields restricting values to only the default value.

## 1.3.23 - 2021-03-16

### Added
- Added `modifyPrefixOptions` event for name fields.
- Added `Mx.` to name field prefixes.
- Allow the `<form>` element to defined the `action` and `method` settings of Ajax requests, and fix POST-ing to site roots with redirects configured.

### Fixed
- Fixed `redirectUrl` not working correctly when using `form.setSettings()` in your templates for Ajax forms.
- Fixed the default template in the control panel (when clicking on “Formie” in the CP nav) throwing an error when trying to load a page the user doesn’t have access to.
- Fixed Sent Notification preview column throwing an error in the control panel.
- Fixed being unable to save a submission in the control panel, when specific form permissions were set.
- Fixed an error when rendering elements fields as checkboxes.
- Cleanup uninstall, fix an error during uninstall, fix not deleting submissions and forms on uninstall.
- Fixed Categories fields not populating their value correctly when `limit` was also set.
- Fixed Entries fields not populating their value correctly when `limit` was also set.
- Fixed Products fields not populating their value correctly when `limit` was also set.
- Fixed Tags fields not populating their value correctly when `limit` was also set.
- Fixed Users fields not populating their value correctly when `limit` was also set.
- Fixed Variants fields not populating their value correctly when `limit` was also set.

## 1.3.22 - 2021-03-09

### Added
- Added “ID” to available submissions index columns.
- Added “ID” to available forms index columns.
- Added “Submission ID” to the edit page for submissions.
- Added “Form Name” to integration mapping and email notification condition variable pickers.
- Added setting to Hidden field to include or exclude their content in email notifications.
- Added “All Visible Fields” options to email notifications, outputting field content only for fields that are visible.
- Added `EVENT_AFTER_SUBMISSION_REQUEST`.
- Added support for querying and mutating Group and Repeater fields for GraphQL.
- Added support for updating entries for Entry integration.
- Added support for updating users for User integration.
- Added support for creating a new draft for Entry element integration.

### Changed
- Update default submission titles to `D, d M Y H:i:s` (eg, “Thu, 04 Mar 2021 10:50:16”).
- Date fields can now use [Flatpickr.js](https://flatpickr.js.org/) when rendered as a calendar.
- Minor performance improvement when submitting submissions, when no custom title format is set.
- Rename `field->getIsVisible()` to `field->getIsHidden()`.
- Change syntax for populating element fields, when using `populateFormValues()`.

### Fixed
- Fixed Number field not having the correct type for GraphQL queries.
- Fixed an error with HubSpot CRM, when mapping a field to the Tracking ID for forms.
- Fixed Date field not having the correct type for GraphQL queries.
- Fixed reCAPTCHA placeholders not being found for custom-templated forms that have no pages containers.
- Fixed custom submission titles not working correctly when using submission attributes (namely submission ID).
- Fixed Sent Notifications index not ordering by descending by default.
- Fixed multi-line text fields not having their content passed through `nl2br` in email notifications.
- Fixed address fields not showing the correct preview in the control panel when “Auto-complete” was enabled.
- Fixed element integrations incorrectly mapping fields and attributes when no value supplied.
- Fixed fields not having their `formId` attribute set correctly.
- Fixed GQL errors when querying subfields inside group/repeater fields.
- Fixed “Reply To” setting for email notifications not being properly parsed for environment variables.
- Fixed email parsing error for email notifications in rare circumstances (where an env variable contained spaces).

## 1.3.21 - 2021-03-01
- Removed `craft.formie.getVisibleFields()`.

### Fixed
- Updated front-end JS to catch ajax-based forms network errors and timeouts.
- Fixed toggling checkboxes triggering required states, when the field wasn’t required at all.
- Removed `siteRootUrl` for included JS, causing issues with JS form submissions on some sites, where cross-domain issues arise. Rely on `siteId` param to determine current site..
- Fixed an error with HTML field when “HTML Content” was empty.
- Fixed Name, Address and Date fields not applying an `id` attribute of legends for accessibility.
- Ensure all front-end field legends output, even when hidden (using `fui-sr-only`) for accessibility.

## 1.3.20 - 2021-02-26

### Added
- Added logging to submit action, capturing form content immediately and saving to logs.

### Changed
- Allow both 0.3.0 and 0.4.0 guzzlehttp/oauth-subscriber. (thanks @verbeeksteven).

### Fixed
- Fixed sub-fields (Name, Address, etc) not working for pre-populating values.
- Fixed value of checkbox fields for integrations when mapping to a plain text field. Field values are now sent as comma-separated.
- Fixed InfusionSoft sending incorrect Phone Number payload values. (thanks @dubcanada).
- Fixed submission error logs not saving log information for ajax forms.
- Fixed Salesforce duplicate leads throwing an error.

## 1.3.19.1 - 2021-02-24

### Added
- Added `populateFormValues ` GraphQL argument to pass options into `templateHtml` render function. (thanks @jaydensmith).
- Added `options` GraphQL argument to pass options into `templateHtml` render function. (thanks @jaydensmith).

### Fixed
- Fixed validation error for new Email Address fields, introduced in 1.3.19.

## 1.3.19 - 2021-02-24

### Added
- Added support for `populateFormValues` to Group fields.
- Added support for `populateFormValues` to Repeater fields.
- Added “Order By” setting for all element fields to control the order options are rendered by.
- Added Checkbox Toggle to Checkboxes fields, providing the ability to toggle all checkbox fields at once.
- Added “Usage Count” column to Forms, to show the number of elements relating to each form.
- Added “Validate Domain (DNS)” setting for email address fields.
- Added “Blocked Domains” setting for email address fields.
- Added tags to Agile CRM integration. (thanks @jaydensmith).

### Fixed
- Fixed element fields not rendering correctly in email notifications, when including a single field token.
- Fixed “Resend” button when editing a sent notification.
- Fixed provider errors for all integrations getting truncated text.
- Fixed potential issue with `siteRootUrl` on site setups with redirects setup to include trailing slashes in URLs.
- Fixed a HubSpot integration form error when some context values (IP) isn’t always available.
- Fixed “Save as a new Form” not generating a nice, sequential handle.
- Fixed an issue where a failed “Save as new form” would retain the incorrect form settings.
- Fixed form errors not showing full error text in control panel.
- Fixed Repeater and Group field styling when editing a submission in the control panel.
- Fixed JavaScript captcha when using template caching not working.

## 1.3.18 - 2021-02-20

### Added
- Added support for HubSpot form integration.
- Added opt-in field support to all CRM integrations.
- Added support for field options defined in HubSpot (for dropdown, select, etc).
- Added custom field support for Sendinblue integration.
- Added `Min File Size` setting for File Upload fields.
- Added support for global variables in Spam Keywords.

### Changed
- Update some email marketing integrations to fetch custom list fields more efficiently.
- Prevent email notifications sending 0kb file uploads. This can lead to spam filters marking the email as invalid.

### Fixed
- Fixed "minutes" to be given a retention length. (thanks @nickdunn).
- Fixed `endpoint` and `method` properties missing from `SendIntegrationPayloadEvent` for Email Marketing and CRM integrations.
- Fixed nested fields (Group, Repeater) not having inner field JS initialized properly.
- Fixed spam checks for some field types.
- Fixed tag fields throwing an error when set to “dropdown” and editing a submission.
- Fixed case insensitivity (not working) for spam keywords.
- Fixed multiple fields with JS config not initialising correctly.
- Fixed Group or Repeater nested fields not getting unqiue handles when cloning.
- Fixed Repeater fields not having their inner fields’ JS initialized properly.
- Fixed some fields (Element, Repeater, Group) not extracting content for spam keyword checks.
- Fixed submission success messages including submission content not working.
- Fixed existing fields not appearing for the form builder.
- Fixed some fields (plain text and other simple fields) not having their labels correctly translated for email notifications.

## 1.3.17 - 2021-02-13

### Added
- Added better caching to `getParsedValue`, which fix a few rare issues with field rendering stale content.
- Allow `formieInclude()` to allow multiple templates (array syntax) to be passed in to be resolve.
- Added support for Integration settings for Stencils.
- Added support for paginated lists for Sendinblue integration.
- Added error logging for email delivery.
- Added ability to set the default value for all Element fields.
- Added checkboxes and radio button display types for Element fields.
- Added support to Element fields to customise the content used for labels, instead of just title.
- Added `submission` to `Submission::EVENT_DEFINE_RULES` event.
- Added Tracking, Account and Campaign IDs to SharpSpring CRM integration.
- Added `configJson` and `templateHtml` to FormInterface for GraphQL.
- Added `ModifyFormRenderOptionsEvent`. (thanks @jaydensmith).
- Added `getIsTextInput` method to Phone field. (thanks @jaydensmith).
- Added `formConfig` to the `initForm()` JS function to provide an object with the form config, rather than rely on the DOM to set it (using `setAttribute('data-config’)`).

### Changed
- Changed `Field::getEmailHtml()` to require a notification model.
- Any globally-enabled captchas will be automatically enabled on new forms.
- Any globally-enabled captchas will be automatically enabled on new stencils.

### Fixed
- Fixed an error for email notifications if its conditions contained an element field, set to `contains` as a condition.
- Fixed Variant field not providing data to integrations correctly.
- Fixed Categories field not providing data to integrations correctly.
- Fixed Entries field not providing data to integrations correctly.
- Fixed File Upload field not providing data to integrations correctly.
- Fixed Products field not providing data to integrations correctly.
- Fixed Tags field not providing data to integrations correctly.
- Fixed Users field not providing data to integrations correctly.
- Fixed email notification conditions not properly testing against element field values (entries, categories, etc).
- Fixed checkbox and radio fields having JS validation checks bound multiple times in a form.
- Fixed checkbox fields producing multiple errors.
- Fixed some reCAPTCHA plugin settings not saving correctly.
- Fixed reCAPTCHA not initializing when the surrounding form was initially hidden (for example, in a modal).
- Fixed multiple forms on a single page not having their associated field JS initialized properly.
- Fixed migration error for Postgres.
- Fixed security warnings of using `eval()` in front-end JS, despite it being safe to call.
- Fixed edit field button in control panel sizing being too small.
- Fixed assuming `TEMPLATE_MODE_SITE` when rendering templates for forms, pages and fields.
- Fixed template conditional that could cause the form to be hidden if “Hide Form” was set, but “Action on Submit” was set to “Display a message”.
- Fixed a JS error with some fields (address auto-complete, multi-line) in some instances.
- Fixed minor `e.g.` typo for stencils.
- Fixed `getValue` method on BaseOptionsField not returning correctly. (thanks @javangriff).

## 1.3.16.1 - 2021-01-31

### Fixed
- Fix an error with `ModifyFieldValueForIntegrationEvent`.

## 1.3.16 - 2021-01-31

### Added
- Added autocomplete value to Address field in email templates.
- Added `SubmissionExport::EVENT_MODIFY_FIELD_EXPORT` to allow modification of values for fields when exporting submissions.
- Added `Submission::EVENT_MODIFY_FIELD_VALUE_FOR_INTEGRATION` to allow modification of submission field values before they’re used in Integrations.
- Added `minutes` as an option for submission data retention.
- Added more feedback for garbage-collection tasks when run directly from the CLI.

### Fixed
- Fixed an error when creating new email templates in an empty directory.
- Fixed email and form templates not retaining “Copy Templates” value after validation.
- Fixed Users field not setting “All users” as default sources when creating a new field.
- Fixed Variant field not exporting correctly.
- Fixed Users field not exporting correctly.
- Fixed Tags field not exporting correctly.
- Fixed Products field not exporting correctly.
- Fixed File Upload field not exporting correctly.
- Fixed Entries field not exporting correctly.
- Fixed Categories field not exporting correctly.
- Fixed Checkboxes field not exporting correctly.
- Fixed Agree field not exporting correctly.
- Fixed an error when trying to delete submissions from the CLI.
- Fixed an error when disconnecting an OAuth-based integration.
- Fixed Salesforce and Zoho CRM integrations not persisting values returned from provider authentication.
- Ensure error message is logged for failed pruning of submission tasks.

## 1.3.15 - 2021-01-29

### Added
- Added more logging info for email notifications, when failed to send.

### Fixed
- Fixed an error when viewing sent notifications, when the submission was deleted.
- Fixed some errors for sent notifications for a deleted submission or form.
- Fixed Zoho integration where the authentication response didn’t contain the required `api_domain`.
- Fixed Salesforce integration where the authentication response didn’t contain the required `instance_url`.
- Fixed failed email notifications not showing the full error in the control panel, when running email notifications via queues.

## 1.3.14 - 2021-01-28

### Added
- Add `completeSubmission` param to forms, to allow providing a full payload and complete the submission.

### Changed
- Saving spam submissions is now enabled by default.
- Agree field description static translation is now defined in `site.php`.

### Fixed
- Fixed IE11 compatibility with some front-end fields (Address, Phone, Repeater).
- Fixed hidden fields not having input attributes setting.
- Fixed description for Agree field being translated twice.
- Fixed error in Craft 3.6+.
- Fixed submissions failing if `spamKeywords` setting was invalid.
- Fixed error when querying form settings using GraphQL

## 1.3.13 - 2021-01-24

### Added
- Added support to Mailchimp integration for tags.

### Fixed
- Fixed an error with Webhook integrations.

## 1.3.12 - 2021-01-23

### Added
- Added support for selecting existing notifications from stencils, when adding a notification to a form.
- Added support for Webhook integration URLs to contain submission variables through shorthand Twig.
- Added support for Freeform and Sprout Form migrations to be run when `allowAdminChanges = false`.

### Fixed
- Fixed existing notifications not appearing when editing a stencil.
- Fixed some potential errors with Sprout Forms migration and address/name fields.
- Fixed Freeform migration not migrating fields correctly.
- Fixed Sprout Forms migration not migrating fields correctly.
- Fixed Sprout Forms migration for Agree field, where the message description would be blank.
- Fixed Sprout Forms migration for File Upload field, where the selected volume wasn’t migrated.
- Fixed Sprout Forms migration for Categories, Entries, Tags and User fields, not setting the `selectionLabel` to the `placeholder` value. 
- Fixed some potential errors with Sprout Forms migration and phone fields.
- Fixed “Save as Stencil” not saving data correctly, by not stripping out page, row and field IDs.

## 1.3.11 - 2021-01-21

### Added
- Added a ‘pageIndex’ param to the submit action. (thanks @joshuabaker).
- Added pagination-helper for ActiveCampaign integrations. The integration will now automatically fetch greater-than 100 resources such as lists.

### Changed
- Changed ActiveCampaign integrations to fetch at least 100 of each resource.

### Fixed
- Fixed deprecation notice for Repeater field. (thanks @danieladarve).
- Fixed ajax forms not redirecting correctly, when overriding `redirectUrl` in templates.
- Fixed HTML field not parsing Twig content in some instances.
- Fixed Entry mapping throwing an error when setting the author to an Entry field.
- Fixed ActiveCampaign integration not showing connection status correctly.
- Fixed sub-fields (Name, Address, Phone) not having their values concatenated for integrations, when not selecting a sub-field.

## 1.3.10 - 2021-01-16

### Added
- File Upload fields now show a summary of uploaded files for multi-page forms. When navigating back to a page with the field, you'll see this summary.
- Ajax-enabled multi-page forms now smooth-scrolls to the top of the loaded page when going to the previous or next pages.
- Front-end validation now adds a `fui-error` class on the `fui-field` element, when a validation error occurs, instead of just on the input.

### Fixed
- Fixed an error when using checkboxes in email notification conditions.
- Fixed a potential error when viewing a submission in the control panel.
- Fixed Address field auto-complete value not persisting on front-end submissions.
- Fixed being unable to set the `siteId` of submissions through GraphQL.
- Fixed submissions not being able to be mutated through GraphQL for non-public schemas.
- Fixed Group and Repeater fields not saving content correctly for non-primary sites.
- Fixed flicker in Safari for the form builder, when hovering over draggable elements.
- Fixed Phone field exporting not formatting international phone numbers correctly.
- Fixed Phone field not always remembering the country dial code selected.
- Fixed Phone field triggering content change warnings for country-enabled fields. This was due to numbers being formatted on-load..

## 1.3.9 - 2021-01-12

### Added
- Added support for Gatsby Source Plugin and Form elements.
- Added support for Gatsby Source Plugin and Submission elements.
- Added support to set the `formId` for a form. This is used as the unique identifier for the `id` attribute and connection JS to the form.

### Changed
- User element integrations now clear any field content mapped to the password field, once the user element is created.

### Fixed
- Fixed name and address fields not showing content in columns, when editing a submission in the control panel.
- Fixed some fields (checkboxes) throwing errors in email notifications.
- Fixed non-multiple name field causing an error in email notifications.
- Fixed non-utf8 characters in email notification email values causing errors.
- Fixed email notification logging message to properly include the template it tried to resolve.

## 1.3.8 - 2021-01-10

### Added
- Added “Country Code” to Phone model, allowing the raw country code (eg “+1”) to be used in email notifications and submissions for a Phone field
- Added `form.setFieldSettings()` function to provide render-time overrides to form fields and their settings.

### Changed
- Change email notification variable output to only contain the value for the field, instead of including the field label/name.

### Fixed
- Fixed default stencil’s “To” and “Reply To” variable fields not being correct.
- Fixed email notification preview not working for stencils
- Fixed form templates with custom template path not saving correctly
- Fixed JavaScript captcha not working correctly for multiple instances of the same form on a page.
- Fixed an error with the HTML field
- Fixed server-side validation errors with Phone field
- Fixed phone numbers not being created as international numbers, when previewing an email notification

## 1.3.7 - 2020-12-23

### Fixed
- Fixed Agile CRM mapping email, website and phone to contacts. 
- Fixed deleting a submission via GraphQL not returning the correct success/fail state.
- Fixed deleting a submission via GraphQL not working for non-default sites.

## 1.3.6 - 2020-12-22

### Fixed
- Fixed element integrations not having their error messages translated correctly (and not containing the required logging detail).

## 1.3.5 - 2020-12-22

### Added
- Added ability to provide htmlpurifier config JSON files for HTML fields.
- Added `ModifyPurifierConfigEvent`.
- Added Agile CRM integration.
- Added Copper CRM integration.
- Added Capsule CRM integration.
- Added all global sets into variable-enabled fields.

### Changed
- Improve performance for very large forms and fields.

### Fixed
- Fixed an error when viewing a trashed submission, with custom fields selected in columns.
- Fixed no captchas appearing in plugin settings.
- Fixed potential error thrown, when trying to catch _other_ errors during older updates.
- Fixed Oauth-based integrations not allowing connection when `'allowAdminChanges' => false`.
- Fixed an error with the recipients field.
- Fixed form permissions not always being run for new forms.
- Fixed “Save as a new form” not working in some cases.
- Fixed multi-page form submissions incorrectly validating fields when going back to a previous page.
- Fixed some fields not displaying correctly in notification emails.
- Fixed sent notifications not always showing the HTML body content.
- Fixed form and email templates not resolving to single template files correctly.
- Fixed an error when trying to delete a submission using GraphQL.

## 1.3.4 - 2020-12-16

### Added
- Added `formie.cp.submissions.edit` template hook.
- Added `formie.cp.submissions.edit.content` template hook.
- Added `formie.cp.submissions.edit.details` |template hook.
- Added `formie.cp.sentNotifications.edit` template hook.
- Added `formie.cp.sentNotifications.edit.content` template hook.
- Added `formie.cp.sentNotifications.edit.details` template hook.
- Update Autopilot integration to include more default fields and fix list-subscribing.
- Added ability to add soft line-breaks to email notifications and other rich-text enable fields.

### Changed
- Pages now have a unique ID, inherited from the form’s `formId`.

### Fixed
- Fixed rendering the same form multiple times on a page not working correctly.
- Fixed “Unknown Integration” error message when trying to connect an integration with `allowAdminChanges = false`.
- Fixed captcha settings resetting when saving plugin settings.
- Fixed the `siteRootUrl` to trim the trailing slash if present. This is an issue on some systems (Servd) where URLs with a trailing slash are redirected.
- Fixed field/notification edit modals not getting properly reset when hidden.
- Fixed HTML field errors when the vendor folder didn’t have write permissions (such as Servd).

## 1.3.3 - 2020-12-06

> {warning} If you are overriding templates for `field.html`, you **must** update your template to include `data-field-config="{{ field.getConfigJson(form) | json_encode | raw }}"`. This is the new and improved method for fields to define their config settings, picked up by JavaScript classes. Without making this change, field JS will not work. Refer to [this commit change](https://github.com/verbb/formie/commit/c5d5eda10b39063e1cf782b38f84bebe0da6fdf9#diff-ba26d5dbf9dcd3281c9b0b3c16f822eff1d2943c2134518d4ecea26d10907be4R90-R92).

### Added
- Added `defaultState` for GraphQL queries for Agree fields. This replaces `defaultValue`.
- Added `defaultDate` for GraphQL queries for Date fields. This replaces `defaultValue`.
- Added “Current URL” to hidden field default value options.
- Added `data-field-config` attribute to all fields that require JS.
- Added `getConfigJson()` for all fields to define settings for JS modules.

### Changed
- Formie now requires Craft 3.5+.
- Form queries via GraphQL are now no longer automatically included in the public schema.
- Submission queries via GraphQL are now no longer automatically included in the public schema.
- Submission mutations via GraphQL are now no longer automatically included in the public schema.
- When (soft) deleting a form, any submissions will also be (soft) deleted. These are also restored if the form is restored.
- Refactor JS behaviour for fields that require it. We now use a `data-field-config` attribute on the field to store JS module settings. This is then initialized once the JS has been lazy-loaded. This allows us to split configuration from initialization and may also help with custom JS.
- Renamed `Field::getFrontEndJsVariables()` to `Field::getFrontEndJsModules()`.
- Improve handling of multi-page non-ajax forms, where some fields required JS. Formie now detects what JS needs to be used for the current page for a page-reload form, or the entire form for an ajax form.
- Improve field JS to stop relying on IDs or classes to hook into field functionality. It now determines this through `data-field-config` attribute on the field wrapper element. This should allow for greater template flexibility.
- Submissions now make use of the same JS/CSS code that the front-end does.

### Fixed
- Fixed errors when garbage collection is called for sent notifications.
- Fixed when deleting a form, the submissions for that form weren't also deleted.
- Fixed an error when trying to view a submission on a deleted form.
- Fixed some GraphQL attributes not being cast to the correct type.
- Fixed some GraphQL errors for some fields.
- Fixed an error when trying to permanently delete a form.
- Fixed an error with date field using a default value.
- Fixed console error for multi-page non-ajax forms containing a phone field.
- Fixed repeater and group fields not initializing their inner fields’ JS.
- Fixed JS module code for fields being loaded multiple times when initially loading the page.
- Fixed an error for address providers when used in a Repeater field.
- Fixed address providers not checking if their provider JS is loaded correctly, in some instances.
- Fixed multi-line rich text fields loading Font Awesome multiple times.
- Fixed checkbox/radio fields not validation correctly inside a Repeater field.
- Fixed warnings/errors for JS fields, where their inputs might not exist on a page.
- Fixed Algolia Places not working correctly.
- Fixed issue where multiple ajax-based forms on a single page would have validation triggered across all forms.
- Fixed incorrect error being shown when custom server-side errors for fields are defined.
- Fixed an error when an email notification's sender email wasn't properly filtered.
- Fixed incorrect output in email notifications when using date fields.

## 1.3.2 - 2020-11-28

### Added
- Added support for using the submission ID, Title and Status in notification conditions.

### Fixed
- Fixed notification conditions not saving correctly when a field with options (dropdown, etc) was selected.
- Fixed “Submission Message” and “Error Message Position” form message parsing HTML incorrectly in some cases.
- Fixed agree field description parsing HTML incorrectly in some cases.
- Fix an error when editing stencils.
- Fix minor error handling for GQL mutations.

## 1.3.1 - 2020-11-26

### Added
- Allow field type classes to provide their own GQL attribute mappings for attributes.

### Fixed
- Fixed Ajax submissions not resolving to the correct current site when using sub-directories for sites, causing translation issues.
- Fixed agree field description not translating correctly.
- Fixed error when querying `allowedKinds` as an attribute on a file upload field with GQL.
- Fixed lack of server-side email validation for email fields.

## 1.3.0 - 2020-11-25

### Added
- Added Sent Notifications section, providing information on sent email notifications. Each Sent Notification contains delivery information and the ability to preview what was sent.
- Added resend Sent Notifications, allowing you to either resend the notification to their original recipients or nominated new ones.
- Added bulk resend Sent Notifications, either to their original recipients or nominated new ones.
- Added support for default field values to contain variable tags for autofilling user info.
- Added pre-populate setting to fields, allowing you to specify a query string param to pre-populate the field with a value.
- Added conditions to notifications. Build complex conditional rules on when to send (or not send) email notifications.
- Added better support for countries in Phone fields, now with a nicer UI for the front-end.
- Added country flags and international/national validation to Phone fields.
- Added new MultiSelect Vue component, for use in custom field schema settings.
- Added ability to control whether form submissions are stored permanently or not.
- Added settings for form submission data retention for hours, days, weeks, months and years.
- Added indicator when editing a submission when it's associated with a user.
- Added `submission->getUser()`.
- Added support for when deleted a user, any submissions associated to them can be transferred to another user, or deleted.
- Added when deleting a user, a summary of their submissions (if any) is shown in the prompt.
- Added support for when restoring a deleted user, we restore any associated submissions.
- Added settings for form submission data retention for uploaded files.
- Added `maxSentNotificationsAge` plugin setting to control sent notification pruning.
- Added `formie/gc/delete-orphaned-fields` console command.
- Added `formie/gc/prune-syncs` console command.
- Added `formie/gc/prune-incomplete-submissions` console command.
- Added `formie/gc/prune-data-retention-submissions` console command.
- Added `formie/gc/prune-content-tables` console command.
- Added variable tags to form “Submission Message” rich text field setting, allowing for the use of submission variables in the submission success message.
- Added ability to use submission attributes and fields in redirect URLs for Ajax forms.

### Changed
- Refactored Phone fields to no longer use a separate dropdown for country code.
- When deleting a user, any form submissions related to that user will be deleted, or transferred to a user of your choice. This only applies if you use the "Collect User" setting for your forms.
- Lock `fakerphp/faker` at 1.9.1 due to PHP compatibility. Hopefully also fix some composer issues when updating with `./craft update all`.

### Fixed
- Fixed critical errors when a fields' setting was removed before migration can take place (looking at you `descriptionHtml` attribute).
- Fixed `registerFormieValidation` JS event not working correctly.
- Fixed a potential error in `craft.formie.getParsedValue()`.
- Fixed error with Postgres and viewing the forms index.
- Fixed error with Postgres and viewing the submissions index.
- Fixed agree field description not outputting line breaks.
- Fixed “Submission Message” and “Error Message Position” form messages not outputting line breaks.
- Fixed form messages not being translated correctly when the form is set as Ajax submit.
- Fixed submit message not showing correctly when set to show at the bottom of the form and the form is hidden on success.
- Fixed error with sending test email notifications in some instances.

### Removed
- The following attributes on Phone fields have been removed: `showCountryCode`, `validate`, `validateType`, `countryCollapsed`, `countryLabel`, `countryPlaceholder`, `countryPrePopulate`, `numberCollapsed`, `numberLabel`, `numberPlaceholder`, `numberDefaultValue`, `numberPrePopulate`.

## 1.2.28 - 2020-11-19

### Added
- Added per-form form permissions for users.
- Added per-form submission permissions for users.

### Changed
- Change `fzaninotto/faker` to non-abandoned `fakerphp/faker`.
- Increase stencil and form settings database column sizes, for large forms.

### Fixed
- Fixed error when submitting a form on a non-primary site, when it contained a group or repeater field.
- Fixed Agree field’s description not translating correctly when using special characters.
- Fixed HTML-based form settings not translating correctly when using special characters.
- Fixed Mercury CRM not mapping email and mobile fields correctly.
- Fixed email notifications incorrectly showing element queries, when trying to output an element field’s value.
- Ensure rich text fields don’t convert underscores to italics, when using as part of field handles.
- Fixed fatal error being thrown when viewing stencils, if a stencil had invalid data.

## 1.2.27 - 2020-11-16

### Added
- Allow captchas to set a `spamReason` property, providing details on why a submission was marked as spam.
- Added “Minimum Submit Time” to Javascript captcha.

### Changed
- Remove table-padding in plugin settings.

### Fixed
- Fixed potential error when processing Monday integrations.
- Fixed front-end JS console error thrown for some fields (table, repeater) for multi-page non-ajax forms.
- Fixed Table and Repeater fields sometimes throwing an incorrect error for min/max rows when not set.
- Fixed checkbox and radio field instructions not working well when set to “Above Input” or “Below Input”.
- Fixed date fields incorrectly converting to the system timezone.
- Fixed potential issue with Name field being used in integrations.
- Fixed spam reason not showing when editing a submission in the control panel.

## 1.2.26 - 2020-11-10

### Added
- Added `afterIncompleteSubmission` event.

### Changed
- Allow incomplete submissions to be used in trigger integrations queue job

### Fixed
- Fixed error when saving a field in Postgres.
- Fixed multiple recaptchas on the same page not working correctly.
- Fixed Postgres error when deleting or restoring forms.
- Fixed date fields storing time incorrectly when a submission is saved in the control panel.
- Fixed date fields not showing the time field in the control panel when editing a submission.
- Fixed table field dropdown column options not saving.

## 1.2.25 - 2020-10-28

### Added
- Added `getIsVisible()` to all field classes.
- Added `craft.formie.getVisibleFields(row)`. For any given row, will return whether there are any visible fields.
- Added `submitActionMessagePosition` to forms to control the position of success messages.
- Added more base-field level attributes for GraphQL `FieldInterface`. No need to supply inline fragments for common attributes.
- Added `redirectUrl` to JS variables, for consistency.
- Added `redirectUrl` to form settings, allowing full override of the URL when redirecting on submission success.
- Added “Badge” setting for ReCAPTCHA V3.
- Added support for element fields in integrations mapping to string-like fields.
- Added `parseMappedFieldValue` event for integrations, allowing modification of the form submission values from Formie to the integration provider.
- Added remove row button for Table field's front-end templates.

### Changed
- Ensure row classes aren’t outputted when there are no visible fields for a given row.
- Ensure eager-loaded fields have the correct content table set.
- Minimum table field rows now create rows when initially loading the form.

### Fixed
- Fixed incorrect submission error logging.
- Fixed Campaign integration and some custom fields (like checkboxes). Be sure to re-save your form's integration settings for this to take effect.
- Fixed checkboxes field validation not working correctly.
- Fixed GraphQL `containerAttributes` and `inputAttributes` properties.
- Fixed phone number sub-field label position not working correctly.
- Fixed address, date, name and phone sub-field labels not displaying correctly for left/right alignments.
- Fixed `onFormieSubmitError` JS event not firing for server-side errors.
- Fixed submissions not showing preview of element field content for submission index columns.
- Fixed stencil notifications showing unsaved.
- Fixed error when saving a new stencil.
- Fixed error when saving a Table field in some cases.
- Fixed some min/max row checks with Table fields.

### Removed
- Removed `redirectEntry` from JS variables.
- Removed `submitActionUrl` from JS variables.

## 1.2.24 - 2020-10-20

### Added
- Added `referrer` property to integrations, to provide the URL where the submission came from.

### Fixed
- Fixed Campaign integration and error thrown when the referrer was missing.
- Fixed `beforeSendNotification` and `beforeTriggerIntegration` events not working consistently across queue jobs and non-queue.
- Fixed parsing `userIp` twice, when used in variable tags.
- Fixed an error when sending notification emails via queue jobs.
- Fixed Integration settings for forms were wiped when an integrations was disabled.
- Fixed synced fields not saving correctly when moved immediately after being added.
- Fixed file upload files defaulting to a single file being allowed to be uploaded.
- Fixed file upload fields not being able to handle multiple files uploaded.

## 1.2.23.1 - 2020-10-16

### Fixed
- Fix Agree field `descriptionHtml` error, introduced in 1.2.20.

## 1.2.23 - 2020-10-16

### Changed
- File upload fields now render links to their control-panel assets in email templates, in addition to being attachments.

### Fixed
- Fixed group fields displaying incorrectly in email previews.
- Fixed group fields not checking for nested field’s `hasLabel` attribute in email content.
- Fixed repeater fields not checking for nested field’s `hasLabel` attribute in email content.
- Fixed form settings now saving correctly when users with limited permissions save forms.
- Fixed an error with entry integrations and author.
- Element integrations now correctly translate Formie fields to Craft fields.

## 1.2.22 - 2020-10-15

### Added
- Added `descriptionHtml` for GraphQL querying Agree fields’ description.
- Table, Repeater and Rich Text JS now provide access to their JS classes on field DOM elements.
- Repeater field’s JS now triggers an `init` event.

### Fixed
- Fix integrations throwing errors when opting-out of the integration (through events or opt-in field).
- When `Validate When Typing` is set on a form, ensure that the global form error message is removed after errors are fixed (when typing).
- Fix Agree fields’ description being incorrectly formatted when calling through GraphQL.

## 1.2.21 - 2020-10-13

### Added
- Added `enableUnloadWarning` plugin setting, to control the “unload” warning for front-end forms. This warning is used to prompt users their form has changed for good UX.
- Added `renderJs` and `renderCss` options to `renderForm()`, to allow for enable/disable of resources on specific render calls.

### Fixed
- Fixed potential error with Agree field descriptions.
- Fixed incorrectly exporting all form submissions site-wide when a specific form’s submissions were selected.
- Fixed submission exports not resolving the content table correctly.
- Fixed submission element index not showing available custom fields to customise with.

## 1.2.20 - 2020-10-12

### Added
- Added more logging for webhook integrations when troubleshooting.
- Added `siteId` to submissions. Now makes it possible to know which site a submission was made on.
- Added multi-site support for submissions.
- Allow fields in submissions to be searchable.
- Added “Error Message Position” option for forms to control where form-wide errors are shown.
- Twig template code can now be included inside a HTML field.
- Setup template roots to allow for much easier template overrides.
- Allow form includes to be overridden individually.
- Allow field hooks to override field settings.
- Added `fui-next` to all forward-progressing submit buttons, except the final submit button, for multi-page forms.
- Added `inputAttributes` and `containerAttributes` for submit buttons.
- Added `defaultLabelPosition` and `defaultInstructionsPosition` to plugin settings for site-wide defaults.
- Added time label to date field.
- Address sub-fields can now be set to hidden. Assists with using only autocomplete field.
- Added User element integration.

### Changed
- Integration settings can now be viewed read-only when `allowAdminChanges` is false.
- Agree field’s description now uses HTML content.
- Re-organised front-end form includes.
- Extract submit button front-end template to its own include, to allow easier overriding.
- Recipients field label position is set to “Hidden” by default (because the default field is set to hidden).
- Address fields can now have only the autocomplete block enabled.
- Forms now no longer forces `novalidate`. HTML5 validation will trigger when Formie’s JS validation is not triggered.

### Fixed
- Fixed file upload fields in nested fields not attaching to email notifications.
- Fixed fields in nested fields showing in email notifications when their field settings don't allow it.
- Fixed an error with email notification preview, preventing CSS bleeding into the control panel.
- Fixed webhook integrations not always using the per-form defined Webhook URL.
- Fixed Formie's front-end templates, as Sass variables not being able to be overwritten. (thanks @leevigraham).
- Fixed submission field data not exporting, when exporting from “All Forms”.
- Fixed “Site Name” for variable picker reflect the site the submission was made on.
- Fixed attributes and custom fields for submissions not being able to be searched.
- Fixed rich text content not having access to all available fields in the variable picker.
- Fixed a few issues with Address field and auto-complete behaving inconsistently with other sub-fields.
- Fixed autocomplete field for Address fields not showing for submissions.
- Remove `<small>` HTML elements from instructions for front-end templates. Produced invalid HTML.

## 1.2.19 - 2020-09-26

### Added
- Allow Redirect URL for a form's settings to contain Twig.
- Added Submission ID as an available variable to pick from in notifications.

### Fixed
- Fixed an error when an Ajax-enabled form's Redirect URL setting contained Twig.
- Fixed submission variables incorrectly caching when sending multiple notifications.

## 1.2.18 - 2020-09-25

### Added
- Added `endpoint` and `method` to payload events for integrations.

### Fixed
- Fixed serialization of phone fields for integrations.
- Fixed being unable to modify payload in `beforeSendPayload` event.
- Fixed a number of fields (checkbox, radio, agree, date, phone) where the custom error message wasn't working.
- Fixed error when connecting with AutoPilot.
- Fixed image uploads not attaching to email notifications for non-local volumes.
- Ensure nested fields (group and repeater) respect MySQL table name limits. Prevents errors when saving a very long field name.
- Fixed an error when editing a form when a user doesn’t have permission for the primary site.

## 1.2.17 - 2020-09-23

### Added
- Added support for group fields and subfield-enabled fields (name, address) to be used as variable tags.
- Added ability to override form settings, classes and attributes in templates.

### Fixed
- Fixed `isJsonObject` error for Craft 3.4.

## 1.2.16 - 2020-09-22

### Fixed
- Fixed name field serializing non-multiple fields for integrations
- Fixed some fields (name, date, phone) always allowing mapping for their subfields for integrations.
- Fixed front-end submission editing not working for multi-page forms.

## 1.2.15 - 2020-09-21

### Added
- Added `status` and `statusId` to be used in GQL mutations for submissions.

### Changed
- Refactor field serialization for integrations. Provides better support for array-like data.
- Recipients field settings now no longer enforce unique values for options.
- Allow table fields in field settings to add new rows with spacebar (for accessibility).
- Switch reCAPTCHA verification servers to `recaptcha.net`. Hopefully to improve global reach, when access to `google.com` isn't allowed.

### Fixed
- Fixed checkboxes fields not serializing correctly for integrations.
- Fixed table validation rules firing for all rules, instead of the specific defined ones per its field settings.
- Fixed initialising multiple forms manually, with the JS API on the same page.
- Fixed calling `destroyForm` in the JS API not destroying event listeners correctly.
- Fixed integrations not saving when setting as disabled.
- Fixed custom fields not being registered correctly.
- Fixed submissions created via mutations in GraphQL not validating correctly.
- Fixed submissions created via mutations in GraphQL not sending email notifications.
- Fixed submissions created via mutations in GraphQL not triggering integrations.

## 1.2.14 - 2020-09-17

### Added
- Added name/address support for GraphQL mutations.
- Added Mercury CRM integration.

### Changed
- Rename `Submission URL` to `Submission CP URL` for better clarity for field variables in email notifications.

### Fixed
- Fixed Entry fields not rendering their values correctly in email notifications, when their section had no URL settings.
- Fixed Category fields not rendering their values correctly in email notifications, when their group had no URL settings.
- Fixed Product fields not rendering their values correctly in email notifications, when their product type had no URL settings.
- Fixed Tag fields not rendering their values correctly in email notifications.
- Fixed User fields not rendering their values correctly in email notifications.
- Fixed Variant fields not rendering their values correctly in email notifications, when their product type had no URL settings.
- Fixed incorrect validation when saving a recipients field with the display type set to hidden.

## 1.2.13 - 2020-09-17

### Fixed
- Fixed error when trying to submit with multiple checkboxes, in some instances.
- Ensure existing project config data (if any) is applied when installing Formie for the first time.
- Fixed error when no statuses exist. Usually caused by a project config mishap, or an incorrect installation.

## 1.2.12 - 2020-09-16

### Added
- Added recipients field. Display a field as a hidden/select/radio/checkboxes to allow dynamic recipient emails to be used in email notifications. Raw emails also aren't exposed in rendered templates.
- Added Freshsales CRM integration.

### Fixed
- Fixed error when deleting a form due to incorrect redirects.
- Fixed rare issue of being unable to create new pages if a form had zero pages.
- Fixed stencil never applying template.
- Fix email notifications being incorrectly deleted after saving the form as a new form.

## 1.2.11 - 2020-09-11

### Added
- Added CSRF controller to allow static-cached sites to handle CSRF re-generation.

### Fixed
- Fixed captchas allowing payload-sending when they don't support it, causing errors on submissions.

## 1.2.10 - 2020-09-10

### Fixed
- Fixed Entry fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Category fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Product fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Tag fields not rendering their values correctly for multi-sites in email notifications.
- Fixed User fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Variant fields not rendering their values correctly for multi-sites in email notifications.

## 1.2.9 - 2020-09-10

### Added
- Added `EVENT_MODIFY_WEBHOOK_PAYLOAD` event for Webhook integrations. Allows modification of the payload sent to webhook URLs.
- Added `EVENT_MODIFY_FIELD` event Freeform and Sprout Forms migrations. This can be used to modify the field-mapping of Freeform and Sprout Forms fields to Formie fields. Particularly useful for custom-built fields.

### Changed
- Webhook integrations URL is now optional when creating the integration.
- File upload fields now serialize the entire asset element for webhook payloads.

### Fixed
- Fixed refreshing CSRF token field triggering a changed form notice on the front-end.
- Fixed tag fields triggering a changed form notice on the front-end.

## 1.2.8 - 2020-09-10

### Added
- Integration settings now support `.env` variables.
- Entry fields can now restrict their sources to entry types.

### Changed
- Entry fields now restrict their element query to only include elements from the current site - for multi-sites.
- Category fields now restrict their element query to only include elements from the current site - for multi-sites.
- Product fields now restrict their element query to only include elements from the current site - for multi-sites.
- Variant fields now restrict their element query to only include elements from the current site - for multi-sites.

### Fixed
- Fixed composer autoload deprecations.
- Lower `league/oauth2-client` requirement to prevent incompatibility with other plugins.
- Fixed Entry fields not restricting to its sources in some instances.
- Fixed Category fields not restricting to its sources in some instances.
- Fixed Product fields not restricting to its sources in some instances.
- Fixed User fields not restricting to its sources in some instances.
- Fixed Variant fields not restricting to its sources in some instances.
- Fix hidden field throwing errors in queue jobs, for emails and notifications.
- Fixed Entry fields not restricting correctly when selecting multiple sources.
- Fixed Product fields not restricting correctly when selecting multiple sources.
- Fixed User fields not restricting correctly when selecting multiple sources.

## 1.2.7.1 - 2020-09-07

### Fixed
- Fixed a potential error when saving an integration.
- Fixed fields not always showing as available to be mapped for integrations.

## 1.2.7 - 2020-09-07

### Added
- Added plugin setting to set the default form template for new forms.
- Added plugin setting to set the default email template for new email notifications.
- Added plugin setting to set the default volume for new file upload fields.
- Added plugin setting to set the display type for new date fields.
- Added plugin setting to set the default value for new date fields.
- Integration field mapping now supports repeater and group nested fields.

### Fixed
- Fixed stencils not showing validation errors for invalid fields.
- Fixed stencils not showing validation errors for notifications.
- Fixed email notifications preview error when viewed in a stencil.
- Fixed email notifications for stencils showing as unsaved.
- Fixed error with integrations event and `isNew`.

## 1.2.6 - 2020-09-06

### Added
- Added SharpSpring CRM integration.
- Added [Campaign Plugin](https://plugins.craftcms.com/campaign) email marketing integration.
- Added Font Awesome to front-end rich text field (multi-line), for much better consistency.
- Added align options to front-end rich text field (multi-line).
- Added clear formatting option to front-end rich text field (multi-line).

### Fixed
- Fixed minor JS issue for webhook integrations in the control panel.
- Improve Multi-line WYSIWYG front-end field styles, so they aren't overridden. Lists for example now show correctly in all circumstances.

## 1.2.5 - 2020-09-04

### Fixed
- Fixed repeater and table fields duplicating new rows.

## 1.2.4 - 2020-09-03

### Added
- Allow hidden fields to be used in email-only variable fields.

### Fixed
- Fixed users field not restricting to its chosen sources.

## 1.2.3 - 2020-09-03

### Added
- Provide easier shortcuts for editing a submission on the front-end.
- Ensure all field attributes for email and form templates are translated for the front-end.

### Fixed
- Downgrade `guzzlehttp/oauth-subscriber` package to be compatible with Social Poster.

## 1.2.2 - 2020-09-02

### Fixed
- Fixed rendering issue for category fields with children.
- Fixed rendering issue for tags fields.
- Fixed JS error when rendering a tag field.
- Fixed JS error when rendering a repeater field.

## 1.2.1 - 2020-09-02

### Added
- Entry fields now support custom element sources when outputting their list of available elements.
- Category fields now support custom element sources when outputting their list of available elements.
- Product fields now support custom element sources when outputting their list of available elements.
- User fields now support custom element sources when outputting their list of available elements.
- Variant fields now support custom element sources when outputting their list of available elements.
- Added `EVENT_MODIFY_ELEMENT_QUERY` to allow modification of the query used by element fields.

### Changed
- Update modal edit windows to show “Apply” instead of “Save” - as actions aren’t immediately saved until you save the form.

### Fixed
- Fixed a minor layout issue for the email notification preview with long text values.
- Fixed rich text-enabled multi-line text fields showing raw HTML in submission.
- Fixed rich text-enabled multi-line text fields not retaining their value after an error.

## 1.2.0 - 2020-09-01

### Added
- Added Email Marketing integrations category.
- Added ActiveCampaign, Autopilot, AWeber, Benchmark, Campaign Monitor, Constant Contact, ConvertKit, Drip, GetResponse, iContact, Mailchimp, MailerLite, Moosend, Omnisend, Ontraport, Sender, Sendinblue Email Marketing integrations.
- Added CRM integrations category.
- Added ActiveCampaign, Avochato, Freshdesk, HubSpot, Infusionsoft, Insightly, Pipedrive, Pipeliner, Salesflare, Salesforce, Scoro, vCita, Zoho CRM integrations.
- Added Webhooks integrations category.
- Added Generic Webhook, Zapier Webhooks integrations.
- Added Miscellaneous integrations category.
- Added Google Sheets, Monday, Slack, Trello Miscellaneous integrations.
- Added `useQueueForNotifications` and `useQueueForIntegrations` plugin settings, to control if queue jobs should be used to send emails and trigger integrations.

### Changed
- Element and Address Provider integrations can now have multiple instances created with different settings.

## 1.1.8 - 2020-08-27

### Added
- Provide `onAfterFormieSubmit` event with content about each submission.
- Added `fieldNamespace` render option for forms.

### Fixed
- Fixed freeform migration using an array for default value for email fields.
- Fixed potential issue that a submission could get “stuck” in a completed form.

## 1.1.7 - 2020-08-20

## Added
- Added `form.settings` for GraphQL requests.
- Added badge to new notifications to prompt the need to save the form.

## Fixed
- Fixed changed notifications not prompting for changed form when trying to navigate away.
- Fixed newly created notifications getting out of sync when continuing to edit the form.

## 1.1.6 - 2020-08-20

### Added
- Added `craft.formie.populateFormValues()`. See [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/available-variables).
- Added translation strings for all translatable text.
- Added setting spam state element action for submissions.
- Allow spam state to be toggled when editing a submission.

### Fixed
- Fixed GQL mutations error in Craft 3.4.
- Fixed multi-line column limit not allowing for content greater than 255 characters. Please re-save any form that uses a multi-line text field to get this change.

## 1.1.5 - 2020-08-18

### Fixed
- Fixed error introduced in Craft 3.5.5 when editing a form.
- Fixed repeater fields inner fields not using export-handling.

## 1.1.4.1 - 2020-08-18

### Fixed
- Fixed submission exports not normalising columns for repeater/table fields across multiple submissions.

## 1.1.4 - 2020-08-18

### Added
- Added GraphQL mutation support for submissions. [See docs](https://verbb.io/craft-plugins/formie/docs/developers/graphql#mutations).

### Changed
- Update exports to not split repeater/table into new rows.

### Fixed
- Fixed table field columns getting incorrect format when re-saving after a validation error.

## 1.1.3 - 2020-08-17

### Added
- Added `form.formId` and `form.configJson` shortcuts for templates.

## 1.1.2 - 2020-08-16

### Fixed
- Fixed form outputting CSS and JS, even when disabled.
- Fixed repeater field minimum instances not pre-populating the defined number of blocks.
- Fixed repeater field allowing to go below the set minimum instances.
- Fixed repeater field add block button not disabling when min and max instances are the same.
- Fixed repeater field add block not toggling disabled state correctly.
- Fixed submission exporting providing all values.
- Fixed submission CSV exports not splitting sub-field-enabled fields (address, multi-name) into multiple columns for their sub-fields.
- Fixed submission CSV exports not splitting complicated fields (repeater, table) into new rows to convey collection of data. JSON/XML exports are unchanged and show collections as arrays.

## 1.1.1.1 - 2020-08-11

### Fixed
- Fixed stencils not saving.

## 1.1.1 - 2020-08-10

### Added
- Added `outputJsBase` option for form templates.
- Added `outputJsTheme` option for form templates.
- Added `outputCssLocation` option for form templates.
- Added `outputJsLocation` option for form templates.
- Form templates can now control where CSS and JS is outputted on the page.
- Added `craft.formie.renderFormCss()` to manually render a form's CSS in your templates.
- Added `craft.formie.renderFormJs()` to manually render a form's JS in your templates.
- Added JavaScript API's and [documentation](https://verbb.io/craft-plugins/formie/docs/developers/javascript-api). Better handling with Vue.js/React.js and more.
- Allow Formie's JS to be imported into JavaScript modules.
- Improve JavaScript loading performance, by lazy-loading JS.
- Improve JavaScript by loading a single file - `formie.js`.

### Changed
- Update form template select to show correct loading indicator.

### Fixed
- Fixed reCAPTCHA JS not loading in some circumstances.
- Slightly improve email testing error message.
- Fixed date field missing subfield classes and hooks.
- Fixed error when “Output JavaScript” is set to false for a custom form template.
- Fixed multiple form error messages appearing in some cases.
- Fixed phone number field not validating server-side.
- Fixed form JS not initialising when using Vue.js as an async module.
- Fixed incorrect `columnWidth` GraphQL type.
- Fixed Form and Submission fields for GraphQL queries.

## 1.1.0 - 2020-08-03

### Added
- Added email notification testing.
- Added email notification preview.
- Added support for email notification duplication.
- Added Rich Text front-end appearance option for multi-text fields.
- Added preset options to hidden field.
- Added Element integration support.
- Added Entry element integration support.
- Added Address Providers integrations.
- Added Google Places, Algolia and Address Finder Address Providers.
- Added email alerts for failed email notifications.
- Added warning to file upload field for server-set upload limit.
- Added Ability to set "Today" as the default date for Date/Time fields.

### Changed
- HTML fields now have their content purified when output.

### Fixed
- Fixed HTML field showing ‘null’ or ‘undefined’ when no value set
- Fixed missing status when applying stencil from project config.
- Fixed incorrect email template crumb.
- Fixed issue where captchas weren't working.
- Ensure we return the current settings for failed plugin saving.
- Fix incorrect redirection when an error occurred saving the plugin settings.

## 1.0.9.1 - 2020-07-30

### Fixed
- Fixed another instance where multiple forms on the same page weren't working.

## 1.0.9 - 2020-07-30

### Added
- Added the ability to select existing notifications, made on other forms.

### Fixed
- Fixed JS error when multiple forms are on the same page.
- Fixed error messages showing for multiple forms on a single page.
- Fixed submission data not working correctly for multiple forms on a single page.

## 1.0.8 - 2020-07-30

### Fixed
- Fixed issue where “Save form as” did not properly clone repeater subfields.
- Fixed default label position class on form.
- Fixed raw submit action message being rendered for non-ajax forms.
- Ensure query string in URL is preserved when showing a success message for a form.
- Fixed front-end field error messages being overwritten on subsequent checks.

## 1.0.7 - 2020-07-28

### Fixed
- Remove unused `HtmlSanitizer` package.
- Fixed installation issue on PHP 7.0.

## 1.0.6 - 2020-07-27

### Fixed
- Allow deleting last stencil.
- Ensure form/email templates are processed in project config before stencils, preventing project config errors when applying stencils.
- Only allow “Save as a new stencil” if `allowAdminChanges` is enabled.

## 1.0.5 - 2020-07-26

### Added
- Added Rich Text field configuration plugin-wide. Provide a config object for available buttons, like you might for [Redactor](https://plugins.craftcms.com/redactor). See [docs](https://verbb.io/craft-plugins/formie/docs/get-started/configuration#rich-text-configuration).
- Added error message rich text field for form/stencil settings.
- Added GraphQL support for Submissions.
- Added support for “Save as new stencil” from a form.

### Changed
- Form/stencil submission message now support rich text.
- Form/stencil submission message is now stored as a prosemirror-compatible object.

### Fixed
- Fixed IP Address not saving for submissions.
- Fixed form change warning when submitting an Ajax form and redirecting.
- Fixed submissions query and `form` parameter not working correctly.
- Fixed “Save as new form” not redirecting to the newly created form.
- Fixed “Save as new stencil” new stencil generates a sequential handle, rather than a random handle.
- Fixed “Submission Message” error message not appearing.
- Fixed rich text fields not having their model values (resulting JSON) populated immediately.

## 1.0.4 - 2020-07-23

### Added
- Added warning to form template if using custom template.
- Added page button hooks and [docs](https://verbb.io/craft-plugins/formie/docs/developers/hooks).
- Added page index to page data attributes.
- Added `craft.formie.registerAssets` for template-cached forms. See [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms).

### Changed
- Moved template validation rule to base template so both email and form templates are validated.

### Fixed
- Fixed incorrect hooks on label-less fields (such as hidden field).
- Fixed missing button container classes.
- Fixed adding existing field always adding to first page.
- Fixed page spacing issue on multi-page Ajax forms.
- Fixed ajax-based multi-page forms validating entire form.
- Fixed CSS/JS issue with forms, when using the `{% cache %}` tag.

## 1.0.3 - 2020-07-22

### Added
- Added GraphQL support. See [docs](https://verbb.io/craft-plugins/formie/docs/developers/graphql).
- Added class to word and character limit text.

### Changed
- Cleaned up form and email templates, changing minor text, fixing some translations, typos and better field feedback.

### Fixed
- Fixed minor CSS causing field edit modal not to show when when clicking on the field label.
- Fixed missing error messages on name and address fields.
- Fixed “Save as a new form” button not saving a new form.
- Fixed checkbox-select Vue component not working correctly.

## 1.0.2 - 2020-07-21

### Added
- Added subfield hooks for name and date fields.

### Fixed
- Added missing style for left/right submit buttons.
- Fixed template theme CSS from being outputted when the layout is disabled.

## 1.0.1 - 2020-07-21

### Added
- Added hooks to address and phone subfields.

### Changed
- Move field errors outside of input containers.
- Hide required option for HTML field.

### Fixed
- Fixed db exception when saving form.
- Only send notifications for fully complete submissions.
- Fixed HTML field not wrapping in submissions edit page.
- Fixed bug where an empty repeater field with required subfields wouldn’t validate.
- Render email templates using `TEMPLATE_MODE_CP`.

## 1.0.0 - 2020-07-20

- Initial release.

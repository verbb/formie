# Changelog

## 3.1.10 - 2026-01-28

### Fixed
- Fix a Twig error introduced in Craft 5.9+ for some fields.
- Fix an error for Freshdesk tickets.

## 3.1.9 - 2026-01-23

### Added
- Add `allowPublicVolumes` plugin setting to allow only showing private (non-public URL) asset volumes for File Upload fields.
- Add extra log output when failing garbage collection jobs for submissions.

### Changed
- Improve client-side validation when using “live” validation mode (as the user types).
- Improve Date field sub-field cleanup when toggling between display types.
- Improve Name field sub-field cleanup when toggling between multi-name and single-name displays.
- When pruning spam submissions in garbage collection, submissions are now hard-deleted.

### Fixed
- Fix form usage site/element table order.
- Fix client-side validation using click events to evaluate.
- Fix field handles not generating correctly when deleting and adding fields at the same time with the same handle.
- Fix Date fields with default values not showing correctly in the form builder for various displayTypes.
- Fix Formie 2 migation for Date fields for display type Dropdowns and Inputs not respecting their previously enabled sub-fields for date/time.
- Fix Date field Dropdowns and Inputs display types not showing enabled sub-fields correctly in the form builder.
- Fix Formie 2 migration for Date fields not working correctly for Inputs or Dropdowns.
- Fix an error for integrations used across multiple forms, where different forms enabled different data objects, and mapping lost.
- Fix Date/Time Dropdown Year validation.
- Fix Date with Text Inputs submission after 3.1.7.
- Fix field conditions not working correctly for content encrypted fields.
- Fix incorrect handling of integration connected state.
- Fix pruning spam submissions not working correctly.
- Fix lack of success checks for garbage collection console commands.

## 3.1.8 - 2026-01-15

### Fixed
- Fix memory limits for garbage collection tasks.
- Fix lack of console output for garbage collection tasks.
- Fix an error when using Date/Time fields and populating or setting default values for calendar displays.
- Fix an error handling email notification “from” values when also setting a “from name”.
- Fix Make the url encoding keep the ampersand unencoded.

## 3.1.7 - 2026-01-13

### Added
- Add `FieldValueInterface` for model classes that represent the value of a field.
- Add `enableJsEvents` and `jsGtmEventOptions` to `PageSettingsInterface` for GraphQL.
- Add support for Formie Address Country to Craft Country field for Element integrations.

### Changed
- Update form import/export to be compatible with `tempAssetUploadFs`.
- Change submission behaviour when editing in the control panel when form settings like scheduling is enabled.
- Element integrations now handle array-like values for relation fields.

### Fixed
- Fix Date/Time fields for Dropdowns or Inputs not having their values treated consistently.
- Fix compatibility with Craft 5.9+.
- Fix element fields not showing their correct values in the submissions index in the control panel.
- Fix an error with File Upload fields with Feed Me.
- Fix unload warning for Snaptcha.
- Fix an error where “From” email notification values weren’t always properly parsed.
- Fix conditional logic handling for Repeater fields.
- Fix an error with Date fields in Repeater fields failing Year restriction validation.
- Fix number input fields in the form builder for Firefox.
- Fix an error where “From” email notification values weren’t always properly parsed.
- Fix Opayo and Moneris iframe event handling registration.
- Fix an error for Opayo billing name mapping.
- Fix overflow for IP Address values (particularly v6) when editing a submission in the control panel.
- Fix Pardot integration JSON/XML responses.
- Fix Pardot CRM base API URL.
- Fix Zoho CRM base API URL.
- Fix Pardot API scope.
- Fix cache-handling for integration connections.
- Fix deprecation error for `log()` calls.
- Fix an error using OopSpam.

## 3.1.6 - 2025-11-29

### Added
- Add `onFormieValidateError` JS event for client-side error handling.
- Add `isNewSubmission` argument to GraphQL mutation for submissions.
- Add debug logging for reCAPTCHA responses.

### Changed
- Webhook-based payment integrations (Mollie, GoCardless) now properly fire integrations and notifications.
- Improve front-end JS event management, to better deal with accidental multiple initialization of Formie’s JS in some setups.
- Question captcha protects against validation when no questions are set.
- Captchas for GraphQL mutations now don’t require a mandatory variables parameter to be named the same as their input type.

### Fixed
- Fix handling of integration settings when connecting.
- Fix Date (Time-only) fields and validation handling.
- Fix an issue with event-binding for front-end JS.
- Fix an import form issue when updating an existing form, where notifications weren’t being treated correctly.
- Fix Payment fields displaying in Summary field content by default.
- Fix Date/Time fields not using min/max values for native date input for non-Flatpickr.
- Fix synced fields overriding the required attribute.
- Fix Name Prefix field not showing its value correctly in email notifications.
- Fix Name Prefix field values not getting properly translated.
- Fix an error for integrations used across multiple forms, where different forms enabled different data objects, and mapping lost.
- Fix email notifications containing .env variables as conditions not working correctly.
- Fix `isNewSubmission` flag for GraphQL mutations for submissions, causing multiple notifications to be sent when conditions exist and were met.
- Fix mapping issues for Pipedrive Leads.
- Fix an error with Ortto and fetching lists.
- Fix HubSpot CRM integration handling of multi File Upload field values.
- Fix HubSpot field mapping for form company fields.
- Fix an error with handling Date field values (for date-picker fields) when in the control panel.
- Fix Formie 2 email notification migration for fields used in conditions.
- Fix an issue with Date fields, where the `availableDaysOfWeek` may not be typed correctly in some instances.
- Fix an issue with ReCAPTCHA Enterprise validation.
- Fix a 3DS processing issue for Moneris and Opayo in some setups.
- Fix an error when programatically changing the available countries to a Countries field.
- Fix Opayo, Stripe and Paddle Payment integrations incorrectly setting the Billing Address value for payments.
- Fix some missing properties for Date fields when querying via GQL.
- Fix some complex fields not having their error messages set when editing a submission in the control panel.
- Fix fetching an email signature controller action using CSRF verification when it doesn’t need to.

## 3.1.5 - 2025-11-06

### Added
- Add support for Name field values to be normalized properly when switching between single or multiple fields.
- Add handling for multi-site refresh-token front-end requests.
- Add support for File Upload fields to have uploaded asset IDs returned in submission data for Ajax, and improve handling for Ajax forms.
- Add `dateDate` and `dateTime` sub-field options to Theme Config for Date/Time field calendar display types.
- Add “Payment Status” column to Submissions element index.

### Changed
- Improve support for Submissions and querying/ordering via custom fields.
- Allow Name fields to be sortable for Submissions in the control panel.
- Update Sender Email Marketing integration to latest API.
- Client side validation is now in true “live” mode after an initial submit.
- HTML fields now default to not showing their content in email notifications.
- Date/Time fields with Flatpickr now use Craft’s default date/time picker when editing a submission in the control panel.
- Improve CSRF token refreshing for multiple forms, particularly for new cookie sessions.
- Update conditional logic evaluation in JS to default to `input` events when handling evaluation for most controls.
- Update Date field templates to use proper Twig coercion.
- Update EmailOctopus integration for V2 API.

### Fixed
- Fix an error when querying/ordering Name fields by their submission value.
- Fix an error when using raw variables in email notifications for some fields.
- Fix a Formie 3 and Events plugin 3 migration incompatibility.
- Fix “is not empty” server-side conditional logic.
- Fix client-side validation using `aria-describedby` and not `aria-errormessage` for accessible error messages.
- Fix Question captcha not working correctly for invalid submissions for Ajax forms.
- Fix Hubspot integration handling when dealing with “Submission Date” value mapped to Date fields in Hubspot.
- Fix HTML field not showing content in email notifications.
- Fix an error for File Upload fields contained within a Group or Repeater for Ajax forms.
- Fix UI for “Mark as Complete” setting when editing a submission in the control panel.
- Fix an error when trying to find nested field content for submissions.
- Fix an issue for nested fields or sub-fields not passing parent field render options.
- Fix “Upload Location” setting for File Upload field not being required.
- Fix an issue with new Recipient fields and the “Email Value” setting.
- Fix visual overflow issue for submission status dropdown in the control panel when editing a submission with a long status name.
- Fix Snaptcha Captcha support for Ajax forms.
- Fix a reactivity issue with Table field Default row values.
- Fix an issue for element integrations, when mapping from Formie Date/Time field to Craft Time field.
- Fix “is not empty” server-side conditional logic.
- Fix an error with JS conditional logic for some fields.
- Fix an issue with Table fields when setting their Default Values.
- Fix an error when handling conditionally-hidden fields within Group fields, which also contained conditions.
- Fix some email notification values not receiving raw values correctly.
- Fix an error when saving submissions from the front-end anonymously.
- Fix deprecation notice for Question captcha.
- Fix lack of navigation for File Upload fields when editing submissions via the control panel.
- Fix AM/PM handling for Date fields (Dropdown and Inputs).
- Fix CSRF handling for multi-site installs with sub-directory setups.
- Fix email notification conditional emails not being case-insensitive for matched emails.
- Fix Date/Time field and Year dropdown offset negative values.
- Fix Recipients field values not working correctly for some variables when needing raw values.
- Fix Zapier integration response handling.
- Fix serialization of element fields for integrations causing an infinite loop.
- Fix Categories field sources not showing correctly.

## 3.1.4 - 2025-09-16

### Added
- Add the ability to bulk-edit submission content from the Submissions element index view.
- Add handling for multi-site refresh-token front-end requests.
- Add handling for deprecated field warnings when upgrading Formie with `HARD_MODE` enabled.

### Changed
- Update migration-mode check.
- Update Element field’s visibility in the form builder when 1-2 sources are available.

### Fixed
- Fix Question captcha not working correctly.
- Fix support for some Dynamics 365 fields (Memo) not appearing in mapping.
- Fix an issue for redirect URLs when containing special (valid) characters.
- Fix handling for Form Usage when linked elements contain fatal errors.
- Fix an error when saving Element fields with specific elements picked.
- Fix Categories field not showing sources correctly.

## 3.1.3 - 2025-09-02

### Added
- Add `onFieldVisible` and `onFetchSummary` JS events for the Summary field.
- Add `setPage` to the Formie theme JS to set the page of a form client and server side.
- Add `Countries::EVENT_MODIFY_ADDRESS_COUNTRIES`.

### Changed
- Update captcha front-end JS to better handle multi-page and multi-capture scenarios.
- Update Monday integration to use new Contries service.
- Changed the Address Country field `getCountryOptions()` function to no longer be static.
- Changed the Phone field `getCountryOptions()` function to no longer be static.
- Deprecated `AddressCountry::EVENT_MODIFY_COUNTRY_OPTIONS` event.
- Renamed `verbb\formie\services\Phone` to `verbb\formie\services\Countries`.

### Fixed
- Fix handling of word-count function to better handle special characters in words.
- Fix File Upload field not working correctly when editing a submission in the control panel.
- Fix Formie 2.x to 3.x migration for Sub Fields within a Group or Repeater fields not migrating correctly.
- Fix an issue when trying to filter Checkboxes fields by their submission value or via GraphQL.
- Fix options fields not having their “Email Notification Value” set correctly.
- Fix Recipients field throwing an error when used in email notifications for Dropdown display type.
- Fix an encoding issue for some fields when used in email notifications.
- Fix Stripe integration setting missing Redirect URI.
- Fix an error with File Upload and Repeater field combinations, when using min/max row validation.
- Fix Pipedrive integration not handling updating person records via email correctly.
- Fix an error for the Monday integration when mapping to Checkbox fields.
- Fix Entry element integration not working correctly for multiple authors.
- Fix File Upload fields not working correctly for multi-page Ajax forms.
- Fix support for Date field validation for Ajax based forms.
- Fix incorrect handling of default values for Date fields.
- Fix an error for Monday integration when mapping to a Country field.
- Fix Name Prefix values in email previews, when customising options.
- Fix Google Sheets integration.

## 3.1.2 - 2025-08-12

### Added
- Add clarification to Salesforce “Use Credentials” setting.
- Add `AddressCountry::EVENT_MODIFY_COUNTRY_OPTIONS` event.
- Add `captcha` Theme Config key.
- Add support for multiple authors for Entry element integration.

### Changed
- Update import/export description for forms.
- Update Summary field for Ajax forms to refresh whenever they are visibly shown.
- Allow Question captcha HTML to be modified via template overrides.
- Allow email templates to make use of `renderOptions.hideName` for field labels in email content.

### Fixed
- Fix HubSpot Form Field Mapping for Ticket fields.
- Fix an error for User integrations and the address field check.
- Fix Google Address autocomplete handling of saved/existing values.
- Fix description for Cloudflare Turnstile captcha integration.
- Fix migration issue from Formie 2 for integration opt-in field settings.
- Fix Address Country sub-field not displaying correctly in email notifications.
- Fix an error when saving an Address field with no auto-complete integration set.
- Fix handling of some legacy field settings for older Formie installs or outdated stencils.
- Fix new fields when added to the form builder not having their defaults set correctly.
- Fix Forms field not working correctly for UIDs.
- Fix an error for Date fields for display type Dropdowns.
- Fix type mismatch when processing `defaultValue` for Date/Time fields.
- Fix Question captcha rendering issues.
- Fix an issue with Question captcha form settings and the “Security Question” setting.
- Fix the Cloudflare Turnstile captcha description.

## 3.1.1 - 2025-07-22

### Added
- Add support for element fields’ querying with `:notempty:` or `:empty:`.

### Fixed
- Fix incorrect handling of system settings for email notifications on non multi-site installs.
- Fix some payment integrations being unable to select fields for dynamic amount.
- Fix extra-small lightswitch visual bug.
- Fix an error when loading some captcha settings for a form (Friendly Captcha, hCpatcha, reCaptcha, Turnstile).
- Fix submission querying for Craft 5.8+.

## 3.1.0 - 2025-07-22

### Added
- Add Automation, Help Desk and Messaging integration types.
- Add PlaceKit Address Provider integration.
- Add n8n Automation integration.
- Add Make Automation integration.
- Add IFTTT Automation integration.
- Add Akismet Captcha integration.
- Add Captcha.eu Captcha integration.
- Add CleanTalk Captcha integration.
- Add OOPSpam Captcha integration.
- Add Question Captcha integration.
- Add Attio CRM integration.
- Add CiviCRM integration.
- Add Flowlu CRM integration.
- Add Marketo CRM Integration.
- Add NoCRM integration.
- Add Outseta CRM integration.
- Add Procurios CRM Integration.
- Add Salesmate CRM integration.
- Add SuiteCRM CRM Integration.
- Add Xero CRM Integration.
- Add Events Element Integration.
- Add Beehiiv Email Marketing integration.
- Add CleverReach Email Marketing Integration.
- Add Customer.io Email Marketing integration.
- Add Ecomail Email Marketing integration.
- Add Mailcoach Email Marketing integration.
- Add Ortto Email Marketing integration.
- Add Vero Email Marketing integration.
- Add Front Help Desk Integration.
- Add Gorgias Help Desk integration.
- Add Help Scout Help Desk Integration.
- Add Intercom Help Desk Integration.
- Add LiveChat Help Desk Integration.
- Add Zendesk Help Desk integration.
- Add BPOINT Payment integration.
- Add Eway Payment integration.
- Add GoCardless Payment integration.
- Add Mollie Payment integration.
- Add Moneris Payment integration.
- Add Paddle Payment integration.
- Add Square Payment integration.
- Add Discord Messaging integration.
- Add Plivo Messaging integration.
- Add Telegram Messaging integration.
- Add Twilio Messaging integration.
- Add ClickUp Miscellaneous integration.
- Add Commerce Product Element integration (for single-variant products).
- Add Ticket object support to HubSpot CRM integration.
- Add `Integration::beforeSaveForm()` and `Integration::defineClient()`.
- Add spam reason for Friendly Captcha when missing client-side token.
- Add integration front-end JS provider classes as separate exports to include in your own code.
- Add “is visible” and “is hidden” field conditions.
- Add parent field information to form builder for conditions.
- Add the ability to map to “Dependant Fields” for HubSpot integrations.
- Add the ability to set Address values for User element integrations.
- Add SharpSpring tracking data when mapping to a native form.
- Add the ability for Elements fields to set specific elements as available to be picked from.
- Add support for Date fields to set their Year Range start setting to a negative value to offset from the current year.
- Add “Progress Value Position” form setting to control where the percentage value for page process sits.
- Add the ability to mark an incomplete submission as complete in the control panel.
- Add `body` variable as alias to `contentHtml` for email notifications, to be compatible with Craft email templates.
- Add support for “Layout” setting for Element fields, when displayed as Checkboxes or Radio Buttons.
- Add `outputConsoleMessages` plugin setting to prevent CSRF token refresh console.log messages.
- Add support for form submissions to be limited by IP address.
- Add JS event `modifyAjaxClient` to modify the XHR client used for Ajax requests.
- Add JS event `modifyScriptUrl` to modify the CDN scripts for Phone and Date Picker libraries.

### Changed
- Re-organise form builder field categories.
- Rename Webhook integration to Web Request, and add more options for request settings.
- Move Slack and Telegram to Messaging integrations.
- Move Freshdesk, Gorgias and Zendesk to Help Desk integrations.
- Webhook integrations are now Automation integrations.
- Captcha integrations now no longer pre-select the first available type when editing.
- Re-order Captcha integrations alphabetically.
- Integrations can now control any required plugins.
- Captchas can now opt to validate earlier in the submission process, and prevent submission saving (like a field would).
- Form integration settings now no longer need to be saved when fetching new data/refreshing data.
- Improve integration success/fail feedback in the form builder.
- Integration settings pages have been re-worked with multiple tabs and an external docs link to instructions.
- Update spam keywords rules to new definition syntax.
- Update Phone field, no longer using CDN for utils and flag icons, updated look and feel.
- Update the `intl-tel-input` package for Phone field validation and handling.
- Change scroll-to-top behaviour to handle non-top level forms (in modal).
- Allow Radio Buttons and Checkboxes field option labels to include HTML (safe) or Markdown.
- Update Checkboxes and Radio Buttons fields to not show invalid label positions to select.
- Hidden or Disabled fields now have a visual indicator in the form builder.
- Google Sheets integration can now have their Spreadsheet ID set per-form.

### Fixed
- Fix `NestedFieldRow` elements not being garbage collected properly for deleted submissions.
- Fix Date field Year Range offsets not using the current year.
- Fix Phone field flag in the form builder.
- Fix Address field’s Autocomplete integration setting not validating correctly.

### Deprecated
- Deprecated `Automation::getWebhookUrl()`. Use `Automation::getEndpointUrl()` instead.

### Removed
- Removed “Webhook URL” plugin setting from Webhook integration (still available per-form).
- Integration docs are no longer provided within Formie, instead visit the [docs](https://verbb.io/craft-plugins/formie/docs).

## 3.0.32 - 2025-07-18

### Added
- Add the ability to set `cssAttributes` when rendering form CSS.
- Add support for proper search index handling for submission fields.
- Add support for Salesforce integration for Leads, for task creation on duplicate, to use the Lead ID by default.
- Add warning text for File Upload fields, when uploading to a filesystem with public URLs.

### Changed
- Update English translations.
- Improve error handling for Dynamics 365 with regards to entity permissions.
- Update Recaptcha server-side verification to use recaptcha.net for better availability.

### Fixed
- Fix phone field value encryption.
- Fix cosmetic fields in emails, improve Phone encrypted values in email, and improve sub-field email placeholder values.
- Fix Submission search handling for cosmetic or long field handles.
- Fix support for site-specific email settings for email notifications.
- Fix cosmetic fields not showing in email notifications.
- Fix typecast of Pipedrive “Owner ID” value.
- Fix Form element fields using IDs for source settings.
- Fix cosmetic fields not showing in email notifications.
- Fix an error for empty encrypted Phone Number fields.
- Fix an issue with Friendly Captcha for Ajax forms with Start Mode = Auto.
- Fix Signature field output in emails for multi-site installs.
- Fix incorrect type for “Submission Date” when mapping to integrations.

## 3.0.31 - 2025-07-03

### Changed
- Update front-end validation to show a error state of sub-fields correctly.

### Fixed
- Fix an error for Campaign integration when running via the queue.
- Fix an error for the Calculations field, when formatting as a number and using non-numeric fields in the formula.
- Fix an error when trying to determine a submissions status in some instances.
- Fix some fields not working correctly with content encryption.
- Fix an error for some fields that contained a `supportsNested` reference from Formie 2.
- Fix GraphQL queries for submissions in Craft 5.8+.
- Fix support for Craft 5.8.

## 3.0.30 - 2025-06-18

### Added
- Add “is empty” and “is not empty” to field and notification conditions.
- Add “Site Name” and “Site Handle” to conditions builders.
- Add multi-site support for form usage.

### Changed
- Update Google Places Address Provider to remove deprecated `google.maps.places.Autocomplete` component.
- Update submission logging to use `info` rather than `error` category, to prevent error handlers from picking up form validation errors.
- Improve performance of exporting submissions.
- Submit buttons on the front-end for forms now get a `data-loading` attribute set when clicked.
- Improve scroll-to-error behaviour for some browsers/front-end setups.

### Fixed
- Fix an error when running integrations with no Guzzle client.
- Fix an error for variable tag rich text nodes in some instances.
- Fix an error for Entry Element integrations and `defaultAuthorId` when set from a Stencil.
- Fix Calculations field parsing Markdown content.
- Fix Single and Multi Line field character/word limits server-side logic to better handle HTML.
- Fix submission titles and special characters.
- Fix element chips and quick-edit handling for Submission and Form elements in the control panel.
- Fix `limit` and `decimals` missing from Number field GraphQL queries.
- Fix an error for Feed Me feeds.
- Fix legacy section/type for Entry element integration.

## 3.0.29 - 2025-06-05

### Added
- Add trace logging to element integration failures.

### Fixed
- Fix “back” button in some instances where session data tracking the current page of a form isn’t handled correctly.
- Fix Recaptcha success logging.
- Fix an error when running element integrations via the queue.
- Fix a client-side validation error for required values when including whitespace characters.
- Fix legacy section/type for Entry element integration.
- Fix `Integration::log()` deprecation error notice.
- Fix an error for Element integrations when mapping a Formie options field to a Craft options field.

## 3.0.28 - 2025-05-20

### Added
- Add support for Freeform 5 Group field in migration.
- Add extra logging for reCaptcha failures.
- Add more payload context for failed queue jobs.
- Add `endpoint` and `method` to `payload` property for failed queue jobs for context.
- Add German translation for Signature Field.
- Add `code` to `countryOptions` property for Phone fields in GraphQL.

### Fixed
- Fix an error with some Payment field providers when resubmitting the form after payment.
- Fix an issue for Payment fields when a client-side error occured.
- Fix Date field handling of min/max dates on statically caches sites when using offsets.
- Fix an error validating minimum words limit for Multi-Line and Single-Line text fields.
- Fix Freeform 5 migration and Hidden fields.
- Fix an error with Freeform/Sprout Forms migrations and submission statuses.
- Fix an error for Element integrations when mapping a Formie options field to a Craft options field.
- Fix new installs not having the default plugin data set.
- Fix Checkboxes field missing `limitOptions`, `min`, `max`, `toggleCheckbox` and `toggleCheckboxLabel` properties in GraphQL queries.
- Fix Freeform 5 migration for HTML and Rich Text fields.
- Fix incorrect handling of serializing failed queue jobs.
- Fix Calculations field parsing Markdown content.
- Fix an error parsing Calculations fields’ formula containing decimals.
- Fix an error with Pipedrive, when only enabling Leads.

## 3.0.27 - 2025-04-29

### Changed
- Update Salesforce CRM integration attachment handling.
- Ensure full errors are logged for integrations. (thanks @boboldehampsink).

### Fixed
- Fix a timezone issue with Date fields when setting a min/max date relative to today.
- Fix PDF handling for Craft Cloud.
- Fix `StringHelper::cleanString` handling.
- Fix JavaScript errors when managing theme config classes.
- Fix an error for payment fields when removing alert classes via Theme Config.
- Fix typo with RegisterDateTimeFormatOptionsEvent. (thanks @jamesmacwhite).
- Fix an issue when using conditions to filter submissions.
- Fix an error on Craft Cloud when a failed queue job’s payload couldn’t be updated on a queue job.
- Fix Formie 2 migration where form titles could be null in some circumstances.
- Fix an error with User element integration when activating users.
- Fix issue with custom Lead fields in Copper. (thanks @antcooper).
- Fix an error when upgrading to Craft 5 and the `m250315_131608_unlimited_authors` Craft migration.

## 3.0.26 - 2025-04-22

### Fixed
- Fix parsing of email notification content and HTML.
- Fix 1CRM integration not querying existing data objects correctly.
- Fix an error when viewing submissions in element fields for Craft 5.7+.
- Fix an error with `submissionsBehaviour`.

## 3.0.25 - 2025-04-16

### Added
- Add `craft.formie.setRenderVariables()`.

### Changed
- Update JavaScript Captcha to use render variables for script attributes.
- Change “Default Submissions State” setting to accept multiple values for states.
- Change “Recent Submissions” dashboard widget table layout to list for better visual layout.

### Fixed
- Fix lightswitch UI for Craft 5.7+

## 3.0.24 - 2025-04-11

### Fixed
- Fix support for Craft 5.7+.

## 3.0.23 - 2025-04-11

### Added
- Add extra logging to Opayo payment failures.
- Add the ability to assign a Campaign to a Contact/Lead/Account in Salesforce.
- Add support for filtering submissions by fields in the control panel.
- Add the ability for HTML fields to reference render options via Twig in their content.
- Add the ability to provide submission query arguments for fields, for GraphQL.
- Add `Field::EVENT_MODIFY_VALUE_FOR_VARIABLE`.
- Add the ability to set the permissions of newly-created forms for users that have limited form access.

### Change
- Improve performance of submission editing screen in the control panel.
- Improve performance of submissions element index in the control panel.
- Improve field layout error output for nested fields and failed validation, which now references the page handle, field handle and any parent field handle.
- Update deprecated `log()` function.
- Move `Field::getValueForVariable` to `Field::defineValueForVariable`.

### Fixed
- Fix `submissionEndpoint` GraphQL property.
- Fix an error for Phone fields, when initializing the form’s JS multiple times.
- Fix payment field race condition when initializing form JS multiple times with conditions.
- Fix an error with Opayo and billing email.
- Fix an XSS vulnerability for importing forms with manipulated field content.
- Fix an XSS vulnerability for email notification content.
- Fix support for Craft 5.7+.
- Fix submission titles displaying incorrectly when referencing some fields in their content.
- Fix Agree field not rendering its empty value correctly in email notifications.
- Fix email notifications not rendering empty valued fields correctly.
- Fix Pardot integration not working correctly. (thanks @mirego).
- Fix sub-field fields not having their label correctly set in some instances when upgrading to Formie 3.
- Fix an error with Summary and Section fields for Formie 3 upgrades.
- Fix large field settings throwing an error (particularly for Formie 3 upgrades).

## 3.0.22 - 2025-03-27

### Added
- Add `Submission Date` to some field mapping controls.
- Add support for Recipients field to set the “Email Notification Value”.
- Add back `Field::EVENT_MODIFY_VALUE_FOR_EMAIL` events to modify the value used in Email Notifications.
- Add `SubmissionEvent::forceRedirect` option for events to force a redirect on form submission.
- Add `Form::setRedirectEntry()`.
- Add the ability to pass arguments to `templateJs` for GraphQL queries to control JS initialization.

### Changed
- Improve performance of form usage for large sites and content structures.
- Improve Submission export performance, by removing eager-loading non Formie fields.
- Update `Field::defineValueForEmail` to return the raw value for the field, rather than the string-representation of the value.
- Updated Feed Me integration for submissions to list forms in alphabetical order.
- Update GraphQL resolvers to use `ElementCollection` correctly.

### Fixed
- Fix some errors with Submissions widget for invalid dates and `weekStartDay` user settings.
- Fix an error when importing a form, made from an export from the Forms element index.
- Fix HubSpot GDPR processing when not enabled.
- Fix an issue with Iterable integration field mapping.
- Fix some fields not having their default values set correctly.
- Fix an error when importing a Formie 2.x export.
- Fix an issue where the row’s empty state wasn’t updated for nested or sub-fields using conditions.
- Fix an error when trying to query Form Template fields for a form with GraphQL.
- Fix Nested Fields not having their summary field values reporting correctly (value as string, json, exports, summary).
- Fix Stripe dynamic amount not processing currency values.
- Fix `providerSettings` when querying Payment fields in GraphQL.
- Fix an error with Payment fields when viewing a submission in the control panel in some instances.
- Fix a race condition for Repeater fields when set to use a minimum number of instances.
- Fix Feed Me being able to map to cosmetic fields.
- Fix an error for Feed Me when mapping to element fields.
- Fix a display error when only “Fields” tab is available for editing, when editing a form.
- Fix an issue where a form’s UID could be out of sync.
- Fix redirect override when setting via the `EVENT_AFTER_SUBMISSION_REQUEST` event.
- Fix an error where fields and rows were being incorrectly filtered when rendering a form multiple times.
- Fix when new Forms are created from a Stencil, their enabled captchas not being respected.

### Removed
- Remove unused “Column Type” setting from Hidden fields.

## 3.0.21 - 2025-03-04

### Added
- Add “Message Type” to Iterable CRM integration.
- Add the ability for IntegrationField’s to contain static `data` in their definitions.
- Add support for Table field to use `id` or `name` values when defining a row schema.
- Add “Attach File Uploads” for all data objects for Salesforce integration.

### Changed
- Improve Hubspot GDPR handling for marketing and processing options.
- Update Monday integration instructions.

### Fixed
- Fix Agree field not allowing `null` value as an empty state indicator.
- Fix Agree field markup to match correct accessibility guidelines.
- Fix Hubspot GDPR handling.
- Fix Monday integration connection requests.
- Fix an error when applying Formie-related project config when uninstalled.
- Fix Phone number fields flag icons in the form builder.
- Fix form usage throwing errors in some instances.
- Fix an error determining the default status for a submission, if none are set.
- Fix an error with Email Notifications when no valid fields could be found to be included in the email content.
- Fix Form Template setting not persisting for Stencils.
- Fix an error for captchas not rendering if the captcha placeholder was visually obscured.
- Fix Address and Multi-Name fields not working correctly when populating their field content.
- Fix Calculations field variables not being set correctly, when referencing other fields.
- Fix handling for some integrations where session data isn’t being persisted when connecting via OAuth.
- Fix server-side error messages for Repeater fields.
- Fix an error for File Upload fields with GraphQL, when min/max size limits were set.
- Fix a display issue for forms with multiple payment fields with the same integration.
- Fix payment summary display when editing a submission for Craft 5.6+.
- Fix multiple Payment fields not validating correctly when re-initializing the fields’ JS.
- Fix Phone Number server-side validation error.
- Fix an error with Stripe payments for subscriptions when including a payment receipt.

## 3.0.20 - 2025-02-02

### Added
- Add `Submission::getFormHandle()`.

### Changed
- Refactor queue jobs to provide better feedback on errors and logging data.
- Improve queue job feedback on error to include the payload being sent.

### Fixed
- Fix an error with Group and Repeater fields with required File Upload fields on multi-page Ajax forms.
- Fix Group and Repeater fields not respecting “Include in Email Notifications” setting and conditionally hidden fields in email notifications.
- Remove incorrect `Campaign = Kampagne` German translation for the Campaign plugin.
- Fix Single-Line text fields in the control panel not showing limit details correctly.
- Fix Multi-Line text fields in the control panel not showing limit details correctly.
- Fix new email notifications not having the conditions logic set correctly.
- Fix email notification queue job causing an infinite loop in some scenarios, and provide better logging feedback.
- Fix an error in Craft 5.6+ where fields’ `queryCondition()` function wasn’t being called.
- Fix some sidebar elements not displaying correctly when editing submissions in the control panel on Craft 5.6+.

## 3.0.19 - 2025-01-24

### Changed
- The `intl-tel-input` for Phone number fields no longer lazy-loads it’s utilities script.
- Update `intl-tel-input` for the latest number validation handling.
- Fields in email notification content, when referenced via their variable tag now no longer show their “no response” placeholder text. These still exist for grouped content like “All Fields”.

### Fixed
- Fix incorrectly bundled `intl-tel-input` version.
- Fix some string content not being escaped properly.
- Fix Freeform 5 migration for success behaviour.
- Fix default value for Date field not being set correctly.
- Fix File Upload handling for some database engines (MariaDB).
- Fix an error with Entry element integrations when updating values.
- Fix email notifications table when columns contained long variable tags.
- Fix incorrect logic when calling `Notifications::getFormNotificationByHandle()`.

## 3.0.18 - 2025-01-17

### Added
- Add support for inline CSS for some string content (Multi-Line Rich Text content).
- Add `Variables::EVENT_PARSE_VARIABLES` to allow you to parse custom registered variables.

### Fixed
- Fix reCAPTCHA Enterprise and score validation.
- Fix Dropdown and Input Date fields not saving correctly.
- Fix content errors with File Upload fields and MariaDB installs.
- Fix TableInput Vue component not respecting `initialValue` values.
- Fix a migration from Formie 2 for Email fields with Blocked Domains setting.

## 3.0.17 - 2025-01-13

### Added
- Add `contentType` to email attachments.
- Add theme config options for Table field inner field inputs.
- Add `Element::EVENT_MODIFY_ELEMENT_MATCH` event to control behaviour for Element integrations and matching an existing element.
- Add `filterIntegrationMapping` plugin setting to opt out of automatic filtering of integration mapping values.

### Changed
- Table field column templates are now split into separate files for easier overriding.
- Improve JS source map filesize.

### Fixed
- Fix signature field image matching on existing field, in some instances.
- Fix email notifications not correctly saving conditional recipients.
- Fix an error with migrating forms from Freeform 5.
- Fix Freeform 5 migration for some invalid field handles.
- Fix Freeform migration console commands.
- Fix some special unicode characters being stripped out of some text values for text-based fields.
- Fix Date fields and the “Available Days” setting not working correctly.
- Fix Entry element integration “Update Element Mapping” values being blank.
- Fix an error when setting a form template with required fields and validation handling.
- Fix a Formie 2 migration error for Calculation fields.
- Fix an error with some OAuth integrations and refresh token scopes..
- Fix Stencils incorrectly saving nested field layout data for sub-fields.
- Fix Entry element integration “Update Element Mapping” values.
- Fix Date fields (Simple) not providing the correct variable picker token for email notifications.
- Fix email notification “Send Test Email” button visual issue.
- Fix an error with Entry element integrations.
- Fix an issue with multi-page forms with session management in some browsers.
- Fix Formie 2 > 3 migration not retaining Form Template custom field values for forms.

## 3.0.16 - 2024-12-27

### Added
- Add context property for integrations to record extra data at submission time.
- Add support for Pardot tracking cookies for Form Handler.
- Add support for field conditions to use non-field conditions such as status.

### Fixed
- Fix any serialized `MissingField` classes not being converted back when the field is no longer missing.
- Fix an error importing forms and nested fields not retaining their submission content.
- Fix Dropdown and Checkboxes not validating correctly when in a nested field.
- Fix `Db::prepareForJsonColumn` deprecation and handling.
- Fix an error with Usage tab when Formie forms are referenced in a Neo block.
- Fix Freeform 4/5 migration. (thanks @ThomasDeMarez).

## 3.0.15 - 2024-12-17

### Added
- Add more comprehensive logging for user element integration.
- Provide Freeform 4 and Freeform 5 migrations.
- Add “Site” to conditions builders.

### Changed
- Update Freeform migration to support Freeform 5+.

### Fixed
- Fix not restoring trashed stencils when applying from project config.
- Fix form export not exporting number values correctly.
- Fix email notification file attachments not working correctly in some instances with sub-paths configured.
- Fix an error with client-side validation not clearing validation errors when rectified for some HTML elements.
- Fix an error with Pardot integration and requests.
- Fix an error when submissions had invalid content.
- Fix not restoring trashed stencils when applying from project config.
- Fix dynamic field settings not being applied to fields when editing a submission.
- Fix a validation error for Name fields in some languages where the Prefix options contained duplicate labels.
- Fix Name fields not validating correctly when saving for single-name fields.
- Fix an error when cloning some fields.
- Fix an issue when pre-populating Group or Repeater fields.
- Fix globally-enabled captchas not being enabled for new forms.
- Fix File Upload fields not working correctly in multi-page forms, in some cases.
- Fix an error for Checkboxes fields when using numeric values for options.
- Fix conditions not handling numbers correctly when evaluating conditions.
- Fix Date field’s default value not working correctly when set to Today’s date.
- Fix `Date::displayType` missing from Date field’s GraphQL schema.
- Fix an error when querying Table fields in some cases.
- Fix MissingField instances being included in GraphQL responses in some cases.
- Fix Captcha integrations not firing `validateCustom()` JS event.

## 3.0.14 - 2024-12-03

### Fixed
- Fix User and Entry element integration settings migration.
- Fix an error during Formie 2 migration.

## 3.0.13 - 2024-12-02

### Added
- Add the ability to change the storage behaviour of forms, rather than rely on sessions.

### Fixed
- Fix an error when saving integration settings for forms.
- Fix User element integrations not using UIDs for the target groups.
- Fix Entry element integrations not using UIDs for the target entry type.
- Fix an issue with User element integration and the “Send Activation Email” setting.
- Fix checkbox select fields field settings not retaining their value.
- Fix some missing translations.
- Fix Nested and Sub fields not showing the correct field label for validation.
- Fix plain-text variable pickers not working for multi-Name and Address fields.
- Fix asset bundle path when editing submissions in the control panel for Craft Cloud compatibility.
- Fix an error when deleting notifications.
- Fix status indicator for disabled notifications.
- Fix 'Required Field Indicator' template value.
- Fix being unable to query submissions by an Elements field.
- Fix element field sources not containing an “All” option, if their element sources have been modifed through events elsewhere in Craft.

### Removed
- Remove references to `relatedTo` for Forms and Submissions.

## 3.0.12 - 2024-11-16

### Fixed
- Fix an error for form usage, when dealing with nested entries.

## 3.0.11 - 2024-11-15

### Fixed
- Fix an error for form usage, when dealing with nested entries.
- Fix a Craft 5.5 migration compatibility issue.
- Fix user permissions for form access in the control panel.

## 3.0.10 - 2024-11-13

### Added
- Add the ability for Payment integration classes to modify the settings of the Payment field.
- Add `Field::modifyFieldSettings()`.
- Add the ability to use Twig in `style` attribute for Theme Config.

### Changed
- Update proxy URL for some integrations.

### Fixed
- Fix duplicated API Key setting for Google Places.
- Fix Iterable integrations when not mapping custom fields.
- Fix reactivity of integration field mapping for forms.
- Fix some integrations causing `post_max_size` and `input_max_vars` issues on Craft Cloud.
- Fix inactive or pending users showing in users field.
- Fix an error when migrating Freeform forms for a specific handle via the CLI.
- Fix `data-repeater-row-id` attribute for Repeater field rows.
- Fix Phone field not being mappable for Feed Me.
- Fix an error with Elements fields.
- Fix a Craft 5.5 compatibility issue.
- Fix an error during Craft 5 migration.
- Fix an error when setting the default value on an element field.
- Fix Date dropdown and input fields not validating correctly.
- Fix an error when ordering forms via the Page Count value.
- Fix Date/Time field values in the submission index.
- Fix Stripe offsite payments not redirecting correctly after callback.
- Fix element fields and their default value throwing an error.
- Fix element select fields not working correctly in some field/notification settings.
- Fix Stripe not retaining some appearance settings.
- Fix field validation including some handles that aren’t reserved.
- Fix deprecation warning when adding an existing field.
- Fix Formie 3 migration for payment fields.
- Fix an error with Calculations fields when using a ternary operator expression.
- Fix custom Name field Prefix values not showing in email notifications.
- Fix cosmetic fields showing in email notifications.
- Fix Address Country sub-field not working correctly for email notifications.
- Fix an error when migrating Freeform forms for a specific handle via the CLI.
- Fix “Default Date Display Type” setting.
- Fix “Default File Upload Volume” plugin setting.
- Fix single Name fields not showing their required indicator.
- Fix an error when migrating Group and Repeater field content from Formie 2.

## 3.0.9 - 2024-10-20

### Added
- Add `data-repeater-row-id` attribute to Repeater field rows.
- Add Data Center setting for Zoho CRM Integration.
- Add attachment support for File Upload fields for Salesforce Case objects.
- Add `templateCss` and `templateJs` for GraphQL.
- Add `Rendering::EVENT_MODIFY_FRONT_END_JS_TRANSLATIONS` event.

### Fixed
- Fix reCaptcha Enterprise flagging spam in certain situations.
- Fix an error with Sent Notifications, when called too early before a `dateCrated` has been set.
- Fix Address field Country sub field not working with conditions.
- Fix an error when migrating from Formie 2 for Postgres for some integrations.
- Fix Stripe payment error/success messages not working with `resetClasses`.
- Fix an error when trying to submit a form without Stripe.js being ready.
- Fix an error with Email Notification subject and special characters.
- Fix reCaptcha Enterprise flagging spam in certain situations.
- Fix option-fields (Checkboxes, Dropdown, Radio) not working correctly for GraphQL.
- Fix File Upload fields not working in integrations correctly.
- Fix Single-Line and Multi-Line Text fields when limiting values, not being translated consistently client-side.
- Fix Number input client-side validation strings not being translated.
- Fix an error when using `templateHtml` for GraphQL.
- Fix Date field preview for Date Picker/Calendar when an inner field is marked as required.
- Fix some fields being able to be marked as required, when they shouldn’t.
- Fix Datepicker/Calendar Date fields not working correctly in a Repeater.
- Fix some session errors on Craft Cloud. (thanks @timkelty).

## 3.0.8 - 2024-10-09

### Added
- Add Iterable Email Marketing integration.
- Add Iterable CRM integration.
- Add separate dropdown in Submissions index view in the control panel for state (all, complete, incomplete, spam), rather than bundle with status.

### Changed
- Update “All Submissions Behaviour” to select-list to pick a specific collection of submissions to show for any source.

### Fixed
- Fix toggling the enabled state of integrations not updating in the sidebar.
- Fix an error when previewing email notifications with Element fields in Postgres.
- Fix Signature field support for Group fields when accessing their image remotely.
- Fix an error when editing a Stencil with integrations enabled.
- Fix an error saving date picker Date fields in the control panel.
- Fix an error when parsing Date field values.
- Fix an error when using a Calculations field in combination with a Group field.
- Fix an error when using Calculations field values for payment field amounts.
- Fix Date fields not working correctly for variable-picker values.
- Fix payment field reference not working correctly.
- Fix Date fields (for date pickers) not showing correctly when editing a submission in the control panel.
- Fix an error when editing stencils with invalid (deprecated) data.
- Fix element fields not working correctly for disabled elements.
- Fix an error when previewing email notifications with Element fields in Postgres.
- Fix form settings not being set correctly when duplicating a form.
- Fix an error for Submissions dashboard widget.
- Fix Signature field support for Group fields when accessing their image remotely.
- Fix element fields not working correctly for disabled elements.
- Fix Salesforce integration and some fields being shown as required, when they aren’t.
- Fix an error when editing a Stencil with integrations enabled.

## 3.0.7 - 2024-09-14

### Added
- Added `Integration::getSettingsHtmlVariables()` and `Integration::getFormSettingsHtmlVariables()`.
- Added support for all CRM integrations to only fetch data objects for ones that are enabled in the form builder integration settings.
- Added Dutch translations. (thanks @jeroenlammerts).

### Changed
- Updated Password field `autocomplete` attribute.
- Improve Integration form instructions translations to remove duplicate translation strings.
- Improve Integration settings instructions translations to remove duplicate translation strings.
- Update integration descriptions to be dynamic for better translation.
- Days and Months predefined options now use Craft’s locale helpers for consistency.
- Country and State predefined options now use `commerceguys/addressing` for consistency.
- Fields now toggle a `data-field-has-error` attribute on inputs when client-side validation occurs.

### Fixed
- Fixed an error when creating forms where a default Form Template had required fields.
- Fixed an error when fetching Signature field image.
- Fixed an issue when using "Validate when typing” and resetting classes via Theme Config.
- Fixed element fields not always populating the correct site-specific element when viewing a submission in the control panel.
- Fixed querying submissions for nested field, via their nested field values.
- Fixed Submission queries not working correctly for custom fields.

### Removed
- Removed `FieldInterface::subFieldLabelPosition` for GraphQL (use a proper field fragment).

## 3.0.6 - 2024-09-07

### Added
- Added “Start Mode” setting to Friendly Captcha.
- Added the ability to set `scriptAttributes` and `jsAttributes` for `<script>` tags that Formie uses.
- Added the ability for `craft.formie.renderJs` to set JS attributes for scripts.
- Added `onFormieCustomValidate` JavaScript event.
- Added `renderOptions.customInputs` to allow custom hidden input content to be inserted into a form.

### Changed
- Changed Phone input autocomplete from `tel-national` to `tel` to ensure valid autocomplete value.

### Fixed
- Fixed an issue for Stripe and Opayo 3DS handling in combination with captchas not working correctly.
- Fixed an error for GraphQL when querying submissions with brand-new Group fields with no content.
- Fixed an error with Freshdesk integration when handling duplicate contacts.
- Fixed an error with Salesforce integration when handling duplicate leads.
- Fixed an error when duplicating forms with nested fields.
- Fixed integrations enabled indicator.
- Fixed a JavaScript error when loading Formie’s JS in a module for a Repeater field.
- Fixed an error validating Repeater sub-fields.
- Fixed an error when email notifications contained references to field content, and didn’t convert special characters correctly.

## 3.0.5 - 2024-08-29

### Fixed
- Fixed an error when parsing variable tokens.

## 3.0.4 - 2024-08-29

### Added
- Added “Page URI” and “Page Name” to HubSpot integration for Forms.
- Added support for Form Template custom field validation for forms.
- Added compatibility with Craft Link field.

### Changed
- Klaviyo Email Marketing integration now orders lists alphabetically by name.
- Klaviyo Email Marketing integration now loads more than 10 lists.
- Improved HubSpot CRM integration for HubSpot Forms, where fields don’t have a label.
- Dynamics365 system users now no longer include disabled user accounts.
- Improve Dynamics365 CRM integration to filter system users that are non-application-specific.
- Updated Dynamics365 CRM Integration and Lookup fields to automatically determine which entities to fetch field values for, rather than a static schema.
- Payment fields now no longer process if they are set to visibility disabled.
- Hidden or Disabled fields now have a visual indicator in the form builder.

### Fixed
- Fixed an edge-case with variables, where cached data matched against incorrect submission values.
- Fixed an error when fetching Summary field HTML.
- Fixed an error when fetching Signature field image.
- Fixed an error with Klaviyo CRM integration.
- Fixed being unable to select the top-level field for a Sub-Field in variable picker fields.
- Fixed an error when viewing a Submission in the control panel for a Date field with “Calendar: Advanced”.
- Fixed an error with Date field default value in some cases when saving a form.
- Fixed Entries fields not being able to select Entry Types as sources.
- Fixed Formie 2 migration for Sub-Field inner fields, not retaining their settings upon migration.
- Fixed server-side empty validation for Phone fields.
- Fixed an issue with Theme Config and disabling HTML elements from rendering not working.
- Fixed Date field validation.
- Fixed Dynamics365 CRM integration and lookup fields when referencing custom entities.
- Fixed field validation for Sub, Group and Repeater fields and their inner fields.
- Fixed an issue with Dynamics365 CRM integration and Picklist field options.
- Fixed conditional logic not working correctly for Groups and Repeaters.
- Ensure view permissions are enforced for “Recent Submissions” dashboard widget.

## 3.0.3 - 2024-08-14

### Fixed
- Fix a compatibility issue with `nystudio107/craft-plugin-vite` 5.0.2.

## 3.0.2 - 2024-08-14

### Added
- Added `initSubmit` JS API function to allow programmatic submissions.
- Added “Tenant” setting to Microsoft Dynamics 365 CRM integration.

### Fixed
- Fixed an error when refreshing tokens on some installs.
- Fixed an error when creating nested fields in some cases.
- Fixed an error rendering element fields.
- Fixed translations.

## 3.0.1 - 2024-08-11

### Added
- Added `processSubmit` JS API function to allow submission processing to continue if preventing submission via the `onBeforeFormieSubmit` JS event.
- Added support for WEBP flag images for Phone fields. Add a `.no-webp` class in your form to opt-out of this behaviour to fallback to PNG flags.

### Fixed
- Fixed errors when attaching some files to support requests.
- Fixed Date fields with a default value, or min/max date not having their values normalized correctly.
- Fixed an error when refreshing tokens via JS, for a non-top-level webroot site.
- Fixed an error when populating Element fields when also limiting field values.
- Fixed an error when trying to order Submissions by their title in the control panel.
- Fixed an error where conditional Email Notifications were being triggered twice for new submissions.
- Fixed an issue where missing required field values for Nested or Sub-Field fields weren’t being marked as required during validation.
- Fixed an error where conditional Email Notifications were being triggered twice for new submissions.
- Fixed some modal button spacing issues.
- Fixed an error viewing Submissions with Radio Button fields with numeric values in the control panel.

## 3.0.0 - 2024-08-06

### Breaking Changes
- Repeater and Group fields values now no longer use elements, just plain arrays. This brings several performance improvements and simplification to these fields.
- OAuth-based integrations now use the [Auth Module](https://verbb.io/packages/auth) to handle authentication under the hood.
- References to `subfield` is now `subField` for various classes.
- Element fields (Categories, Entries, File Upload, Products, Tags, Users, Variants) now use their public URL in email notifications.
- Options fields (Checkboxes, Dropdown, Radio) now use their option labels in email notifications.
- Changed `fieldInputContainer` to `fieldInputWrapper` for Theme Config and `.fui-input-container` class to `.fui-input-wrapper` for fields.
- Date fields now no longer use Flatpickr as a date-picker by default.
- Changed the value returned for Address fields when queried via GraphQL.
- Change Field’s `name` to `label` for GraphQL queries.
- Change Page’s `name` to `label` for GraphQL queries.

### Added
- Added new user interface for sub-field (Address, Date, and Name).
- Added the ability to re-order sub-fields.
- Added the ability to edit the full settings of sub-field fields.
- Added ability to send email notifications or trigger integrations when unmarking a submission as spam.
- Added the ability to set the control panel or public URL for element fields (Categories, Entries, File Upload, Products, Tags, Users, Variants).
- Added the ability to set the label or value for options fields (Checkboxes, Dropdown, Radio).
- Added the ability to override “All Form Fields”, “All Non Empty Fields” and “All Visible Fields” variables with Email Notification templates.
- Added “Calendar (Simple)” and “Calendar (Advanced)” to Date field display types, replacing “Use Date Picker”.
- Added CSS Layers support for front-end CSS.
- Fields moved in and out of Group fields now have their content moved as well.
- Fields can now be moved to and from Group/Repeater fields.
- Added “Required Field Indicator” for forms, to either show an asterisk for required fields (default) or show optional for non-required fields.
- Added the `form.setPageSettings()` function to override page settings in your Twig templates.
- Added support for Group and Repeater fields to be added as an existing field, or a synced field in the form builder.
- Added support for Repeater fields to use conditions (within their own row of fields).
- Added support for all CRM integrations to only fetch data objects for ones that are enabled in the form builder integration settings.
- Added keyboard navigation to variable picker dropdown.
- Added the ability to type `{` in variable picker components to autocomplete variables.
- Added `handle` to Email Notifications that can be accessed directly, instead of by their ID.
- Added `isFinalPage` in JSON response for Ajax-based forms.
- Added “All Submissions Behaviour” plugin setting.
- Added the ability to store custom data (`customSettings`) on a Notification, to store extra data against a Notification.
- Added the ability to modify Notification tabs and field settings (schema) via `Notifications::EVENT_MODIFY_NOTIFICATION_SCHEMA`.
- You can now get submission field values via dot-notation for nested values. e.g. `submission.getFieldValue('group.text')` or  `submission.getFieldValue('repeater.1.text')`
- You can now query submission field values via dot-notation for nested values. e.g. `submission.field('group.text').one()` or  `submission.field('repeater.1.text').one()`
- Integrations can now populate a `$context` property with arbitrary data that's stored before processing, and accessible in the queue job.
- Allow `craft.formie.renderJs` to set JS attributes for scripts.
- Added `data-fui-alert-error` and `data-fui-alert-success` attributes on front-end alerts.
- Added `data-field-label` attribute to labels/legends for fields.
- Added `data-validation` to fields, to denote what validators to use for the field.
- Added `initRow` to Repeater field JS events.
- Added client-side validation for min/max word/character limit for text fields.
- Added the current rowId for the `data-repeater-row` attribute for Repeater fields.
- Added `onFormieLoaded` JS event.
- Added double-clicking a page in the form builder now opens the pages editor.
- Added “Recipients” to the Email Notifications index table.
- Added Table node to rich text editor settings (used for numerous form, field and notification settings).
- Added the ability for Recipients fields to pre-populate the field via their option label.
- Added `verbb\formie\fields\subfields` classes to better handle sub-field inner fields.
- Added `NestedField::EVENT_MODIFY_NESTED_FIELD_LAYOUT` to modify the field layout of Nested or Sub-Fields.
- Added `verbb\formie\base\CosmeticField` class.
- Added `verbb\formie\base\ElementField` class.
- Added `verbb\formie\base\MultiNestedField` class.
- Added `verbb\formie\base\OptionsField` class.
- Added `verbb\formie\base\SingleNestedField` class.
- Added `verbb\formie\base\SubField` class.
- Added `Field::getValueForVariable()` to allow fields to handle logic for variables.
- Added `Field::getValueForCondition()` for handling serialization for condition evaluation.
- Added `Field::getValueForEmailPreview()` for fields to define their own preview for email notifications.
- Added `Field::fieldKey` to represent the handles of a field and any parent field. e.g. `group.text` or `repeater.text`.
- Added `Field:: lowerClassName()`.
- Added `Field::isDisabled`.
- Added `Field::enabled` to allow you to disable a field.
- Added `Submission::hasStatusChanged()` and `Submission::hasSpamChanged()`.

### Changed
- Now requires PHP `8.2.0+`.
- Now requires Craft `5.0.0+`.
- Updated Vue, Vite, Formkit and all JS dependencies to their latest versions.
- Updated Feed Me integration support for Feed Me 6+.
- Updated Freeform migration to support Freeform 5+.
- Submission content no longer have their own content tables. Content is now in a single `content` column, in your `formie_submissions` database table.
- Submissions now have Create/Save/Delete user permissions.
- Submissions now have separate view and manage user permissions.
- Sent Notifications now have “All” or per-form user permissions for View/Resend/Delete.
- `Formie::log()` is now `Formie::info()`.
- `Integration::log()` is now `Integration::info()`.
- Updated form builder modals and implement better modal accessibility.
- Switched Stripe payments to use “Payment Web Element”, adding the ability to use non-credit card payments like Apple Pay, AfterPay, etc
- Revamped front-end validation and removed `bouncer.js`.
- Submissions now send any email notifications that have status conditions when a completed submission is saved.
- Field errors now only show their first error when validation fails.
- Re-organise validator rules and add client-side match field validator.
- `data-field-handle` for fields now includes the full dot-notation “fieldKey” of the field, including any parent. So `name.firstName`, `group.text` or `repeater.new1.text`.
- Captchas for GraphQL mutations now don’t require a mandatory variables parameter to be named the same as their input type.
- Querying fields and rows via GraphQL now default to only returned enabled fields.
- Front-end form JavaScript now waits until the form has entered the viewable area on the page to be initialized.
- The `onFormieInit` now fires on every initialization of a form, when it’s visible on the page.
- Captchas now smartly load whenever they have entered the viewable area on the page. This greatly improves page-load performance when the form is initially hidden (in a modal for example).
- Sub-fields now extend from the `verbb\formie\base\SingleNestedField` and inherit many behaviours from Group fields.
- Phone fields are no longer `verbb\formie\base\SubField` fields.
- Sub-field fields now store their field config in their own row in the `formie_fields` database table, under their own layout (page, row, field).
- Update GraphQL interfaces for all fields to explicitly define fields to query. Previously these were automatically done via Reflection.
- Integration field mapping now uses `field:fieldHandle` syntax for fields.
- Integration field mapping now uses dot-notation (`field:group.text`) syntax for nested fields.
- Conditions (fields, pages, notifications) now uses `field:fieldHandle` syntax for fields.
- Conditions (fields, pages, notifications)  now uses dot-notation (`field:group.text`) syntax for nested fields.
- Submissions element index now show incomplete and spam submissions alongside completed submissions.
- Changed form `Title` references to form `Name`.
- Changed `fieldErrors` and `fieldError` elements from `ul` and `li` respectively to `div`.
- HubSpot CRM integration now automatically saves the `hubspotutk` cookie at the time of submission, to be sent with API requests. This means you now no longer need to map a form field to ensure the `hubspotutk` tracking cookie is sent.
- `nextPageIndex` in JSON response for Ajax-based forms now returns `null` when submitting on the final page to match `nextPageId`.
- Update Date field’s `availableDaysOfWeek` to return an array of strings as opposed to a JSON-encoded array for GraphQL.
- Email Notification field templates now no longer output a paragraph tag and the field label.
- Recipients fields values are now included in Email Notification content.
- Updated the `intl-tel-input` package for Phone field validation and handling.
- Date fields now show the required state on the outer label for Calendar and Date Picker display types.
- Name field values now return the full name including prefix and middle name (if provided).
- Address Country and Name Prefix fields now use their respective label values for string representations of their value.
- Adjusted dropzone size for form builder.
- Update Payment fields to provide a more client-friendly error message when a payment fails.
- Payment integrations can now have their field templates overridden in Form Templates.
- Updated email notifications index to show Name and Subject variable previews when used.
- Improved email notification preview error message.
- Updated `stripe/stripe-php` to be compatible with (commerce-stripe)[https://github.com/craftcms/commerce-stripe].
- Remove Section and Summary fields from rich text editor and variable picker options.
- Changed `craft\fields\data\MultiOptionsFieldData` to `verbb\formie\fields\data\MultiOptionsFieldData`.
- Changed `craft\fields\data\OptionData` to `verbb\formie\fields\data\OptionData`.
- Changed `craft\fields\data\SingleOptionFieldData` to `verbb\formie\fields\data\SingleOptionFieldData`.
- Changed `craft\fields\data\ColorData` to `verbb\formie\fields\data\ColorData`.

### Fixed
- Fixed multiple Tippy.js instances in the form builder when field settings contained multiple “info” elements.
- Fixed alerts on front-end not respecting theme config.
- Fixed Commerce fields initializing when Commerce wasn’t installed or classes exist.
- Fixed text-limit character check for emojis on the front-end.
- Fixed lack of validation for Date fields and their Default Value when setting to a specific date.
- Fixed behaviour of field variable tags in Email Notifications, where referencing a single field produced different output compared to when used in consolidated variables (e.g. “All Form Fields”).
- Fixed lack of client-side validation for min/max Number fields.
- Fixed lack of server-side validation for min/max Number fields.

### Removed
- Removed `currentPageId` from JSON response for Ajax-based forms, as it’s no longer necessary.
- Removed `verbb\formie\base\NestedFieldTrait` class.
- Removed `verbb\formie\elements\NestedFieldRow` class.
- Removed `verbb\formie\elements\dbNestedFieldRowQuery` class.
- Removed `verbb\formie\events\FieldPageEvent` class.
- Removed `verbb\formie\events\FieldRowEvent` class.
- Removed `verbb\formie\events\ModifyEmailFieldUniqueQueryEvent` class.
- Removed `verbb\formie\events\OauthTokenEvent` class.
- Removed `verbb\formie\events\SyncedFieldEvent` class.
- Removed `verbb\formie\events\TokenEvent` class.
- Removed `verbb\formie\models\Sync` class.
- Removed `verbb\formie\models\SyncField` class.
- Removed `verbb\formie\models\Token` class.
- Removed `verbb\formie\records\NestedFieldRow` class.
- Removed `verbb\formie\records\PageSettings` class.
- Removed `verbb\formie\records\Row` class.
- Removed `verbb\formie\records\Sync` class.
- Removed `verbb\formie\records\SyncField` class.
- Removed `verbb\formie\records\Token` class.
- Removed `verbb\formie\services\NestedFields` class.
- Removed `verbb\formie\services\Syncs` class.
- Removed `verbb\formie\services\Tokens` class.
- Removed `formie/gc/delete-orphaned-fields` console command.
- Removed `formie/gc/prune-syncs` console command.
- Removed `formie/gc/prune-content-tables` console command.
- Removed `formie/gc/prune-content-table-fields` console command.
- Removed `Formie::$plugin->getNestedFields()`.
- Removed `Formie::$plugin->getSyncs()`.
- Removed `Formie::$plugin->getTokens()`.
- Removed `Categories:categoriesQuery` variable for Category element field templates.
- Removed `Categories:entriesQuery` variable for Entry element field templates.
- Removed `Categories:productsQuery` variable for Product element field templates.
- Removed `Categories:usersQuery` variable for User element field templates.
- Removed `Categories:variantsQuery` variable for Variant element field templates.
- Removed `Syncs::EVENT_BEFORE_SAVE_SYNCED_FIELD` event
- Removed `Syncs::EVENT_AFTER_SAVE_SYNCED_FIELD` event
- Removed `verbb\formie\events\ModifyFrontEndSubFieldsEvent`.

### Deprecated
- `Submission::getCustomFields()` method has been deprecated. Use `Submission::getFields()` instead.
- `Field::name` attribute has been deprecated. Use `Field::label` instead.
- `Field::inputHtml()` method has been deprecated. Use `Field::cpInputHtml()` instead.

## 2.2.9 - 2026-01-15

### Fixed
- Fix an error handling email notification “from” values when also setting a “from name”.
- Fix Make the url encoding keep the ampersand unencoded.

## 2.2.8 - 2026-01-13

### Changed
- Element integrations now handle array-like values for relation fields.

### Fixed
- Fix cache-handling for integration connections.

## 2.2.7 - 2025-11-29

### Added
- Add debug logging for reCAPTCHA responses.

### Changed
- Webhook-based payment integrations (Mollie, GoCardless) now properly fire integrations and notifications.
- Question captcha protects against validation when no questions are set.
- Captchas for GraphQL mutations now don’t require a mandatory variables parameter to be named the same as their input type.

### Fixed
- Fix Payment fields displaying in Summary field content by default.
- Fix Date/Time fields not using min/max values for native date input for non-Flatpickr.
- Fix an issue with Date fields, where the `availableDaysOfWeek` may not be typed correctly in some instances.
- Fix an issue with ReCAPTCHA Enterprise validation.
- Fix fetching an email signature controller action using CSRF verification when it doesn’t need to.

## 2.2.6 - 2025-11-06

### Added
- Add handling for multi-site refresh-token front-end requests.
- Add “Payment Status” column to Submissions element index.

### Changed
- Improve CSRF token refreshing for multiple forms, particularly for new cookie sessions.
- Update conditional logic evaluation in JS to default to `input` events when handling evaluation for most controls.
- Update EmailOctopus integration for V2 API.

### Fixed
- Fix Snaptcha Captcha support for Ajax forms.
- Fix “is not empty” server-side conditional logic.
- Fix an error with JS conditional logic for some fields.
- Fix an error when handling conditionally-hidden fields within Group fields, which also contained conditions.
- Fix some email notification values not receiving raw values correctly.
- Fix email notification conditional emails not being case-insensitive for matched emails.

## 2.2.5 - 2025-09-16

### Fixed
- Fix Categories field sources not showing correctly.

## 2.2.4 - 2025-09-16

### Changed
- Update Element field’s visibility in the form builder when 1-2 sources are available.

### Fixed
- Fix support for some Dynamics 365 fields (Memo) not appearing in mapping.
- Fix an issue for redirect URLs when containing special (valid) characters.
- Fix Categories field not showing sources correctly.

## 2.2.3 - 2025-09-02

### Added
- Add `onFieldVisible` and `onFetchSummary` JS events for the Summary field.
- Add `setPage` to the Formie theme JS to set the page of a form client and server side.

### Fixed
- Fix an encoding issue for some fields when used in email notifications.
- Fix Stripe integration setting missing Redirect URI.
- Fix Google Sheets integration.

## 2.2.2 - 2025-08-12

### Added
- Add clarification to Salesforce “Use Credentials” setting.

### Changed
- Update import/export description for forms.
- Update Summary field for Ajax forms to refresh whenever they are visibly shown.

### Fixed
- Fix HubSpot Form Field Mapping for Ticket fields.
- Fix an error for Tags fields, being unable to change the “Label Source” setting.
- Fix an error for Users fields, being unable to change the “Label Source” setting.
- Fix an error for User integrations and the address field check.
- Fix Google Address autocomplete handling of saved/existing values.
- Fix description for Cloudflare Turnstile captcha integration.

## 2.2.1 - 2025-07-22

### Fixed
- Fix an error when loading some captcha settings for a form (Friendly Captcha, hCpatcha, reCaptcha, Turnstile).

## 2.2.0 - 2025-07-22

### Added
- Add Automation, Help Desk and Messaging integration types.
- Add PlaceKit Address Provider integration.
- Add n8n Automation integration.
- Add Make Automation integration.
- Add IFTTT Automation integration.
- Add Akismet Captcha integration.
- Add Captcha.eu Captcha integration.
- Add CleanTalk Captcha integration.
- Add OOPSpam Captcha integration.
- Add Question Captcha integration.
- Add Attio CRM integration.
- Add CiviCRM integration.
- Add Flowlu CRM integration.
- Add NoCRM integration.
- Add Outseta CRM integration.
- Add Salesmate CRM integration.
- Add Beehiiv Email Marketing integration.
- Add Customer.io Email Marketing integration.
- Add Ecomail Email Marketing integration.
- Add Mailcoach Email Marketing integration.
- Add Ortto Email Marketing integration.
- Add Vero Email Marketing integration.
- Add Gorgias Help Desk integration.
- Add Zendesk Help Desk integration.
- Add BPOINT Payment integration.
- Add Eway Payment integration.
- Add GoCardless Payment integration.
- Add Mollie Payment integration.
- Add Moneris Payment integration.
- Add Paddle Payment integration.
- Add Square Payment integration.
- Add Discord Messaging integration.
- Add Plivo Messaging integration.
- Add Telegram Messaging integration.
- Add Twilio Messaging integration.
- Add ClickUp Miscellaneous integration.
- Add Commerce Product Element integration (for single-variant products).
- Add Ticket object support to HubSpot CRM integration.
- Add `Integration::beforeSaveForm()` and `Integration::defineClient()`.
- Add spam reason for Friendly Captcha when missing client-side token.
- Add integration front-end JS provider classes as separate exports to include in your own code.
- Add “is visible” and “is hidden” field conditions.
- Add parent field information to form builder for conditions.
- Add the ability to map to “Dependant Fields” for HubSpot integrations.
- Add the ability to set Address values for User element integrations.
- Add SharpSpring tracking data when mapping to a native form.
- Add the ability for Elements fields to set specific elements as available to be picked from.
- Add support for Date fields to set their Year Range start setting to a negative value to offset from the current year.
- Add “Progress Value Position” form setting to control where the percentage value for page process sits.
- Add the ability to mark an incomplete submission as complete in the control panel.
- Add `body` variable as alias to `contentHtml` for email notifications, to be compatible with Craft email templates.
- Add support for “Layout” setting for Element fields, when displayed as Checkboxes or Radio Buttons.
- Add `outputConsoleMessages` plugin setting to prevent CSRF token refresh console.log messages.
- Add support for form submissions to be limited by IP address.
- Add JS event `modifyAjaxClient` to modify the XHR client used for Ajax requests.
- Add JS event `modifyScriptUrl` to modify the CDN scripts for Phone and Date Picker libraries.

### Changed
- Re-organise form builder field categories.
- Rename Webhook integration to Web Request, and add more options for request settings.
- Move Slack and Telegram to Messaging integrations.
- Move Freshdesk, Gorgias and Zendesk to Help Desk integrations.
- Webhook integrations are now Automation integrations.
- Captcha integrations now no longer pre-select the first available type when editing.
- Re-order Captcha integrations alphabetically.
- Integrations can now control any required plugins.
- Captchas can now opt to validate earlier in the submission process, and prevent submission saving (like a field would).
- Form integration settings now no longer need to be saved when fetching new data/refreshing data.
- Improve integration success/fail feedback in the form builder.
- Integration settings pages have been re-worked with multiple tabs and an external docs link to instructions.
- Update spam keywords rules to new definition syntax.
- Update Phone field, no longer using CDN for utils and flag icons, updated look and feel.
- Update the `intl-tel-input` package for Phone field validation and handling.
- Change scroll-to-top behaviour to handle non-top level forms (in modal).
- Allow Radio Buttons and Checkboxes field option labels to include HTML (safe) or Markdown.
- Update Checkboxes and Radio Buttons fields to not show invalid label positions to select.
- Hidden or Disabled fields now have a visual indicator in the form builder.
- Google Sheets integration can now have their Spreadsheet ID set per-form.

### Fixed
- Fix `NestedFieldRow` elements not being garbage collected properly for deleted submissions.
- Fix Date field Year Range offsets not using the current year.
- Fix Phone field flag in the form builder.

### Deprecated
- Deprecated `Automation::getWebhookUrl()`. Use `Automation::getEndpointUrl()` instead.

### Removed
- Removed “Webhook URL” plugin setting from Webhook integration (still available per-form).
- Integration docs are no longer provided within Formie, instead visit the [docs](https://verbb.io/craft-plugins/formie/docs).

## 2.1.52 - 2025-07-18

### Added
- Add support for Salesforce integration for Leads, for task creation on duplicate, to use the Lead ID by default.
- Add warning text for File Upload fields, when uploading to a filesystem with public URLs.

### Changed
- Update English translations.
- Update Recaptcha server-side verification to use recaptcha.net for better availability.

### Fixed
- Fix typecast of Pipedrive “Owner ID” value.
- Fix Signature field output in emails for multi-site installs.

## 2.1.51 - 2025-07-03

### Fixed
- Fix encrypted fields storing data in search indexes for submissions.
- Fix an error for Campaign integration when running via the queue.
- Fix an error for the Calculations field, when formatting as a number and using non-numeric fields in the formula.
- Fix an error when trying to determine a submissions status in some instances.

## 2.1.50 - 2025-06-18

### Added
- Add “is empty” and “is not empty” to field and notification conditions.

### Changed
- Update Google Places Address Provider to remove deprecated `google.maps.places.Autocomplete` component.
- Update submission logging to use `info` rather than `error` category, to prevent error handlers from picking up form validation errors.

### Fixed
- Fix an error when running integrations with no Guzzle client.
- Fix an error for variable tag rich text nodes in some instances.
- Fix an error for Entry Element integrations and `defaultAuthorId` when set from a Stencil.
- Fix Calculations field parsing Markdown content.

## 2.1.49 - 2025-06-05

### Changed
- Unlock `league/oauth2-client` from `2.7.0`.

### Fixed
- Fix “back” button in some instances where session data tracking the current page of a form isn’t handled correctly.
- Fix Recaptcha success logging.
- Fix an error when running element integrations via the queue.
- Fix a client-side validation error for required values when including whitespace characters.

## 2.1.48 - 2025-05-20

### Added
- Add support for Freeform 5 Group field in migration.
- Add extra logging for reCaptcha failures.
- Add more payload context for failed queue jobs.
- Add `endpoint` and `method` to `payload` property for failed queue jobs for context.

### Fixed
- Fix an error with some Payment field providers when resubmitting the form after payment.
- Fix an issue for Payment fields when a client-side error occured.
- Fix Date field handling of min/max dates on statically caches sites when using offsets.
- Fix an error validating minimum words limit for Multi-Line and Single-Line text fields.
- Fix Freeform 5 migration and Hidden fields.
- Fix an error with Freeform/Sprout Forms migrations and submission statuses.

## 2.1.47 - 2025-04-29

### Fixed
- Fix a timezone issue with Date fields when setting a min/max date relative to today.
- Fix PDF handling for Craft Cloud.
- Fix `StringHelper::cleanString` handling.
- Fix JavaScript errors when managing theme config classes.
- Fix an error for payment fields when removing alert classes via Theme Config.

## 2.1.46 - 2025-04-22

### Fixed
- Fix parsing of email notification content and HTML.
- Fix 1CRM integration not querying existing data objects correctly.
- Fix an error for payment fields when removing alert classes via Theme Config.

## 2.1.45 - 2025-04-16

### Added
- Add `craft.formie.setRenderVariables()`.

### Changed
- Update JavaScript Captcha to use render variables for script attributes.

### Fixed
- Fix an error with Craft 4.15+.
- Fix an error when validating element field values.

## 2.1.44 - 2025-04-11

### Added
- Add extra logging to Opayo payment failures.
- Add the ability to assign a Campaign to a Contact/Lead/Account in Salesforce.

### Fixed
- Fix `submissionEndpoint` GraphQL property.
- Fix an error for Phone fields, when initializing the form’s JS multiple times.
- Fix payment field race condition when initializing form JS multiple times with conditions.
- Fix an error with Opayo and billing email.
- Fix an XSS vulnerability for importing forms with manipulated field content.
- Fix an XSS vulnerability for email notification content.

## 2.1.43 - 2025-03-27

### Added
- Add `Submission Date` to some field mapping controls.

### Fixed
- Fix some errors with Submissions widget for invalid dates and `weekStartDay` user settings.
- Fix an error when importing a form, made from an export from the Forms element index.
- Fix HubSpot GDPR processing when not enabled.
- Fix an issue with Iterable integration field mapping.
- Fix some fields not having their default values set correctly.

## 2.1.42 - 2025-03-04

### Added
- Add “Message Type” to Iterable CRM integration.
- Add the ability for IntegrationField’s to contain static `data` in their definitions.
- Add support for Table field to use `id` or `name` values when defining a row schema.

### Changed
- Improve Hubspot GDPR handling for marketing and processing options.
- Update Monday integration instructions.

### Fixed
- Fix Agree field not allowing `null` value as an empty state indicator.
- Fix Agree field markup to match correct accessibility guidelines.
- Fix Hubspot GDPR handling.
- Fix Monday integration connection requests.
- Fix an error when applying Formie-related project config when uninstalled.
- Fix Phone number fields flag icons in the form builder.

## 2.1.41 - 2025-02-02

### Fixed
- Fix an error with Group and Repeater fields with required File Upload fields on multi-page Ajax forms.
- Fix Group and Repeater fields not respecting “Include in Email Notifications” setting and conditionally hidden fields in email notifications.
- Remove incorrect `Campaign = Kampagne` German translation for the Campaign plugin.

## 2.1.40 - 2025-01-24

### Fixed
- Fix incorrectly bundled `intl-tel-input` version.
- Fix some string content not being escaped properly.
- Fix Freeform 5 migration for success behaviour.
- Fix default value for Date field not being set correctly.

## 2.1.39 - 2025-01-17

### Added
- Add support for inline CSS for some string content (Multi-Line Rich Text content).
- Add `Variables::EVENT_PARSE_VARIABLES` to allow you to parse custom registered variables.

### Changed
- Bump `guzzlehttp/oauth-subscriber` to `^0.8.1`.
- Lock `league/oauth2-client` to `2.7.0` to prevent an issue with refresh token scopes on some providers.

### Fixed
- Fix reCAPTCHA Enterprise and score validation.

## 2.1.38 - 2025-01-13

### Added
- Add `contentType` to email attachments.
- Add theme config options for Table field inner field inputs.
- Add `Element::EVENT_MODIFY_ELEMENT_MATCH` event to control behaviour for Element integrations and matching an existing element.

### Changed
- Bump `guzzlehttp/oauth-subscriber`.
- Table field column templates are now split into separate files for easier overriding.

### Fixed
- Fix signature field image matching on existing field, in some instances.
- Fix email notifications not correctly saving conditional recipients.
- Fix an error with migrating forms from Freeform 5.
- Fix Freeform 5 migration for some invalid field handles.
- Fix Freeform migration console commands.
- Fix some special unicode characters being stripped out of some text values for text-based fields.
- Fix Date fields and the “Available Days” setting not working correctly.
- Fix Entry element integration “Update Element Mapping” values being blank.
- Fix an error when setting a form template with required fields and validation handling.

## 2.1.37 - 2024-12-27

### Added
- Add context property for integrations to record extra data at submission time.
- Add support for Pardot tracking cookies for Form Handler.

## 2.1.36 - 2024-12-17

### Added
- Add more comprehensive logging for user element integration.
- Provide Freeform 4 and Freeform 5 migrations.

### Changed
- Update Freeform migration to support Freeform 5+.

### Fixed
- Fix not restoring trashed stencils when applying from project config.
- Fix form export not exporting number values correctly.

## 2.1.35 - 2024-12-03

### Fixed
- Fix User and Entry element integration settings migration.

## 2.1.34 - 2024-12-02

### Fixed
- Fix an error when saving integration settings for forms.
- Fix User element integrations not using UIDs for the target groups.
- Fix Entry element integrations not using UIDs for the target entry type.
- Fix an issue with User element integration and the “Send Activation Email” setting.
- Fix checkbox select fields field settings not retaining their value.

## 2.1.33 - 2024-11-13

### Added
- Add Iterable CRM integration.

### Changed
- Update proxy URL for some integrations.

### Fixed
- Fix duplicated API Key setting for Google Places.
- Fix Iterable integrations when not mapping custom fields.
- Fix reactivity of integration field mapping for forms.
- Fix some integrations causing `post_max_size` and `input_max_vars` issues on Craft Cloud.
- Fix inactive or pending users showing in users field.
- Fix “Action on Submit” not toggling options correctly.
- Fix an error when migrating Freeform forms for a specific handle via the CLI.
- Fix `data-repeater-row-id` attribute for Repeater field rows.

## 2.1.32 - 2024-10-20

### Added
- Add `data-repeater-row-id` attribute to Repeater field rows.
- Add Data Center setting for Zoho CRM Integration.

### Fixed
- Fix reCaptcha Enterprise flagging spam in certain situations.
- Fix an error with Sent Notifications, when called too early before a `dateCrated` has been set.

## 2.1.31 - 2024-10-09

### Added
- Add Iterable Email Marketing integration.

### Fixed
- Fix toggling the enabled state of integrations not updating in the sidebar.
- Fix an error when previewing email notifications with Element fields in Postgres.
- Fix Signature field support for Group fields when accessing their image remotely.
- Fix an error when editing a Stencil with integrations enabled.

## 2.1.30 - 2024-09-14

### Added
- Added `Integration::getSettingsHtmlVariables()` and `Integration::getFormSettingsHtmlVariables()`.
- Added support for all CRM integrations to only fetch data objects for ones that are enabled in the form builder integration settings.

### Changed
- Updated Password field `autocomplete` attribute.
- Improve Integration form instructions translations to remove duplicate translation strings.
- Improve Integration form instructions translations to remove duplicate translation strings.
- Improve Integration settings instructions translations to remove duplicate translation strings.
- Update integration descriptions to be dynamic for better translation.
- Days and Months predefined options now use Craft’s locale helpers for consistency.
- Country and State predefined options now use `commerceguys/addressing` for consistency.

### Fixed
- Fixed an error when creating forms where a default Form Template had required fields.
- Fixed an error when fetching Signature field image.

## 2.1.29 - 2024-09-07

### Added
- Added “Start Mode” setting to Friendly Captcha.
- Added the ability to set `scriptAttributes` and `jsAttributes` for `<script>` tags that Formie uses.
- Added the ability for `craft.formie.renderJs` to set JS attributes for scripts.

### Changed
- Changed Phone input autocomplete from `tel-national` to `tel` to ensure valid autocomplete value.

### Fixed
- Fixed an issue for Stripe and Opayo 3DS handling in combination with captchas not working correctly.
- Fixed an error for GraphQL when querying submissions with brand-new Group fields with no content.
- Fixed an error with Freshdesk integration when handling duplicate contacts.
- Fixed an error with Salesforce integration when handling duplicate leads.
- Fixed global “View Form Usage” user permission not appearing.

## 2.1.28 - 2024-08-29

### Fixed
- Fixed an error when parsing variable tokens.

## 2.1.27 - 2024-08-29

### Added
- Added “Page URI” and “Page Name” to HubSpot integration for Forms.
- Added support for Form Template custom field validation for forms.

### Changed
- Klaviyo Email Marketing integration now orders lists alphabetically by name.
- Klaviyo Email Marketing integration now loads more than 10 lists.
- Improved HubSpot CRM integration for HubSpot Forms, where fields don’t have a label.

### Fixed
- Fixed an edge-case with variables, where cached data matched against incorrect submission values.
- Fixed an error when fetching Summary field HTML.
- Fixed an error when fetching Signature field image.
- Fixed an error with Klaviyo CRM integration.

## 2.1.26 - 2024-08-14

### Fixed
- Fix a compatibility issue with `nystudio107/craft-plugin-vite` 4.0.12.

## 2.1.25 - 2024-08-14

### Added
- Added `initSubmit` JS API function to allow programmatic submissions.

### Fixed
- Fixed an error when refreshing tokens on some installs.

## 2.1.24 - 2024-08-11

### Added
- Added `processSubmit` JS API function to allow submission processing to continue if preventing submission via the `onBeforeFormieSubmit` JS event.

### Fixed
- Fixed errors when attaching some files to support requests.
- Fixed Date fields with a default value, or min/max date not having their values normalized correctly.
- Fixed an error when viewing Forms in the control panel for a specific template, and improve Form element index performance.
- Fixed an error when refreshing tokens via JS, for a non-top-level webroot site.
- Fixed an error when populating Element fields when also limiting field values.

## 2.1.23 - 2024-07-29

### Added
- Added `Address 1` and `Address 2` to field mapping for Klaviyo integration.
- Added `useEmailTemplateForFieldVariables` plugin setting to enforce field variables to use their email template. This is opt-in behaviour until Formie 3.

### Changed
- Updated SharpSpring integration to not require the Form URL, and improve instructions.
- Updated English translations.
- Date fields now return date settings (`defaultValue`, `defaultDate`, `minDate`, `maxDate`) as `Y-m-dTH:i:s` formatted strings without timezone information (as none is stored).

### Fixed
- Fixed form element index behaviour for users with only “View Forms” permissions.
- Fixed an error for Date fields and the Default Date, and Min/Max Date settings being inconsistent.
- Fixed location values for Klaviyo integration.
- Fixed Klaviyo Email Marketing integration not working correctly.

## 2.1.22 - 2024-07-21

### Changed
- Address and Multi-Name fields now strip out invalid content in email notifications.

### Fixed
- Fix Salesforce integration and Case objects by excluding the `IsClosedOnCreate` field.

## 2.1.21 - 2024-07-16

### Added
- Added structure sorting options to Entries fields.
- Added `allIntegrations` property to `EVENT_MODIFY_FORM_INTEGRATIONS` event.
- Added `form` property to `EVENT_MODIFY_FORM_INTEGRATIONS` event.
- Added `setNoCacheHeaders()` to the `formie/forms/refresh-tokens` action endpoint to prevent caching.
- Added `Integrations::EVENT_MODIFY_FORM_INTEGRATION` event.

### Changed
- Allow sending email notifications for incomplete submissions from the control panel.
- `status` is now a reserved field handle.
- Updated form builder preview for Summary field.

### Fixed
- Fixed an error with Date fields and their default value timezone.
- Fixed an XSS vulnerability for sub-fields and sent email notifications.
- Fixed `EVENT_MODIFY_FORM_INTEGRATION` not firing in some instances.
- Fixed Phone field allowing invalid phone numbers and country codes.
- Fixed an error with single Name fields used in Summary fields.
- Fixed session call for `refresh-tokens`.
- Fixed Opayo payments and custom email values.
- Fixed some fields not using `getValueAsString()` to render content for email notifications.
- Fixed single-value fields not being able to be ordered in the submissions index in the control panel.
- Fixed lack of server-side validation for min/max Number fields.

## 2.1.20 - 2024-06-27

### Added
- Added `sourceType` for all integration custom fields to check against the provider-defined field type.
- Added Company mapping support for HubSpot CRM integration.

### Changed
- Changed the default state of “Include in Email Notifications” for fields to be `true`.

### Fixed
- Fixed an error with some fields when enabling content encryption.
- Fixed Pipedrive integration for "Multiple Options" (set) fields.
- Fixed server-side validation for Phone fields on Ajax-based forms not showing correctly.
- Fixed an error with Element integrations when mapping to an element select field type on the resulting element.
- Fixed Phone field validation for empty state.
- Fixed a typo in Turnstile appearance settings.
- Fixed Address field country values not showing correctly when editing a submission in the control panel.
- Fixed Name field Prefix not using the label for its content.
- Fixed Name fields not using their full name value for Summary fields.

## 2.1.19 - 2024-06-15

### Added
- Added support for new Klaviyo integrations due to [API changes](https://developers.klaviyo.com/en/v1-2/reference/api-overview).
- Added support for Calculations field when used in field conditions.
- Added appearance settings to Turnstile captcha. (thanks @jmauzyk).

### Changed
- Calculations fields can now reference other Calculations fields.
- Improved handling of spam, deleted, and agent contacts for Freshdesk integration. (thanks @jmauzyk).

### Fixed
- Fixed default values for fields not being trimmed of whitespace.
- Fixed some variables not supporting env variables in Email Notifications.

## 2.1.18 - 2024-05-31

### Fixed
- Fixed an error when submitting a form and manipulating the `goingBack` param.
- Fixed an error when testing email notifications from a Stencil.

## 2.1.17 - 2024-05-29

### Changed
- Ensure that sessions exists when calling `formie/forms/refresh-tokens`.

### Fixed
- Fixed element integrations update matching logic where matched data is empty.
- Fixed `populateFormValues` values and dynamic Twig.
- Fixed an error with Phone number fields and `countryCode`.
- Fixed an error with Phone number fields and `countryName`.

## 2.1.16 - 2024-05-27

### Added
- Phone fields now include `countryCode` and `countryName` in their value when the value is JSON.

### Changed
- Removed unused `e.target` from Repeater `addRow()` JS function.
- Updated English translations.
- Updated reCAPTCHA Enterprise’s Secret API Key plugin setting.

### Fixed
- Fixed Entry element integrations not using their section’s default entry status when `enabled` wasn’t mapped.
- Fixed country-enabled Phone fields not having their generated value set correctly.

## 2.1.15 - 2024-05-20

### Fixed
- Fixed an error with options fields where the incorrect ID was being generated when an option value contained special characters.
- Fixed an error when querying submissions by `userId` and not just a single ID.
- Fixed options-based fields not trimming their option value.

## 2.1.14 - 2024-05-08

### Added
- Added `disabled` property to GraphQL Dropdown field interface
- Added `Formie::EVENT_MODIFY_TWIG_ENVIRONMENT` event to modify the Twig Sandbox for variable parsing.
- Added `Variables::EVENT_REGISTER_VARIABLES` event to register your own.

### Fixed
- Fixed an error when using dynamic Dropdown options
- Fixed a PHP 8 error.
- Fixed paths for Craft Cloud. (thanks @timkelty).
- Fixed Repeater fields not retaining their values correctly.
- Fixed Form export (from the Form element index) not using custom Formie export logic.
- Fixed Name and Address sub-field conditions not working correctly.
- Fixed an error when importing forms, where custom field content existed while the custom fields themselves didn’t.
- Fixed Group/Repeater field conditions not working correctly when complex rules were created.

## 2.1.13 - 2024-04-27

### Changed
- Improved German translations. (thanks @MoritzLost).
- Updated non-English translations to include latest strings.
- Updated English translations to include latest strings.

### Fixed
- Fixed an error where `renderOptions` weren’t available to field templates.
- Fixed duplicated heading text for Heading fields when viewing a submission in the control panel.
- Fixed an error with Flatpickr and live client-side validation.
- Fixed Element integrations not working correctly for non-updating elements.
- Fixed Address field default country not working correctly.

## 2.1.12 - 2024-04-18

### Added
- Added more missing translation strings.
- Added full error for reCAPTCHA captchas when failing to initialize.
- Added the ability for `Submission::setStatus()` to accept the handle of a status.
- Added the ability to set the captcha type for reCAPTCHA Enterprise.
- Added Google Console API Key for reCAPTCHA Enterprise.
- Added Referer, User Agent and User IP headers for reCAPTCHA Enterprise requests.

### Changed
- HubSpot forms are now listed in alphabetical order.

### Fixed
- Fixed formatting for German translations. (thanks @MoritzLost).
- Fixed spelling and style issues in German translations. (thanks @MoritzLost).
- Fixed an error with File Upload fields within Repeater fields for GraphQL.
- Fixed User variables not working in Email Notification previews.
- Fixed "Manage all forms" permission.
- Submission UIDs when used in Email Notifications now show a generated value in preview.

## 2.1.11 - 2024-04-15

### Added
- Added missing form builder translation strings.

### Fixed
- Fixed a Formie 1 migration where fields contained an underscore, and were affected by synced field issues in need of fixing.
- Fixed a PHP 8.2 deprecation.
- Fixed populating Group fields not working consistently.
- Fixed Repeater and Group fields not working correctly for multi-page forms.
- Fixed limited users permissions for forms not working correctly.
- Fixed an error with Stripe creating a plan for subscription payments.
- Fixed Internal fields showing in the form builder for non-English languages.

## 2.1.10 - 2024-04-10

### Added
- Added `FieldInterface::subfieldLabelPosition` for GraphQL.

### Changed
- Radio Button fields `data-field-type` attribute has been changed from `fui-type-radio-buttons` to `fui-type-radio`.
- Date fields `data-field-type` attribute has been changed from `fui-type-date-time` to `fui-type-date`.
- Email fields `data-field-type` attribute has been changed from `fui-type-email-address` to `fui-type-email`.
- Hidden fields `data-field-type` attribute has been changed from `fui-type-hidden-field` to `fui-type-hidden`.
- Phone fields `data-field-type` attribute has been changed from `fui-type-phone-number` to `fui-type-phone`.

### Fixed
- Fixed Opayo payments not sending customer email address.
- Fixed Phone fields default value not working.
- Fixed “All Fields” and similar summary variables causing invalid HTML in some email clients for email notifications.
- Fixed Solspace Calendar element integration incorrectly matching existing elements from other entry types.
- Fixed Entry element integration incorrectly matching existing elements from other entry types.
- Fixed compatibility with Solspace Calendar 5.x.
- Fixed consent field values for Campaign Monitor.
- Fixed an error for Checkboxes fields when the “Toggle Checkbox” was included.
- Fixed multi-name fields not showing correctly in email notification previews.
- Fixed `data-field-type` attribute being incorrectly translated for fields.
- Fixed JS event listeners being attached multiple times for some integrations when calling `Formie.initForms()` multiple times.
- Fixed front-end JS throwing an error in some circumstances.
- Fixed an error when populating Group/Repeater field values.

## 2.1.9 - 2024-03-29

### Fixed
- Fixed a dependency error with `verbb/base` version.
- Fixed user permissions being incorrect for view submissions in the control panel.
- Fixed an error when populating Table fields.

## 2.1.8 - 2024-03-29

### Added
- Added “Source” to Klaviyo Email Marketing integration.
- Added support for additional SugarCRM fields.
- Added the ability for cosmetic fields (Heading, HTML, etc) to be included in email notifications.
- Added the ability to map to HubSpot Hidden fields.

### Changed
- Remove Section and Summary fields from rich text editor and variable picker options.

### Fixed
- Fixed Opayo and `1017` error responses.
- Fixed Algolia and Google address provider templates.
- Fixed Table fields not populating properly with `populateFormValues()` and allow usage of the column `handle`.
- Fixed Date/Time columns in Table field with timezone information when editing a submission in the control panel.
- Fixed Salesforce DateTime fields throwing an error.
- Fixed renamed theme config keys for some fields (wait until Formie 3).
- Fixed success/error messages containing paragraph tags not displaying correctly for Ajax-based forms.
- Fixed field and integration handles using the translated class name when they shouldn’t.
- Fixed cosmetic field handles not working correctly for non-English-default installs.
- Fixed a translation error in the control panel when the users language or site is set to German.
- Fixed some integrations throwing errors when the control panel language was set to non-English.

## 2.1.7 - 2024-03-18

### Added
- Added the ability to query `SentNotification` elements by `submissionId` and `notificationId`.
- Added “Subscribe Status” mapping option to ActiveCampaign integrations.
- Added `ModifyFieldIntegrationValueEvent::rawValue`.
- Added German Translations. (thanks @alexanderloewe).

### Changed
- Repeater/Group new-row templates now have spaceless HTML to take up less space in the page source.
- Update Monday integration mutation to latest API compatibility.
- Updated Form Template directories setting to auto-complete directories, not single templates.

### Fixed
- Fixed an error when viewing Stripe subscriptions in the control panel.
- Fixed failed Opayo 3DS payments creating a new payment model instead of updating the pending payment.
- Fixed Slack/Trello integration messages not including paragraph nodes.
- Fixed success/error/other form messages not including paragraph nodes.
- Fixed a reactivity issue when editing notifications, causing values to not always save.
- Fixed Slack webhooks not sending.
- Fixed Checkboxes/Radio field preview for horizontal layout and overflow.
- Fixed general errors, manually set to a submission’s `form` attribute not showing on the front-end.
- Fixed Theme Config not working correctly when supplying attributes with empty values (to output just the attribute like `readonly` or `disabled`) on elements.
- Customer information is now included for single Stripe payments.
- Fixed an error when rendering Payment fields with an invalid Payment integration.
- Fixed incorrect value when mapping to a Date field in HubSpot.
- Fixed `formie` translations not working correctly (defaulting to English) when there is a non-English primary site, and content has been written in non-English.
- Fixed scroll-offset calculation when scrolling to the top of a form.
- Fixed incorrect value when mapping to a Date field in HubSpot.

## 2.1.6 - 2024-03-03

### Added
- Added “Full Name” to User Element integration mapping.

### Changed
- Changed all instances of dynamic Twig to use safe, sandboxed environment to protect against potential security issues.

### Fixed
- Fixed an issue with File Upload fields with a custom filename format not working in a Repeater field.
- Fixed layout issues when editing a submission via a Submissions element select field in other elements.

## 2.1.5 - 2024-02-21

### Changed
- Updated Formie 3 layout prep.

### Fixed
- Fixed an error with Opayo integration.
- Fixed payment integrations’ `getFieldSetting()` not always returning a default value.
- Fixed an error with Stripe payments where a correct ID was not being generated.

## 2.1.4 - 2024-02-17

### Added
- Added `status`, `statusId` and `siteId` to Submission query arguments for GraphQL queries.
- Added missing translations for some strings.
- Added `redirectCallback` to `onAfterFormieSubmit` JS event.
- Added `redirectTarget` to `onAfterFormieSubmit` JS event.
- Added `exportVersion` to form exports.
- Added support for `headlessMode` mode for integration redirectUri’s.
- Added `data-fui-field-count` attribute to `row`, `subFieldRow`, and `nestedFieldRow` theme config elements.

### Changed
- Update Sent Notifications to use `TEXT` database column types for some values like `cc` and `bcc`.
- Update Dompdf 2.0.4+.
- Replace deprecated `utf8_encode` function with `mb_convert_encoding`.

### Fixed
- Fixed ajax-based, multi-page forms with File Upload fields creating duplicate assets.
- Fixed an error when importing forms, set to “create” where there was a conflicting UID for an existing form.
- Fixed changing the submissions status not persisting in the control panel element index view.
- Fixed sub-field fields not showing custom error messages for required validation.
- Fixed an error when calling `populateFormValues()` with Repeater fields.
- Fixed lack of error logging for `populateFormValues()`.
- Fixed when creating a new form, and an error occurs, the selected stencil not persisting.
- Fixed order of operations when uninstalling the plugin.
- Fixed uninstall not removing some database tables.
- Fixed an error saving notifications with long names.
- Fixed submissions processing payments when flagged as spam.
- Fixed HTML field outputting invalid labels.
- Fixed progress bar not updating when going back to first page.

## 2.1.3 - 2024-01-25

### Changed
- Improved performance of email notification content parsing for complex fields, and fix Slack integration when rendering complex fields in their rich text message.

### Fixed
- Fixed hcaptcha executing captcha multiple times, and not working correctly for submitted forms (if filling out the form again).
- Fixed saving a new form with a UID already in place, not working correctly.
- Fixed import/export of forms not respecting UIDs of forms or notifications.
- Fixed an error when showing spam error messages on the front-end.
- Fixed element fields not working correctly when pre-populating the value for multi-page forms.
- Fixed .env variable support for email notifications not working correctly.
- Fixed rich text editor “link to an asset” not working correctly.
- Fixed `craft.formie.populateFormValues` not sanitizing potentially harmful strings.

## 2.1.2 - 2024-01-16

### Added
- Added “Page Count” to form conditions when making custom form sources.
- Added Formie 3 migration prep for field layout changes.

### Fixed
- Fixed payment fields not filtering out currency symbols for dynamic values.
- Fixed validation error for Address field Zip subfield not showing correctly for Ajax forms.
- Fixed label position “hidden” not working work Date fields.
- Fixed label position “hidden” not working work Checkboxes fields.
- Fixed an error when sending an email with non-lowercase values for some mailers.
- Fixed a new `formId` being created when calling `renderFormCss/Js`.
- Fixed an error for Entry element integrations and setting the `authorId` to a field value.
- Fixed submissions index not working correctly in some instances.
- Fixed “Include in Email” field setting for Single-Line Text fields to “Settings” tab.
- Fixed `ModifyFieldUniqueQueryEvent` error.
- Fixed submit methods toggling not working correctly in some instances.
- Fixed autoloading for `ModifyFieldUniqueQueryEvent` class.

## 2.1.1 - 2023-12-29

### Fixed
- Fixed a migration error with generating notification handles.
- Fixed custom error messages for field being applied for every error (not just for required value failures).

## 2.1.0 - 2023-12-27

### Added
- Added the ability to set the label and value for Address field Country sub-field dropdown options.
- Added `populateAddress` JS event for Google Address provider to modify field-population when an address is found.
- Added the ability to set a min/max number of options to pick for a Checkboxes and multi-Dropdown field.
- Added support for Freeform migration with form handles containing invalid characters. Formie will try and rename to a valid handle.
- Added `fui-tab-complete` class to tabs previous to the active one on the front-end.
- Added page count to form element index, and the ability to query forms via their `pageCount`.
- Added support for unique values for Single-Line Text, Multi-Line Text and Number fields.
- Added note to data retention form setting on garbage collection.
- Added “Visibility” settings to Agree fields.
- Added the ability to set a submission as spam when editing it in the control panel.
- Added info tooltips to email notifications for deliverability gotchas.
- Added `description` field setting for Summary field, to control the heading text at the top of the field.
- Added `before` and `after` options to the `formie/submissions/delete` console command.
- Added `before` and `after` element query params for submissions.
- Added “Opt-in” field to all integrations.
- Added “User Email” to email variable pickers.
- All fields now have the ability to be excluded from the “All Fields” variable for email notifications.

### Changed
- Submission index chart now shows the same submissions in the table view, and provides a consolidated date range filter.
- Revamped submissions index chart to be more performant.
- Changed “Configure Import” text to “Review Import” for Import/Export page.

### Fixed
- Fixed front-end alert compatibility with Theme Config for alerts, for ajax-driven forms.
- Fixed formatting buttons not appearing at the top-level of a rich text editor instance.
- Fixed custom error messages for field being applied for every error (not just for required value failures).
- Fixed orphaned field cleanup not working for Repeater/Group nested fields.
- Fixed Zip/Postal Code ordering for Address fields in control panel to match what’s produced on the front-end.

## 2.0.45 - 2023-12-26

### Added
- Added `setOnlyCurrentPagePayload` to force only saving the current page’s fields for performance.
- Added `isAvailable` to FormInterface for GraphQL.
- Added `displayType` for element fields for GraphQL queries.
- Added `data-placeholder` on Pell editor to support placeholder on Rich Text fields. (thanks @IrateGod).

### Changed
- Switch Hidden field `hasLabel` to theme config output for form builder label.
- Hidden fields now no longer output a label.

### Fixed
- Fixed populated Repeater fields not working correctly for multi-sites.
- Fixed a JS error with `Formie.refreshFormTokens` when importing Formie’s JS in your own JS files.
- Fixed limit submissions check for GraphQL.
- Fixed validation messages not showing for Opayo payments.
- Fixed lack of autocomplete attributes for Opayo payment fields.
- Fixed Repeater/Group inner fields incorrectly being shown as able to have conditions set on the same field.
- Fixed initial fields in Group/Repeater fields not being marked as `isNested`.
- Fixed cache-clearing for CSRF/Captchas not working correctly for multi-page forms.
- Fixed options fields (Radio, Checkboxes, Dropdown) defaults not working in Group/Repeater fields.
- Fixed `setOnlyCurrentPagePayload`.
- Fixed `onFormieSubmitError` JS event not containing the server response.

## 2.0.44.1 - 2023-12-12

### Changed
- Improve error message when failing to save a form.

### Fixed
- Fixed an error when creating new forms.

## 2.0.44 - 2023-12-12

### Added
- Added `afterInit` JS event to Multi-Line Text fields that have the rich text editor enabled.
- Added `beforeInit` JS event for Multi-Line Text fields that have the rich text editor enabled.
- Added `aria-live=“polite”` and `aria-atomic=“true”` to error messages on the front-end.
- Added `modifyQueryParams` event for PayPal.

### Changed
- Changed `finalise` to `finalize` for PayPal message.
- Changed `fui-sr-only` for hidden label position to use `data-fui-sr-only` instead of class, for Theme Config compatibility.
- Updated Multi-Line field, Rich Text editor to support placeholder attribute. (thanks @IrateGod).

### Fixed
- Fixed an error for Products and Variants fields when checking for Commerce being installed, when the fields are initialized too early.
- Fixed File Upload assets within Repeater or Group fields not being deleted according to their submission data retention settings.
- Fixed an error when a stencil’s email notification contained an attached asset.
- Fixed lack of enter key accessibility for sent notification and submission modals in the control panel.
- Fixed an error when form message settings contained emoji’s.
- Fixed setting elementQuery via templates not overwriting Element fields.
- Fixed an issue when a PayPal payment field was toggled by conditional rules.
- Fixed an issue using `populateFormValues` for Repeater fields.
- Fixed an error when mapping Group fields to some integrations when no value is present.
- Fixed an error when updating queue job information cache for integrations.

## 2.0.43 - 2023-11-26

### Added
- Added page and row reference to fields.
- Allow Stripe metadata to pull data from other fields in the form.

### Fixed
- Fixed option fields (Dropdown, Checkboxes, Radio Buttons) having an incorrect column length when no options were provided (for dynamic options).
- Fixed handling of token errors for integrations.
- Fixed an error with validating Address fields.
- Fixed Brevo `templateId` parameter type.
- Fixed field labels not being rendered when set to position hidden.
- Fixed interest categories not being shown correctly for Mailchimp.

## 2.0.42 - 2023-11-09

### Added
- Added `Element::EVENT_MODIFY_ELEMENT_FIELDS` event for element integrations.

### Fixed
- Fixed incorrect File Upload validation translation message for min/max filesize.
- Fixed lack of proper check for integration response when manually triggering an integration from a submission in the control panel.
- Fixed Honeypot captcha when refreshing cached tokens and improve logging.

## 2.0.41 - 2023-11-05

### Changed
- Changed checkbox checked state to use `checked` not `checked=“true”`.

### Fixed
- Fixed PDF template filename format not persisting when saved.
- Fixed Checkboxes not being reset after submitting a form.
- Fixed Table field not normalizing cell values properly.
- Fixed an error with File Upload fields introduced in 2.0.40.

## 2.0.40 - 2023-11-02

### Changed
- Updated Brevo integration to include `templateId` and `redirectionUrl` for Double-Optin.

### Fixed
- Fixed GraphQL mutations for multiple File Upload fields.
- Fixed honeypot captcha trigger unload warnings when using cache-busting JS.
- Fixed GraphQL mutations for multiple File Upload fields.
- Fixed an error with binding events to JS multiple times not working correctly when required (for conditions).
- Fixed Duplicate captcha causing unload warnings.

## 2.0.39 - 2023-10-25

### Added
- Added “Reply-To Name” setting for email notifications.
- Added “Webhook URL” as setting for Webhook integration when querying via GraphQL.
- Added `autocomplete=“name”` to single Name fields.
- Added language options to Friendly Captcha.
- Added double-optin setting for Brevo integration.
- Added checks to Formie JS to protect against multiple-initialization of the same form.
- Added better handling for destroying an initialize form in JS.
- Added `initJs` to render options for forms, to prevent auto-initializing of Formie’s JS.
- Added `Formie.refreshForCache` to simpiify statically-cached forms and token refreshing.
- Added missing `{startTag}{num}{endTag}` non-plural translation strings.
- Added “Reply-To Name” setting for email notifications.
- Added `FORMIE_SECURITY_KEY` .env variable (based off `CRAFT_SECURITY_KEY` or `SECURITY_KEY` for maintaining a separate key for encrypting field values.
- Added “Webhook URL” as setting for Webhook integration when querying via GraphQL.

### Changed
- Move `.fui-btn *` CSS rule from theme to base CSS to handle inner elements of buttons not triggering the correct submit behaviour
- Ajax-based forms now automatically fetch tokens (CSRF, captchas) after a successful form is submitted
- Implement `Element::trackChanges()` for Blitz compatibility

### Fixed
- Fixed a PHP 8 deprecation notice.
- Fixed an error for Dropdown fields when toggling between options being an optgroup and not.
- Fixed widget charts for line/pie charts.
- Fixed an error when creating dashboard widgets for submissions.
- Fixed Friendly Captcha triggering unload warnings.
- Fixed Friendly Captcha styles.
- Fixed Duplicate captcha not refreshing its value for Ajax forms, after a successful submission.
- Fixed JavaScript captcha not refreshing its token after a successful submission, and trying to submit again without a page refresh.
- Fixed captcha behaviour to handle multiple initializations.
- Fixed captcha integrations with initializing Formie’s JS multiple times.
- Fixed race condition for refreshing captcha tokens when the captchas hadn’t been initialized yet.
- Fixed JS `destroyForm()` not removing the form from the factory collection correctly.
- Fixed multiple event bindings for some JS elements.
- Fixed spam email notifications throwing an error in the queue when trying to send (if enabled to do so).
- Fixed dynamically `redirectUrl` having any query params overwritten if the same query param was on the current URL.
- Fixed submissions using query string params for populating some properties when they shouldn’t.
- Fixed some fields not having their settings normalized.
- Fixed an error when editing a form and invalid default status.
- Fixed an error with File Upload fields and an invalid Upload Location set.
- Fixed File Upload fields not working correctly for Repeater or Group fields, when the only nested field.
- Fixed double-initialization checks for Formie’s JS, causing incorrect behaviour with a race condition with JS frameworks.

## 2.0.38 - 2023-10-08

### Added
- Added better support for editing submissions.
- Added “Consent To Track” and “Consent To Send SMS” to Campaign Monitor integration.
- Added Phone integration field type, for formatting phone numbers sent to integrations.
- Added support for expand parameter on target schemas for Microsoft Dynamics 365 CRM. (thanks @jamesmacwhite).

### Changed
- Changed references for `Linked.in` to `LinkedIn`.
- Calling `craft.formie.renderForm()` now sets a unique `formId` to assist with rendering the same form multiple times to retain JS functionality.
- Clearing a current submission can now be done without a POST request.

### Fixed
- Fixed an issue with Dynamics 365 and Created By value.
- Fixed query restrictions for system users for Microsoft Dynamics 365 integration.
- Fixed payment fields not working within Group fields.
- Fixed Turnstile captcha firing form submissions multiple times for Ajax based forms.
- Fixed Turnstile captcha triggering unload warnings.
- Fixed custom error messages for fields not being used for server-side errors.
- Fixed DotDigital CRM Integration response when updating the address book.
- Fixed an error with JS binding to the same form rendered multiple times.
- Fixed `actionUrl` not taking into account incomplete editing submissions.
- Fixed an error for some integrations and an invalid enabled state.
- Fixed an error processing User element integrations and user profile.

## 2.0.37 - 2023-09-25

### Added
- Added “Created By” field mapping for Dynamics 365 CRM integration.
- Added `Field::getRequiredPlugins()` to better support plugin-dependant fields.
- Added Honeypot value for GraphQL queries.
- Added `siteKey` values for supported Captchas for GraphQL queries.
- Added “Mobile Number” to Campaign Monitor integration.
- Added `form.setActionUrl()`.
- Added `FormSettings::pageRedirectUrl` to allow setting the redirect URL for every page submission.

### Changed
- Updated `isPluginInstalledAndEnabled` check.
- Updated `stripe/stripe-php` dependency to align with Craft Commerce.

### Fixed
- Fixed Dropdown field throwing errors after changing options from being an `optgroup`.
- Fixed an error when toggling Dropdown field options from using an optgroup to a default.
- Fixed an error when setting cookie values for a Hidden field, and not dealing with encoded values properly.
- Fixed showing “Edit Form” element action when viewing forms.
- Fixed mapping a Formie Submission field to the Submission ID for Element integrations.
- Fixed `form` not being available for PDF templates.
- Fixed an issue with Honeypot Captcha and GraphQL mutations.
- Fixed File Upload fields in Repeater fields not working correctly with GraphQL mutations.
- Fixed redirect URL value when the URL contained certain UTF characters.

## 2.0.36 - 2023-09-08

### Added
- Added consolidated errors when saving forms in the control panel.
- Added `beforeInit` and `onApprove` JS events for PayPal.
- Added Brevo email marketing integration (to replace Sendinblue).

### Fixed
- Fixed being unable to choose which site a submission is saved in, when editing a submission from the control panel.
- Fixed element select fields not retaining their values before being saved for the form builder.
- Fixed Number field not correctly typed in GraphQL mutations.
- Fixed being unable to choose which site a submission is saved in, when editing a submission from the control panel.
- Fixed missing translations for Opayo and PayPal’s front-end JS.
- Fixed an error when creating custom templates.
- Fixed Friendly Captcha not working correctly for multi-page forms.

### Deprecated
- Deprecated the Sendinblue integration. Please use the Brevo integration instead.

## 2.0.35 - 2023-08-31

### Added
- Added 1CRM integration.
- Added support for Checkboxes fields with Calculations variables.
- Added `Integration::getClassHandle()`.
- Added support for Segmented Lists for Pardot CRM integration.

### Changed
- Map an account to lead data using `parentaccountid` or `customerid_account` for Dynamics 365 integration.
- Changed Number fields to use `TEXT` columns for their content, to allow large numbers to be used.
- Updated reCAPTCHA Enterprise endpoint to address deprecation.

### Fixed
- Fixed being able to send email notifications and trigger integrations for spam submissions in the control panel.
- Fixed Salesforce integration not working for production without “Use Credentials” enabled.
- Fixed ActiveCampaign integration dropdown field values not working correctly.
- Fixed an error when normalizing Date field values.
- Fixed Pardot integration and prospect accounts.
- Fixed an error with Sprout Forms migration.
- Fixed being able to select Repeater fields for field conditions.
- Fixed Group and Repeater fields having content populated from outer fields with the same field handles as inner fields.
- Improved submission content filtering potential XSS payloads.
- Fixed lightswitch UI on Craft 4.4.16+.
- Fixed `tabindex` removal on captchas, preventing good accessibility to keyboard navigate into captchas.
- Fixed Formie JS binding multiple times when calling `endBody()` in Twig manually.
- Fixed Agree field description not correctly enforced as required.

## 2.0.34.1 - 2023-08-08

### Fixed
- Fixed an issue with Honeypot Captcha not working, due to change in 2.0.34.

## 2.0.34 - 2023-08-06

### Added
- Added “Incident” object to Microsoft Dynamics 365 CRM integration.
- Added a flag to getRequestParam to distinguish empty string from null.
- Added `Date::availableDaysOfWeek` for GraphQL.
- Added `data-fui-alert` attribute to alerts.

### Changed
- Switch Formie JS to use `[data-fui-alert]` instead of `[role="alert"]` to target alerts

### Fixed
- Fixed Dropdown field templates when using numbers as values for options.
- Fixed submit/error messages not falling back on Twig-defined values with `form.setSettings`.
- Fixed a GraphQL error for the default value for Date fields.
- Fixed disabled element fields not having their default values set correctly.
- Fixed an migration error for Freeform and Number fields.
- Fixed Variants fields not persisting their “Source” setting.
- Fixed an issue with the Calculations field with special characters.
- Fixed Element Integrations not updating their “Update Entries” values refresh when changing the element group.
- Fixed some characters (quotes) being encoded for field values, causing issues for integration values and email notifications.
- Fixed Webhook payloads not including URL and some other properties for Element and File Upload fields.
- Fixed being able to navigate back further than the first page in submission flow.
- Fixed Date field settings returning incorrect timezone information when querying via GQL.
- Fixed an issue when duplicating forms, not all settings were being duplicated.
- Fixed `Date::availableDaysOfWeek` for GraphQL.
- Fixed view submission permissions.
- Fixed an issue with theme config when only set in `formie.php` config and using `resetClass`.
- Fixed an issue with submission editing and submission limit setting.
- Fixed an issue with Honeypot captchas for GraphQL.

## 2.0.33 - 2023-07-11

### Added
- Added support for Multi-Line Text fields retaining their HTML when mapped to text fields in Craft for Element integrations.
- Added support for Single-Line Text fields retaining their HTML when mapped to text fields in Craft for Element integrations.

### Fixed
- Fixed Element integrations mapping to Multi-Line text fields with single quotes being encoded.
- Fixed a type error with Solspace Calendar integration.
- Fixed front-end JS/CSS assets not being minified correctly.
- Fixed HubSpot integration and Agree fields (and other boolean types) not correctly casting to true/false.
- Fixed Contact Form stencil’s admin Email Notification from using the users’ email address as the “From” value, to improve email deliverability.
- Fixed an error with form usage and Super Table fields.
- Fixed an error for options fields (Dropdown, Checkboxes, Radio Buttons) when options were numbers.
- Fixed an error when mapping fields with Feed Me submissions and Repeater/Group fields.
- Fixed some fields not working on the form builder (Vizy, Icon Picker).

## 2.0.32 - 2023-06-25

### Added
- Added Money, Number and Date Time field support for ActiveCampaign CRM integration.

### Fixed
- Fixed min/max character limit server-side validation when containing newlines.
- Fixed Deal Stages options not being populates for Active Campaign integration.
- Fixed HubSpot integration with static values mapped to a Checkbox field in HubSpot.
- Fixed classes defined in “Input Attributes” for field settings not working correctly with Theme Config and `resetClass`.
- Fixed an issue with reCAPTCHA v2 Checkbox when not using Formie’s JS theme.

## 2.0.31 - 2023-06-12

### Added
- Added `pointer-events: none;` to inner elements added to `.fui-btn` elements, which can prevent event-binding of submit buttons correctly.

### Changed
- Updated submit buttons instruction text for form builder.
- Changed `data-submit-method` and `data-submit-action` to  `data-form-submit-method` and `data-form-submit-action` on the `<form>` attribute.

### Fixed
- Fixed filtering Sent Notifications by failed status not working.
- Fixed min/max character validation for Multi-Line Text fields.
- Fixed an issue where Date field datepickers would show a validation error when picking a date and “Validate When Typing” was enabled.
- Fixed dropdown and input Date fields not working correctly with validation.
- Fixed Date field dropdowns not showing the defined placeholder.
- Fixed “Form Usage” number in form element index.
- Fixed an error with Solspace Calendar integration.
- Fixed an issue where captchas weren’t working with custom-rendered buttons missing the `data-submit-action` attribute.

## 2.0.30 - 2023-05-27

### Added
- Add `form.setRedirectUrl()`.
- Add support for Recipients field to be shown in element indexes for submissions.
- Add `includeQueryString` parameter to `form.getRedirectUrl()`.
- Add `craft`, `currentSite`, `currentUser` and `siteUrl` to available dynamic variables

### Changed
- Update Pipedrive integration with lead custom fields.
- Update Litemoji to handle some multi-byte strings.
- Ensure events are still triggered if the integration is creating a Draft. (thanks @taylordaughtry).
- Element integrations now factor in fetching existing elements of any status.

### Fixed
- Fix an error with Solspace Calender integration.
- Remove `inline-template` warning from Solspace Calendar integration.
- Fix ActiveCampaign Email Marketing integration not using pagination for tags. (thanks @jimirobaer).

## 2.0.29 - 2023-05-18

### Added
- Added “Form Name” to submission export.
- Added “Submission UID” as an option for variable pickers.
- Added proper loading checks for payment field providers (Stripe, PayWay, PayPal).

### Changed
- Payment integrations can now register `htmlTag` for Theme Config.

### Fixed
- Fixed an error when previewing Email Notifications containing a Payment field.
- Fixed Stripe payment field not honouring Theme Config settings for `fieldInput`.
- Fixed incorrect API url for Pardot integration.
- Fixed User element integration not working correctly with Password fields and non-queue running processing.
- Fixed render function types for `null` forms.
- Fixed an issue migrating Checkbox fields from Freeform forms.
- Fixed Mailchimp integration and setting existing contacts with `status = pending` when double-opt-in is enabled.
- Fixed Repeater/Group fields not having their inner field’s JS initialized correctly (for multi-line rich text).
- Fixed Campaign integration not mapping certain fields (Table, Date, Element) correctly.
- Fixed Element integrations not mapping Element fields correctly.
- Fixed Freshdesk integration for existing contact handling.
- Fixed an error when populating element field values with limits.

## 2.0.28 - 2023-05-02

### Changed
- Updated front-end JS to only use ES6 modules for some utilities.

### Fixed
- Fixed Entry element integration not assigning the correct default author.
- Fixed Formie log files not being attached to support requests.
- Fixed an error with Recruitee integration and the payload response check.
- Fixed validation handling for support requests.
- Fixed an error with migrating newer Freeform submissions with email fields.
- Fixed incorrect submissions being shown when restricting with user permissions.
- Fixed an error when migrating Freeform.
- Fixed an error when processing HTML content in some instances.

## 2.0.27.1 - 2023-04-20

### Fixed
- Fixed Group fields (and their inner fields) not working when used as source of conditions.

## 2.0.27 - 2023-04-19

### Added
- Added support for Opayo payment integration.
- Added support for Friendly Captcha captcha integration.
- Added support for Cloudflare Turnstile captcha integration.
- Added support for pagination of tags for ActiveCampaign integration. (thanks @martinleveille).

### Changed
- Payment fields now implement a `PaymentModel`, allowing access to the payment info in email notifications.
- Improve payment fields to only initialize when in view for the current page.
- Improve variable-parsing performance by checking if the provided value contains any variables to parse.
- Improve edit checks for form/submissions which affected element index actions like duplication.

### Fixed
- Fixed Solspace Calendar element integration.
- Fixed an error when trying to delete newly created statuses.
- Fixed an error when trying to mark a submission as spam/non-spam from an element action.
- Fixed “Default Status” menu button not working for forms.
- Fixed emoji support for HTML field.
- Fixed Campaign integration not enforcing the opt-in setting.
- Fixed payment fields in email notifications not outputting HTML.
- Fixed PayWay field preview in the form builder.
- Fixed global alert being shown for 3DS redirection for some payment integrations.
- Fixed a potential issue when importing a form with an incorrect `defaultStatusId`.
- Fixed an error recording referrer for form submissions.
- Fixed Group fields (and their inner fields) not working when used as source of conditions.
- Fixed conditions not working correctly for custom `fieldNamespace`.
- Fixed form settings not being retained when using the Duplicate element action.
- Fixed custom fields not resolving their form/email notification templates correctly.
- Fixed Table Date/Time columns not setting the correct timezone for Element integrations.

## 2.0.26 - 2023-04-04

### Added
- Added “Purify Content” setting for HTML fields to control HTML Purifier.

### Fixed
- Fixed rich text content being unable to be translated.
- Fixed an issue where manipulated submit buttons containing inner elements (such as icons) affected submission behaviour.

## 2.0.25.1 - 2023-03-27

### Fixed
- Fixed an issue with email notifications “All Fields” values not outputting correctly.

## 2.0.25 - 2023-03-25

### Added
- Added validation checks for some form-related routes and missing form IDs.
- Added failsafe for custom date default. (thanks @friartuck6000).
- Added Microsoft Dynamics 365 Web API version to be configurable via settings. (thanks @jamesmacwhite).

### Changed
- Improve submission performance from the front-end, excluding unnecessary value parsing when not required (`allFields`, etc).
- Improve performance of saving submissions from the front-end. Particularly for large and complex forms.
- Improve performance of saving submissions for large forms with many conditions, containing Element, Group and Repeater fields.
- Update Zapier and Slack to use `Integration::deliverPayloadRequest()` for webhooks.
- Update references of `setError` and `setNotice` to `setFailFlash` and `setSuccessFlash`.
- Update references of `Craft::$app->getRequest()` to `$this->request`.
- Require Dompdf 2.0.3+ to fix vulnerabilities. (thanks @licvido).
- Webhook integrations now no longer requires a JSON response.

### Fixed
- Fixed an issue deleting assets when a form contained multiple File Upload fields.
- Fixed an error when querying a Dropdown with optgroup settings for GraphQL.
- Fixed lack of error handling for Google Sheets when no OAuth token.
- Fixed rich text editor link fields not persisting the “Open in new tab” setting for links.
- Fixed `sessionKey` set on forms not working correctly.
- Fixed forms set to “Reload” on submission not working correctly.
- Fixed Repeater/Group fields not having their inner field’s JS initialized correctly.
- Fixed a log error when using a hidden Recipients field.
- Fixed a potential issue when importing a form with an incorrect `defaultStatusId`.
- Fixed an error recording referrer for form submissions.
- Fixed minor PayWay performance issues.
- Fixed non-Date Picker Date fields not having their “Input Attributes” setting applied to the date input element.
- Fixed an error with Zoho when mapping to some fields classified as JSON Objects.
- Fixed checking the validity of a token use the WhoAmI endpoint for Microsoft Dynamics 365. (thanks @jamesmacwhite).
- Fixed for #1324 undefined array key for Microsoft Dynamics 365. (thanks @jamesmacwhite).

## 2.0.24 - 2023-02-28

### Added
- Added additional error handling for front-end PayPal transactions.
- Added pagination support for ActiveCampaign integration fields.

### Changed
- Changed Microsoft Dynamics 365 `convertFieldType()` function as protected. (thanks @jamesmacwhite).

### Fixed
- Fixed an error when exporting submissions when none exist.
- Fixed Autopilot integration sending empty values for fields.
- Fixed PayPal payment not showing a useful error when missing required `paypalAuthId` for payment request.
- Fixed payment integrations not showing the last payment in emails and submissions, if multiple attempts have been made.
- Fixed an error when exporting submissions when none exist.
- Fixed a compatibility error with both reCaptcha and hCaptcha enabled.
- Fixed an error when mapping to boolean fields for Monday integration.
- Fixed Captcha integrations being all enabled on first install of Formie.
- Fixed submissions not saving correctly when changing the submission spam state from the “All Forms” option in the control panel.
- Fixed submissions not saving correctly when changing the submission status from the “All Forms” option in the control panel.
- Fixed Monday integration when mapping to a Country field from an Address field.

## 2.0.23.1 - 2023-02-20

### Fixed
- Fixed an error when running submissions via the queue.

## 2.0.23 - 2023-02-19

### Added
- Added support for Multi-Line Text fields retaining their HTML when mapped to text fields in Craft for Element integrations.
- Added `IntegrationField::sourceType` for element integrations to track the origin Craft field.
- Added `remove` JS event to Repeater and Table fields.
- Added tags support for ActiveCampaign CRM integration for contact objects.
- Added validation rule for Address field Zip/Postcode length.
- Added `dateCreated` support for Feed Me importing submissions.
- Added ability to change queue job description. (thanks @jamesmacwhite).

### Changed
- Changed element fields to use their titles for values with integrations, when used as an array-value, except for Element integrations.
- Microsoft Dynamics 365 - Order fields in mapping by required status first followed by name ASC. (thanks @jamesmacwhite).
- Numerous Microsoft Dynamics 365 improvements and updates (see https://github.com/verbb/formie/pull/1263). (thanks @jamesmacwhite).

### Fixed
- Fixed element integrations not setting the correct timezone on Date fields.
- Fixed Stripe payments not working correctly when challenged with a 3DS verification.
- Fixed an error when a form contained multiple payment fields, combined with conditions.
- Fixed an error when using scheduled forms with either start/end not provided.
- Fixed Monday integration when mapping to a Country field from an Address field.
- Fixed mapping sub-values for Date fields in integrations not formatting correctly.
- Fixed `league/html-to-markdown:^5.0` dependency.
- Fixed an error on some installs where Markdown in the field builder caused a fatal error.
- Fixed sent notifications throwing an error for `CC` and `BCC` values.
- Fixed an error when creating a form from an outdated stencil.
- Fixed being unable to query Submissions by their `title`.
- Fixed an error with saving Hidden field content.
- Fixed Feed Me integration not importing some field types.

## 2.0.22 - 2023-02-11

### Added
- Added logging to spam/status element actions when failed.
- Added more debugging to reCaptcha integration to provide a reason for failure.
- Allow field settings component element select to be more than just `defaultValue`.

### Fixed
- Fixed some field settings (`columnSuffix`, `contentTable`) not being handled properly when duplicating a form.
- Fixed an error with lead objects for Copper CRM integration.
- Fixed an error running the `prune-content-table-fields` console command.
- Fixed being unable to remove “Match Field” setting for some fields.
- Fixed Recaptcha v2 (checkbox) not using Theme Config classes for its error message.
- Fixed an issue with HubSpot mapping to a Form, when using checkboxes and other array-like fields.
- Fixed Phone field “Allowed Countries” values not persisting.
- Fixed Number fields not allowing `0` as a default value.
- Fixed Recaptcha not working with Theme JS disabled.
- Fixed Group/Repeater server-side errors not showing for Ajax-based forms.
- Fixed an error with disabled fields and validation checks for `isEmpty`.
- Fixed not showing server-side errors inside Group/Repeater fields.
- Fixed tabs not showing errors for server-side validation for Group/Repeater fields.
- Fixed server-side errors not being set correctly for sub-fields.
- Fixed an error with the Table field and `LitEmoji`.
- Fixed an error with Sent Notifications, when setting the `sender`.
- Fixed “Opt-in Field” setting for integrations not persisting correctly.
- Fixed a type error for `PdfRenderOptionsEvent`.
- Fixed an error with most mailers sending large attachments (over 15mb) to email notifications.

## 2.0.21 - 2023-01-30

> {warning} If you are using Twig in hidden fields' default value, refer to breaking changes.

### Added
- Added Solspace Calendar Event element integration.
- Added `enableLargeFieldStorage` plugin setting to allow creating large forms exceeding 100+ fields.
- Added support for `postal_town` for Google Places address provider, as a fallback when populating City values (useful for UK).
- Added anonymous support to `save-submission` to edit already existing submissions.
- Added `craft.formie.renderCss` and `craft.formie.renderJs` to aid with SPA rendering
- Added spam reason notes to failed Honeypot captchas.
- Added `—all` and `—hard-delete` options to `formie/sent-notifications/delete` console commands

### Changed
- Email content referencing single field values now escapes HTML content for all fields.
- Increased the height of the `textarea` element for Multi-Line Text fields in the control panel when editing Submissions.
- Integration settings for forms now only return settings for ReCAPTCHA and hCaptcha captchas when querying via GraphQL.

### Fixed
- Fixed recommended fields being marked as required for Microsoft Dynamics CRM integration.
- Fixed an error with dashboard widget for Postgres.
- Fixed a Twig injection vulnerability for Hidden fields.
- Fixed an issue where `Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE` wasn’t being fired in a queue job.
- Fixed pre-populated date fields not submitting their values correctly with the date picker enabled.
- Fixed calendar-based Date fields showing duplicate asterisks when a required field.
- Fixed date parsing for integrations for some formats.
- Fixed a browser formatting warning for Date fields in some instances.
- Fixed text limits not working correctly for Rich Text-enabled Multi-Line fields.
- Fixed an issue where form validation could be skipped in some cases.
- Fixed Single-Line and Multi-Line Text fields not respecting Content Encryption settings.
- Fixed an error with reCAPTCHA settings using GraphQL.
- Fixed `setFieldSettings()` snapshot data persisting beyond the current submission on the front-end.
- Fixed an issue with MySQL 8 and field handle column lengths.
- Fixed an error with Honeypot captchas.
- Fixed an issue with Stripe payments combined with conditions submitting multiple payments in some cases.
- Fixed compliance for `aria-hidden` for inputs.
- Fixed lack of error feedback when trigging an integration from a submission in the control panel.
- Fixed lack of error-handling for Salesforce Lead integrations.
- Fixed fields inside nested fields (Group, Repeater) not resolving to the parent form correctly.
- Fixed an error with Calculations fields where the formula contains decimals.
- Fixed an error when setting the “Sender Email” setting for email notifications.
- Fixed an issue using `populateFormValues` for Repeater fields.
- Fixed error message location for Checkboxes and Radio fields.
- Fixed being unable to delete Sent Submissions from the element index.
- Fixed Table field Date/Time columns not showing content correctly for saved values (timezone).
- Fixed an error when pre-populating Radio/Checkboxes/Dropdown fields from query string values.
- Fixed an error when marking spam from the submissions index when a submission contained a group or repeater field.
- Fixed an error when `submitAction` was missing from submission requests.
- Fixed a migration error when pruning synced fields, where the field handle contains underscores.
- Fixed Page Reload forms not having the correct `redirectUrl` applied when dynamically setting with `form.setSettings()`.

### Breaking Changes
- Hidden field "Default Value" now no longer supports full Twig syntax (anything that requires double `{{` brackets). Shorthand (`{`) Twig is still supported.

## 2.0.20 - 2022-12-15

> {warning} Webhook integrations have their payload altered. They now no longer group submission/form data in a `json` key, they are instead "flat" values. Your Zapier and custom Webhook endpoints will need to factor in this change.

### Added
- Added `data-col-handle` attribute to Table field columns.
- Added support to set Checkboxes and Multi-Dropdown fields with comma-delimited values from a query params.

### Changed
- Moved `updateFormHash` to Flatpickr `onReady` to ensure the form hash is updated when Flatpickr is ready.
- Allow `league/oauth2-google` `^3.0` to fix a conflict with `dukt/analytics`.

### Fixed
- Fixed an issue with MySQL 8 and field handle column lengths.
- Fixed Flatpickr triggering unload warnings for non-English locale sites.
- Fixed Email fields when marked as unique, not validating correctly when editing a submission.
- Fixed an error with flags throwing an error for the Phone field with countries enabled.
- Fixed text limits not working correctly for Rich Text-enabled Multi-Line fields.
- Fixed an error initializing field JS for inner Repeater fields.
- Fixed HTML attributes not allowing `0` as values.
- Fixed a potential error with `submitAction` not being set.
- Fixed Payment model `getField()` throwing an error. (thanks @JeroenOnstuimig).
- Fixed Calculations fields not working on multi-page forms with Group/Repeater fields.
- Fixed hCaptcha triggering an unload warning.
- Fixed webhook integrations testing and live payload not being the same (removed an extra `json` key).
- Fixed an issue where `password` (and other fields) may have some non-unique attributes stripped.

## 2.0.19 - 2022-12-06

### Added
- Added `fieldRequired` Theme Config setting for fields.
- Added `getCurrentPageIndex()` to Formie Theme JS.
- Added `getCurrentPage()` to Formie Theme JS.
- Added `data-field-display-type` to default field container HTML.
- When submitting a form, a `submission` variable is now available for Page Reload forms that display a success message.

### Changed
- Now requires Craft 4.3.2+.
- Integration settings for forms now only return settings for ReCAPTCHA and hCaptcha captchas when querying via GraphQL.
- Constant Contact lists are now sorted alphabetically.
- Changed interface type of nested field. (thanks @kunz1412).
- Changed countries option “Turkey” to “Turkiye”.
- Sent Notifications are now retained after a Form, Submission or Email Notification has been deleted.
- Update Microsoft Dynamics CRM integration connection to use a limited query for performance.

### Fixed
- Fixed sync fields creating duplicate content columns.
- Fixed an error when toggling status, spam and incomplete submissions in the submissions index for the control panel.
- Fixed an issue where single-page, Ajax forms would be hidden when encountering a server-side error (like a timeout request).
- Fixed a JS error with empty calculation fields.
- Fixed an error when some data attributes were being stripped out of rendering.
- Fixed an error for eager loading nested field rows.
- Fixed a typo in DotDigital integration.
- Fixed Phone Number country dropdown not working correctly with Theme Config and `resetClasses` enabled.
- Fixed an duplicated attributes when using Theme Config.
- Fixed an error when creating a new From from a Stencil containing Group/Repeater fields.
- Fixed an error when validating min-values for Single-Line/Multi-Line Text fields.
- Fixed custom error messages not working correctly with Theme Config and `resetClasses`.
- Fixed disabled captchas being enabled when creating a new form from a stencil.
- Fixed an error when populating Repeater fields with `populateFormValues()`.
- Fixed accessibility issues for Flatpickr-based Date/Time fields.
- Fixed a type error for Gatsby/GraphQL for Number fields with `min/max` settings being a float.
- Fixed an issue when calling `form.setFieldSettings()` multiple times, and settings not applying.
- Fixed an issue with Microsoft Dynamics when mapping to campaigns.

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

## 1.6.43 - 2024-05-31

### Fixed
- Fix an error when submitting a form and manipulating the `goingBack` param.

## 1.6.42 - 2024-05-20

### Fixed
- Fix an error when triggering an integration where the `tokenId` has become invalid.

## 1.6.41 - 2024-04-18

### Added
- Added the ability to set the captcha type for reCAPTCHA Enterprise.
- Added Google Console API Key for reCAPTCHA Enterprise.
- Added Referer, User Agent and User IP headers for reCAPTCHA Enterprise requests.

## 1.6.40 - 2024-03-29

### Added
- Added support for additional SugarCRM fields.

## 1.6.39 - 2024-03-16

### Fixed
- Fixed Tiptap v1 and ProseMirror compatibility.

## 1.6.38 - 2024-03-13

### Fixed
- Fixed a Tiptap dependency causing the form builder to not load properly.

## 1.6.37 - 2024-03-08

### Fixed
- Fixed mutli-name fields that are conditionally hidden throwing an error in email notifications.
- Fixed a JS error when validating File Upload fields.

## 1.6.36.1 - 2023-12-12

### Changed
- Improve error message when failing to save a form.

### Fixed
- Fixed an error when creating new forms.

## 1.6.36 - 2023-12-12

### Fixed
- Fixed an error when form message settings contained emoji’s.
- Fixed lack of enter key accessibility for sent notification and submission modals in the control panel.
- Fixed an issue using `populateFormValues` for Repeater fields.

## 1.6.35 - 2023-10-25

### Added
- Added “Reply-To Name” setting for email notifications.
- Added “Webhook URL” as setting for Webhook integration when querying via GraphQL.
- Added `autocomplete=“name”` to single Name fields.

### Fixed
- Fixed a PHP 8 deprecation notice.
- Fixed an error for Dropdown fields when toggling between options being an optgroup and not.

## 1.6.34 - 2023-10-08

### Added
- Added support for expand parameter on target schemas for Microsoft Dynamics 365 CRM. (thanks @jamesmacwhite).

### Fixed
- Fixed an issue with Dynamics 365 and Created By value.
- Fixed query restrictions for system users for Microsoft Dynamics 365 integration.

## 1.6.33 - 2023-09-25

### Added
- Added “Created By” field mapping for Dynamics 365 CRM integration.
- Added `Field::getRequiredPlugins()` to better support plugin-dependant fields.

### Changed
- Updated `isPluginInstalledAndEnabled` check.

### Fixed
- Fixed Dropdown field throwing errors after changing options from being an `optgroup`.
- Fixed an error when toggling Dropdown field options from using an optgroup to a default.

## 1.6.32 - 2023-08-31

### Added
- Added consolidated errors when saving forms in the control panel.

### Fixed
- Fixed being unable to choose which site a submission is saved in, when editing a submission from the control panel.

## 1.6.31 - 2023-08-31

### Changed
- Map an account to lead data using `parentaccountid` or `customerid_account` for Dynamics 365 integration.
- Exporting submissions now use each fields' Name instead of Handle.

### Fixed
- Fixed some characters (quotes) being encoded for field values, causing issues for integration values and email notifications.
- Fixed being able to send email notifications and trigger integrations for spam submissions in the control panel.

## 1.6.30 - 2023-08-06

### Added
- Added “Incident” object to Microsoft Dynamics 365 CRM integration.

### Fixed
- Fixed Dropdown field templates when using numbers as values for options.
- Fixed submit/error messages not falling back on Twig-defined values with `form.setSettings`.

## 1.6.29 - 2023-07-11

### Added
- Added support for Multi-Line Text fields retaining their HTML when mapped to text fields in Craft for Element integrations.
- Added support for Single-Line Text fields retaining their HTML when mapped to text fields in Craft for Element integrations.

### Fixed
- Fixed Element integrations mapping to Multi-Line text fields with single quotes being encoded.
- Fixed a type error with Solspace Calendar integration.

## 1.6.28 - 2023-06-25

### Fixed
- Fixed Deal Stages options not being populates for Active Campaign integration.

## 1.6.27 - 2023-05-27

### Added
- Added `craft`, `currentSite`, `currentUser` and `siteUrl` to available dynamic variables

### Changed
- Ensure events are still triggered if the integration is creating a Draft. (thanks @taylordaughtry).
- Element integrations now factor in fetching existing elements of any status.

### Fixed
- Fixed ActiveCampaign Email Marketing integration not using pagination for tags. (thanks @jimirobaer).

## 1.6.26 - 2023-03-25

### Added
- Added Microsoft Dynamics 365 Web API version to be configurable via settings. (thanks @jamesmacwhite).

### Fixed
- Fixed an issue deleting assets when a form contained multiple File Upload fields.
- Fixed an error when querying a Dropdown with optgroup settings for GraphQL.
- Fixed checking the validity of a token use the WhoAmI endpoint for Microsoft Dynamics 365. (thanks @jamesmacwhite).
- Fixed for #1324 undefined array key for Microsoft Dynamics 365. (thanks @jamesmacwhite).
- Fixed lack of error handling for Google Sheets when no OAuth token.

## 1.6.25 - 2023-02-28

### Added
- Added pagination support for ActiveCampaign integration fields.

### Changed
- Changed Microsoft Dynamics 365 `convertFieldType()` function as protected. (thanks @jamesmacwhite).

### Fixed
- Fixed an error when exporting submissions when none exist.
- Fixed Autopilot integration sending empty values for fields.

## 1.6.24 - 2023-02-19

### Added
- Added tags support for ActiveCampaign CRM integration for contact objects.
- Added validation rule for Address field Zip/Postcode length.
- Added `dateCreated` support for Feed Me importing submissions.
- Added ability to change queue job description. (thanks @jamesmacwhite).

### Changed
- Microsoft Dynamics 365 - Order fields in mapping by required status first followed by name ASC. (thanks @jamesmacwhite).
- Numerous Microsoft Dynamics 365 improvements and updates (see https://github.com/verbb/formie/pull/1263). (thanks @jamesmacwhite).

### Fixed
- Fixed sent notifications throwing an error for `CC` and `BCC` values.
- Fixed an error when creating a form from an outdated stencil.
- Fixed being unable to query Submissions by their `title`.
- Fixed an error with saving Hidden field content.
- Fixed Feed Me integration not importing some field types.

### Removed
- Remove deprecated `countryRestrict` from Phone field.

## 1.6.23 - 2023-02-11

### Fixed
- Fixed an error with most mailers sending large attachments (over 15mb) to email notifications.

## 1.6.22 - 2023-01-30

> {warning} If you are using Twig in hidden fields' default value, refer to breaking changes.

### Added
- Added Solspace Calendar Event element integration.
- Added `enableLargeFieldStorage` plugin setting to allow creating large forms exceeding 100+ fields.
- Added support for `postal_town` for Google Places address provider, as a fallback when populating City values (useful for UK).

### Changed
- Email content referencing single field values now escapes HTML content for all fields.
- Increased the height of the `textarea` element for Multi-Line Text fields in the control panel when editing Submissions.
- Integration settings for forms now only return settings for ReCAPTCHA and hCaptcha captchas when querying via GraphQL.

### Fixed
- Fixed recommended fields being marked as required for Microsoft Dynamics CRM integration.
- Fixed an error with dashboard widget for Postgres.
- Fixed a Twig injection vulnerability for Hidden fields.
- Fixed an issue where `Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE` wasn’t being fired in a queue job.
- Fixed pre-populated date fields not submitting their values correctly with the date picker enabled.
- Fixed calendar-based Date fields showing duplicate asterisks when a required field.
- Fixed date parsing for integrations for some formats.
- Fixed a browser formatting warning for Date fields in some instances.
- Fixed text limits not working correctly for Rich Text-enabled Multi-Line fields.
- Fixed an issue where form validation could be skipped in some cases.
- Fixed Single-Line and Multi-Line Text fields not respecting Content Encryption settings.
- Fixed an error with reCAPTCHA settings using GraphQL.
- Fixed `setFieldSettings()` snapshot data persisting beyond the current submission on the front-end.
- Fixed an issue with MySQL 8 and field handle column lengths.

### Breaking Changes
- Hidden field "Default Value" now no longer supports full Twig syntax (anything that requires double `{{` brackets). Shorthand (`{`) Twig is still supported.

## 1.6.21 - 2022-12-15

### Changed
- Moved `updateFormHash` to Flatpickr `onReady` to ensure the form hash is updated when Flatpickr is ready.

### Fixed
- Fixed an issue with MySQL 8 and field handle column lengths.
- Fixed Flatpickr triggering unload warnings for non-English locale sites.
- Fixed Email fields when marked as unique, not validating correctly when editing a submission.

## 1.6.20 - 2022-12-06

### Changed
- Updated Microsoft Dynamics CRM integration connection to use a limited query for performance.

### Fixed
- Fixed an issue where single-page, Ajax forms would be hidden when encountering a server-side error (like a timeout request).
- Fixed a JS error with empty calculation fields.
- Fixed accessibility issues for Flatpickr-based Date/Time fields.
- Fixed an issue with Microsoft Dynamics when mapping to campaigns.

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

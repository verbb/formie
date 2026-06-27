# Fields

Field behaviour, calculations, option sources, and custom field authoring.


##### [Bringing your Craft field into Formie (Custom Field adapters)](/guides/fields/bringing-your-craft-field-into-formie-custom-field-adapters)

Formie's **Custom Field** field type lets authors pick from supported Craft fields without you registering a separate Formie field for every plugin. Each supported option is an **adapter** — explicit Formie code that handles front-end rendering, validation, storage, exports, integrations, and GraphQL.

##### [Calculations field in detail](/guides/fields/calculations-field-in-detail)

This guide walks through Calculations syntax, field references, built-in functions, and practical examples. Use it alongside the [Calculations field reference](/fields/calculations) when you are building pricing, scoring, or conditional output on a form.

##### [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider)

Custom option source providers expose server-resolved `{ label, value }` rows for Dropdown, Radio, Checkboxes, and Recipients fields. They appear in the form builder as **Custom Provider** when they declare support for the current field usage.

##### [Creating a Formie field type from scratch](/guides/fields/creating-a-formie-field-type-from-scratch)

In this guide we build a URL field from scratch — similar to Single-line Text, but with URL validation and optional front-end behaviour. By the end you will have a field that appears in the form builder palette, renders on the front end, validates submissions, and supports Theme Config.

##### [Dynamic option sources in practice](/guides/fields/dynamic-option-sources-in-practice)

Dropdown, Radio, and Checkboxes fields can pull their options from several source types — not just a manually maintained table. This walkthrough shows when to use each option source and how they behave at render and submit time.

##### [How to modify Element field queries to change what elements can be chosen](/guides/fields/how-to-modify-element-field-queries)

Element fields — Entries, Categories, Users, Tags, Products, and Variants — all work the same way underneath: Formie builds an element query from the field settings, then renders that list as a dropdown, radio buttons, checkboxes, or multi-select. That is much more efficient than copying hundreds of options into a static Dropdown field.

##### [Payment field with Stripe — full flow](/guides/fields/payment-field-with-stripe-full-flow)

This walkthrough connects Stripe to a Formie form end to end: integration setup, Payment field configuration, form behaviour, and optional subscription features.

##### [Surveys, polls and quizzes end-to-end](/guides/fields/surveys-polls-and-quizzes-end-to-end)

Formie separates **questionnaire** use cases into two field types: **Survey** for collecting and aggregating responses, and **Quiz** for scored assessments with pass/fail logic. Simple **polls** — single-question votes — are built with Survey fields using Radio or Dropdown presentation.

##### [Write your own parsing logic for the Calculations field](/guides/fields/write-your-own-parsing-logic-for-the-calculations-field)

The Calculations field evaluates a formula from other field values and shows the result in a read-only input. Formie uses a JavaScript implementation of Symfony Expression Syntax for front-end evaluation. When built-in formatting is not enough — currency display, rounding rules, capping input values before they enter the formula — you can hook into evaluation events and adjust the formula, variables, or result.

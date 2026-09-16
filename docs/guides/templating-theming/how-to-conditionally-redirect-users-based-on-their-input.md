# How to Conditionally Redirect Users Based on Their Input

Sometimes the thank-you destination should depend on what the user submitted — send opted-in users to one URL and everyone else to another, route by department, or branch on a quiz score. Formie supports this natively with **Redirect Rules**; the patterns below cover cases where you need more control.

## Prerequisites

- A form with submit action set to **URL** or **Entry**
- Familiarity with [Conditions](/forms/conditions) and [Reference tokens](/developers/reference-tokens)

## Use Redirect Rules (Recommended)

Formie adds **Enable Redirect Rules** in form settings. Each rule has conditions and a redirect target. Rules are evaluated in order; the first match wins.

1. Open your form in the form builder
2. Under submit settings, enable **Enable Redirect Rules**
3. Add rules with conditions (for example, "Agree field is checked") and set each rule's redirect URL or entry

This works for both page-reload and Ajax forms, stays in the control panel, and does not require custom code. Prefer this approach whenever it covers your logic.

## Use an Account-Only Intermediary Template

If a redirect needs server-side logic that the built-in rules cannot express, first establish access to the submission. Follow [the signed-in summary pattern](/guides/templating-theming/build-a-success-page-for-your-form#show-answers-to-the-signed-in-submitter), including collection of the current user, the form and site filters, and the ownership check.

After that template has obtained the authorised `submission`, replace its output with this partial Twig snippet. It assumes your form has a Dropdown field with handle `department`, whose saved values include `sales`:

```twig
{% if submission.getFieldValueAsString('department') == 'sales' %}
    {% redirect '/sales/thanks' %}
{% else %}
    {% redirect '/support/thanks' %}
{% endif %}
```

Create both destination templates before testing. Submit each choice as the owning user and confirm the destination. Then open the intermediary URL from another account and confirm it cannot read or branch on that submission. Do not fetch arbitrary numeric submission IDs or use a submitted email address to decide ownership.

Use fixed server-controlled destinations. A hidden input is still editable by the visitor and should not decide access to a resource. For public forms without account ownership, use the built-in redirect rules above and keep the destination page free of private submission content.

## Test the Redirect Rules

Submit answers matching the first rule and check the URL. Repeat for a later rule and for answers matching none. Check both valid and invalid form input: a failed validation must not take the visitor to a success page. If several rules match, verify that their order produces the intended first match.

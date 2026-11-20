# Field Support

Client-rendered forms show every Formie field, with the same settings as the form builder gives them in server-rendered forms. React renders the controls from Formie's client definition. Where a server-rendered form enhances a control with a browser library, such as the date picker or the searchable dropdown, a client-rendered form uses the same browser module on markup React leaves alone, so the control looks and works the same way. A submission saves the same answer whichever way the form was rendered.

This page lists how each field appears, then the few places where a client-rendered form needs something from your site that a server-rendered form gets from Formie's theme.

## Fields

| Field | In a client-rendered form |
| --- | --- |
| Single-line Text, Multi-line Text | A text input or textarea. **Use Rich Text Field** shows the rich text editor and saves its HTML. With **Limit Value**, a counter under the field (`.formie-react-limit`) shows how many characters or words are left, and the limits are checked before the form submits. |
| Email Address, Number, Password | An input of the matching type. A Password field's requirements, such as a minimum length or a special character, are checked before the form submits. |
| Phone Number | A telephone input, with the country picker when **Country Enabled** is on. |
| Hidden Field | A value the visitor doesn't see, including values read from a cookie, the query string, the page URL or the referring page. |
| Dropdown, Radio Buttons, Checkboxes, Agree, Recipients | The matching control. **Use Searchable Dropdown** shows the searchable dropdown, and Checkboxes with a **Select all** option show it. |
| Entries, Categories, Users and other element fields | A dropdown, radio buttons or checkboxes, as the field's display type sets. |
| Date/Time | Separate inputs or dropdowns, or the date picker for **Calendar (Advanced)**, including date ranges. |
| Name, Address, Group | Their parts, grouped under the field label. Address auto-complete providers fill the parts, **State / Province** can list the chosen country's subdivisions, and the country can be preselected from the visitor's IP address. |
| Repeater, Table | Rows the visitor can add and remove. |
| File Upload | A file input, or the Upload Manager for **Upload Manager (Advanced)**, which uploads each file as soon as it's chosen. |
| Signature | A signature pad. |
| Calculations | The calculated answer, updated as the visitor fills in the form. |
| Summary | The answers saved so far, once a page has been submitted. |
| Payment | The payment provider's own form, for every payment integration. |
| Survey | Every display type, including Likert scales, Rank and Rating. |
| Heading, HTML, Rich Text, Section | Their content. |
| Custom Field | Craft Link fields show the link type, label and advanced options, and Maps and Google Maps fields show the map and its search. Other custom fields show a text input. |

## Labels, Instructions and Errors

Each field's label, instructions and errors go where its **Label Position**, **Instructions Position** and **Error Message Position** put them. The field wrapper says which positions it uses in `data-formie-label-position`, `data-formie-instructions-position` and `data-formie-error-position`. When the label is on the left or right, the rest of the field is grouped in `.formie-react-field-content`, so your styles can place the two side by side:

```css
[data-formie-label-position="left"] {
    display: grid;
    grid-template-columns: fit-content(12rem) minmax(0, 1fr);
    align-items: start;
}
```

A label in the **Hidden** position stays in the page, visually hidden, so it still names its control for screen readers.

## Captchas

reCAPTCHA (v2 checkbox, v2 invisible, v3 and Enterprise), hCaptcha, Cloudflare Turnstile, Friendly Captcha, Captcha.eu and the Question captcha work in client-rendered forms. Each one adds its widget or question to the end of the form when the form loads, and its token is sent with the submission. Akismet, CleanTalk and OopSpam check the submitted content on the server, so they need nothing in the browser.

## Controls and Form Data

The form's controls don't have `name` attributes, because Formie submits the answers from the form's state rather than posting the form. Browsers still offer to fill in names, addresses and other details, using the `autocomplete` hints each control has.

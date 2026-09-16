# GraphQL Submission Flow End-to-End

Build a small contact form that loads a Formie session, submits answers through GraphQL and displays validation errors. This guide uses a single-page form with text fields so you can follow the complete request flow before adding multi-page navigation or complex fields.

Formie's frontend packages handle this flow for you. Use this walkthrough when writing your own client or investigating the requests it sends.

## Prepare the Contact Form

Create a form with handle `contactForm`, a Single-Line Text field with handle `yourName`, and a required Email Address field with handle `emailAddress`. Save it. For this first local test, use no Payment fields or captcha providers; connect those after the basic flow works.

In Craft's GraphQL settings, enable the endpoint and give the schema access to read this form and create its submissions. The client mutations need both permissions. A browser-accessible schema must not expose private submission queries or unrelated forms. Do not embed a privileged GraphQL token in browser code.

The example assumes the GraphQL endpoint is `/api` on the same origin as the page. Replace that path if your Craft endpoint differs. Using the same origin keeps cookies with the requests. Cross-origin deployments also need a matching CORS and cookie configuration; establish that separately before testing the form.

## Load the Definition and Session

The form definition describes the fields and layout. The session holds the current page, request tokens and continuation data for this visitor. Query the session's subfields because it is a GraphQL object:

```graphql
query ContactForm($handle: String!) {
    formieClientForm(handle: $handle) {
        schemaVersion
        definition
        session {
            id
            currentPageId
            tokens
            continuation
        }
    }
}
```

Send `{"handle":"contactForm"}` as the variables. Keep the returned session intact, including `tokens` and `continuation`; do not invent token values or share a session between visitors. A missing form or permission produces an error before you can submit.

## Submit the Answers

The client mutation accepts one `input` object. Supply the form handle, the current session and a `values` map keyed by field handle:

```graphql
mutation SubmitContact($input: FormieClientSubmitInput!) {
    submitFormieClientForm(input: $input) {
        success
        submissionUid
        isFinalPage
        nextPageId
        errors
        messages
        session {
            id
            currentPageId
            tokens
            continuation
        }
    }
}
```

Construct variables in JavaScript from the actual bootstrap response:

```javascript
const variables = {
    input: {
        handle: 'contactForm',
        action: 'submit',
        session: envelope.session,
        values: {
            yourName: 'Alex Taylor',
            emailAddress: 'alex@example.test',
        },
    },
};
```

Here `envelope` is the returned `data.formieClientForm` object. The complete example below defines it and sends the request. Always replace your session with the one returned by the mutation, including on validation failure when a session is present.

## Build a Working Page

Create `templates/contact-client.twig` in the Craft project. This complete example uses the public schema configured above and the same-origin `/api` endpoint. It deliberately uses browser text output rather than inserting returned messages as HTML.

```twig
<form id="contact-client" novalidate>
    <label for="contact-name">Your Name</label>
    <input id="contact-name" name="yourName" autocomplete="name">
    <label for="contact-email">Email Address</label>
    <input id="contact-email" name="emailAddress" type="email" autocomplete="email" required>
    <button type="submit" disabled>Send Enquiry</button>
    <p role="status" aria-live="polite"></p>
</form>

{% js %}
const contactElement = document.querySelector('#contact-client');
const contactButton = contactElement.querySelector('button');
const contactStatus = contactElement.querySelector('[role="status"]');
let contactSession;

async function contactRequest(query, variables) {
    const response = await fetch('/api', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ query, variables }),
    });
    if (!response.ok) {
        throw new Error(`Request failed (${response.status}).`);
    }
    const result = await response.json();
    if (result.errors?.length || !result.data) {
        throw new Error(result.errors?.[0]?.message || 'No form response was returned.');
    }
    return result.data;
}

const contactSessionSelection = 'id currentPageId tokens continuation';

async function loadContact() {
    const data = await contactRequest(`
        query ContactForm($handle: String!) {
            formieClientForm(handle: $handle) {
                schemaVersion
                definition
                session { ${contactSessionSelection} }
            }
        }
    `, { handle: 'contactForm' });
    if (!data.formieClientForm) {
        throw new Error('The contact form is unavailable.');
    }
    contactSession = data.formieClientForm.session;
    contactButton.disabled = false;
}

contactElement.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (contactButton.disabled || !contactSession) return;
    contactButton.disabled = true;
    contactStatus.textContent = 'Sending your enquiry…';
    try {
        const data = await contactRequest(`
            mutation SubmitContact($input: FormieClientSubmitInput!) {
                submitFormieClientForm(input: $input) {
                    success submissionUid isFinalPage nextPageId errors messages
                    session { ${contactSessionSelection} }
                }
            }
        `, {
            input: {
                handle: 'contactForm',
                action: 'submit',
                session: contactSession,
                values: {
                    yourName: contactElement.elements.yourName.value,
                    emailAddress: contactElement.elements.emailAddress.value,
                },
            },
        });
        const result = data.submitFormieClientForm;
        if (!result) throw new Error('No submission response was returned.');
        if (result.session) contactSession = result.session;
        if (!result.success) {
            contactStatus.textContent = Object.values(result.errors || {}).flat().join(' ')
                || 'Please check your answers and try again.';
            contactButton.disabled = false;
            return;
        }
        if (!result.isFinalPage || result.nextPageId) {
            throw new Error('This example requires a single-page contact form.');
        }
        contactStatus.textContent = 'Thanks. Your enquiry has been submitted.';
    } catch (error) {
        contactStatus.textContent = error.message;
        contactButton.disabled = false;
    }
});

loadContact().catch((error) => {
    contactStatus.textContent = error.message;
});
{% endjs %}
```

Open `/contact-client`. The button becomes available after bootstrap succeeds. An empty email should produce a field-validation message; entering a valid email should show the confirmation. The button stays disabled after success to avoid another deliberate submission of the completed form.

## Distinguish Validation from Request Errors

For `submitFormieClientForm`, field validation is returned in `data.submitFormieClientForm.errors` with `success: false`. Display these messages beside the affected inputs in a production UI and move focus to the first invalid field or an error summary. The small example combines them in a live status region.

GraphQL syntax, permission and transport failures are separate from field validation. Check the top-level `errors` array and HTTP status as well. The typed `save_<handle>_Submission` mutations use a different validation-error contract; see [Create Submissions](/graphql/create-submissions). Do not apply that contract to the client mutation.

## Refresh a Session

A cached page must obtain a visitor-specific session before submitting. When refreshing an existing session, send that whole session to the refresh mutation:

```graphql
mutation RefreshContact($input: FormieClientSessionRefreshInput!) {
    refreshFormieClientSession(input: $input) {
        id
        currentPageId
        tokens
        continuation
    }
}
```

In the page's JavaScript, a refresh uses the existing `contactRequest` helper and replaces `contactSession`:

```javascript
async function refreshContact() {
    const data = await contactRequest(`
        mutation RefreshContact($input: FormieClientSessionRefreshInput!) {
            refreshFormieClientSession(input: $input) {
                id currentPageId tokens continuation
            }
        }
    `, { input: { handle: 'contactForm', session: contactSession } });
    contactSession = data.refreshFormieClientSession;
}
```

Add this function inside the existing `{% js %}` block if your UI needs an explicit refresh action. A refresh returns the session directly, not an envelope containing another `session` property. Do not automatically retry a submission after a network timeout: the server may already have saved it. Preserve the session and investigate the result before sending again.

## Add Multiple Pages

For a multi-page form, render the fields belonging to `session.currentPageId` from the definition. Submit that page's values with the same client mutation. Keep the returned session and show `nextPageId` when present. Only show a final confirmation when the result succeeds and the flow has completed.

For navigation without a normal submit, use:

```graphql
mutation ChangeContactPage($input: FormieClientSetPageInput!) {
    setFormieClientPage(input: $input) {
        id
        currentPageId
        tokens
        continuation
    }
}
```

Its input contains `handle`, the current `session`, `currentPageId`, `targetPageId` and `values`. Use page IDs from the definition and returned session, not hard-coded labels. Replace your session with the result. Page navigation is not a substitute for submitting and validating the final page.

The single-page UI above must be extended to render each page before using a multi-page form. [Formie's frontend packages](https://docs.verbb.io/formie/react/) provide that rendering and navigation flow.

## Verify the Saved Result

Submit a valid test enquiry and find it in **Formie → Submissions**. Check the answers and complete state. If you configured a notification, inspect its delivery too. Repeat with an invalid email and confirm that the page shows an error and no completed enquiry is created.

Try a schema without access to the form and confirm that bootstrap or submission fails. Test two browser sessions to confirm their session values remain separate. Keep this page out of full-page caches that would cache visitor tokens.

After this works, add [captcha handling](/graphql/create-submissions#captchas), [complex fields](/graphql/create-submissions#complex-fields), or [Headless Payments](/graphql/headless-payments) as needed. Complex values and uploads need their documented serialisation; the two text inputs above do not cover those field types.

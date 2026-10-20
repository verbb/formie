# Spam Protection

::: tip
For keyword syntax and examples, see [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail). For how screening layers work together in practice, see [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice).
:::

Spam protection is about reducing junk submissions without making the form harder for real people to use.

Formie gives you a few layers to work with, so you can start with lighter filtering and add stronger checks only where they are needed. Most of the control lives in **Formie → Spam Protection**.

That page brings together spam handling, keyword rules, built-in submission guards, and captcha provider credentials in one place.

Users with the relevant permissions can edit spam protection settings even when Craft’s `allowAdminChanges` is `false`. Settings are saved in the current installation’s database. See [Control Panel Settings](#control-panel-settings) for permissions and production setup.

## When Checks Run

Request safeguards run before Formie processes a form, including draft saves and page changes. Content rules and CAPTCHA checks run when a visitor completes the form, after field validation so visitors can correct mistakes first.

Formie checks email rules, suspicious text, link limits, spam keywords and IP rules before running the enabled CAPTCHA providers. If a content rule has already identified spam, CAPTCHA checks are skipped. Draft saves and edits to existing submissions skip content screening; a payment returning from its provider continues the already-checked submission.

To add project-specific checks in PHP, see the [Screen stage](/developers/submission-workflow#screen) and [custom workflow task guide](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch).

## Spam Handling

At the plugin level, you can choose whether spam submissions should still be saved. Saving them is useful when you need to review false positives, understand what kind of spam is hitting the form, or debug a captcha or filtering rule.

If you do save spam, you can also set a limit for how many spam submissions Formie should keep before older ones are pruned. That helps keep the database under control without losing all visibility into what is being blocked.

You can also decide how Formie should respond when a submission is flagged as spam. In many cases, it is better to behave as though the submission was accepted, rather than giving a clear rejection message that helps bots learn what is being blocked. If you prefer, you can instead show an error message.

If you use email notifications, there is also a plugin setting for whether spam submissions should still trigger them.

## Content Rules

### Spam Keywords

Spam keywords are the simplest built-in screening tool. Formie checks the whole submission, and if it matches your keyword definition, the submission will be marked as spam.

You can match against:

- words or phrases
- combinations such as `AND`, `OR`, and `NOT`
- IP addresses or IP ranges

This makes spam keywords useful for obvious repeat attacks or recurring junk content.

#### Keyword Definition

```text
# Flags content containing the word "spam". This will not match "spamming" or "Spam".
[match: spam]

# Flags content containing the exact phrase "cheap ham".
[match: cheap ham]

# Flags content only if both "spam" and "bulk" are present.
[match: spam AND bulk]

# Flags content if either "spam" or "phishing" is present.
[match: spam OR phishing]

# Flags content if it contains either "spam" or "junk" along with "email".
[match: (spam OR junk) AND email]

# Flags content if it does not contain "client".
[match: NOT client]
```

You can define each rule on a new line, and you can use parentheses to group logic when needed.

#### IP Addresses

```text
# Flags content if the sender's IP matches. Supports singular, multiple, ranges and CIDR notation.
[ip: 192.168.0.1, 192.168.0.2, 192.168.0.3]
[ip: 10.0.0.1]
[ip: 192.168.0.1-192.168.0.255]
[ip: 192.168.0.0/24]
```

#### Referencing Other Content

If you want content editors to manage the keyword text without access to the Spam Protection settings page, you can reference another field instead.

This is commonly done with a Global Set. For example, if you had a Global Set called `Forms` and a field called `Spam Keywords`, you could reference it in the Formie spam keywords setting with `{forms.spamKeywords}`.

Keyword rules can include `[match:]` comparisons, `[ip:]` ranges and CIDR notation, and field or global references.

### Email Rules

Global email rules apply to every **Email Address** field on every form during the **`screen`** stage. They mark matching submissions as spam rather than showing a field validation error.

Configure them under **Formie → Spam Protection → Spam Rules → Content Rules → Email Rules**:

- **Allowed domains** — one domain or full email address per line (for example `company.com` or `user@company.com`). Allowlisted addresses skip global blocked-domain and free-provider checks only; keywords, captchas, guards, and other spam rules still apply.
- **Blocked domains** — one domain per line (for example `mailinator.com`)
- **Block free email providers** — rejects addresses from Formie’s maintained free/disposable provider list

Per-field email settings such as **Blocked Domains**, **Block Free Email Providers**, and **Validate Domain (DNS)** still run separately during field validation. Use global rules when you want the same policy everywhere; use per-field settings when only some forms need stricter email checks.

### Text Rules

Configure these under **Formie → Spam Protection → Spam Rules → Content Rules → Text Rules**:

- **Suspicious text detection** — flags keyboard spam, random character strings, and other low-quality filler text in submission fields. Add **Allowed terms** (one per line) for abbreviations or product codes that should not be flagged.
- **Maximum links** — marks submissions as spam when the total number of links across all fields exceeds the configured limit.

Formie checks these rules after email rules and before spam keywords.

## Submission Throttling

Submission throttling is **abuse protection**, not entry caps. It limits how quickly forms can be submitted during floods or rapid repeat attempts. Configure it under **Formie → Spam Protection → Spam Rules → Submission Throttling**.

For contest, registration, or quota-style caps, use per-form **[Submission limits](/forms/submission-limits)** in the form builder instead. Throttling rejects excess requests with a too-many-requests response (HTTP 429); submission limits close the form or show a validation error according to your business rules.

| Setting | What it does |
| --- | --- |
| **Global submission throttling** | Caps the total number of submissions allowed across **all forms** within a time window. Intended for emergency abuse protection during spam floods, not fine-tuning individual forms. |
| **IP submission throttling** | Requires a minimum **wait time** between submissions from the same IP address on the **same form**. This is not the same as a per-IP **count** limit on the form — see [Submission limits](/forms/submission-limits#ip-wait-time-vs-ip-count-limits). |

Throttling blocks requests that exceed the configured limits, including draft saves and page changes. It runs before the other spam checks.

Use conservative values for global throttling. For example, `1 per minute` across the entire site would block all users after the first submission.

## Submission Guards

Submission guards are built-in passive checks that run **before** captcha integrations and keyword rules.

Configure them under **Formie → Spam Protection → Spam Rules → Submission Guards**.

| Guard | What It Does |
| --- | --- |
| **Honeypot** | Renders a hidden field that legitimate users should leave empty. Requests that fill it in are rejected before the submission is saved. |
| **Minimum submit time** | Requires a minimum delay between the form loading and submission. |
| **Form submit expiration** | Rejects submissions when too much time has passed since the form was first loaded. |
| **Replay protection** | Prevents duplicate submissions from reusing the same `requestToken`. |

Honeypot, minimum submit time, and replay protection are enabled by default. Form submit expiration is off by default.

### How Guards Run in the Workflow

Guards are not captcha integrations. They do not appear in the form builder’s captcha picker, and they do not use provider credentials.

Formie checks these safeguards before processing the form, including for logged-in visitors. A failed honeypot or timing check can return a fake success when that spam policy is configured, but it does not create a saved submission marked as spam. Visitors can correct validation errors and submit again. If a connection fails after a successful submission, retrying the same request recovers its result rather than creating a duplicate.

Custom REST and GraphQL clients must send the session tokens returned when they load the form. See [Rendering Forms](/graphql/rendering-forms) for client setup.

The honeypot field and `formStartedAt` timestamp are rendered automatically for browser forms. You do not need to add them manually in templates.

### Honeypot Field Name

The default honeypot input name is `formieHoneypot`. You can change it under **Honeypot Field Name** if you need to avoid a clash with a real field on your forms.

If you customise the name, make sure it does not match any field handle or name your forms already use.

### Minimum Submit Time

The browser package records when a form instance was first mounted and sends that value as `formStartedAt`. Formie compares it against the configured minimum (in seconds) on the server.

Very fast automated submissions are rejected before they are saved. Real users who submit immediately after the page loads may also be caught if the minimum is set too high, so tune this value for your forms.

### Form Submit Expiration

Form submit expiration uses the same `formStartedAt` timestamp as minimum submit time. If the elapsed time exceeds the configured maximum (in seconds), the request is rejected before the submission is saved.

Use this when you want to reject stale form sessions left open for hours or days. It is disabled by default.

### Replay Protection

Replay protection uses the existing per-render `requestToken` that Formie already issues for browser forms. Formie records the operation in the database. Retrying the same completed operation recovers its result; reusing its identity for different submitted content is rejected.

This is separate from save-and-continue draft tokens and static-cache refresh behaviour. It targets repeat POST abuse, not legitimate multi-page navigation.

## Captchas

Formie also supports captcha integrations when you need a stronger challenge layer.

Configure provider credentials under **Formie → Spam Protection → CAPTCHAs**, then enable the providers you need per form in the form builder.

Use captchas when keyword matching and submission guards are not enough, or when the form is a common attack target. Third-party spam scoring services such as Akismet, CleanTalk and OOPSpam are configured under **Spam Filtering Services**. They classify submission content and can be used alongside a CAPTCHA.

See [Captchas](/integrations/captchas/) for provider-specific setup guides.

## Control Panel Settings

Open **Formie → Spam Protection**. **Spam Rules** contains spam handling, content rules, submission guards and throttling. **CAPTCHAs** contains visitor-verification tools such as Turnstile and reCAPTCHA. **Spam Filtering Services** contains Akismet, CleanTalk and OOPSpam, which analyse submissions. Both groups have separate settings and a Save button for each provider. You can use a CAPTCHA and a spam filtering service together.

### Spam Settings

**Access spam protection** contains separate **Manage spam rules** and **Manage CAPTCHAs and spam filtering services** permissions. Authorised users can edit these settings directly, including on production with admin changes disabled. Form editors can use configured providers without permission to change their credentials. Settings permissions do not grant spam-rule or provider management.

Spam rules and provider settings are stored in the current installation’s database. They are shared by the sites within a Craft multisite installation. Saving a provider changes only that provider; saving spam rules does not change provider credentials. Project config does not include or apply these settings.

### Setting Up Production

Open **Formie → Spam Protection** on production, configure and enable the providers you need, and save. Then enable each provider on the required forms. Configuring Turnstile on your development installation does not configure it on production. A fresh installation starts with the default spam rules and disabled providers.

Credential fields accept environment-variable references where indicated. For example, enter `$TURNSTILE_SECRET_KEY` and supply its value on that installation. References remain editable; resolved secrets are not displayed. Literal saved credentials are masked: leave the dots to keep a key, replace them to change it, or empty the field to remove it. Disable a provider before clearing credentials it requires.

Copying a database also copies spam rules and provider settings. Check the credentials and submit a test form after importing a database. Form exports carry the form’s provider selections, so configure the corresponding providers on the destination installation too.

### Captcha Provider Settings

Configure and save each provider independently under **CAPTCHAs** or **Spam Filtering Services**, then enable it on the required forms. See [Captcha Provider Settings](/integrations/overview#captcha-provider-settings) and the provider’s setup guide for credential requirements. The form editor warns when a saved provider selection is disabled, missing or lacks required configuration. A saved key does not establish that the provider accepts it; submit a test form to check the connection.

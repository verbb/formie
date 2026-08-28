# Major upgrade — real-project checklist

Major Formie upgrades touch templates, custom fields, integrations, front-end JavaScript, spam settings, and status APIs. This checklist distils [Upgrading From v3](/get-started/upgrading-from-v3) into a practical project runbook — for upgrading an older Formie codebase.

## Prerequisites

- Staging environment mirroring production
- [Upgrading From v3](/get-started/upgrading-from-v3) open for detailed breaking-change reference
- Database and files backup

Leave `compatibilityMode` **enabled** until deprecation logs are clear:

```php [config/formie.php]
return [
    'compatibilityMode' => true,
];
```

## Phase 1 — Pre-upgrade audit

- List custom Formie **fields**, **integrations**, **modules** listening to Formie events
- Grep for deprecated APIs: `getStatuses()`, `renderFormAssets`, `onAfterFormieSubmit`, `defineGeneralSchema`, `getValueAsJson`
- Inventory Twig templates using `craft.formie.*` and `form.getFormId()`
- Note static cache / Blitz setup and any custom CSRF refresh snippets
- Export critical forms JSON (**Settings → Import/Export**) as rollback snapshots
- Review `translations/*/formie.php` for field labels that should move to CP site overrides

## Phase 2 — Install and migrate

- Update Formie via Composer on staging
- Run pending Craft migrations / `project-config/sync`
- Confirm `compatibilityMode` is `true` in config
- Open **Formie → Settings → Spam Protection** — verify guards and captcha providers seeded from legacy settings
- Remove obsolete config keys: `enableGatsbyCompatibility`, `submissionStateMode`, `submissionStore`
- Clear deprecation log baseline: `./craft clear-deprecations`

## Phase 3 — Control panel verification

- Open each form in the builder — schema loads without JS errors
- Verify **Submission Statuses** and **Form Statuses** in the control panel
- Test notification preview and test send
- Reconnect OAuth integrations (Stripe, Mailchimp, and so on)
- Confirm form groups, stencils, and reports if used
- Verify submission export via **Formie → Reports** (the **Export** button no longer appears on the submissions index — see [Reports and submission export](/get-started/upgrading-from-v3#reports-and-submission-export))
- Grant **Access reports** and **Export submissions** (or **Manage reports**) to user groups that exported from the submissions index in Formie 3

## Phase 4 — Template and front-end

Run the full [template compatibility audit](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade). Minimum checks:

- `craft.formie.renderFormAssets()` → `craft.formie.formAssets()`
- `renderCss` / `renderJs` options → `includeCss` / `includeJs`
- `form.getFormId()` → `form.getRenderId()`
- `submission.getValueAsString()` → `submission.getFieldValueAsString()`
- Front-end event listeners updated (`formie:submit:result`, `formie:validator:ready`)
- Remove custom static-cache token refresh JS if `staticCacheRefreshOnLoad` handles it
- GraphQL: `formieHtmlForm` instead of `templateHtml`; `enableClientEvents` instead of `enableJsEvents`

## Phase 5 — Custom PHP

- Field schema methods renamed to `defineFormBuilder*Schema()`
- Integration settings use `defineFormSettingsSchema()` not Twig HTML
- Captchas/address providers use `getClientModule()` not `getFrontEndJsVariables()`
- Notification events on `Notifications` service; integration events on `Integrations`
- Dispatch via `IntegrationTriggers` / `Notifications` services, not `Submissions` helpers
- Submission status classes: `SubmissionStatuses`, `SubmissionStatus`
- Theme config: `defineFieldSlotTag()` / `SlotTag` instead of `defineHtmlTag()` / `HtmlTag`

## Phase 6 — Spam and screening

- Legacy Honeypot/Javascript/Duplicate **captcha integrations** removed — replaced by **submission guards**
- Spam keywords and captcha secrets in **control panel settings**, not old `project.yaml` plugin keys
- Review [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail) if rules were complex
- Test submit with honeypot filled (should mark spam) and normal submit (should succeed)

## Phase 7 — Translations

- Remove form builder content from `formie.php` (field labels, messages)
- Update validation string keys: `{attribute}` → `{label}`
- Update text-limit counter plural strings (see upgrading doc)
- Multi-site: French copy in CP site overrides, not locale files

## Phase 8 — Functional testing

- Single-page form — page reload submit
- Multi-page form — Ajax navigation, back button, incomplete save
- Save and continue later — resume link works after 24h if within TTL
- File upload field
- Payment form (if applicable) — including 3DS retry
- Email notifications — inbox placement, PDF attachment
- Integrations — CRM/marketing trigger on submit
- GraphQL/headless submit if used
- Static cached page submit
- Client events / GTM payload on success

## Phase 9 — Production cutover

- Deploy during low traffic
- `project-config/sync` on production
- Re-enter production integration secrets and captcha keys (if DB not copied from staging)
- Monitor Craft queue for notification/integration failures
- Monitor `storage/logs/web.log` for Formie deprecations

## Phase 10 — Disable compatibility mode

When staging shows **zero Formie deprecation warnings** after realistic traffic:

```php
return [
    'compatibilityMode' => false,
];
```

- Re-run full test suite
- Deploy config change
- Watch logs for 48 hours

## Quick replacement reference

| Old | New |
| --- | --- |
| `craft.formie.renderFormAssets(form)` | `craft.formie.formAssets(form)` |
| `form.getFormId()` | `form.getRenderId()` |
| `submission.getValueAsJson()` | `submission.getFieldValueAsArray()` |
| `onAfterFormieSubmit` | `formie:submit:result` |
| `getStatuses()` | `getSubmissionStatuses()` |
| `defineGeneralSchema()` | `defineFormBuilderGeneralSchema()` |
| Honeypot captcha integration | Honeypot submission guard |
| Submissions index **Export** | **Formie → Reports** → **Export** |
| `enableJsEvents` | `enableClientEvents` |

Full table in [Upgrading From v3](/get-started/upgrading-from-v3#replacement-reference).

## Related

- [Upgrading From v3](/get-started/upgrading-from-v3)
- [Template compatibility audit after upgrade](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade)
- [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)
- [Cached forms in production](/guides/frontend-headless/cached-forms-in-production)

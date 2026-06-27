# Spam keywords in detail

Spam keywords are Formie's built-in content screening tool — match words, phrases, boolean logic, or IP addresses against a submission and mark it as spam during the **`screen`** workflow stage. This guide covers full syntax, real-world rule sets, and how keywords fit alongside email rules, text rules, guards, and captchas.

## Prerequisites

- [Spam Protection](/forms/spam-protection)
- [Submission Screening](/forms/submission-screening) — where `screen.runSpamChecks` runs

Configure keywords under **Formie → Settings → Spam Protection → Content Rules → Spam Keywords**. Values live in Formie's [control panel settings store](/guides/configuration/project-config-environment-and-control-panel-settings) — not in `config/formie.php` by default.

## How matching works

1. A visitor submits a form
2. Field validation passes
3. During **`screen.runSpamChecks`**, `SpamHelper` evaluates keyword and IP rules against submission content
4. On match, the submission is marked spam with a reason; behaviour follows **Spam submission behavior** (show success or show message)

Keywords check the **whole submission** — all field values combined — not individual fields in isolation.

Evaluation order within spam checks (simplified): email rules → text rules → spam keywords → captcha/scoring integrations. See [Submission Screening](/forms/submission-screening) for the full pipeline.

## Keyword definition syntax

One rule per line. Lines starting with `#` are comments.

### Simple word match

```text
# Flags content containing the word "spam". Does not match "spamming" or "Spam".
[match: spam]
```

Matching is case-sensitive for the term as written. Use separate lines for case variants if needed.

### Exact phrase

```text
# Flags content containing the exact phrase "cheap ham"
[match: cheap ham]
```

### Boolean AND

```text
# Flags content only if both "spam" and "bulk" are present
[match: spam AND bulk]
```

### Boolean OR

```text
# Flags content if either "spam" or "phishing" is present
[match: spam OR phishing]
```

### Grouped logic

```text
# Flags content if it contains either "spam" or "junk" along with "email"
[match: (spam OR junk) AND email]
```

Use parentheses to group when mixing AND and OR.

### NOT

```text
# Flags content if it does not contain "client"
[match: NOT client]
```

Useful for allowlist-style exceptions combined with other rules on separate lines (each line is evaluated).

## IP address rules

Match the submitter's IP — supports singular addresses, lists, ranges, and CIDR notation.

```text
# Single IP
[ip: 192.168.0.1]

# Multiple IPs
[ip: 192.168.0.1, 192.168.0.2, 192.168.0.3]

# Range
[ip: 192.168.0.1-192.168.0.255]

# CIDR
[ip: 192.168.0.0/24]
```

IP rules are evaluated alongside `[match:]` rules during `screen.runSpamChecks`.

## Referencing external content

Keywords are stored in control panel settings, which are awkward to edit on production when project-scoped. Reference a Global Set field instead:

```text
{forms.spamKeywords}
```

If you have a Global Set `Forms` with field handle `spamKeywords`, Formie pulls keyword lines from that field — content editors update rules without deploys.

Field/global references work in keyword lines the same way as other Formie reference tokens.

## Real-world rule sets

### Obvious junk phrases

```text
# Common SEO spam
[match: buy followers]
[match: (viagra OR cialis) AND cheap]
[match: crypto AND guaranteed returns]

# Phishing patterns
[match: verify your account AND click]
[match: suspended AND immediately]
```

### Competitor / abuse blocklist

```text
# Block specific domains mentioned in message fields
[match: competitor-domain.com]
[match: spammer-network.net]
```

Combine with [Email Rules](/forms/spam-protection#email-rules) blocked domains for defence in depth.

### Internal test exclusions

```text
# Flag everything EXCEPT submissions mentioning our client code
[match: NOT ACME-2026]
```

Use carefully — broad NOT rules can have unexpected matches. Test with saved spam review enabled.

### Office IP allowlist complement

Keywords do not allowlist IPs — they only flag. For office IPs that should never be blocked by keywords, rely on IP rules only targeting bad actors, not inverted NOT patterns.

Pair IP **block** rules with throttling instead:

```text
# Known bad range from repeat attacks
[ip: 203.0.113.0/24]
```

### Editor-managed list via Global Set

In the Global Set rich text or plain text field:

```text
[match: word1]
[match: word2 AND word3]
```

In **Spam Keywords** setting:

```text
{siteSettings.spamKeywordList}
```

Editors update the Global Set; Formie reads it at screening time.

## Spam behaviour settings

Keywords mark submissions as spam — they do not delete them. Related plugin settings:

| Setting | Effect |
| --- | --- |
| `saveSpam` | Store spam submissions for review |
| `spamLimit` | Cap stored spam count |
| `spamBehaviour` | `showSuccess` (default) or `showMessage` |
| `spamBehaviourMessage` | Custom message when showing rejection |
| `spamEmailNotifications` | Whether spam still triggers email |

Showing success to spammers avoids teaching bots which rules fired.

## What keywords do not replace

| Layer | Use for |
| --- | --- |
| [Email rules](/forms/spam-protection#email-rules) | Blocked/allowed domains globally |
| [Text rules](/forms/spam-protection#text-rules) | Keyboard spam, link count limits |
| [Submission guards](/forms/spam-protection#submission-guards) | Honeypot, timing, replay |
| [Submission throttling](/forms/spam-protection#submission-throttling) | Flood protection |
| [Captcha integrations](/integrations/captchas/) | Strong bot challenges |
| [Submission limits](/forms/submission-limits) | Business quotas (not spam) |

Keywords excel at recurring phrases and known bad IPs — not at behavioural bot detection alone.

## Headless and GraphQL

Keyword screening runs server-side for all submit paths — browser, client REST, and GraphQL. No front-end configuration required.

Browser-only guards (honeypot, minimum submit time) do **not** run on GraphQL; keywords and captchas still do.

## Troubleshooting

**False positives**

- Enable `saveSpam` and review flagged submissions
- Narrow rules — replace broad `[match: http]` with `[match: (viagra OR casino)]`
- Add allowed terms via Text Rules **Allowed terms** for product codes caught by suspicious text detection

**Rule not firing**

- Confirm the keyword setting is saved on the correct site scope (multi-site)
- Check Global Set reference resolves on production
- Verify screening stage is not skipped by an earlier workflow customization

**Keywords lost after deploy**

- Confirm keywords are saved under **Settings → Spam Protection**, not in project config YAML
- See [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)

## Related

- [Spam Protection](/forms/spam-protection)
- [Submission Screening](/forms/submission-screening)
- [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)
- [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice)

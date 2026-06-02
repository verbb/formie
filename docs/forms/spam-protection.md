# Spam Protection

Spam protection is about reducing junk submissions without making the form harder for real people to use.

Formie gives you a few layers to work with, so you can start with lighter filtering and add stronger checks only where they are needed. Most of the control lives in `Formie → Settings → Spam`.

Captcha failures, keyword matches, and related signals are evaluated together in the submission workflow’s screening stage. For how that stage is ordered, how it relates to validation, and how to extend it, see [Submission screening](/forms/submission-screening).

## Handling spam submissions

At the plugin level, you can choose whether spam submissions should still be saved. Saving them is useful when you need to review false positives, understand what kind of spam is hitting the form, or debug a captcha or filtering rule.

If you do save spam, you can also set a limit for how many spam submissions Formie should keep before older ones are pruned. That helps keep the database under control without losing all visibility into what is being blocked.

You can also decide how Formie should respond when a submission is flagged as spam. In many cases, it is better to behave as though the submission was accepted, rather than giving a clear rejection message that helps bots learn what is being blocked. If you prefer, you can instead show an error message.

If you use email notifications, there is also a plugin setting for whether spam submissions should still trigger them.

## Spam keywords

Spam keywords are the simplest built-in screening tool. Formie checks the whole submission, and if it matches your keyword definition, the submission will be marked as spam.

You can match against:

- words or phrases
- combinations such as `AND`, `OR`, and `NOT`
- IP addresses or IP ranges

This makes spam keywords useful for obvious repeat attacks or recurring junk content.

### Keyword definition

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

### IP addresses

```text
# Flags content if the sender's IP matches. Supports singular, multiple, ranges and CIDR notation.
[ip: 192.168.0.1, 192.168.0.2, 192.168.0.3]
[ip: 10.0.0.1]
[ip: 192.168.0.1-192.168.0.255]
[ip: 192.168.0.0/24]
```

### Referencing other content

Spam keywords are stored in project config, which means they are not always convenient to edit directly on staging or production. If you want content admins to manage them, or you need them to vary by environment, you can reference another field instead.

This is commonly done with a Global Set. For example, if you had a Global Set called `Forms` and a field called `Spam Keywords`, you could reference it in the Formie spam keywords setting with `{forms.spamKeywords}`.

## Captchas

Formie also supports captcha integrations when you need a stronger challenge layer.

Use those when keyword matching and basic screening are not enough, or when the form is a common attack target.

See [Captchas](/integrations/captchas/) for the available providers.

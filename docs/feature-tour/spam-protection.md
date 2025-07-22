# Spam Protection
Protecting your forms from spam submissions, bot attacks and other nefarious parties is vitally important. Spam can range from annoying inconveniences to potential security threats, so it's something Formie takes seriously.

Formie provides a collection of settings via Formie → Settings → Spam to combat spam.

## Save Spam Submissions
This setting controls whether to save spam submissions, so they can be viewed in the control panel. Otherwise, spam submissions will be discarded. Enabling this can be useful for debugging potential issues with legitimate submissions being marked incorrectly as spam.

## Spam Submission Behavior
When a submission is marked as spam, you can select what behaviour to perform for users. It's highly recommended to act as if the submission was successful to prevent parties from learning how to get around the spam protection. However, you can also show an error message.

## Spam Keywords
You can also flag submissions as spam in a more manual way, using keywords. Formie will look at the entire submission, and if it matches the spam keyword definition, the submission will be marked as spam.

### Keyword Definition
We have a specific syntax for defining your spam keywords to give you flexibility in how Formie matches content.

```
# Flags content containing the word "spam". This will **not** match "spamming" (whole-words) or "Spam" (case sensitive).
[match: spam]

# Flags content containing the exact phrase "cheap ham".
[match: cheap ham]

# Flags content only if both "spam" and "bulk" are present.
[match: spam AND bulk]

# Flags content if either "spam" or "phishing" is present.
[match: spam OR phishing]

# Flags content if it contains either "spam" or "junk" along with "email".
[match: (spam OR junk) AND email]

# Flags content if it doesn't contain "client".
[match: NOT client]
```

You can simply define your keywords each on a new line or include logic operators like "AND", "OR" or "NOT", including parenthesis to group logic as required.

### IP Address
```
# Flags content if the sender's IP matches. Supports singular, multiple, ranges and CIDR notation.
[ip: 192.168.0.1, 192.168.0.2, 192.168.0.3]
[ip: 10.0.0.1]
[ip: 192.168.0.1-192.168.0.255]
[ip: 192.168.0.0/24]
```

### Referencing Fields
One limitation of the “Spam Keywords” plugin setting, is that it's stored in Project Config. As such, it cannot typically be modified on staging or production environments. This poses an issue where if you find your site under attack and want to implement keywords, you need to have your developer add keywords. That's not always a viable option.

Instead, you can reference other fields where you can manage content on a per-environment basis. This is common to do with **Global Sets**.

For example, you might have a Global Set called “Forms” and a field called “Spam Keywords”. In the Formie Spam Keywords setting, you could reference that Global Set field with `{forms.spamKeywords}`.

## Captchas
Formie also provides integrations for blocking spam, in the form of [Captchas](docs:integrations/captchas).

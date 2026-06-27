# How to keep Email Notifications out of your junk emails

Spam filters are good at separating legitimate mail from junk — but Formie notification settings can still work against you if they look like spoofed or misconfigured messages. These tips help your Formie emails arrive in the inbox.

## Prerequisites

- [Email Notifications](/forms/email-notifications) configured on at least one form
- A working Craft mail transport (not PHP `mail()` on a shared host)

## Use a third-party mailer

Sendmail on a server rarely cuts it. Even a well-tuned server setup is usually less reliable than a provider dedicated to delivery.

Use Mailgun, SendGrid, Amazon SES, Postmark, or any transport Craft supports. Do not compromise on delivery for notification mail.

## Do not use the submitter's email as the "From" address

A common mistake is putting the user-provided email in **From** so admins can hit Reply. That hurts deliverability badly.

Receiving servers compare the sending IP and the domain in **From**. If they do not match — for example, mail sent from your server claiming to be `@gmail.com` — the message looks suspicious. Some providers block it outright.

Use a consistent **From** address you control, such as `notifications@yourdomain.com`. Put the submitter's address in **Reply-To** instead for the same reply behaviour.

## Do not duplicate addresses across From, To, CC, and BCC

Some providers flag messages when the same address appears in multiple recipient fields, or when **From** matches **To**.

Do not CC an address that is already in **To**.

## Set up SPF, DKIM, and DMARC

Domain-level authentication has a real impact on inbox placement:

- [Sender Policy Framework (SPF)](https://www.cloudflare.com/learning/dns/dns-records/dns-spf-record/)
- [DomainKeys Identified Mail (DKIM)](https://www.cloudflare.com/learning/dns/dns-records/dns-dkim-record/)
- [Domain-based Message Authentication, Reporting and Conformance (DMARC)](https://www.cloudflare.com/learning/dns/dns-records/dns-dmarc-record/)

Configure these for the domain you send from.

## Only send from an address you are allowed to use

Your **From** domain must match what your mail provider authorises. You cannot reliably send as `@example.com` unless you control that domain's DNS and provider settings.

## Related

- [Email Notifications](/forms/email-notifications)

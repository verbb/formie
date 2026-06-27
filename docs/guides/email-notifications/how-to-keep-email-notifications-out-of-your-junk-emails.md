# How to keep Email Notifications out of your junk emails

We've all come to accept spam emails as an unavoidable part of modern life — or at least when it comes to emails. Fortunately, most providers out there like Google, Office 365, Apple and Yahoo (no judgement) are pretty excellent at detecting what's a legitimate email and not. This comes down to a whole range of factors, from content of the email, the headers, the originating server, and lots, lots more.

Here's some quick and useful tips you can employ with Formie to ensure that your email notifications don't end up in junk.

### Use a third-party mailer
Simply put, using Sendmail on a server just isn't going to cut it. Sure, you (or your host) can configure it to the nth degree, but in our experience, it's nowhere near as reliable at a third-party provider that's dedicated to mail delivery. Don't compromise when it comes to email delivery! 💪

We'd recommend [Mailgun](https://www.mailgun.com/), [Sendgrid](https://sendgrid.com/), [Amazon SES](https://aws.amazon.com/ses/), or [Postmark](https://postmarkapp.com/) for emails — but anything that can be used with Craft will do. 

### Don't use the users' email as the "from"
One of the most common issues is using the user-provided email address as the "from" email. While this might seem useful so that when an email is delivered, you can hit reply to respond to the user who filled out the form, it's incredibly unreliable for mail delivery.

What happens is an email is delivered from your server (or third-party provider) and arrives at a mailbox. Spam checks will commonly look at the IP address of the origin server where it was sent from, and the TLD of the email address that sent it. If they don't match, then it doesn't look good.

For example, I might send an email from a web server, saying it's from `formie@gmail.com`. But when delivered, it'll see that the sending server wasn't Gmail's servers. What's more is that some providers won't let the email be sent in the first place, without a relay set up. Otherwise, anyone could send an email "from" any email they don't even have access to, and chaos would ensue.

For this reason, it's a good idea to use a standard email as the "from" — like `admin@my-site.com` or `noreply@my-site.com`. You can add the users' provided email to the "reply to" field instead, to get the same reply behaviour.

### Don't duplicate to/from/cc
We've noticed some providers don't like it if you use the same email in the "from", "to", "cc" and "bcc" fields, or if you have the same email twice in any of these fields.

Simply put — don't do this! There's no need to cc an email that's already set "to" you.

### Ensure your domain has SPF, DKIM and DMARC setup
These domain-level policies can have a great effect on deliverability. Have a read through the below articles on how to proceed with configuration.

- [Sender Policy Framework (SPF)](https://support.google.com/a/answer/33786)
- [DomainKeys Identified Mail (DKIM)](https://support.google.com/a/answer/174124)
- [Domain-based Message Authentication Reporting and Conformance (DMARC)](https://support.google.com/a/answer/2466580)

### Only send from an email you allow
Tied in with the third-party provider that you pick, and selecting an appropriate "from" email — make sure you have authority to send emails using the email you pick. It's no good you picking the "from" email to be `steve@apple.com` because you certainly won't have the authority to send on behalf of that email.

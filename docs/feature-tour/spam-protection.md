# Spam Protection
Protecting your forms from spam submissions, bot attacks and other nefarious parties is vitally important. Spam can range from annoying inconveniences to potential security threats, so it's something Formie takes seriously.

Formie provides a collection of settings via Formie → Settings → Spam to combat spam.

#### Save Spam Submissions
This setting controls whether to save spam submissions, so they can be viewed in the control panel. Otherwise, spam submissions will be discarded. Enabling this can be useful for debugging potential issues with legitimate submissions being marked incorrectly as spam.

#### Spam Submission Behavior
When a submission is marked as spam, you can select what behaviour to perform for users. It's highly recommended to act as if the submission was succeessful to prevent parties from learning how to get around the spam protection. However you can also show an error message.

#### Spam Keywords
If a submission contains any of these words (in any field), it will be marked as spam. This field supports multiple words or phrases, along with IP addresses. It will also match words within other words (eg. 'craft' will match 'crafty') and is case insensitive.

## Captchas
Formie also provides 4 integrations for blocking spam, in the form of [Captchas]().

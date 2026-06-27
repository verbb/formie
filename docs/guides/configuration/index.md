# Configuration & Deployment

Project config, environment scope, control panel settings, and deployment behaviour.


##### [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)

Formie spreads settings across project config, `config/formie.php`, and control panel database stores. This guide walks through a staging-to-production deploy so you know what syncs, what stays local, and where to change captchas, spam rules, integrations, and form structure without surprises.

##### [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail)

Spam keywords are Formie's built-in content screening tool — match words, phrases, boolean logic, or IP addresses against a submission and mark it as spam during the **`screen`** workflow stage. This guide covers full syntax, real-world rule sets, and how keywords fit alongside email rules, text rules, guards, and captchas.

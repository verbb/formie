# Creating OAuth integrations with Formie

OAuth is awkward to implement yourself — token storage, refresh flows, provider quirks, and scope handling add up quickly. Formie uses [Verbb Auth](https://github.com/verbb/auth) under the hood, so your integration can focus on Formie-specific settings and API calls while Formie manages connection, tokens, and authorized requests.

This guide walks through registering a custom OAuth integration in a Craft module, connecting it in the control panel, and calling the provider API from your integration code — or from anywhere else in your project once connected.

Read the [OAuth Integration](/developers/custom-integration/oauth-integration) reference first if you have not built an OAuth integration before.

## When to build vs reuse

Before writing a custom class, check whether Formie already ships your provider. Formie includes OAuth connections for Campaign Monitor, Mailchimp, Google Sheets, HubSpot, Slack, Salesforce, and many others.

If your provider is already supported, fetch the integration instance and call `$integration->request()` directly — no custom module required. See [Using Guzzle clients from Formie integrations in your own code](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code).

When Formie does not support your provider, register a custom integration class. The example below connects to a fictional webinar API — adapt authorization URLs, scopes, token handling, and API base URLs to your provider's documentation.

## Create your module

First, you need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html).

When creating your module, set the namespace to `modules\gotowebinar` and the module ID to `go-to-webinar`. OAuth integrations need two PHP classes — the Formie integration and a Verbb Auth provider — plus a plugin settings template:

```treeview
my-project/
├── modules/
│   └── gotowebinar/
│       └── src/
│           ├── integrations/
│           │   └── GoToWebinar.php
│           ├── providers/
│           │   └── GoToWebinar.php
│           ├── templates/
│           │   └── _plugin-settings.html
│           └── GoToWebinar.php
└── ...
```

Register the module in your project config, then register the integration with Formie:

```php [modules/gotowebinar/src/GoToWebinar.php]
<?php
namespace modules\gotowebinar;

use modules\gotowebinar\integrations\GoToWebinar as GoToWebinarIntegration;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\base\Module;

class GoToWebinar extends Module
{
    public function init(): void
    {
        parent::init();

        Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
            $event->miscellaneous[] = GoToWebinarIntegration::class;
        });
    }
}
```

Push your integration class onto the category that best fits your provider — `$event->crm[]`, `$event->emailMarketing[]`, `$event->miscellaneous[]`, and so on.

## The integration class

Create `modules/gotowebinar/src/integrations/GoToWebinar.php`. OAuth integrations implement `OAuthProviderInterface` and tell Formie which Verbb Auth provider class handles token exchange.

Here is the full integration class. We walk through the OAuth-specific methods below.

```php [modules/gotowebinar/src/integrations/GoToWebinar.php]
<?php
namespace modules\gotowebinar\integrations;

use Craft;
use modules\gotowebinar\providers\GoToWebinar as GoToWebinarProvider;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationFormSettings;

class GoToWebinar extends Miscellaneous implements OAuthProviderInterface
{
    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return GoToWebinarProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'GoToWebinar');
    }

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $accountKey = null;
    public ?string $organizerKey = null;

    public function getDescription(): string
    {
        return Craft::t('formie', 'Connect to GoToWebinar via OAuth.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['clientId', 'clientSecret'], 'required'];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('go-to-webinar/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = ['collaboration:webinar:read', 'collaboration:webinar:write'];

        return $options;
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['apiDomain'] = 'https://api.getgo.com';

        return $config;
    }

    public function afterFetchAccessToken(Token $token): void
    {
        // Some providers return extra values only available after authorization.
        $this->accountKey = $token->getValues()['account_key'] ?? '';
        $this->organizerKey = $token->getValues()['organizer_key'] ?? '';
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        return 'https://api.getgo.com/G2W/rest/v2/';
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        return new IntegrationFormSettings();
    }

    public function sendPayload(Submission $submission): bool
    {
        return true;
    }
}
```

If your integration also maps submission data to the provider, implement `fetchFormSettings()` and `sendPayload()` the same way you would for a non-OAuth integration — see [Building a CRM integration from scratch](/guides/integrations/building-a-crm-integration-from-scratch).

### OAuth hooks

These methods connect Formie to your provider's OAuth flow:

| Method | Purpose |
| --- | --- |
| `supportsOAuthConnection()` | Return `true` so the control panel shows **Connect** |
| `getOAuthProviderClass()` | Verbb Auth provider class that exchanges codes for tokens |
| `getAuthorizationUrlOptions()` | Scopes and other authorize URL parameters |
| `getOAuthProviderConfig()` | Extra provider configuration (API domain, endpoints) |
| `afterFetchAccessToken()` | Persist provider-specific values from the token response |
| `getBaseApiUrl()` | API base URL for `$this->request()` after connection |

Once connected, use `$this->request('GET', 'accounts')` for API calls. Formie routes requests through the stored token and refreshes when needed.

### Plugin settings template

Editors need your OAuth app's **Client ID** and **Client Secret**, plus the **Redirect URI** to register in the provider's developer console.

```twig [modules/gotowebinar/src/templates/_plugin-settings.html]
{% import '_includes/forms' as forms %}
{% import 'verbb-base/_macros' as macros %}

{{ forms.textField({
    readonly: true,
    label: 'Redirect URI' | t('formie'),
    instructions: 'Use this URI when setting up your {name} app.' | t('formie', { name: integration.displayName() }),
    value: integration.getRedirectUri(),
}) }}

{{ forms.autosuggestField({
    label: 'Client ID' | t('formie'),
    name: 'clientId',
    required: true,
    suggestEnvVars: true,
    value: integration.settings.clientId ?? '',
    warning: macros.configWarning('clientId', 'formie'),
    errors: integration.getErrors('clientId'),
}) }}

{{ forms.autosuggestField({
    label: 'Client Secret' | t('formie'),
    name: 'clientSecret',
    required: true,
    suggestEnvVars: true,
    value: integration.settings.clientSecret ?? '',
    warning: macros.configWarning('clientSecret', 'formie'),
    errors: integration.getErrors('clientSecret'),
}) }}
```

Store `clientId` and `clientSecret` in `.env` variables on production. Formie resolves env syntax when settings are loaded.

The redirect URI shown in settings must match your provider app configuration exactly. Override globally with the `redirectUri` config setting if needed — see [Configuration](/get-started/configuration).

### Custom OAuth provider

Most providers work with Verbb Auth's built-in provider classes. When a provider needs non-standard token requests — for example, Basic auth on the token endpoint — extend `GenericProvider`:

```php [modules/gotowebinar/src/providers/GoToWebinar.php]
<?php
namespace modules\gotowebinar\providers;

use League\OAuth2\Client\Provider\GenericProvider;

class GoToWebinar extends GenericProvider
{
    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ];
    }
}
```

Browse Formie's built-in integrations for patterns before writing a custom provider — many providers share similar token endpoint behaviour.

## Connect in the control panel

With the module in place:

1. Go to **Formie → Integrations → {Your category}** and create a new integration instance.
2. Select your custom class. Enter **Client ID** and **Client Secret**, then save.
3. Click **Connect**, authorize with the provider in the popup, and confirm Formie stores a token.

If connection fails, check the redirect URI, scopes, and that your provider app is in the correct mode (sandbox vs production).

## Using the authorized client

After connecting, fetch the integration anywhere in your project and call the API:

```php
use verbb\formie\Formie;

$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle('goToWebinar');
$accountKey = $integration->accountKey;

$webinars = $integration->request('GET', "accounts/{$accountKey}/webinars", [
    'query' => [
        'fromTime' => '2026-01-01T00:00:00Z',
        'toTime' => '2026-12-31T23:59:59Z',
    ],
]);
```

`$integration->request()` decodes JSON responses. For full control over the Guzzle client, use `$integration->getClient()` instead.

Ensure your code runs after plugins load — see the [Guzzle clients guide](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code) for the `Plugins::EVENT_AFTER_LOAD_PLUGINS` pattern.

You do not need to use Formie forms to benefit from this — any Craft module or console command can reuse a connected integration's client.

## Finishing up

Every provider differs in scopes, token shape, and API base URLs. A practical path:

1. Find the closest built-in Formie integration and read its OAuth hooks.
2. Implement `OAuthProviderInterface` with your provider's URLs and scopes.
3. Connect in the control panel and verify `$this->request()` works.
4. Add `fetchFormSettings()` and `sendPayload()` if the integration should run on form submit.

## Related

- [OAuth Integration](/developers/custom-integration/oauth-integration)
- [Using Guzzle clients from Formie integrations in your own code](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code)
- [Building a CRM integration from scratch](/guides/integrations/building-a-crm-integration-from-scratch)
- [Configuration](/get-started/configuration)

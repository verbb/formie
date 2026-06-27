# Using Guzzle clients from Formie integrations in your own code

Formie supports more than 50 integrations — Mailchimp, HubSpot, Salesforce, Google Sheets, and many others. Each one configures a Guzzle client with the correct authentication, base URI, and error handling. You can reuse that work in your own PHP or Twig code without reimplementing OAuth or API credentials.

## Prerequisites

- At least one Formie integration configured and connected in the control panel
- For module usage, a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html)

## Usage in PHP

Fetch an integration by handle and call its API through Formie’s request helper.

```php
use craft\services\Plugins;
use verbb\formie\Formie;
use yii\base\Event;

public function init(): void
{
    parent::init();

    Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function(Event $event) {
        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle('mailchimp');

        if (!$integration) {
            return;
        }

        $json = $integration->request('GET', 'lists');
    });
}
```

Run this inside `Plugins::EVENT_AFTER_LOAD_PLUGINS` so Formie and Craft are fully bootstrapped. Calling integrations earlier can fail if the plugin has not loaded.

`$integration->request()` is a JSON helper. It is equivalent to:

```php
use craft\helpers\Json;

$client = $integration->getClient();
$response = $client->request('GET', 'lists');
$json = Json::decode((string)$response->getBody());
```

Use `getClient()` when you need the raw Guzzle client — non-JSON payloads, custom headers, or direct access to the response object.

### Practical example

Suppose you want a checkbox on a user profile page to subscribe someone to a mailing list. Rather than building a separate Mailchimp connection, reuse Formie’s:

```php
$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle('mailchimp');

$integration->request('POST', 'lists/abc123/members', [
    'json' => [
        'email_address' => $user->email,
        'status' => 'subscribed',
    ],
]);
```

The integration handle matches the handle shown in **Formie → Integrations**.

## Usage in Twig

You can do the same from Twig:

```twig
{% set integration = craft.formie.getPlugin().getIntegrations.getIntegrationByHandle('mailchimp') %}
{% set lists = integration.request('GET', 'lists') %}

{% for list in lists.lists %}
    {{ list.id }}: {{ list.name }}<br>
{% endfor %}
```

Subscribe a user directly:

```twig
{% set integration = craft.formie.getPlugin().getIntegrations.getIntegrationByHandle('mailchimp') %}

{% set response = integration.request('POST', 'lists/1234/members', {
    json: {
        email_address: 'user@example.com',
        status: 'subscribed',
    },
}) %}
```

Use Twig integration calls carefully on cached pages — they run at render time, not on every cached page load.

## OAuth and special handling

Creating a Guzzle client with Craft’s `Craft::createGuzzleClient()` is straightforward. OAuth, token refresh, and provider-specific auth headers are not.

When a provider requires OAuth, let Formie manage the connection — see [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie). Formie stores tokens, refreshes them, and returns a ready-to-use client through `getClient()` and `request()`.

## Closing thoughts

Formie integrations are not limited to form submissions. Anywhere you need an authenticated API client for a provider Formie already supports, fetch the integration instance and call `request()`. You get consistent credentials, env variable support, and OAuth handling without maintaining a parallel integration layer.

## Related

- [Automation Integration](/developers/custom-integration/automation-integration)
- [OAuth Integration](/developers/custom-integration/oauth-integration)
- [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie)

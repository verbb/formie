# Using Guzzle clients from Formie integrations in your own code

Formie supports over **50** different types of integrations, from **Mailchimp**, **Zoho**, **Salesforce** and more. But did you know you can use these APIs in your project without having to create your own connection to their APIs?

### Using Guzzle clients
Each integration creates a [Guzzle](https://docs.guzzlephp.org/en/stable/) client to perform all the API requests to fetch, create or update content. This is usually to push content from your Formie form submissions into these providers as newsletter subscribers, CRM leads, rows in a Google Sheet, and lots more. Which is all well and good for form submissions, but what if you wanted to do _more_?

Let's say you want to add a checkbox to your users' profile page, whether they want to subscribe to a mailing list you provide. Sure, you could make that a Formie form, but that's probably overkill. You could implement your own logic to add or remove the user from the chosen list, all while letting Formie handle the API side of things.

#### Usage in PHP
Let's look at an example using an integration in PHP via a module. First, you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP, and added to our custom module. 

Place the below in your module's `init()` method.

```php
use craft\services\Plugins;
use verbb\formie\Formie;
use yii\base\Event;

public function init(): void
{
    // ...

    // Ensure we only run this when Craft is ready, and plugins are loaded
    Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function(Event $event) {
        // Fetch the Formie integration with the handle `mailchimp`
        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle('mailchimp');

        // Fetch all the lists from Mailchimp (which uses the `getClient()` defined in the integration class)
        $json = $integration->request('GET', 'lists');
    });

    // ...
}
```

This code illustrates us fetching an integration by its handle, and calling `getClient()` to retrieve the Guzzle client. We can then do anything that the Mailchimp API allows (in this case, we're fetching all the lists on the account.

It's really important to only run our code inside the `Plugins::EVENT_AFTER_LOAD_PLUGINS` event, otherwise, we risk this code running before the Formie plugin (or Craft itself) has loaded.

We also use [`$integration->request()`](https://github.com/verbb/formie/blob/40a98cceacaa2c648c4e9ba88e148b36ae75eb8a/src/base/Integration.php#L491) from Formie as a bit of a helper function to just handle JSON APIs, but it's totally equivalent to:

```php
use craft\helpers\Json;

$client = $integration->getClient();
$response = $client->request('GET', 'lists');
$json = Json::decode((string)$response->getBody());
```

Which can be useful if you want access to the Guzzle client itself, if the API doesn't support JSON payloads, or if you want full control over the request and response.

#### Usage in Twig
You can do a similar thing in Twig, even a little briefer.

```twig
{% set integration = craft.formie.getPlugin().getIntegrations.getIntegrationByHandle('mailchimp') %}
{% set lists = integration.request('GET', 'lists') %}

{% for list in lists.lists %}
    {{ list.id }}: {{ list.name }}<br>
{% endfor %}
```

You can of course do way more than `GET` requests. We could even subscribe a user to a list right from Twig.

```twig
{% set integration = craft.formie.getPlugin().getIntegrations.getIntegrationByHandle('mailchimp') %}

{# For the list id `1234` subscribe the email `test@gmail.com`  #}
{% set response = integration.request('POST', 'lists/1234/members', {
    email_address: 'test@gmail.com',
}) %}

{# Do something with the response... #}
{% dd response %}
```

### Closing thoughts
While creating Guzzle clients through Craft is very straightforward without Formie (Craft requires Guzzle), things can get complicated quick if an API provider requires OAuth, or other special handling (see [Creating OAuth integrations with Formie](/craft-plugins/formie/user-guides/creating-oauth-integrations-with-formie)).

So using this approach takes away the burden of setting all that up, and you can worry about doing amazing things with APIs instead!

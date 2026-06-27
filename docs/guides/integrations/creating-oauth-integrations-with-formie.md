# Creating OAuth integrations with Formie

If you're anything like me, you'll find OAuth to be a bit of a pain to deal with. From figuring out which OAuth mechanism to use (v1, v2, 2-legged in use, some bespoke, non-standard protocol), handling of tokens, checking for refresh tokens, different scopes, some providers requiring different grants  — the list goes on. But to connect to some APIs via OAuth, it's a necessary pain point.

So maybe your project calls for you to integrate with a third-party API. Now, of course, you could implement OAuth handling yourself. **But here's another option**. Why not use _Formie_ to leverage everything to do with the OAuth client, fetching tokens, refreshing tokens and get a ready-to-go authorized [Guzzle](https://docs.guzzlephp.org/en/stable/) client for you to use in your project?

You don't even need to use Formie for your forms! Just let Formie handle the heavy lifting of managing the client for you.

### Pick an existing integration
Take a look at the [existing integrations](https://verbb.io/craft-plugins/formie/docs/feature-tour/integrations) Formie already supports. If you're looking to make use of **Campaign Monitor**, **Mailchimp**, **Active Campaign**, and more — you're in luck! You can just use the clients in your project.

Read more about this in the [Using Formie integration Guzzle clients in your own code](/craft-plugins/formie/user-guides/using-guzzle-clients-from-formie-integrations-in-your-own-code) guide.

### Create your own integration
If Formie doesn't already support your integration, then that's okay, we can make our own. Let's look at a real-world example by integrating with [GoToWebinar](https://www.goto.com/webinar) using OAuth.

#### Create your module
First, you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP, and added to our custom module. 

When creating your module, set the namespace to `modules\gotowebinar` and the module ID to `go-to-webinar`.

Create the following directory and file structure:

```treeview
my-project/
├── modules/
│    └── gotowebinar/
│        └── src/
│            └── integrations/
│                └── GoToWebinar.php
│            └── providers/
│                └── GoToWebinar.php
│            └── templates/
│                └── _plugin-settings.html
│            └── GoToWebinar.php
└── ...
```

:::tip
GoToWebinar requires its own custom Guzzle OAuth provider, which is more advanced than you might need, but great practice! Some providers might not require this, as they support standard OAuth authentication, which can be handled by just the `GenericProvider` for Guzzle.
:::

Let's walk through each file and detail how it all comes together.

```php
// modules/gotowebinar/src/GoToWebinar.php

<?php
namespace modules\gotowebinar;

use modules\gotowebinar\integrations\GoToWebinar as GoToWebinarIntegration;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\base\Module;

class GoToWebinar extends Module
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        // Register our integration as a "Miscellaneous" integration for Formie
        Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
            $event->miscellaneous[] = GoToWebinarIntegration::class;
        });
    }
}
```

Here our main module file is pretty simple. We tell Formie we want to register a new "Miscellaneous" integration, which points to our `integrations/GoToWebinar.php` class. The bulk of our logic will be in this class.

:::tip
We're using "Miscellaneous" because this provider doesn't really fit into any other category — like CRM, Email Marketing, etc. Depending on your provider, it might make sense to categorize it properly.
:::

Next, we'll get into the actual Formie integration.

```php
// modules/gotowebinar/src/integrations/GoToWebinar.php

<?php
namespace modules\gotowebinar\integrations;

use Craft;
use League\OAuth2\Client\Provider\AbstractProvider;
use modules\gotowebinar\providers\GoToWebinar as GoToWebinarProvider;
use verbb\formie\base\Miscellaneous;

class GoToWebinar extends Miscellaneous
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $accountKey;
    public $organizerKey;


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return 'https://api.getgo.com/oauth/v2/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://api.getgo.com/oauth/v2/token';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function afterFetchAccessToken($token): void
    {
        // Save these properties for later...
        $this->accountKey = $token->getValues()['account_key'] ?? '';
        $this->organizerKey = $token->getValues()['organizer_key'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getOauthProvider(): AbstractProvider
    {
        return new GoToWebinarProvider($this->getOauthProviderConfig());
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'GoToWebinar');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to GoToWebinar.');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('go-to-webinar/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml($form): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.getgo.com/G2W/rest/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'me');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://api.getgo.com/G2W/rest/v2/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }
}
```

Lots to cover here!

- We have properties for the settings we'll require to make the authorized client with GoToWebinar: `clientId`, `clientSecret`, `accountKey`, `organizerKey`. The `clientId` and `clientSecret` are provided in the integration settings.
- We tell Formie that this integration uses OAuth with `supportsOauthConnection()`.
- We add the applicable URLs to `getAuthorizeUrl()` and `getAccessTokenUrl()` (your provider will document these).
- GoToWebinar actually needs two extra bits of information to make calls to their API; `accountKey`, `organizerKey`. These are only available after the OAuth token has been fetched. As such, in `afterFetchAccessToken()` we grab these from the response and save them to the respective properties as settings.
- `getOauthProvider()` allows us to define our own Guzzle OAuth provider. For most cases, the default `GenericProvider` will suffice and you won't need this method, but you can certainly provide your own.

There's a few GoToWebinar-specifics in there, but hopefully, you get the gist of what's possible. Every provider will be different!

The `getClient()` is also worth touching on. It follows the same structure for just about every integration:
- Check for `$this->_client` which is a cached, in-memory instance of the Guzzle client we're about to create. No need to create it multiple times in a single request!
- Fetch the Formie Token (this is a row in your `formie_tokens` database table). This will be created when you first connect to GoToWebinar in the integration settings.
- Create the Guzzle client. We like providing a base URI so you don't have to type it all the time and define it in a single place.
- Almost all Guzzle OAuth clients provide the access token via `'Authorization' => 'Bearer __token__`.
- Then, the most surefire way to check the client is "ready" is to test it. Pick an endpoint in the API that's simple.
- We then catch a `401` response, which will signal the OAuth token has expired. We then fetch a refresh token and try again.
- Finally, we save this authenticated client for the next time we call this method.

:::tip
We can't really rely on the `endOfLife` that we get from OAuth tokens, as some providers don't even provide this. Instead, a far more reliable and consistent way is to test the connection straight away when creating it. For this reason, we cache the client so we aren't doing x2 the number of requests when using the client later.
:::

We're also including `go-to-webinar/_plugin-settings` for our integration settings. This gives us the ability to add some fields for settings, like `clientId` and `clientSecret`.

```twig
// modules/gotowebinar/src/templates/_plugin-settings.html

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
    instructions: 'Enter your {name} Client ID key here.' | t('formie', { name: integration.displayName() }),
    name: 'clientId',
    required: true,
    suggestEnvVars: true,
    value: integration.settings.clientId ?? '',
    warning: macros.configWarning('clientId', 'formie'),
    errors: integration.getErrors('clientId'),
}) }}

{{ forms.autosuggestField({
    label: 'Client Secret' | t('formie'),
    instructions: 'Enter your {name} Client Secret here.' | t('formie', { name: integration.displayName() }),
    name: 'clientSecret',
    required: true,
    suggestEnvVars: true,
    value: integration.settings.clientSecret ?? '',
    warning: macros.configWarning('clientSecret', 'formie'),
    errors: integration.getErrors('clientSecret'),
}) }}
```

The rest of the integration class isn't relevant for this guide, but you can read more about it in [Building a CRM integration from scratch](/craft-plugins/formie/user-guides/building-a-crm-integration-from-scratch).

Finally, we'll create our custom League OAuth provider for Guzzle to use. This is purely because GoToWebinar requires the `clientId` and `clientSecret` to be included in the `Authorization` header as a `base64` string.

```php
// modules/gotowebinar/src/providers/GoToWebinar.php

<?php
namespace modules\gotowebinar\providers;

use Craft;
use League\OAuth2\Client\Provider\GenericProvider;

class GoToWebinar extends GenericProvider
{
    // Protected Methods
    // =========================================================================

    protected function getDefaultHeaders()
    {
        return ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)];
    }
}
```

#### Create the integration
With all that in place, you should be able to go to **Formie** → **Settings** → **Miscellaneous** and click the **New Integration** button. From the dropdown field, select your new **GoToWebinar** integration. Your screen should look similar to the one below.

<img src="https://share.cleanshot.com/08eWg1">

Fill in the **Client ID** and **Client Secret** values from GoToWebinar (follow [their guide](https://developer.goto.com/guides/Get%20Started/02_HOW_createClient/)) on how to retrieve these and save the integration. Finally, click the **Connect** button to connect to GoToWebinar, authorize your integration, and generate a Formie Token.

All going well, you've done everything to need to do.

#### Using the Guzzle client
With everything all setup in Formie, you can move on to your actual usage of the Guzzle client to do whatever your project needs to do.

For example, let's say we want to fetch all the webinars for a particular date.

```php
use verbb\formie\Formie;

// Fetch the Formie integration instance
$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle('goToWebinar');

// For this API call, we need the `accountKey` which is saved to the integration when we fetch the token
$accountKey = $integration->accountKey;

// Create a request to the API for our content. We'll get an array back.
$json = $integration->request('GET', "accounts/${accountKey}/webinars", [
    'query' => [
        'fromTime' => '2020-03-13T10:00:00Z',
        'toTime' => '2023-03-13T10:00:00Z',
    ],
]);
```

We use [`$integration->request()`](https://github.com/verbb/formie/blob/40a98cceacaa2c648c4e9ba88e148b36ae75eb8a/src/base/Integration.php#L491) from Formie as a bit of a helper function to just handle JSON APIs, but it's totally equivalent to:

```php
use craft\helpers\Json;

$client = $integration->getClient();

$response = $client->request('GET', "accounts/${accountKey}/webinars", [
    'query' => [
        'fromTime' => '2020-03-13T10:00:00Z',
        'toTime' => '2023-03-13T10:00:00Z',
    ],
]);

$json = Json::decode((string)$response->getBody());
```

#### Finishing up
As I've mentioned, every provider has different requirements for their APIs. Some request special scopes, different `Authorization` headers (like we've shown with GoToWebinar), or even different grants. We highly recommend if you tackle your own integration to check out the [50+ integrations](https://github.com/verbb/formie/tree/craft-4/src/integrations) we've already built for ways to do things.

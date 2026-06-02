# OAuth Integration
OAuth can apply to many integration types. Use it when Formie needs to connect to a provider account before it can fetch settings or send payloads.

::: tip
For a full walkthrough, see [Creating OAuth Integrations with Formie](https://verbb.io/craft-plugins/formie/user-guides/creating-oauth-integrations-with-formie).
:::

## Provider Class
For OAuth integrations, implement `OAuthProviderInterface`, return the provider class, and enable OAuth support.

```php
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\providers\ProviderName;

class ExampleCrm extends Crm implements OAuthProviderInterface
{
    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return ProviderName::class;
    }
}
```

Formie uses Verbb Auth under the hood. The provider class is responsible for the provider-specific OAuth behavior, while the integration owns Formie-specific settings and API calls.

## Provider Config
Most OAuth integrations expose `clientId`, `clientSecret` and provider-specific settings as plugin-level integration settings. The OAuth trait reads `clientId`, `clientSecret`, `redirectUri`, `scopes`, `scopeSeparator` and `config` from the integration.

Override `getOAuthProviderConfig()` when the provider needs extra configuration.

```php
public function getOAuthProviderConfig(): array
{
    $config = parent::getOAuthProviderConfig();
    $config['apiDomain'] = $this->getApiDomain();

    return $config;
}
```

## Authorization Options
Override `getAuthorizationUrlOptions()` when the provider needs scopes or other authorization URL options.

```php
public function getAuthorizationUrlOptions(): array
{
    $options = parent::getAuthorizationUrlOptions();

    $options['scope'] = [
        'read',
        'write',
    ];

    return $options;
}
```

## Access Tokens and Requests
The OAuth trait provides hooks for access token and request handling.

Method | Use
--- | ---
`getAccessTokenOptions()` | Adds options when Formie fetches an access token.
`getRequestOptions()` | Adds request options for OAuth API requests.
`beforeFetchAccessToken()` | Runs before Formie fetches the access token.
`afterFetchAccessToken()` | Runs after Formie fetches and saves the token.
`getBaseApiUrl()` | Overrides the API base URL for the provider client.

Once OAuth is enabled, use `$this->request()` for provider API calls. Formie will route the request through the OAuth provider and stored token.


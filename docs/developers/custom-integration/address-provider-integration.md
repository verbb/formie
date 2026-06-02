# Address Provider Integration

Address provider integrations extend `AddressProvider`. They are used by Address fields for address lookup, autocomplete and current-location support.

Address providers are configured at the plugin settings level, then selected on the Auto-Complete sub-field inside an Address field. They do not create submissions, send payloads or add form-level settings. Most of the work is connecting the Address field’s autocomplete input to the provider’s front-end SDK, then copying the selected address parts back into Formie’s address sub-fields.

The front-end and back-end pieces work together:

1. The user creates and configures the address provider in Formie’s plugin settings.
2. The user edits an Address field and selects that provider on the Auto-Complete sub-field.
3. The Address field renders its normal autocomplete input and address sub-fields.
4. `getClientModule()` registers a front-end module for that Address field.
5. The front-end module loads the provider SDK, attaches autocomplete to the Address field and writes selected address parts back to Formie inputs.
6. If the provider supports current-location lookup, `supportsCurrentLocation()` enables the field setting, and the front-end module can handle the browser geolocation result.

## PHP Integration

```php
use Craft;
use craft\helpers\App;
use verbb\formie\base\AddressProvider;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

class ExampleAddressProvider extends AddressProvider
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Address Provider');
    }

    public static function supportsCurrentLocation(): bool
    {
        return true;
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$this->hasValidSettings()) {
            return null;
        }

        return new ClientModule([
            'id' => 'example-address-provider',
            'src' => '/assets/formie/example-address-provider.js',
            'config' => [
                'apiKey' => App::parseEnv($this->apiKey),
                'countryCode' => $this->countryCode,
            ],
        ]);
    }

    public function hasValidSettings(): bool
    {
        return $this->apiKey && $this->countryCode;
    }
}
```

`getClientModule()` should return `null` if the provider is not configured enough to run. For third-party modules, set `src` on the `ClientModule` to a public module file path.

## Client Modules

Address provider modules should usually use `defineAddressModule()` from `@verbb/formie-browser`. It gives you shared services for finding the Address field’s autocomplete input, setting address sub-field values, listening for the current-location button and cleaning up when the form is replaced.

```ts
import { defineAddressModule } from '@verbb/formie-browser';

type ExampleAddressApi = {
  createAutocomplete: (input: HTMLInputElement, options: Record<string, unknown>) => {
    on: (eventName: 'select', callback: (result: ExampleAddressResult) => void) => void;
    destroy: () => void;
  };
  reverseGeocode: (latitude: number, longitude: number) => Promise<ExampleAddressResult>;
};

type ExampleAddressResult = {
  line1?: string;
  line2?: string;
  city?: string;
  state?: string;
  postalCode?: string;
  countryCode?: string;
};

type ExampleAddressOptions = {
  apiKey?: string | null;
  countryCode?: string | null;
};

async function loadExampleAddressApi(): Promise<ExampleAddressApi> {
  const existing = window.ExampleAddress as ExampleAddressApi | undefined;

  if (existing) {
    return existing;
  }

  await import('https://address.example.test/sdk.js');

  return window.ExampleAddress as ExampleAddressApi;
}

export default defineAddressModule<ExampleAddressOptions, ExampleAddressApi, ReturnType<ExampleAddressApi['createAutocomplete']>>({
  id: 'example-address-provider',

  load: () => {
    return loadExampleAddressApi();
  },

  mount: ({ api, services, provider }) => {
    const input = services.input.getAutocomplete();

    if (!input || !provider.apiKey) {
      throw new Error('Example Address Provider API key is required.');
    }

    const widget = api.createAutocomplete(input, {
      apiKey: provider.apiKey,
      countryCode: provider.countryCode || 'AU',
    });

    widget.on('select', (result) => {
      services.input.setValue('address1', result.line1 || '');
      services.input.setValue('address2', result.line2 || '');
      services.input.setValue('city', result.city || '');
      services.input.setValue('state', result.state || '');
      services.input.setValue('zip', result.postalCode || '');
      services.input.setValue('country', result.countryCode || '');
    });

    return widget;
  },

  onCurrentLocation: async (position, { api, services }) => {
    const result = await api.reverseGeocode(
      position.coords.latitude,
      position.coords.longitude,
    );

    services.input.setValue('address1', result.line1 || '');
    services.input.setValue('address2', result.line2 || '');
    services.input.setValue('city', result.city || '');
    services.input.setValue('state', result.state || '');
    services.input.setValue('zip', result.postalCode || '');
    services.input.setValue('country', result.countryCode || '');
  },

  unmount: ({ widget }) => {
    widget.destroy();
  },
});
```

The Address field already renders the autocomplete input and marks it with the data attributes Formie needs. Your module should use `services.input.getAutocomplete()` to find that input, then `services.input.setValue()` to populate the supported sub-fields: `address1`, `address2`, `address3`, `city`, `state`, `zip` and `country`.

`onCurrentLocation()` only runs when the provider returns `true` from `supportsCurrentLocation()` and the field has the current-location button enabled.

## Methods

Address providers usually focus on these methods:

Method | Use
--- | ---
`getClientModule()` | Registers front-end behavior for the Address field.
`supportsCurrentLocation()` | Enables current-location support for providers that can handle it.
`getSettingsHtml()` | Renders plugin-level settings for the provider.
`hasValidSettings()` | Confirms the provider has enough settings to run.

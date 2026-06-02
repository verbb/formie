import type { FormieModuleDefinition } from '#contracts/modules';

export const builtinAddressModuleLoaders: Record<string, () => Promise<FormieModuleDefinition>> = {
    // Address providers stay behind lazy importer entries because their SDKs are
    // optional and often much heavier than the base form client.
    'address-finder': () => import('#modules/address/address-finder').then((m) => m.addressFinderModule),
    'google-address': () => import('#modules/address/google-address').then((m) => m.googleAddressModule),
    'loqate': () => import('#modules/address/loqate').then((m) => m.loqateModule),
    'place-kit': () => import('#modules/address/place-kit').then((m) => m.placeKitModule),
};

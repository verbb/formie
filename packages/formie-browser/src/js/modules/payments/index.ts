import type { FormieModuleDefinition } from '#contracts/modules';

export const builtinPaymentModuleLoaders: Record<string, () => Promise<FormieModuleDefinition>> = {
    // Keep payment providers lazy and separately addressable so forms only ship
    // the payment SDK wrapper code they actually declare in their manifest.
    'bpoint': () => import('#modules/payments/bpoint').then((module) => module.bpointModule),
    'eway': () => import('#modules/payments/eway').then((module) => module.ewayModule),
    'go-cardless': () => import('#modules/payments/go-cardless').then((module) => module.goCardlessModule),
    'mollie': () => import('#modules/payments/mollie').then((module) => module.mollieModule),
    'moneris': () => import('#modules/payments/moneris').then((module) => module.monerisModule),
    'opayo': () => import('#modules/payments/opayo').then((module) => module.opayoModule),
    'paddle': () => import('#modules/payments/paddle').then((module) => module.paddleModule),
    'paypal': () => import('#modules/payments/paypal').then((module) => module.paypalModule),
    'payway': () => import('#modules/payments/payway').then((module) => module.paywayModule),
    'square': () => import('#modules/payments/square').then((module) => module.squareModule),
    'stripe': () => import('#modules/payments/stripe').then((module) => module.stripeModule),
};

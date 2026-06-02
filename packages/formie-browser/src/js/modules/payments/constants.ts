/** Default timeout (ms) to wait for payment token inputs to populate. */
export const DEFAULT_WAIT_FOR_VALUE_MS = 2500;

/** Per-provider default required input name suffixes for payment tokens. */
export const DEFAULT_REQUIRED_INPUT_SUFFIXES: Record<string, string[]> = {
    bpoint: ['bpointToken'],
    stripe: ['stripePaymentIntentId'],
    paypal: ['paypalOrderId', 'paypalAuthId'],
    payway: ['paywayTokenId'],
    opayo: ['opayoTokenId'],
    eway: ['ewayTokenData'],
    'go-cardless': ['goCardlessRedirectId'],
    mollie: ['molliePaymentId'],
    moneris: ['monerisTokenId'],
    paddle: ['paddleTransactionId'],
    square: ['squarePaymentId'],
};

// ==========================================================================

// Formie Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// CSS needs to be imported here as it's treated as a module
import '../scss/formie-plugin-settings.scss';
import { initDurationHints } from './duration-hint';
import { initCaptchaIntegrationStatus } from './captcha-integration-status';

initDurationHints();
initCaptchaIntegrationStatus();

// ==========================================================================

// Formie Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// CSS needs to be imported here as it's treated as a module
import '../scss/formie-cp.scss';

import './includes/submission-index';
import './includes/sent-notifications';

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

(function($) {



})(jQuery);

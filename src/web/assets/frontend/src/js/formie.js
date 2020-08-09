import { Formie } from './formie-lib';

// This should only be used when initializing Formie from the browser. When initializing with JS directly
// import `formie-lib.js` directly into your JS modules.
window.Formie = new Formie();

// Don't init forms until the document is ready
document.addEventListener('DOMContentLoaded', (event) => {
    window.Formie.initForms();
});

import { Formie } from './formie-lib';

window.Formie = new Formie();

// Don't init forms until the document is ready
document.addEventListener('DOMContentLoaded', (event) => {
    window.Formie.initForms();
});

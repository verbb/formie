// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

import {
    get, isEmpty, merge, flattenDeep, map, flatMap, isObject, isArray,
} from 'lodash-es';
import { generateHandle, getNextAvailableHandle, newId } from '@utils/string';
import { clone } from '@utils/object';
import { getErrorMessage } from '@utils/forms';

import { createVueApp, store } from './config.js';

//
// Start Vue Apps
//

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

//
// Components
//

import { TabPanels, TabPanel } from '@vendor/vue-accessible-tabs';

import FormBuilder from '@components/FormBuilder.vue';
import FormKitForm from '@formkit-components/FormKitForm.vue';
import NotificationsBuilder from '@components/NotificationsBuilder.vue';
import SubFields from '@formkit-components/SubFields.vue';
import ToggleBlock from '@formkit-components/inputs/toggle-blocks/ToggleBlock.vue';
import TableBulkOptions from '@formkit-components/inputs/table/TableBulkOptions.vue';
import TableCell from '@formkit-components/inputs/table/TableCell.vue';

// Field Preview components
import DatePreview from '@components/DatePreview.vue';
import ElementFieldPreview from '@components/ElementFieldPreview.vue';
import FieldGroup from '@components/FieldGroup.vue';
import FieldRepeater from '@components/FieldRepeater.vue';
import HtmlBlocks from '@components/HtmlBlocks.vue';

// Notifications
import NotificationPreview from '@components/NotificationPreview.vue';
import NotificationTest from '@components/NotificationTest.vue';

// Integrations
import FieldSelect from '@components/FieldSelect.vue';
import IntegrationFieldMapping from '@components/IntegrationFieldMapping.vue';
import IntegrationFormSettings from '@components/IntegrationFormSettings.vue';


//
// Start Vue Apps
//

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie.EditForm = Garnish.Base.extend({
    init(settings) {
        // Initialise our Vuex stores with data ASAP
        store.dispatch('form/setFormConfig', settings.config);
        store.dispatch('form/setVariables', settings.variables);
        store.dispatch('fieldtypes/setFieldtypes', settings.fields);
        store.dispatch('fieldGroups/setFieldGroups', settings.fields);
        store.dispatch('notifications/setNotifications', settings.notifications);
        store.dispatch('formie/setEmailTemplates', settings.emailTemplates);
        store.dispatch('formie/setMaxFieldHandleLength', settings.maxFieldHandleLength);
        store.dispatch('formie/setMaxFormHandleLength', settings.maxFormHandleLength);
        store.dispatch('formie/setReservedHandles', settings.reservedHandles);
        store.dispatch('formie/setStatuses', settings.statuses);

        // Create some Vue instances for other elements on the page, outside of the form builder
        new Craft.Formie.PageTitle();
        new Craft.Formie.ErrorSummary();
        new Craft.Formie.SaveButton();

        const app = createVueApp({
            components: {
                FormBuilder,
                NotificationsBuilder,
            },

            data() {
                return {
                    templateReloadNotice: false,
                    formHandles: settings.formHandles,
                };
            },

            computed: {
                form() {
                    return this.$store.state.form;
                },

                notifications() {
                    return this.$store.state.notifications;
                },

                plainTextVariables() {
                    return this.$store.getters['form/plainTextFields'](true);
                },
            },

            watch: {
                'form.templateId': function(newValue, oldValue) {
                    // Prevent reloading tabs when empty values.
                    if (!newValue || !oldValue) {
                        return;
                    }

                    if (!this.form.isStencil) {
                        this.templateReloadNotice = true;
                    }
                },
            },

            created() {
                this.$events.on('formie:save-form', (options) => {
                    this.onSave(options);
                });

                this.$events.on('formie:delete-form', (e) => {
                    this.onDelete(e);
                });
            },

            mounted() {
                this.$nextTick().then(() => {
                    Craft.initUiElements(this.$el.parentNode);
                });
            },

            methods: {
                getFieldsForType(type) {
                    return this.$store.getters['form/fieldsForType'](type);
                },

                get(object, key) {
                    return get(object, key);
                },

                isEmpty(object) {
                    return isEmpty(object);
                },

                getFormElement() {
                    return this.$el.parentNode;
                },

                forcedAjax() {
                    // Stripe/Opayo requires Ajax to cater for 3DS payments for some cards.
                    const allFields = this.$store.getters['form/fields']();

                    const stripeFields = allFields.filter((field) => {
                        return field.type === 'verbb\\formie\\fields\\Payment' && (field.settings.paymentIntegration === 'stripe' || field.settings.paymentIntegration === 'opayo' || field.settings.paymentIntegration === 'mollie');
                    });

                    return stripeFields.length;
                },

                getFormData(options = {}) {
                    let data = new FormData();

                    // For Craft Cloud, we need to be mindful of what is sent to the server, due to platform requirements
                    // but some people do have issues with mapping going away, so opt-in.
                    if (settings.filterIntegrationMapping) {
                        const formData = new FormData(this.getFormElement());

                        // Filter out empty integration field mapping values, to keep payload size down
                        const pattern = /^settings\[integrations\]\[.+\]\[.*fieldMapping.*\]$/i;

                        for (const [key, value] of formData.entries()) {
                            if (pattern.test(key) && value === '') {
                                continue;
                            }

                            data.append(key, value);
                        }
                    } else {
                        data = new FormData(this.getFormElement());
                    }

                    // Add serialized models for form and notifications
                    data.append('pages', JSON.stringify(this.$store.getters['form/serializedPayload']));
                    data.append('notifications', JSON.stringify(this.$store.getters['notifications/serializedPayload']));
                    data.append('deleted', JSON.stringify(this.$store.getters['form/serializedDeleted']));

                    Object.keys(options).forEach((option) => {
                        data.append(option, options[option]);
                    });

                    return data;
                },

                onSave(options = {}) {
                    const isValid = true;
                    const fieldsValid = true;
                    const notificationsValid = true;

                    // Reset any errors stored on the form
                    this.form.errors = [];

                    const $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    const $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    const $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

                    if ($fieldsTab) {
                        $fieldsTab.classList.remove('error');
                    }

                    if ($notificationsTab) {
                        $notificationsTab.classList.remove('error');
                    }

                    if ($integrationsTab) {
                        $integrationsTab.classList.remove('error');
                    }

                    // Serialize the form page, then add in the form builder and notifications data
                    const data = this.getFormData(options);

                    let actionUrl = 'formie/forms/save';

                    if (this.form.isStencil) {
                        actionUrl = 'formie/stencils/save';
                    }

                    if (options.saveAsStencil) {
                        actionUrl = 'formie/forms/save-as-stencil';
                    }

                    Craft.sendActionRequest('POST', actionUrl, { data }).then((response) => {
                        if (response.data) {
                            if (response.data.config) {
                                // Merge (deep) the server response with our client-side data (for success or fail)
                                const newConfig = merge(this.clone(this.form), response.data.config);

                                this.$store.dispatch('form/setFormConfig', newConfig);
                                this.$store.dispatch('notifications/setNotifications', response.data.notifications);
                            }

                            if (response.data.success) {
                                this.onSuccess(response.data);
                            } else {
                                this.onError(response.data);
                            }
                        }
                    }).catch((error) => {
                        console.error(error);

                        this.onError(error, true);
                    });
                },

                onSuccess(data) {
                    const { formBuilder } = this.$refs;

                    // Update the saved hash to prevent browser warnings
                    formBuilder.saveUpdatedHash();

                    if (data.redirect) {
                        Craft.cp.displayNotice(Craft.t('formie', data.redirectMessage));

                        return window.location = data.redirect;
                    } if (this.form.isStencil) {
                        history.replaceState({}, '', Craft.getUrl(`formie/settings/stencils/edit/${data.config.id}${location.hash}`));

                        this.addInput('stencilId', data.config.id);
                    } else {
                        history.replaceState({}, '', Craft.getUrl(`formie/forms/edit/${data.config.id}${location.hash}`));

                        this.addInput('formId', data.config.id);
                    }

                    if (this.form.isStencil) {
                        Craft.cp.displayNotice(Craft.t('formie', 'Stencil saved.'));
                    } else {
                        Craft.cp.displayNotice(Craft.t('formie', 'Form saved.'));
                    }

                    this.$events.emit('formie:save-form-loading', false);
                },

                onError(data = {}, serverError = false) {
                    if (this.form.isStencil) {
                        Craft.cp.displayError(Craft.t('formie', 'Couldn’t save stencil.'));
                    } else {
                        Craft.cp.displayError(Craft.t('formie', 'Couldn’t save form.'));
                    }

                    // Save a formatted error to `serverError` to show users
                    if (serverError) {
                        this.form.errors.push({ serverError: getErrorMessage(data) });
                    }

                    this.$events.emit('formie:save-form-loading', false);

                    // Not really a great way to handle this, with tabs being outside of Vue...
                    const $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    const $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    const $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

                    this.form.pages.forEach((page) => {
                        if ($fieldsTab && !isEmpty(page.errors)) {
                            $fieldsTab.classList.add('error');
                        }
                    });

                    if (this.form.settings.errors) {
                        Object.keys(this.form.settings.errors).forEach((errorKey) => {
                            if ($integrationsTab && errorKey.startsWith('integrations.')) {
                                $integrationsTab.classList.add('error');
                            }
                        });
                    }

                    this.notifications.forEach((notification) => {
                        if ($notificationsTab && !isEmpty(notification.errors)) {
                            $notificationsTab.classList.add('error');
                        }
                    });
                },

                addInput(name, value) {
                    const formElem = this.getFormElement();
                    let input = formElem.querySelector(`[name="${name}"]`);

                    if (!input) {
                        input = document.createElement('input');
                        input.setAttribute('type', 'hidden');
                        input.setAttribute('name', name);
                        input.setAttribute('value', value);
                        formElem.appendChild(input);
                    } else {
                        input.setAttribute('value', value);
                    }
                },

                onDelete(e) {
                    const formElem = this.getFormElement();

                    const data = {
                        redirect: e.target.getAttribute('data-redirect'),
                        formId: formElem.querySelector('[name="formId"]').value,
                    };

                    Craft.sendActionRequest('POST', 'formie/forms/delete-form', { data }).then((response) => {
                        window.location = response.data.redirect;
                    }).catch(() => { return this.onError(); });
                },
            },
        });

        // Define global components
        app.component('FormKitForm', FormKitForm);
        app.component('SubFields', SubFields);
        app.component('ToggleBlock', ToggleBlock);
        app.component('TabPanel', TabPanel);
        app.component('TabPanels', TabPanels);
        app.component('TableBulkOptions', TableBulkOptions);
        app.component('TableCell', TableCell);

        // Field Preview components
        app.component('DatePreview', DatePreview);
        app.component('ElementFieldPreview', ElementFieldPreview);
        app.component('FieldGroup', FieldGroup);
        app.component('FieldRepeater', FieldRepeater);
        app.component('HtmlBlocks', HtmlBlocks);

        // Notifications
        app.component('NotificationPreview', NotificationPreview);
        app.component('NotificationTest', NotificationTest);

        // Integrations
        app.component('FieldSelect', FieldSelect);
        app.component('IntegrationFieldMapping', IntegrationFieldMapping);
        app.component('IntegrationFormSettings', IntegrationFormSettings);

        app.mount('#fui-forms');
    },
});

Craft.Formie.PageTitle = Garnish.Base.extend({
    init() {
        const app = createVueApp({
            computed: {
                form() {
                    return this.$store.state.form;
                },
            },
        });

        app.mount('#fui-page-title');
    },
});

Craft.Formie.ErrorSummary = Garnish.Base.extend({
    init() {
        const app = createVueApp({
            computed: {
                form() {
                    return this.$store.state.form;
                },

                errorMessage() {
                    if (this.errors.length === 1) {
                        return Craft.t('formie', 'Found {num} error', { num: this.errors.length });
                    }

                    return Craft.t('formie', 'Found {num} errors', { num: this.errors.length });
                },

                errors() {
                    const errors = [];

                    this.form.pages.forEach((page) => {
                        page.rows.forEach((row) => {
                            row.fields.forEach((field) => {
                                if (field.errors) {
                                    Object.entries(field.errors).forEach(([key, value]) => {
                                        value.forEach((error) => {
                                            const message = [`${page.label}:`, `${field.settings.label} -`, error];

                                            errors.push(message.join(' '));
                                        });
                                    });
                                }
                            });
                        });
                    });

                    this.form.errors.forEach((formErrors) => {
                        Object.entries(formErrors).forEach(([key, value]) => {
                            if (key === 'serverError') {
                                const message = [`${value.heading}:`, `${value.text}`, `<br><small>${value.trace}</small>`];

                                errors.push(message.join(' '));
                            }
                        });
                    });

                    return errors;
                },
            },
        });

        app.mount('#fui-error-summary');
    },
});

Craft.Formie.SaveButton = Garnish.Base.extend({
    init() {
        if (!document.getElementById('fui-save-form-button')) {
            return;
        }

        const app = createVueApp({
            data() {
                return {
                    loading: false,
                };
            },

            created() {
                this.$events.on('formie:save-form-loading', (state) => {
                    this.loading = state;
                });
            },

            mounted() {
                this._keyListener = function(e) {
                    if (e.key === 's' && (e.ctrlKey || e.metaKey)) {
                        e.preventDefault();

                        this.onSave();
                    }
                };

                document.addEventListener('keydown', this._keyListener.bind(this));

                // Implement a custom menubtn, because his will be after the VDOM has started
                $('.menubtn-custom', this.$el).menubtn();
            },

            beforeDestroy() {
                document.removeEventListener('keydown', this._keyListener);
            },

            methods: {
                onSave() {
                    this.loading = true;

                    this.$nextTick(() => {
                        this.$events.emit('formie:save-form');
                    });
                },

                onSaveAs(params) {
                    this.loading = true;

                    this.$nextTick(() => {
                        this.$events.emit('formie:save-form', params);
                    });
                },

                onDelete(e) {
                    const message = Craft.t('formie', 'Are you sure you want to delete this form?');

                    if (confirm(message)) {
                        this.$events.emit('formie:delete-form', e);
                    }
                },
            },
        });

        app.mount('#fui-save-form-button');
    },
});


// Create a site-aware element select input
Craft.Formie.SiteElementSelect = Craft.BaseElementSelectInput.extend({
    createNewElement(elementInfo) {
        const $element = elementInfo.$element.clone();

        $element.prepend(`
            <input type="hidden" name="${this.settings.name}[id]" value="${elementInfo.id}">
            <input type="hidden" name="${this.settings.name}[siteId]" value="${elementInfo.siteId}">
        `);

        return $element;
    },
});


// Re-broadcast the custom `vite-script-loaded` event so that we know that this module has loaded
// Needed because when <script> tags are appended to the DOM, the `onload` handlers
// are not executed, which happens in the field Settings page, and in slideouts
// Do this after the document is ready to ensure proper execution order
$(document).ready(() => {
    document.dispatchEvent(new CustomEvent('vite-script-loaded', { detail: { path: 'src/js/formie-form.js' } }));
});

// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

import { get, isEmpty } from 'lodash-es';
import { generateHandle, getNextAvailableHandle, newId } from '@utils/string';
import { clone } from '@utils/object';

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
import ToggleBlock from '@formkit-components/ToggleBlock.vue';

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
        // Add isStencil flag
        settings.config.isStencil = settings.isStencil;

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

                    if (!settings.isStencil) {
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
                    Craft.initUiElements();
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

                containsStripeField() {
                    // Stripe requires Ajax to cater for 3DS payments for some cards.
                    const allFields = this.$store.getters['form/fields'];

                    const stripeFields = allFields.filter((field) => {
                        return field.type === 'verbb\\formie\\fields\\formfields\\Payment' && field.settings.paymentIntegration === 'stripe';
                    });

                    return stripeFields.length;
                },

                getFormData(options = {}) {
                    const formElem = this.getFormElement();
                    const data = new FormData(formElem);

                    // Quick-n-easy clone
                    const pageData = clone(this.form.pages);

                    // Filter out some unwanted data
                    if (pageData) {
                        pageData.forEach((page) => {
                            delete page.errors;
                            delete page.hasError;

                            page.rows.forEach((row) => {
                                row.fields.forEach((field) => {
                                    delete field.icon;
                                    delete field.errors;
                                    delete field.hasError;
                                });
                            });
                        });
                    }

                    // Quick-n-easy clone
                    const notificationsData = clone(this.notifications);

                    // Filter out some unwanted data
                    if (notificationsData) {
                        notificationsData.forEach((notification) => {
                            delete notification.errors;
                            delete notification.hasError;
                        });
                    }

                    data.append('pages', JSON.stringify(pageData));
                    data.append('notifications', JSON.stringify(notificationsData));

                    Object.keys(options).forEach((option) => {
                        data.append(option, options[option]);
                    });

                    return data;
                },

                onSave(options = {}) {
                    const isValid = true;
                    const fieldsValid = true;
                    const notificationsValid = true;

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

                    const { formBuilder, notificationBuilder } = this.$refs;

                    const data = this.getFormData(options);

                    let actionUrl = 'formie/forms/save';

                    if (settings.isStencil) {
                        actionUrl = 'formie/stencils/save';
                    }

                    if (options.saveAsStencil) {
                        actionUrl = 'formie/forms/save-as-stencil';
                    }

                    Craft.sendActionRequest('POST', actionUrl, { data }).then((response) => {
                        if (response.data) {
                            if (response.data.config) {
                                // Because of how validation works on the Craft side, any new pages with an id of `newXXXX-XXXX` will be
                                // stripped out. This is because we _have_ to strip them out as they need to be `null` to be saved properly.
                                // But on failed validation, they're stripped out and we end up with all sorts of issues. This is the same for
                                // rows. So - always ensure pages and rows have an ID.
                                response.data.config.pages.forEach((page) => {
                                    if (!page.id) {
                                        page.id = newId();
                                    }

                                    page.rows.forEach((row) => {
                                        if (!row.id) {
                                            row.id = newId();
                                        }
                                    });
                                });

                                this.$store.dispatch('form/setFormConfig', response.data.config);
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

                        this.onError(error);
                    });
                },

                onSuccess(data) {
                    const { formBuilder } = this.$refs;

                    // Update the saved hash to prevent browser warnings
                    formBuilder.saveUpdatedHash();

                    if (data.redirect) {
                        Craft.cp.displayNotice(Craft.t('formie', data.redirectMessage));

                        return window.location = data.redirect;
                    } if (!settings.isStencil) {
                        history.replaceState({}, '', Craft.getUrl(`formie/forms/edit/${data.id}${location.hash}`));

                        this.addInput('formId', data.id);
                        this.addInput('fieldLayoutId', data.fieldLayoutId);
                    } else {
                        history.replaceState({}, '', Craft.getUrl(`formie/settings/stencils/edit/${data.id}${location.hash}`));

                        this.addInput('stencilId', data.id);
                    }

                    Craft.cp.displayNotice(Craft.t('formie', 'Form saved.'));
                    this.$events.emit('formie:save-form-loading', false);
                },

                onError(data = {}) {
                    let message = 'Unable to save form.';

                    if (data.errors && (data.errors.length || data.errors)) {
                        message = `Unable to save form: ${JSON.stringify(data.errors)}.`;
                    }

                    Craft.cp.displayError(Craft.t('formie', message));
                    this.$events.emit('formie:save-form-loading', false);

                    // TODO: Clean this up...
                    const $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    const $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    const $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

                    if (data && data.config) {
                        data.config.pages.forEach((page) => {
                            page.rows.forEach((row) => {
                                row.fields.forEach((field) => {
                                    if ($fieldsTab && field.hasError) {
                                        $fieldsTab.classList.add('error');
                                    }
                                });
                            });
                        });

                        // Check for integration errors
                        if (data.config.settings.integrations) {
                            Object.keys(data.config.settings.integrations).forEach((handle) => {
                                const integration = data.config.settings.integrations[handle];

                                if ($integrationsTab && integration.errors) {
                                    $integrationsTab.classList.add('error');
                                }
                            });
                        }
                    }

                    if (data && data.notifications) {
                        data.notifications.forEach((notification) => {
                            if ($notificationsTab && notification.hasError) {
                                $notificationsTab.classList.add('error');
                            }
                        });
                    }
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
        app.component('ToggleBlock', ToggleBlock);
        app.component('TabPanel', TabPanel);
        app.component('TabPanels', TabPanels);

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
        const removeText = Craft.t('app', 'Remove {label}', {
            label: Craft.escapeHtml(elementInfo.label),
        });

        // Make a couple tweaks
        Craft.setElementSize(
            $element,
            this.settings.viewMode === 'large' ? 'large' : 'small',
        );

        $element.addClass('removable');
        $element.prepend(`
            <input type="hidden" name="${this.settings.name}[id]" value="${elementInfo.id}">
            <input type="hidden" name="${this.settings.name}[siteId]" value="${elementInfo.siteId}">
            <button type="button" class="delete icon" title="${Craft.t('app', 'Remove')}" aria-label="${removeText}"></button>
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

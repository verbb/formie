import Vue from 'vue';
import config from './config.js';
import get from 'lodash/get';
import isEmpty from 'lodash/isEmpty';
import { newId } from './utils/string';

// Apply our config settings, which do most of the grunt work
Vue.use(config);

//
// Vuex
//

import store from './store';

//
// Utils
//

import { generateHandle, getNextAvailableHandle } from './utils/string';

//
// Components
//

import FormBuilder from './components/FormBuilder.vue';
import FieldRepeater from './components/FieldRepeater.vue';
import FieldGroup from './components/FieldGroup.vue';
import DatePreview from './components/DatePreview.vue';
import ElementFieldPreview from './components/ElementFieldPreview.vue';
import NotificationsBuilder from './components/NotificationsBuilder.vue';
import NotificationPreview from './components/NotificationPreview.vue';
import NotificationTest from './components/NotificationTest.vue';
import FieldSelect from './components/FieldSelect.vue';
import IntegrationFieldMapping from './components/IntegrationFieldMapping.vue';
import IntegrationFormSettings from './components/IntegrationFormSettings.vue';

// Globally register components
Vue.component('FieldRepeater', FieldRepeater);
Vue.component('FieldGroup', FieldGroup);
Vue.component('DatePreview', DatePreview);
Vue.component('ElementFieldPreview', ElementFieldPreview);
Vue.component('NotificationPreview', NotificationPreview);
Vue.component('NotificationTest', NotificationTest);
Vue.component('FieldSelect', FieldSelect);
Vue.component('IntegrationFieldMapping', IntegrationFieldMapping);
Vue.component('IntegrationFormSettings', IntegrationFormSettings);

//
// Start Vue Apps
//

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie = Garnish.Base.extend({
    init(settings) {
        // Add isStencil flag
        settings.config.isStencil = settings.isStencil;

        // Initialise our Vuex stores with data
        store.dispatch('form/setFormConfig', settings.config);
        store.dispatch('form/setVariables', settings.variables);
        store.dispatch('fieldtypes/setFieldtypes', settings.fields);
        store.dispatch('fieldGroups/setFieldGroups', settings.fields);
        store.dispatch('notifications/setNotifications', settings.notifications);
        store.dispatch('formie/setExistingFields', settings.existingFields);
        store.dispatch('formie/setExistingNotifications', settings.existingNotifications);
        store.dispatch('formie/setEmailTemplates', settings.emailTemplates);
        store.dispatch('formie/setMaxFieldHandleLength', settings.maxFieldHandleLength);
        store.dispatch('formie/setMaxFormHandleLength', settings.maxFormHandleLength);
        store.dispatch('formie/setReservedHandles', settings.reservedHandles);
        store.dispatch('formie/setStatuses', settings.statuses);

        new Vue({
            el: '#fui-page-title',
            delimiters: ['${', '}'],
            store,

            computed: {
                form() {
                    return this.$store.state.form;
                },
            },
        });

        if (document.getElementById('fui-save-form-button')) {
            new Vue({
                el: '#fui-save-form-button',
                delimiters: ['${', '}'],

                data() {
                    return {
                        loading: false,
                    };
                },

                created() {
                    this.$events.$on('formie:save-form-loading', state => {
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
                },

                beforeDestroy() {
                    document.removeEventListener('keydown', this._keyListener);
                },

                methods: {
                    onSave() {
                        this.loading = true;

                        this.$nextTick(() => {
                            this.$events.$emit('formie:save-form');
                        });
                    },

                    onSaveAs(params) {
                        this.loading = true;

                        this.$nextTick(() => {
                            this.$events.$emit('formie:save-form', params);
                        });
                    },

                    onDelete(e) {
                        const message = Craft.t('formie', 'Are you sure you want to delete this form?');

                        if (confirm(message)) {
                            this.$events.$emit('formie:delete-form', e);
                        }
                    },
                },
            });
        }

        new Vue({
            el: '#fui-forms',
            delimiters: ['${', '}'],
            store,

            components: {
                FormBuilder,
                FieldRepeater,
                NotificationsBuilder,
            },

            data() {
                return {
                    formTemplateLoading: false,
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
                'form.templateId'(newValue, oldValue) {
                    // Prevent reloading tabs when empty string != null.
                    if (!newValue && !oldValue) {
                        return;
                    }

                    if (!settings.isStencil) {
                        this.reloadTabs();
                    }
                },
            },

            created() {
                this.$events.$on('formie:save-form', (options) => {
                    this.onSave(options);
                });

                this.$events.$on('formie:delete-form', (e) => {
                    this.onDelete(e);
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

                getFormData(options = {}) {
                    const { formElem } = this.$refs;

                    const data = new FormData(formElem);

                    // Quick-n-easy clone
                    const pageData = clone(this.form.pages);

                    // Filter out some unwanted data
                    if (pageData) {
                        pageData.forEach(page => {
                            delete page.errors;
                            delete page.hasError;

                            page.rows.forEach(row => {
                                row.fields.forEach(field => {
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
                        notificationsData.forEach(notification => {
                            delete notification.errors;
                            delete notification.hasError;
                        });
                    }

                    data.append('pages', JSON.stringify(pageData));
                    data.append('notifications', JSON.stringify(notificationsData));

                    Object.keys(options).forEach(option => {
                        data.append(option, options[option]);
                    });

                    return data;
                },

                onSave(options = {}) {
                    let isValid = true;
                    let fieldsValid = true;
                    let notificationsValid = true;

                    let $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    let $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    let $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

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

                    // Disable client-side validation for fields and notifications for the moment
                    // Gotta figure out how best to do it in Formulate, that doesn't kill things for
                    // large forms with lots of fields. We might just have to live with server-side here
                    // (when saving the whole form - validation happens in modals)

                    // Find all fields - maybe a better way?
                    // formBuilder.$refs.pages.forEach(page => {
                    //     if (page.$refs.rows) {
                    //         page.$refs.rows.forEach(row => {
                    //             if (row.$refs.fields) {
                    //                 row.$refs.fields.forEach(field => {
                    //                     // Try to save the field, which triggers validation and Vuex state updates
                    //                     // It'll also return true if valid.
                    //                     if (!field.saveField()) {
                    //                         isValid = false;

                    //                         $fieldsTab.classList.add('error');
                    //                     }
                    //                 });
                    //             }
                    //         });
                    //     }
                    // });

                    // Validate all notifications
                    // if (notificationBuilder.$refs.notification) {
                    //     notificationBuilder.$refs.notification.forEach(notification => {
                    //         if (!notification.saveNotification()) {
                    //             isValid = false;

                    //             // TODO: Clean this up...
                    //             $notificationsTab.classList.add('error');
                    //         }
                    //     });
                    // }

                    if (isValid) {
                        const data = this.getFormData(options);

                        let actionUrl = 'formie/forms/save';

                        if (settings.isStencil) {
                            actionUrl = 'formie/stencils/save';
                        }

                        if (options.saveAsStencil) {
                            actionUrl = 'formie/forms/save-as-stencil';
                        }

                        this.$axios.post(Craft.getActionUrl(actionUrl), data).then(({ data }) => {
                            if (data && data.config) {
                                // Because of how validation works on the Craft side, any new pages with an id of `newXXXX-XXXX` will be
                                // stripped out. This is because we _have_ to strip them out as they need to be `null` to be saved properly.
                                // But on failed validation, they're stripped out and we end up with all sorts of issues. This is the same for
                                // rows. So - always ensure pages and rows have an ID.
                                data.config.pages.forEach(page => {
                                    if (!page.id) {
                                        page.id = newId();
                                    }

                                    page.rows.forEach(row => {
                                        if (!row.id) {
                                            row.id = newId();
                                        }
                                    });
                                });

                                store.dispatch('form/setFormConfig', data.config);
                                store.dispatch('notifications/setNotifications', data.notifications);
                            }

                            if (data && data.success !== undefined) {
                                if (data.success) {
                                    this.onSuccess(data);
                                    return;
                                }
                            }

                            this.onError(data);
                        }).catch(() => this.onError());
                    } else {
                        this.onError();
                    }
                },

                onSuccess(data) {
                    const { formBuilder } = this.$refs;

                    // Update the saved hash to prevent browser warnings
                    formBuilder.saveUpdatedHash();

                    if (data.redirect) {
                        Craft.cp.displayNotice(Craft.t('formie', data.redirectMessage));

                        return window.location = data.redirect;
                    } else if (!settings.isStencil) {
                        history.replaceState({}, '', Craft.getUrl(`formie/forms/edit/${data.id}${location.hash}`));

                        this.addInput('formId', data.id);
                        this.addInput('fieldLayoutId', data.fieldLayoutId);
                    } else {
                        history.replaceState({}, '', Craft.getUrl(`formie/settings/stencils/edit/${data.id}${location.hash}`));

                        this.addInput('stencilId', data.id);
                    }

                    Craft.cp.displayNotice(Craft.t('formie', 'Form saved.'));
                    this.$events.$emit('formie:save-form-loading', false);
                },

                onError(data = {}) {
                    let message = 'Unable to save form.';

                    if (data.errors && data.errors.length) {
                        message = 'Unable to save form: ' + JSON.stringify(data.errors) + '.';
                    }

                    Craft.cp.displayError(Craft.t('formie', message));
                    this.$events.$emit('formie:save-form-loading', false);

                    // TODO: Clean this up...
                    let $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    let $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    let $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

                    if (data && data.config) {
                        data.config.pages.forEach(page => {
                            page.rows.forEach(row => {
                                row.fields.forEach(field => {
                                    if ($fieldsTab && field.hasError) {
                                        $fieldsTab.classList.add('error');
                                    }
                                });
                            });
                        });

                        // Check for integration errors
                        if (data.config.settings.integrations) {
                            Object.keys(data.config.settings.integrations).forEach(handle => {
                                let integration = data.config.settings.integrations[handle];

                                if ($integrationsTab && integration.errors) {
                                    $integrationsTab.classList.add('error');
                                }
                            });
                        }
                    }

                    if (data && data.notifications) {
                        data.notifications.forEach(notification => {
                            if ($notificationsTab && notification.hasError) {
                                $notificationsTab.classList.add('error');
                            }
                        });
                    }
                },

                addInput(name, value) {
                    const { formElem } = this.$refs;

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

                reloadTabs() {
                    this.formTemplateLoading = true;

                    const { formElem } = this.$refs;

                    const data = this.getFormData();

                    this.$axios.post(Craft.getActionUrl('formie/forms/switch-template'), data).then(({ data }) => {
                        $('#tabs').replaceWith(data.tabsHtml);
                        $('#appearance-positions').replaceWith(data.positionsHtml);
                        $('.tab-form-fields', formElem).remove();
                        const $content = $(formElem);

                        for (const tab of data.fieldsHtml) {
                            const $tab = $content.append(`<div id="${tab.id}" class="tab-form-fields hidden">${tab.html}</div>`);
                            Craft.initUiElements($tab);
                        }

                        Craft.appendHeadHtml(data.headHtml);
                        Craft.appendFootHtml(data.bodyHtml);
                        Craft.cp.initTabs();

                        this.formTemplateLoading = false;
                    }).catch(e => {
                        console.error(e);

                        this.formTemplateLoading = false;
                    });
                },

                onDelete(e) {
                    var { formElem } = this.$refs;

                    var data = {
                        redirect: e.target.getAttribute('data-redirect'),
                        formId: formElem.querySelector('[name="formId"]').value,
                    };

                    this.$axios.post(Craft.getActionUrl('formie/forms/delete-form'), data).then(({ data }) => {
                        if (data && data.success !== undefined) {
                            window.location = data.redirect;
                        }
                    }).catch(() => this.onError());
                },
            },
        });
    },
});

Craft.Formie.NewForm = Garnish.Base.extend({
    init(settings) {
        // Initialise our Vuex stores with data
        store.dispatch('formie/setReservedHandles', settings.reservedHandles);
        store.dispatch('formie/setMaxFormHandleLength', settings.maxFormHandleLength);

        new Vue({
            el: '#fui-new-form',
            delimiters: ['${', '}'],
            store,

            data() {
                return {
                    name: '',
                    handle: '',
                    handles: [],
                };
            },

            watch: {
                name(val) {
                    const maxHandleLength = this.$store.getters['formie/maxFormHandleLength']();

                    // Let's get smart about generating a handle. Check if its unique - if it isn't, make it unique
                    // Be sure to restrict handles well below their limit
                    this.handle = getNextAvailableHandle(this.handles, generateHandle(this.name), 0).substr(0, maxHandleLength);
                },
            },

            created() {
                // Fetch all reserved handles
                const reservedHandles = this.$store.getters['formie/reservedHandles']();

                this.handles = settings.formHandles.concat(reservedHandles);
            },

            beforeMount() {
                // Load up defaults from Twig
                this.name = this.$el.querySelector('[name="title"]').value;
                this.handle = this.$el.querySelector('[name="handle"]').value;
            },

            mounted() {
                this.$el.querySelector('[name="title"]').focus();
            },
        });
    },
});

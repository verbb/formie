import Vue from 'vue';
import config from './config.js';
import get from 'lodash/get';
import isEmpty from 'lodash/isEmpty';

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
import NotificationsBuilder from './components/NotificationsBuilder.vue';
import NotificationPreview from './components/NotificationPreview.vue';
import NotificationTest from './components/NotificationTest.vue';
import ElementMapping from './components/ElementMapping.vue';

// Globally register components
Vue.component('FieldRepeater', FieldRepeater);
Vue.component('FieldGroup', FieldGroup);
Vue.component('DatePreview', DatePreview);
Vue.component('NotificationPreview', NotificationPreview);
Vue.component('NotificationTest', NotificationTest);
Vue.component('ElementMapping', ElementMapping);

//
// Start Vue Apps
//

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie = Garnish.Base.extend({
    init(settings) {
        // Initialise our Vuex stores with data
        store.dispatch('form/setFormConfig', settings.config);
        store.dispatch('form/setVariables', settings.variables);
        store.dispatch('fieldtypes/setFieldtypes', settings.fields);
        store.dispatch('fieldGroups/setFieldGroups', settings.fields);
        store.dispatch('notifications/setNotifications', settings.notifications);
        store.dispatch('formie/setExistingFields', settings.existingFields);
        store.dispatch('formie/setExistingNotifications', settings.existingNotifications);
        store.dispatch('formie/setEmailTemplates', settings.emailTemplates);
        store.dispatch('formie/setReservedHandles', settings.reservedHandles);

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

                    $fieldsTab.classList.remove('error');
                    $notificationsTab.classList.remove('error');
                    $integrationsTab.classList.remove('error');

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

                onError(data) {
                    Craft.cp.displayError(Craft.t('formie', 'Unable to save form.'));
                    this.$events.$emit('formie:save-form-loading', false);

                    // TODO: Clean this up...
                    let $fieldsTab = document.querySelector('a[href="#tab-fields"]');
                    let $notificationsTab = document.querySelector('a[href="#tab-notifications"]');
                    let $integrationsTab = document.querySelector('a[href="#tab-integrations"]');

                    if (data && data.config) {
                        data.config.pages.forEach(page => {
                            page.rows.forEach(row => {
                                row.fields.forEach(field => {
                                    if (field.hasError) {
                                        $fieldsTab.classList.add('error');
                                    }
                                });
                            });
                        });

                        // Check for integration errors
                        if (data.config.settings.integrations) {
                            Object.keys(data.config.settings.integrations).forEach(handle => {
                                let integration = data.config.settings.integrations[handle];
                            
                                if (integration.errors) {
                                    $integrationsTab.classList.add('error');
                                }
                            });
                        }
                    }

                    if (data && data.notifications) {
                        data.notifications.forEach(notification => {
                            if (notification.hasError) {
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
                    this.$events.$emit('formie:save-form-loading', true);

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

                        this.$events.$emit('formie:save-form-loading', false);
                    }).catch(e => {
                        console.error(e);
                        this.$events.$emit('formie:save-form-loading', false);
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
                    // Let's get smart about generating a handle. Check if its unqique - if it isn't, make it unique
                    this.handle = getNextAvailableHandle(this.handles, generateHandle(this.name), 0);
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

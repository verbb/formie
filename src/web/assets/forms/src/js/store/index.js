import Vue from 'vue';
import Vuex from 'vuex';
import form from './modules/form';
import fieldtypes from './modules/fieldtypes';
import fieldGroups from './modules/fieldGroups';
import notifications from './modules/notifications';
import formie from './modules/formie';

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        form,
        fieldtypes,
        fieldGroups,
        notifications,
        formie,
    },
});

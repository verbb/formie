import { createStore } from 'vuex';

import form from './modules/form';
import fieldtypes from './modules/fieldtypes';
import fieldGroups from './modules/fieldGroups';
import notifications from './modules/notifications';
import formie from './modules/formie';

export default createStore({
    modules: {
        form,
        fieldtypes,
        fieldGroups,
        notifications,
        formie,
    },
});

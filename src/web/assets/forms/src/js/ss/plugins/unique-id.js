import UniqueId from 'vue-unique-id';

// See https://github.com/berniegp/vue-unique-id
// Add a `uid` property to each component, but also provides some handy functions for ID's (think form labels)

export default Vue => {
    Vue.use(UniqueId);
};

import filter from './translate';

export default Vue => {
    Vue.filter('t', filter);
};

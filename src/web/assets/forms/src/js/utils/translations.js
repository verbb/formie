import { template } from 'lodash-es';

export const t = function(category, message, params) {
    if (
        typeof Craft.translations[category] !== 'undefined' &&
      typeof Craft.translations[category][message] !== 'undefined'
    ) {
        message = Craft.translations[category][message];
    }

    if (params) {
        return template(message, {
            interpolate: /{([\s\S]+?)}/g, // {myVar}
        })(params);
    }

    return message;
};
